<?php

declare(strict_types=1);

use App\Models\ContentType;
use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Ssr\HttpGateway;

uses(RefreshDatabase::class);

// Editor Tiptap wajib SSR-safe (immediatelyRender: false + guard `!editor`).
// Assertion `assertOk()` berlaku selalu (menangkap crash PHP/props apa pun);
// assertion markup HTML lanjutan hanya berjalan bila server SSR Node aktif —
// mengikuti konvensi tests/Feature/WalkingSkeletonSsrTest.php.

beforeEach(function () {
    $this->seed();
    Language::flushCache();
    $this->idLang = Language::idFor('id');
});

function editorSsrAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

it('GET /admin/posts/create tidak 500 (editor Tiptap kosong)', function () {
    $this->actingAs(editorSsrAdmin())->get('/admin/posts/create')->assertOk();
});

it('GET /admin/posts/{id}/edit dengan body rich-text tidak 500 saat SSR', function () {
    $type = ContentType::query()->firstOrFail();
    $post = Post::factory()->withTranslation('id', $this->idLang, [
        'body' => '<h2>Judul</h2><p>Isi <strong>tebal</strong> dengan <a href="https://example.com">tautan</a>.</p>',
    ])->create(['type_id' => $type->id]);

    $response = $this->actingAs(editorSsrAdmin())->get("/admin/posts/{$post->id}/edit");

    $response->assertOk();

    if (! app(HttpGateway::class)->isHealthy()) {
        $this->markTestSkipped('Inertia SSR server tidak berjalan — jalankan: php artisan inertia:start-ssr');
    }

    // Editor Tiptap sendiri tak dirender di server (immediatelyRender: false —
    // baru hidrasi di client), tapi form di sekitarnya harus tetap ter-render.
    expect($response->getContent())->toMatch('/<form|<h1/i');
});

it('GET /admin/pages/create (mode Template default) tidak 500 (editor Tiptap kosong)', function () {
    $this->actingAs(editorSsrAdmin())->get('/admin/pages/create')->assertOk();
});

it('GET /admin/pages/{id}/edit mode Template dengan konten rich-text tidak 500 saat SSR', function () {
    $page = Page::factory()->create(['mode' => 'Template']);
    $page->translations()->create([
        'language_id' => $this->idLang,
        'slug' => 'halaman-ssr-template',
        'title' => 'Halaman SSR Template',
        'content' => ['html' => '<h2>Judul</h2><p>Isi <em>miring</em>.</p>'],
        'status' => 'Draft',
    ]);

    $response = $this->actingAs(editorSsrAdmin())->get("/admin/pages/{$page->id}/edit");

    $response->assertOk();

    if (! app(HttpGateway::class)->isHealthy()) {
        $this->markTestSkipped('Inertia SSR server tidak berjalan — jalankan: php artisan inertia:start-ssr');
    }

    expect($response->getContent())->toMatch('/<form|<h1/i');
});

it('GET /admin/pages/{id}/edit mode Code tetap textarea raw — tidak 500 saat SSR', function () {
    $page = Page::factory()->create(['mode' => 'Code']);
    $page->translations()->create([
        'language_id' => $this->idLang,
        'slug' => 'halaman-ssr-code',
        'title' => 'Halaman SSR Code',
        'content' => ['html' => '<div class="hero">Halo</div>'],
        'status' => 'Draft',
    ]);

    $this->actingAs(editorSsrAdmin())->get("/admin/pages/{$page->id}/edit")->assertOk();
});
