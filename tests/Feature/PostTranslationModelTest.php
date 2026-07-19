<?php

use App\Enums\PostStatus;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Support\HasTranslations;
use Illuminate\Database\QueryException;

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();
    Language::create(['code' => 'id', 'name' => 'Indonesia', 'is_default' => true, 'sort_order' => 1]);
    Language::create(['code' => 'en', 'name' => 'English', 'sort_order' => 2]);
    Language::flushCache();
});

it('Post menggunakan HasTranslations', function () {
    expect(in_array(HasTranslations::class, class_uses(Post::class)))->toBeTrue();
});

it('PostTranslation cast status ke enum PostStatus', function () {
    $type = ContentType::create(['slug' => 'berita']);
    $post = Post::create(['type_id' => $type->id]);
    $tr = PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => Language::idFor('id'),
        'slug' => 'judul-slug',
        'title' => 'Judul',
        'status' => PostStatus::Published,
    ]);
    expect($tr->status)->toBe(PostStatus::Published);
});

it('Post::translate fallback ke default jika locale tidak ada', function () {
    app()->setLocale('en');
    $type = ContentType::create(['slug' => 'berita']);
    $post = Post::create(['type_id' => $type->id]);
    PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => Language::idFor('id'),
        'slug' => 'judul-id',
        'title' => 'Indonesia',
        'status' => PostStatus::Published,
    ]);
    $post->load('translations');
    expect($post->translate('en')?->title)->toBe('Indonesia'); // fallback
    expect($post->translate('id')?->title)->toBe('Indonesia');
});

it('UNIQUE(language_id, slug) mencegah duplikat', function () {
    $type = ContentType::create(['slug' => 'berita']);
    $p1 = Post::create(['type_id' => $type->id]);
    $p2 = Post::create(['type_id' => $type->id]);
    PostTranslation::create([
        'post_id' => $p1->id,
        'language_id' => Language::idFor('id'),
        'slug' => 'sama',
        'title' => 'A',
        'status' => PostStatus::Draft,
    ]);
    PostTranslation::create([
        'post_id' => $p2->id,
        'language_id' => Language::idFor('id'),
        'slug' => 'sama',
        'title' => 'B',
        'status' => PostStatus::Draft,
    ]);
})->throws(QueryException::class);
