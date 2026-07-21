<?php

declare(strict_types=1);

use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// D5(B): editor Post sebelumnya hanya bisa memilih tag yang SUDAH ada
// (checkbox dari tagOptions). Delta ini mengizinkan tag baru dibuat saat
// mengetik di editor (payload `new_tags: string[]`), dengan `firstOrCreate`
// di server berbasis nama (case-insensitive) supaya tidak terjadi duplikat —
// lihat PostController::resolveTagIds/findOrCreateTagIdByName.

beforeEach(function () {
    $this->seed();
    $this->idLang = Language::idFor('id');
    $this->type = ContentType::query()->firstOrFail();
});

function tagCreateOnTypeAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

it('POST /admin/posts dengan new_tags membuat Tag + TagTranslation baru dan menautkannya ke post', function () {
    $this->actingAs(tagCreateOnTypeAdmin())
        ->post('/admin/posts', [
            'type_id' => $this->type->id,
            'new_tags' => ['Kuliner'],
            'translations' => [[
                'language_id' => $this->idLang,
                'title' => 'Judul post kuliner',
                'status' => 'Draft',
            ]],
        ])
        ->assertRedirect();

    $tag = Tag::query()->where('slug', 'kuliner')->first();
    expect($tag)->not->toBeNull();

    // latest('id') — bukan firstOrFail() — karena DemoPostSeeder (dijalankan
    // via $this->seed() di beforeEach) sudah membuat 1 post lebih dulu (id
    // lebih kecil); post yang baru dibuat lewat request ini selalu id terbesar.
    $post = Post::query()->latest('id')->first();
    expect($post->tags->pluck('id')->all())->toBe([$tag->id]);

    // Translation dibuat di SEMUA bahasa aktif dengan nama yang sama (default
    // aman, mengikuti pola TagController::syncTranslations) — bukan skema baru.
    expect($tag->translations()->count())->toBe(Language::active()->count())
        ->and($tag->translations()->where('language_id', $this->idLang)->value('name'))->toBe('Kuliner');
});

it('nama tag baru yang cocok (case-insensitive) dengan tag yang sudah ada tidak diduplikasi', function () {
    $existing = Tag::factory()->withTranslation('id', $this->idLang, ['name' => 'Wisata'])->create();

    $this->actingAs(tagCreateOnTypeAdmin())
        ->post('/admin/posts', [
            'type_id' => $this->type->id,
            'new_tags' => ['wisata'],
            'translations' => [[
                'language_id' => $this->idLang,
                'title' => 'Judul post wisata',
                'status' => 'Draft',
            ]],
        ])
        ->assertRedirect();

    expect(Tag::query()->count())->toBe(1);

    // latest('id') — bukan firstOrFail() — karena DemoPostSeeder (dijalankan
    // via $this->seed() di beforeEach) sudah membuat 1 post lebih dulu (id
    // lebih kecil); post yang baru dibuat lewat request ini selalu id terbesar.
    $post = Post::query()->latest('id')->first();
    expect($post->tags->pluck('id')->all())->toBe([$existing->id]);
});

it('tag existing (tags[]) dan tag baru (new_tags[]) tertaut bersamaan tanpa duplikat', function () {
    $existing = Tag::factory()->withTranslation('id', $this->idLang, ['name' => 'Wisata'])->create();

    $this->actingAs(tagCreateOnTypeAdmin())
        ->post('/admin/posts', [
            'type_id' => $this->type->id,
            'tags' => [$existing->id],
            'new_tags' => ['Kuliner', 'kuliner'], // duplikat penulisan pada request yang sama
            'translations' => [[
                'language_id' => $this->idLang,
                'title' => 'Judul gabungan tag',
                'status' => 'Draft',
            ]],
        ])
        ->assertRedirect();

    $newTag = Tag::query()->where('slug', 'kuliner')->firstOrFail();
    // latest('id') — bukan firstOrFail() — karena DemoPostSeeder (dijalankan
    // via $this->seed() di beforeEach) sudah membuat 1 post lebih dulu (id
    // lebih kecil); post yang baru dibuat lewat request ini selalu id terbesar.
    $post = Post::query()->latest('id')->first();

    expect($post->tags->pluck('id')->sort()->values()->all())
        ->toBe(collect([$existing->id, $newTag->id])->sort()->values()->all());
});

it('PUT update post dengan new_tags menautkan tag baru tanpa menghapus tag existing yang dipertahankan', function () {
    $existing = Tag::factory()->withTranslation('id', $this->idLang, ['name' => 'Wisata'])->create();
    $post = Post::factory()->withTranslation('id', $this->idLang)->create(['type_id' => $this->type->id]);
    $post->tags()->attach($existing->id);
    $translation = $post->translations()->first();

    $this->actingAs(tagCreateOnTypeAdmin())
        ->put("/admin/posts/{$post->id}", [
            'type_id' => $this->type->id,
            'tags' => [$existing->id],
            'new_tags' => ['Kuliner'],
            'translations' => [[
                'language_id' => $this->idLang,
                'title' => $translation->title,
                'slug' => $translation->slug,
                'status' => 'Published',
            ]],
        ])
        ->assertRedirect();

    $newTag = Tag::query()->where('slug', 'kuliner')->firstOrFail();

    expect($post->fresh()->tags->pluck('id')->sort()->values()->all())
        ->toBe(collect([$existing->id, $newTag->id])->sort()->values()->all());
});

it('new_tags kosong atau tidak dikirim tidak membuat tag apa pun', function () {
    $this->actingAs(tagCreateOnTypeAdmin())
        ->post('/admin/posts', [
            'type_id' => $this->type->id,
            'translations' => [[
                'language_id' => $this->idLang,
                'title' => 'Judul tanpa tag',
                'status' => 'Draft',
            ]],
        ])
        ->assertRedirect();

    expect(Tag::query()->count())->toBe(0);

    // latest('id') — bukan firstOrFail() — karena DemoPostSeeder (dijalankan
    // via $this->seed() di beforeEach) sudah membuat 1 post lebih dulu (id
    // lebih kecil); post yang baru dibuat lewat request ini selalu id terbesar.
    $post = Post::query()->latest('id')->first();
    expect($post->tags)->toBeEmpty();
});
