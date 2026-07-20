<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PostStatus;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\PageTranslation;
use App\Models\PostTranslation;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate sitemap.xml untuk semua konten published per locale.';

    public function handle(): int
    {
        $sitemap = Sitemap::create();
        $defaultCode = Language::defaultModel()->code;
        $activeLocales = Language::active()->pluck('code');

        // Home per locale
        foreach ($activeLocales as $code) {
            $url = $code === $defaultCode ? url('/') : url("/{$code}");
            $sitemap->add(Url::create($url)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));
        }

        // Posts: archive + single per type & locale
        $types = ContentType::query()->where('is_active', true)->get();
        foreach ($types as $type) {
            foreach ($activeLocales as $code) {
                $url = $code === $defaultCode
                    ? url("/{$type->slug}")
                    : url("/{$code}/{$type->slug}");
                $sitemap->add(Url::create($url));

                PostTranslation::query()
                    ->where('language_id', Language::idFor($code))
                    ->where('status', PostStatus::Published->value)
                    ->whereHas('post', fn ($q) => $q->where('type_id', $type->id))
                    ->each(function (PostTranslation $tr) use ($code, $defaultCode, $type, $sitemap): void {
                        $url = $code === $defaultCode
                            ? url("/{$type->slug}/{$tr->slug}")
                            : url("/{$code}/{$type->slug}/{$tr->slug}");
                        $sitemap->add(
                            Url::create($url)->setLastModificationDate($tr->updated_at)
                        );
                    });
            }
        }

        // Custom pages published
        PageTranslation::query()
            ->where('status', PostStatus::Published->value)
            // Halaman induk yang sudah di-trash (SoftDeletes) harus dikecualikan dari sitemap.
            ->whereHas('page')
            ->with('language')
            ->each(function (PageTranslation $pt) use ($defaultCode, $sitemap): void {
                $locale = $pt->language->code;
                $url = $locale === $defaultCode
                    ? url("/{$pt->slug}")
                    : url("/{$locale}/{$pt->slug}");
                $sitemap->add(
                    Url::create($url)->setLastModificationDate($pt->updated_at)
                );
            });

        $sitemap->writeToFile(public_path('sitemap.xml'));
        $this->info('Sitemap generated.');

        return self::SUCCESS;
    }
}
