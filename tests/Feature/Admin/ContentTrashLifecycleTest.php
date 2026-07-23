<?php

declare(strict_types=1);

use App\Enums\LinkType;
use App\Enums\PlacementScope;
use App\Enums\WidgetPosition;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Models\PostTag;
use App\Models\PostTranslation;
use App\Models\Tag;
use App\Models\User;
use App\Models\Widget;
use App\Models\WidgetPlacement;
use App\Models\WidgetPlacementTarget;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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
            ->component('admin/posts/trash')
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Post Terhapus'));

    $this->actingAs($admin)
        ->get('/admin/pages/trash')
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/pages/trash')
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
            ->component('admin/posts/trash')
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

it('endpoint restore dan force-delete menolak Post aktif', function (): void {
    $contentType = ContentType::query()->firstOrFail();
    $post = Post::factory()->create(['type_id' => $contentType->id]);
    $admin = User::query()->where('email', config('admin.email'))->firstOrFail();

    $this->actingAs($admin)
        ->patch("/admin/posts/{$post->id}/restore")
        ->assertNotFound();
    $this->actingAs($admin)
        ->delete("/admin/posts/{$post->id}/force-delete")
        ->assertNotFound();

    expect(Post::query()->find($post->id))->not->toBeNull();
});

it('endpoint restore dan force-delete menolak Page aktif', function (): void {
    $page = Page::factory()->create();
    $admin = User::query()->where('email', config('admin.email'))->firstOrFail();

    $this->actingAs($admin)
        ->patch("/admin/pages/{$page->id}/restore")
        ->assertNotFound();
    $this->actingAs($admin)
        ->delete("/admin/pages/{$page->id}/force-delete")
        ->assertNotFound();

    expect(Page::query()->find($page->id))->not->toBeNull();
});

it('Editor dengan role tambahan Author tetap dapat melihat dan restore seluruh Post', function (): void {
    $languageId = Language::idFor('id');
    $contentType = ContentType::query()->firstOrFail();
    $editor = User::factory()->create();
    $editor->assignRole(['Editor', 'Author']);
    $otherAuthor = User::factory()->create()->assignRole('Author');
    $ownPost = Post::factory()
        ->withTranslation('id', $languageId, ['title' => 'Milik Editor'])
        ->create(['type_id' => $contentType->id, 'author_id' => $editor->id]);
    $otherPost = Post::factory()
        ->withTranslation('id', $languageId, ['title' => 'Milik Author Lain'])
        ->create(['type_id' => $contentType->id, 'author_id' => $otherAuthor->id]);
    $ownPost->delete();
    $otherPost->delete();

    $this->actingAs($editor)
        ->get('/admin/posts/trash')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2));
    $this->actingAs($editor)
        ->patch("/admin/posts/{$otherPost->id}/restore")
        ->assertRedirect('/admin/posts/trash');

    expect($otherPost->fresh())->not->toBeNull();
});

it('force-delete Post membersihkan relasi target dan media secara permanen', function (): void {
    $languageId = Language::idFor('id');
    $contentType = ContentType::query()->firstOrFail();
    $post = Post::factory()
        ->withTranslation('id', $languageId, ['title' => 'Post Permanen'])
        ->hasAttached(Tag::factory()->withTranslation('id', $languageId))
        ->create(['type_id' => $contentType->id]);
    $translationId = $post->translations()->value('id');
    $media = $post
        ->addMedia(UploadedFile::fake()->image('permanent-post.jpg'))
        ->toMediaCollection('featured_image');
    $mediaPath = $media->getPathRelativeToRoot();
    $menuItem = MenuItem::factory()->create([
        'link_type' => LinkType::ContentSingle,
        'link_ref' => (string) $post->id,
        'url' => null,
    ]);
    $target = makeWidgetTarget(WidgetPlacementTarget::TYPE_CONTENT_SINGLE, (string) $post->id);
    $admin = User::query()->where('email', config('admin.email'))->firstOrFail();
    $post->delete();
    foreach (Language::query()->pluck('id') as $languageId) {
        Cache::put("public_layout.{$languageId}", ['stale'], now()->addHour());
    }

    $this->actingAs($admin)
        ->delete("/admin/posts/{$post->id}/force-delete")
        ->assertRedirect('/admin/posts/trash');

    expect(Post::withTrashed()->find($post->id))->toBeNull()
        ->and(PostTranslation::query()->whereKey($translationId)->exists())->toBeFalse()
        ->and(PostTag::query()->where('post_id', $post->id)->exists())->toBeFalse()
        ->and(MenuItem::query()->whereKey($menuItem->id)->exists())->toBeFalse()
        ->and(WidgetPlacementTarget::query()->whereKey($target->id)->exists())->toBeFalse()
        ->and(Media::query()->whereKey($media->id)->exists())->toBeFalse()
        ->and(
            Language::query()
                ->pluck('id')
                ->contains(fn (int $languageId): bool => Cache::has("public_layout.{$languageId}")),
        )->toBeFalse();
    Storage::disk('public')->assertMissing($mediaPath);
});

it('force-delete Page membersihkan relasi target dan media secara permanen', function (): void {
    $languageId = Language::idFor('id');
    $page = Page::factory()->create();
    $translation = PageTranslation::factory()
        ->for($page)
        ->create(['language_id' => $languageId, 'title' => 'Page Permanen']);
    $media = $page
        ->addMedia(UploadedFile::fake()->image('permanent-page.jpg'))
        ->toMediaCollection('hero_image');
    $mediaPath = $media->getPathRelativeToRoot();
    $menuItem = MenuItem::factory()->create([
        'link_type' => LinkType::Page,
        'link_ref' => (string) $page->id,
        'url' => null,
    ]);
    $target = makeWidgetTarget(WidgetPlacementTarget::TYPE_PAGE, (string) $page->id);
    $admin = User::query()->where('email', config('admin.email'))->firstOrFail();
    $page->delete();

    $this->actingAs($admin)
        ->delete("/admin/pages/{$page->id}/force-delete")
        ->assertRedirect('/admin/pages/trash');

    expect(Page::withTrashed()->find($page->id))->toBeNull()
        ->and(PageTranslation::query()->whereKey($translation->id)->exists())->toBeFalse()
        ->and(MenuItem::query()->whereKey($menuItem->id)->exists())->toBeFalse()
        ->and(WidgetPlacementTarget::query()->whereKey($target->id)->exists())->toBeFalse()
        ->and(Media::query()->whereKey($media->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing($mediaPath);
});

function makeWidgetTarget(string $targetType, string $targetRef): WidgetPlacementTarget
{
    $widget = Widget::factory()->create();
    $placement = WidgetPlacement::query()->create([
        'widget_id' => $widget->id,
        'position' => WidgetPosition::BeforeContent,
        'scope' => PlacementScope::Only,
        'sort_order' => 0,
    ]);

    return WidgetPlacementTarget::query()->create([
        'placement_id' => $placement->id,
        'target_type' => $targetType,
        'target_ref' => $targetRef,
    ]);
}
