<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->idLang = Language::idFor('id');
    $this->type = ContentType::query()->firstOrFail();
});

it('DELETE post melakukan soft delete: deleted_at terisi, hilang dari index, translations & media tetap ada', function () {
    $admin = User::where('email', config('admin.email'))->firstOrFail();
    $post = Post::factory()->withTranslation('id', $this->idLang)->create(['type_id' => $this->type->id]);
    $media = $post->addMedia(UploadedFile::fake()->image('x.jpg', 100, 100))->toMediaCollection('featured');

    $this->actingAs($admin)
        ->delete("/admin/posts/{$post->id}")
        ->assertRedirect();

    expect(Post::find($post->id))->toBeNull()
        ->and(Post::withTrashed()->find($post->id))->not->toBeNull()
        ->and(Post::withTrashed()->find($post->id)->deleted_at)->not->toBeNull()
        ->and(PostTranslation::where('post_id', $post->id)->count())->toBe(1)
        ->and(Media::find($media->id))->not->toBeNull();

    // Index hanya menghitung post aktif (default query Eloquent mengecualikan trashed).
    $this->actingAs($admin)
        ->get('/admin/posts')
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', Post::query()->count()));
});

it('GET /admin/posts/trash menampilkan hanya post yang sudah di-trash', function () {
    $admin = User::where('email', config('admin.email'))->firstOrFail();
    Post::factory()->withTranslation('id', $this->idLang, ['title' => 'Aktif'])->create(['type_id' => $this->type->id]);
    $trashed = Post::factory()->withTranslation('id', $this->idLang, ['title' => 'Terhapus'])->create(['type_id' => $this->type->id]);
    $trashed->delete();

    $this->actingAs($admin)
        ->get('/admin/posts/trash')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/posts/trash')
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Terhapus')
        );
});

it('Trash: Author hanya melihat post miliknya sendiri yang sudah di-trash', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $other = User::factory()->create()->assignRole(UserRole::Author->value);

    $ownTrashed = Post::factory()->withTranslation('id', $this->idLang, ['title' => 'Punya Author'])->create([
        'type_id' => $this->type->id,
        'author_id' => $author->id,
    ]);
    $ownTrashed->delete();

    $otherTrashed = Post::factory()->withTranslation('id', $this->idLang, ['title' => 'Punya Lain'])->create([
        'type_id' => $this->type->id,
        'author_id' => $other->id,
    ]);
    $otherTrashed->delete();

    $this->actingAs($author)
        ->get('/admin/posts/trash')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Punya Author')
        );
});

it('PUT restore mengembalikan post dari trash', function () {
    $admin = User::where('email', config('admin.email'))->firstOrFail();
    $post = Post::factory()->withTranslation('id', $this->idLang)->create(['type_id' => $this->type->id]);
    $post->delete();

    $this->actingAs($admin)
        ->put("/admin/posts/{$post->id}/restore")
        ->assertRedirect();

    expect(Post::find($post->id))->not->toBeNull()
        ->and(Post::find($post->id)->deleted_at)->toBeNull();
});

it('Editor boleh restore post siapa pun', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $post = Post::factory()->withTranslation('id', $this->idLang)->create(['type_id' => $this->type->id]);
    $post->delete();

    $this->actingAs($editor)
        ->put("/admin/posts/{$post->id}/restore")
        ->assertRedirect();

    expect(Post::find($post->id))->not->toBeNull();
});

it('Author tidak boleh restore post — meski miliknya sendiri', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $post = Post::factory()->withTranslation('id', $this->idLang)->create([
        'type_id' => $this->type->id,
        'author_id' => $author->id,
    ]);
    $post->delete();

    $this->actingAs($author)
        ->put("/admin/posts/{$post->id}/restore")
        ->assertForbidden();

    expect(Post::withTrashed()->find($post->id)->trashed())->toBeTrue();
});

it('DELETE force menghapus post permanen beserta translations & media', function () {
    $admin = User::where('email', config('admin.email'))->firstOrFail();
    $post = Post::factory()->withTranslation('id', $this->idLang)->create(['type_id' => $this->type->id]);
    $media = $post->addMedia(UploadedFile::fake()->image('x.jpg', 100, 100))->toMediaCollection('featured');
    $mediaId = $media->id;
    $post->delete();

    $this->actingAs($admin)
        ->delete("/admin/posts/{$post->id}/force")
        ->assertRedirect();

    expect(Post::withTrashed()->find($post->id))->toBeNull()
        ->and(PostTranslation::where('post_id', $post->id)->count())->toBe(0)
        ->and(Media::find($mediaId))->toBeNull();
});

it('Author tidak boleh forceDelete post', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $post = Post::factory()->withTranslation('id', $this->idLang)->create([
        'type_id' => $this->type->id,
        'author_id' => $author->id,
    ]);
    $post->delete();

    $this->actingAs($author)
        ->delete("/admin/posts/{$post->id}/force")
        ->assertForbidden();

    expect(Post::withTrashed()->find($post->id))->not->toBeNull();
});
