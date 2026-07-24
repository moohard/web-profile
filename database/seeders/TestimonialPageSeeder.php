<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PageMode;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Database\Seeder;

class TestimonialPageSeeder extends Seeder
{
    public function run(): void
    {
        $language = Language::query()->where('code', 'id')->firstOrFail();
        $translation = PageTranslation::query()
            ->where('language_id', $language->id)
            ->where('slug', 'testimoni')
            ->first();

        if ($translation === null) {
            $page = Page::query()->create([
                'mode' => PageMode::Template,
                'template_key' => 'testimonials',
                'hero_enabled' => false,
                'sidebar_enabled' => false,
            ]);

            PageTranslation::query()->create([
                'page_id' => $page->id,
                'language_id' => $language->id,
                'slug' => 'testimoni',
                'title' => 'Testimoni',
                'content' => ['html' => '<p>Bagikan pengalaman Anda menggunakan layanan kami.</p>'],
                'status' => 'Published',
            ]);

            return;
        }

        $translation->page()->update(['template_key' => 'testimonials']);
    }
}
