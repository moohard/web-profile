<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->idLang = Language::idFor('id');
    $this->type = ContentType::query()->firstOrFail();
});

function postAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

it('GET /admin/posts menampilkan daftar post untuk admin', function () {
    Post::factory()->withTranslation('id', $this->idLang)->create(['type_id' => $this->type->id]);

    $this->actingAs(postAdmin())
        ->get('/admin/posts')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/posts/index')
            ->has('posts.data')
            ->has('contentTypes')
        );
});

it('index mengirim status setiap bahasa aktif pada tiap post', function () {
    $post = Post::factory()->withTranslation('id', $this->idLang, [
        'title' => 'Multibahasa',
        'status' => PostStatus::Published,
    ])->create(['type_id' => $this->type->id]);
    $post->forceFill(['updated_at' => now()->addDay()])->saveQuietly();

    $this->actingAs(postAdmin())
        ->get('/admin/posts')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('posts.data.0.id', $post->id)
            ->where('posts.data.0.statuses.0.code', 'id')
            ->where('posts.data.0.statuses.0.status', PostStatus::Published->value)
            ->where('posts.data.0.statuses.1.code', 'en')
            ->where('posts.data.0.statuses.1.status', null)
            ->has('languages', 2)
        );
});

it('Filter ?type menyaring post berdasarkan jenis konten', function () {
    $otherType = ContentType::factory()->withTranslation('id', $this->idLang, ['name' => 'Lain'])->create(['slug' => 'lain-jenis']);
    Post::factory()->withTranslation('id', $this->idLang, ['title' => 'Punya A'])->create(['type_id' => $this->type->id]);
    Post::factory()->withTranslation('id', $this->idLang, ['title' => 'Punya B'])->create(['type_id' => $otherType->id]);

    $this->actingAs(postAdmin())
        ->get("/admin/posts?type={$this->type->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Punya A')
        );
});

it('Filter ?status menyaring post berdasarkan status', function () {
    Post::factory()->withTranslation('id', $this->idLang, ['status' => PostStatus::Draft])->create(['type_id' => $this->type->id]);
    Post::factory()->withTranslation('id', $this->idLang, ['status' => PostStatus::Published])->create(['type_id' => $this->type->id]);

    $this->actingAs(postAdmin())
        ->get('/admin/posts?status=Draft')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            // D5: filter tetap berdasar locale aktif, tapi payload kini mengirim
            // status per-bahasa (statuses[]) — lihat PostStatusPerLanguageTest.
            ->where('posts.data.0.statuses.0.code', 'id')
            ->where('posts.data.0.statuses.0.status', 'Draft')
        );
});

it('Author hanya melihat post miliknya sendiri', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $other = User::factory()->create()->assignRole(UserRole::Author->value);

    Post::factory()->withTranslation('id', $this->idLang, ['title' => 'Punya Author'])->create([
        'type_id' => $this->type->id,
        'author_id' => $author->id,
    ]);
    Post::factory()->withTranslation('id', $this->idLang, ['title' => 'Punya Lain'])->create([
        'type_id' => $this->type->id,
        'author_id' => $other->id,
    ]);

    $this->actingAs($author)
        ->get('/admin/posts')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Punya Author')
        );
});

it('Editor melihat semua post (bukan hanya miliknya)', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $author = User::factory()->create()->assignRole(UserRole::Author->value);

    Post::factory()->withTranslation('id', $this->idLang)->create([
        'type_id' => $this->type->id,
        'author_id' => $author->id,
    ]);
    Post::factory()->withTranslation('id', $this->idLang)->create(['type_id' => $this->type->id]);

    $totalPosts = Post::query()->count();

    $this->actingAs($editor)
        ->get('/admin/posts')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', min($totalPosts, 20)));
});

it('DELETE oleh Admin menghapus post', function () {
    $post = Post::factory()->withTranslation('id', $this->idLang)->create(['type_id' => $this->type->id]);

    $this->actingAs(postAdmin())
        ->delete("/admin/posts/{$post->id}")
        ->assertRedirect();

    expect(Post::find($post->id))->toBeNull();
});

it('Author tidak boleh menghapus post milik orang lain', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $other = User::factory()->create()->assignRole(UserRole::Author->value);
    $post = Post::factory()->withTranslation('id', $this->idLang)->create([
        'type_id' => $this->type->id,
        'author_id' => $other->id,
    ]);

    $this->actingAs($author)
        ->delete("/admin/posts/{$post->id}")
        ->assertForbidden();

    expect(Post::find($post->id))->not->toBeNull();
});

it('Author boleh menghapus post miliknya sendiri', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $post = Post::factory()->withTranslation('id', $this->idLang)->create([
        'type_id' => $this->type->id,
        'author_id' => $author->id,
    ]);

    $this->actingAs($author)
        ->delete("/admin/posts/{$post->id}")
        ->assertRedirect();

    expect(Post::find($post->id))->toBeNull();
});

// NB: PostPolicy::viewAny sengaja `return true` untuk SEMUA user terautentikasi
// ("Semua role di area admin bisa melihat daftar post" — lihat app/Policies/PostPolicy.php,
// yang menurut deskripsi task sudah ada & tidak boleh diubah pada task ini). Karena itu
// user dengan permission access-admin saja tetap mendapat 200, bukan 403, pada index.
it('User dengan hanya access-admin tetap bisa melihat index karena viewAny berlaku untuk semua', function () {
    $user = User::factory()->create()->givePermissionTo('access-admin');

    $this->actingAs($user)
        ->get('/admin/posts')
        ->assertOk();
});
