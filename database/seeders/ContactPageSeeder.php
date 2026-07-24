<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PageMode;
use App\Models\Language;
use App\Models\Page;
use Illuminate\Database\Seeder;

class ContactPageSeeder extends Seeder
{
    public function run(): void
    {
        $languageId = Language::query()->where('code', 'id')->value('id');

        if (! $languageId) {
            return;
        }

        $page = Page::query()->firstOrCreate(
            ['template_key' => 'contact'],
            [
                'mode' => PageMode::Template,
                'hero_enabled' => false,
                'sidebar_enabled' => false,
            ],
        );

        $page->translations()->updateOrCreate(
            ['language_id' => $languageId],
            [
                'slug' => 'kontak',
                'title' => 'Kontak',
                'content' => ['html' => ''],
                'status' => 'Published',
            ],
        );
    }
}
