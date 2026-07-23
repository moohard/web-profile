<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PostStatus;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\PageTranslation;
use App\Models\PostTranslation;
use App\Support\LocaleUrl;
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
        $activeLanguages = Language::active()->get(['id', 'code']);

        // Home per locale
        foreach ($activeLanguages as $language) {
            $url = url(LocaleUrl::for($language->code, '/'));
            $sitemap->add(Url::create($url)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));
        }

        // Posts: archive + single per type & locale
        $types = ContentType::query()->where('is_active', true)->get();
        foreach ($types as $type) {
            foreach ($activeLanguages as $language) {
                $url = url(LocaleUrl::for($language->code, "/{$type->slug}"));
                $sitemap->add(Url::create($url));

                PostTranslation::query()
                    ->where('language_id', $language->id)
                    ->where('status', PostStatus::Published->value)
                    ->whereHas('post', fn ($q) => $q->where('type_id', $type->id))
                    ->each(function (PostTranslation $tr) use ($language, $type, $sitemap): void {
                        $url = url(LocaleUrl::for(
                            $language->code,
                            "/{$type->slug}/{$tr->slug}",
                        ));
                        $sitemap->add(
                            Url::create($url)->setLastModificationDate($tr->updated_at)
                        );
                    });
            }
        }

        // Custom pages published
        PageTranslation::query()
            ->where('status', PostStatus::Published->value)
            ->whereHas('page')
            ->whereHas('language', fn ($query) => $query->where('is_active', true))
            ->with('language:id,code')
            ->each(function (PageTranslation $pt) use ($sitemap): void {
                $locale = $pt->language->code;
                $url = url(LocaleUrl::for($locale, "/{$pt->slug}"));
                $sitemap->add(
                    Url::create($url)->setLastModificationDate($pt->updated_at)
                );
            });

        $sitemap->writeToFile(public_path('sitemap.xml'));
        $this->info('Sitemap generated.');

        return self::SUCCESS;
    }
}
