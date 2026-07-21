<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

// D5(A): daftar admin sebelumnya hanya mengirim status locale aktif saja
// (lihat riwayat PostCrudTest). Delta ini mengganti field `status` tunggal
// menjadi `statuses` — daftar status per-bahasa aktif — agar admin bisa
// melihat progres terjemahan tanpa ganti locale.

beforeEach(function () {
    $this->seed();
    $this->idLang = Language::idFor('id');
    $this->enLang = Language::idFor('en');
    // Type khusus (bukan 'berita') supaya query index() tidak ikut menghitung
    // post demo dari DemoPostSeeder — index() default urut updated_at DESC,
    // dan post demo bisa "seri" dengan post baru bila dibuat pada detik yang
    // sama (lihat juga pola isolasi di PostCrudTest::'Filter ?type').
    $this->type = ContentType::factory()
        ->withTranslation('id', $this->idLang, ['name' => 'Uji Status'])
        ->create(['slug' => 'uji-status-per-bahasa']);
});

function statusPerLanguageAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

it('index() mengirim status per-bahasa untuk post dengan status berbeda tiap bahasa', function () {
    $post = Post::factory()
        ->withTranslation('id', $this->idLang, ['status' => PostStatus::Published])
        ->create(['type_id' => $this->type->id]);

    $post->translations()->create([
        'language_id' => $this->enLang,
        'slug' => 'draft-en-slug',
        'title' => 'Draft title',
        'status' => PostStatus::Draft,
    ]);

    $this->actingAs(statusPerLanguageAdmin())
        ->get("/admin/posts?type={$this->type->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->has('posts.data.0.statuses', 2)
            ->where('posts.data.0.statuses.0.code', 'id')
            ->where('posts.data.0.statuses.0.label', 'ID')
            ->where('posts.data.0.statuses.0.status', 'Published')
            ->where('posts.data.0.statuses.1.code', 'en')
            ->where('posts.data.0.statuses.1.label', 'EN')
            ->where('posts.data.0.statuses.1.status', 'Draft')
        );
});

it('index() mengirim status null untuk bahasa yang belum punya translation sama sekali', function () {
    Post::factory()
        ->withTranslation('id', $this->idLang, ['status' => PostStatus::Draft])
        ->create(['type_id' => $this->type->id]);

    $this->actingAs(statusPerLanguageAdmin())
        ->get("/admin/posts?type={$this->type->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.statuses.0.status', 'Draft')
            ->where('posts.data.0.statuses.1.code', 'en')
            ->where('posts.data.0.statuses.1.status', null)
        );
});

it('trash() ikut mengirim status per-bahasa untuk post yang di-soft-delete', function () {
    $post = Post::factory()
        ->withTranslation('id', $this->idLang, ['status' => PostStatus::Published])
        ->create(['type_id' => $this->type->id]);
    $post->delete();

    $this->actingAs(statusPerLanguageAdmin())
        ->get('/admin/posts/trash')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data.0.statuses', 2)
            ->where('posts.data.0.statuses.0.status', 'Published')
        );
});
