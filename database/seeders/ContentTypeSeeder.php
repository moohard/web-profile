<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ContentType;
use App\Models\Language;
use App\Models\WritingStyle;
use Illuminate\Database\Seeder;

class ContentTypeSeeder extends Seeder
{
    public function run(): void
    {
        ContentType::query()->delete();
        $ws = WritingStyle::first();
        $wsId = $ws?->id;

        $types = [
            ['slug' => 'artikel', 'id' => 'Artikel', 'en' => 'Articles'],
            ['slug' => 'berita', 'id' => 'Berita', 'en' => 'News'],
            ['slug' => 'pengumuman', 'id' => 'Pengumuman', 'en' => 'Announcements'],
        ];
        $langId = Language::where('code', 'id')->value('id');
        $langEn = Language::where('code', 'en')->value('id');

        foreach ($types as $i => $type) {
            $contentType = ContentType::create([
                'slug' => $type['slug'],
                'writing_style_id' => $wsId,
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);
            $contentType->translations()->create(['language_id' => $langId, 'name' => $type['id']]);
            $contentType->translations()->create(['language_id' => $langEn, 'name' => $type['en']]);
        }
    }
}
