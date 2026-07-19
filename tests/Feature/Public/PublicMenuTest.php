<?php

use App\Enums\LinkType;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuItemTranslation;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Post;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
    $this->langId = Language::idFor('id');
});

it('resolve header menu untuk semua link_type + hierarki anak', function () {
    $langId = $this->langId;
    $menu = Menu::create(['name' => 'Utama', 'location' => 'Header']);
    $type = ContentType::where('slug', 'berita')->firstOrFail();
    $post = Post::firstOrFail(); // demo post: selamat-datang (id)
    $page = Page::create(['mode' => 'Template']);
    PageTranslation::create([
        'page_id' => $page->id,
        'language_id' => $langId,
        'slug' => 'tentang',
        'title' => 'Tentang',
        'status' => 'Published',
    ]);

    $make = function (LinkType $type, ?string $ref, ?string $url, int $sort, string $label, ?int $parentId = null) use ($menu, $langId): MenuItem {
        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $parentId,
            'link_type' => $type,
            'link_ref' => $ref,
            'url' => $url,
            'sort_order' => $sort,
        ]);
        MenuItemTranslation::create([
            'menu_item_id' => $item->id,
            'language_id' => $langId,
            'label' => $label,
        ]);

        return $item;
    };

    $make(LinkType::Url, null, '/kontak', 1, 'Kontak');
    $archive = $make(LinkType::ContentArchive, (string) $type->id, null, 2, 'Berita');
    $make(LinkType::ContentSingle, (string) $post->id, null, 3, 'Selamat Datang');
    $make(LinkType::Page, (string) $page->id, null, 4, 'Tentang');
    // Anak di bawah "Berita" — menguji hierarki
    $make(LinkType::Url, null, '/berita/khusus', 1, 'Khusus', $archive->id);

    $this->get('/')->assertInertia(fn (Assert $inertia) => $inertia
        ->component('public/home')
        ->has('headerMenu', 4) // hanya root item; anak dinested
        ->where('headerMenu.0.url', '/kontak')
        ->where('headerMenu.1.url', '/berita')
        ->where('headerMenu.2.url', '/berita/selamat-datang')
        ->where('headerMenu.3.url', '/tentang')
        ->where('headerMenu.1.children.0.url', '/berita/khusus')
        ->where('headerMenu.1.children.0.label', 'Khusus')
    );
});
