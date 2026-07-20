<?php

declare(strict_types=1);

use App\Enums\PageMode;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Tag `<div class="...">` sengaja dipakai sebagai pembeda profil: allowlist
// `cms_page` mengizinkan `div[class]`, sedangkan `default` (rich-text) tidak
// mengizinkan `div` sama sekali (tag dibuang, teks di dalamnya tetap ada).

beforeEach(function () {
    $this->seed();
    $this->idLang = Language::idFor('id');
});

function richTextAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

it('body Post disimpan dengan profil rich-text (default) — bukan cms_page', function () {
    $type = ContentType::query()->firstOrFail();

    $this->actingAs(richTextAdmin())->post('/admin/posts', [
        'type_id' => $type->id,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Post Rich Text',
            'body' => '<h2>Judul</h2><div class="callout">Penting</div><p>Isi</p>',
            'status' => 'Draft',
        ]],
    ])->assertRedirect();

    $post = Post::query()->latest('id')->firstOrFail();
    $translation = $post->translations()->where('language_id', $this->idLang)->firstOrFail();

    expect($translation->body)->toContain('<h2>Judul</h2>')
        ->and($translation->body)->not->toContain('<div')
        ->and($translation->body)->toContain('Penting');
});

it('konten Page mode Template disimpan dengan profil rich-text (default)', function () {
    $this->actingAs(richTextAdmin())->post('/admin/pages', [
        'mode' => PageMode::Template->value,
        'template_key' => 'default',
        'hero_enabled' => false,
        'sidebar_enabled' => false,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Halaman Template Rich Text',
            'content' => '<div class="callout">Penting</div><p>Isi</p>',
            'status' => 'Draft',
        ]],
    ])->assertRedirect();

    $page = Page::query()->latest('id')->firstOrFail();
    $translation = $page->translations()->where('language_id', $this->idLang)->firstOrFail();

    expect($translation->content['html'])->not->toContain('<div')
        ->and($translation->content['html'])->toContain('Penting');
});

it('konten Page mode Code tetap disimpan dengan profil cms_page (regresi)', function () {
    $this->actingAs(richTextAdmin())->post('/admin/pages', [
        'mode' => PageMode::Code->value,
        'template_key' => 'default',
        'hero_enabled' => false,
        'sidebar_enabled' => false,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Halaman Code',
            'content' => '<div class="callout">Penting</div><p>Isi</p>',
            'status' => 'Draft',
        ]],
    ])->assertRedirect();

    $page = Page::query()->latest('id')->firstOrFail();
    $translation = $page->translations()->where('language_id', $this->idLang)->firstOrFail();

    expect($translation->content['html'])->toContain('<div class="callout">')
        ->and($translation->content['html'])->toContain('Penting');
});

it('body Post yang sudah tersimpan tetap terlihat dengan profil rich-text saat diperbarui', function () {
    $type = ContentType::query()->firstOrFail();
    $post = Post::factory()->create(['type_id' => $type->id]);
    PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => $this->idLang,
        'slug' => 'post-lama',
        'title' => 'Post Lama',
        'body' => '<p>Lama</p>',
        'status' => 'Draft',
    ]);

    $this->actingAs(richTextAdmin())->put("/admin/posts/{$post->id}", [
        'type_id' => $type->id,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Post Lama',
            'body' => '<div class="callout">Baru</div>',
            'status' => 'Draft',
        ]],
    ])->assertRedirect();

    $translation = $post->translations()->where('language_id', $this->idLang)->firstOrFail();

    expect($translation->body)->not->toContain('<div')
        ->and($translation->body)->toContain('Baru');
});
