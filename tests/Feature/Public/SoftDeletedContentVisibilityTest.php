<?php

declare(strict_types=1);

use App\Enums\LinkType;
use App\Enums\PostStatus;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuItemTranslation;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Support\PublicPathResolver;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->seed();
    Language::flushCache();
    Cache::flush();
    $this->languageId = Language::idFor('id');
    $this->contentType = ContentType::query()->where('slug', 'berita')->firstOrFail();
});

it('resolver publik menolak Post dan Page yang ada di Trash', function (): void {
    $post = Post::factory()
        ->withTranslation('id', $this->languageId, [
            'slug' => 'post-di-trash',
            'status' => PostStatus::Published,
        ])
        ->create(['type_id' => $this->contentType->id]);
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->create([
        'language_id' => $this->languageId,
        'slug' => 'page-di-trash',
        'status' => PostStatus::Published->value,
    ]);
    $post->delete();
    $page->delete();

    expect(PublicPathResolver::resolve('berita/post-di-trash')['kind'])->toBe('notFound')
        ->and(PublicPathResolver::resolve('page-di-trash')['kind'])->toBe('notFound');
});

it('home dan archive tidak memuat Post yang ada di Trash', function (): void {
    $post = Post::factory()
        ->withTranslation('id', $this->languageId, [
            'title' => 'Tidak Boleh Publik',
            'slug' => 'tidak-boleh-publik',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ])
        ->create(['type_id' => $this->contentType->id]);
    $post->delete();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->where(
                'latestPosts',
                fn ($posts): bool => collect($posts)->doesntContain('title', 'Tidak Boleh Publik'),
            ));

    $this->get('/berita')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/post-archive')
            ->where(
                'posts.data',
                fn ($posts): bool => collect($posts)->doesntContain('title', 'Tidak Boleh Publik'),
            ));
});

it('sitemap tidak memuat slug Post dan Page yang ada di Trash', function (): void {
    $post = Post::factory()
        ->withTranslation('id', $this->languageId, [
            'slug' => 'sitemap-post-trash',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ])
        ->create(['type_id' => $this->contentType->id]);
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->create([
        'language_id' => $this->languageId,
        'slug' => 'sitemap-page-trash',
        'status' => PostStatus::Published->value,
    ]);
    $post->delete();
    $page->delete();

    $this->artisan('sitemap:generate')->assertSuccessful();
    $sitemap = file_get_contents(public_path('sitemap.xml'));

    expect($sitemap)->toBeString()
        ->not->toContain('sitemap-post-trash')
        ->not->toContain('sitemap-page-trash');
});

it('menu publik melewati item yang menargetkan Page di Trash', function (): void {
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->create([
        'language_id' => $this->languageId,
        'slug' => 'menu-page-trash',
        'status' => PostStatus::Published->value,
    ]);
    $menu = Menu::factory()->create();
    $item = MenuItem::factory()->for($menu)->create([
        'link_type' => LinkType::Page,
        'link_ref' => (string) $page->id,
        'url' => null,
    ]);
    MenuItemTranslation::query()->create([
        'menu_item_id' => $item->id,
        'language_id' => $this->languageId,
        'label' => 'Page Trash',
    ]);
    $page->delete();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('public/home')
            ->where('headerMenu', []));
});
