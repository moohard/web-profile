<?php

declare(strict_types=1);

use App\Enums\LinkType;
use App\Enums\UserRole;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    Cache::flush();
});

function menuAdmin(): User
{
    return User::query()->where('email', config('admin.email'))->firstOrFail();
}

test('admin dapat membuat, memperbarui, dan menghapus menu', function () {
    $admin = menuAdmin();

    $this->actingAs($admin)
        ->get('/admin/menus')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/menus/index')
            ->has('menus')
            ->has('languages')
        );

    $this->actingAs($admin)
        ->post('/admin/menus', ['name' => 'Navigasi Utama', 'location' => 'Header'])
        ->assertRedirect();

    $menu = Menu::query()->where('name', 'Navigasi Utama')->firstOrFail();

    $this->actingAs($admin)
        ->put("/admin/menus/{$menu->id}", ['name' => 'Navigasi Baru', 'location' => 'Footer'])
        ->assertRedirect();

    expect($menu->fresh()?->name)->toBe('Navigasi Baru')
        ->and($menu->fresh()?->location->value)->toBe('Footer');

    $this->actingAs($admin)
        ->delete("/admin/menus/{$menu->id}")
        ->assertRedirect();

    expect(Menu::find($menu->id))->toBeNull();
});

test('admin menyimpan item Page bersarang dua tingkat, mengurutkan ulang, dan memperbarui prop publik', function () {
    $admin = menuAdmin();
    $languageId = Language::idFor('id');
    $menu = Menu::factory()->create(['location' => 'Header']);
    $page = Page::factory()->create();
    PageTranslation::factory()->create([
        'page_id' => $page->id,
        'language_id' => $languageId,
        'slug' => 'profil',
        'title' => 'Profil',
        'status' => 'Published',
    ]);

    $this->get('/')->assertOk();

    $this->actingAs($admin)
        ->post("/admin/menus/{$menu->id}/items", [
            'parent_id' => null,
            'link_type' => LinkType::Page->value,
            'link_ref' => (string) $page->id,
            'url' => null,
            'sort_order' => 2,
            'translations' => [['language_id' => $languageId, 'label' => 'Profil']],
        ])
        ->assertRedirect();

    $parent = MenuItem::query()->where('menu_id', $menu->id)->firstOrFail();

    $this->actingAs($admin)
        ->post("/admin/menus/{$menu->id}/items", [
            'parent_id' => $parent->id,
            'link_type' => LinkType::Url->value,
            'link_ref' => null,
            'url' => '/profil/tim',
            'sort_order' => 1,
            'translations' => [['language_id' => $languageId, 'label' => 'Tim']],
        ])
        ->assertRedirect();

    $child = MenuItem::query()->where('parent_id', $parent->id)->firstOrFail();

    $this->actingAs($admin)
        ->put("/admin/menus/{$menu->id}/items/sync", [
            'items' => [
                [
                    'id' => $parent->id,
                    'parent_id' => null,
                    'link_type' => LinkType::Page->value,
                    'link_ref' => (string) $page->id,
                    'url' => null,
                    'sort_order' => 1,
                    'translations' => [['language_id' => $languageId, 'label' => 'Profil']],
                ],
                [
                    'id' => $child->id,
                    'parent_id' => $parent->id,
                    'link_type' => LinkType::Url->value,
                    'link_ref' => null,
                    'url' => '/profil/tim',
                    'sort_order' => 2,
                    'translations' => [['language_id' => $languageId, 'label' => 'Tim']],
                ],
            ],
        ])
        ->assertRedirect();

    expect($parent->fresh()?->sort_order)->toBe(1)
        ->and($child->fresh()?->parent_id)->toBe($parent->id)
        ->and($child->fresh()?->translations()->where('language_id', $languageId)->value('label'))->toBe('Tim');

    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->where('headerMenu.0.label', 'Profil')
        ->where('headerMenu.0.url', '/profil')
        ->where('headerMenu.0.children.0.label', 'Tim')
    );
});

test('menolak item tingkat ketiga dan siklus parent-child', function () {
    $admin = menuAdmin();
    $languageId = Language::idFor('id');
    $menu = Menu::factory()->create();
    $parent = MenuItem::factory()->create(['menu_id' => $menu->id]);
    $child = MenuItem::factory()->create(['menu_id' => $menu->id, 'parent_id' => $parent->id]);

    $payload = [
        'parent_id' => $child->id,
        'link_type' => LinkType::Url->value,
        'link_ref' => null,
        'url' => '/terlalu-dalam',
        'sort_order' => 1,
        'translations' => [['language_id' => $languageId, 'label' => 'Terlalu dalam']],
    ];

    $this->actingAs($admin)
        ->post("/admin/menus/{$menu->id}/items", $payload)
        ->assertSessionHasErrors('parent_id');

    $this->actingAs($admin)
        ->put("/admin/menus/{$menu->id}/items/sync", [
            'items' => [
                [
                    'id' => $parent->id,
                    'parent_id' => $child->id,
                    'link_type' => LinkType::Url->value,
                    'link_ref' => null,
                    'url' => '/',
                    'sort_order' => 1,
                    'translations' => [['language_id' => $languageId, 'label' => 'Parent']],
                ],
                [
                    'id' => $child->id,
                    'parent_id' => $parent->id,
                    'link_type' => LinkType::Url->value,
                    'link_ref' => null,
                    'url' => '/anak',
                    'sort_order' => 1,
                    'translations' => [['language_id' => $languageId, 'label' => 'Anak']],
                ],
            ],
        ])
        ->assertSessionHasErrors('items.0.parent_id');
});

test('Editor dan Author tidak dapat mengakses menu', function () {
    $menu = Menu::factory()->create();

    foreach ([UserRole::Editor, UserRole::Author] as $role) {
        $user = User::factory()->create()->assignRole($role->value);

        $this->actingAs($user)->get('/admin/menus')->assertForbidden();
        $this->actingAs($user)->post('/admin/menus', ['name' => 'Terlarang', 'location' => 'Header'])->assertForbidden();
        $this->actingAs($user)->put("/admin/menus/{$menu->id}", ['name' => 'Terlarang', 'location' => 'Header'])->assertForbidden();
        $this->actingAs($user)->delete("/admin/menus/{$menu->id}")->assertForbidden();
    }
});
