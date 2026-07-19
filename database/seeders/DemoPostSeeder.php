<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use Illuminate\Database\Seeder;

class DemoPostSeeder extends Seeder
{
    public function run(): void
    {
        $type = ContentType::where('slug', 'berita')->first();
        if (! $type) {
            return;
        }

        $langId = Language::where('code', 'id')->value('id');
        $langEn = Language::where('code', 'en')->value('id');

        $post = Post::create(['type_id' => $type->id]);

        PostTranslation::create([
            'post_id' => $post->id,
            'language_id' => $langId,
            'slug' => 'selamat-datang',
            'title' => 'Selamat Datang di Papenajam',
            'body' => '<p>Ini adalah konten demo pertama untuk verifikasi pondasi CMS.</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
            'meta_title' => 'Selamat Datang — Papenajam',
            'meta_description' => 'Konten demo pertama CMS Papenajam.',
        ]);

        PostTranslation::create([
            'post_id' => $post->id,
            'language_id' => $langEn,
            'slug' => 'welcome',
            'title' => 'Welcome to Papenajam',
            'body' => '<p>This is the first demo content to verify the CMS foundation.</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
            'meta_title' => 'Welcome — Papenajam',
            'meta_description' => 'First demo content of the Papenajam CMS.',
        ]);
    }
}
