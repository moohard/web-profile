<?php

declare(strict_types=1);

use App\Models\ContentType;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function (): void {
    $this->seed();
    Storage::fake('public');
});

it('soft delete Post mempertahankan translation dan media di Trash', function (): void {
    $languageId = Language::idFor('id');
    $contentType = ContentType::query()->firstOrFail();
    $post = Post::factory()
        ->withTranslation('id', $languageId, ['title' => 'Masuk Trash'])
        ->create(['type_id' => $contentType->id]);
    $media = $post
        ->addMedia(UploadedFile::fake()->image('featured.jpg'))
        ->toMediaCollection('featured_image');

    $post->delete();

    expect(Post::query()->find($post->id))->toBeNull()
        ->and(Post::withTrashed()->findOrFail($post->id)->trashed())->toBeTrue()
        ->and($post->translations()->where('title', 'Masuk Trash')->exists())->toBeTrue()
        ->and(Media::query()->whereKey($media->id)->exists())->toBeTrue();
});

it('soft delete Page mempertahankan translation dan media di Trash', function (): void {
    $languageId = Language::idFor('id');
    $page = Page::factory()->create();
    $translation = PageTranslation::factory()
        ->for($page)
        ->create(['language_id' => $languageId, 'title' => 'Halaman Trash']);
    $media = $page
        ->addMedia(UploadedFile::fake()->image('hero.jpg'))
        ->toMediaCollection('hero_image');

    $page->delete();

    expect(Page::query()->find($page->id))->toBeNull()
        ->and(Page::withTrashed()->findOrFail($page->id)->trashed())->toBeTrue()
        ->and(PageTranslation::query()->whereKey($translation->id)->exists())->toBeTrue()
        ->and(Media::query()->whereKey($media->id)->exists())->toBeTrue();
});

it('Admin melihat daftar Trash Post dan Page yang terpisah', function (): void {
    $languageId = Language::idFor('id');
    $contentType = ContentType::query()->firstOrFail();
    $post = Post::factory()
        ->withTranslation('id', $languageId, ['title' => 'Post Terhapus'])
        ->create(['type_id' => $contentType->id]);
    $page = Page::factory()->create();
    PageTranslation::factory()
        ->for($page)
        ->create(['language_id' => $languageId, 'title' => 'Page Terhapus']);
    $post->delete();
    $page->delete();
    $admin = User::query()->where('email', config('admin.email'))->firstOrFail();

    $this->actingAs($admin)
        ->get('/admin/posts/trash')
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/posts/trash', false)
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Post Terhapus'));

    $this->actingAs($admin)
        ->get('/admin/pages/trash')
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/pages/trash', false)
            ->has('pages.data', 1)
            ->where('pages.data.0.title', 'Page Terhapus'));
});

it('Admin dan Editor dapat restore dan force-delete Post serta Page', function (string $role): void {
    $languageId = Language::idFor('id');
    $contentType = ContentType::query()->firstOrFail();
    $user = $role === 'Admin'
        ? User::query()->where('email', config('admin.email'))->firstOrFail()
        : User::factory()->create()->assignRole($role);

    $postToRestore = Post::factory()
        ->withTranslation('id', $languageId, ['title' => "Restore {$role}"])
        ->create(['type_id' => $contentType->id]);
    $postToDelete = Post::factory()
        ->withTranslation('id', $languageId, ['title' => "Delete {$role}"])
        ->create(['type_id' => $contentType->id]);
    $pageToRestore = Page::factory()->create();
    $pageToDelete = Page::factory()->create();

    $postToRestore->delete();
    $postToDelete->delete();
    $pageToRestore->delete();
    $pageToDelete->delete();

    $this->actingAs($user)
        ->patch("/admin/posts/{$postToRestore->id}/restore")
        ->assertRedirect('/admin/posts/trash');
    $this->actingAs($user)
        ->delete("/admin/posts/{$postToDelete->id}/force-delete")
        ->assertRedirect('/admin/posts/trash');
    $this->actingAs($user)
        ->patch("/admin/pages/{$pageToRestore->id}/restore")
        ->assertRedirect('/admin/pages/trash');
    $this->actingAs($user)
        ->delete("/admin/pages/{$pageToDelete->id}/force-delete")
        ->assertRedirect('/admin/pages/trash');

    expect($postToRestore->fresh())->not->toBeNull()
        ->and(Post::withTrashed()->find($postToDelete->id))->toBeNull()
        ->and($pageToRestore->fresh())->not->toBeNull()
        ->and(Page::withTrashed()->find($pageToDelete->id))->toBeNull();
})->with(['Admin', 'Editor']);

it('Author hanya melihat dan restore Post miliknya tanpa force-delete', function (): void {
    $languageId = Language::idFor('id');
    $contentType = ContentType::query()->firstOrFail();
    $author = User::factory()->create()->assignRole('Author');
    $otherAuthor = User::factory()->create()->assignRole('Author');
    $ownPost = Post::factory()
        ->withTranslation('id', $languageId, ['title' => 'Milik Author'])
        ->create(['type_id' => $contentType->id, 'author_id' => $author->id]);
    $otherPost = Post::factory()
        ->withTranslation('id', $languageId, ['title' => 'Milik Orang Lain'])
        ->create(['type_id' => $contentType->id, 'author_id' => $otherAuthor->id]);
    $ownPost->delete();
    $otherPost->delete();

    $this->actingAs($author)
        ->get('/admin/posts/trash')
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/posts/trash', false)
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Milik Author')
            ->where('posts.data.0.canForceDelete', false));

    $this->actingAs($author)
        ->patch("/admin/posts/{$otherPost->id}/restore")
        ->assertForbidden();
    $this->actingAs($author)
        ->delete("/admin/posts/{$ownPost->id}/force-delete")
        ->assertForbidden();
    $this->actingAs($author)
        ->get('/admin/pages/trash')
        ->assertForbidden();
    $this->actingAs($author)
        ->patch("/admin/posts/{$ownPost->id}/restore")
        ->assertRedirect('/admin/posts/trash');

    expect($ownPost->fresh())->not->toBeNull()
        ->and(Post::withTrashed()->find($otherPost->id)?->trashed())->toBeTrue();
});
