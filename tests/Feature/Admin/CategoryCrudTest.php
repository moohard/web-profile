<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

function categoryAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

it('GET /admin/categories menampilkan daftar kategori untuk admin', function () {
    $idLang = Language::idFor('id');
    Category::factory()->withTranslation('id', $idLang, ['name' => 'Berita'])->create();

    $this->actingAs(categoryAdmin())
        ->get('/admin/categories')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/categories/index')
            ->has('categories', 1)
            ->has('languages')
        );
});

it('Editor dapat mengelola kategori', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $idLang = Language::idFor('id');

    $this->actingAs($editor)
        ->get('/admin/categories')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/categories/index'));

    $this->actingAs($editor)
        ->post('/admin/categories', [
            'translations' => [['language_id' => $idLang, 'name' => 'Kategori Editor']],
        ])
        ->assertRedirect();

    $category = Category::query()->where('slug', 'kategori-editor')->firstOrFail();

    $this->actingAs($editor)
        ->put("/admin/categories/{$category->id}", [
            'slug' => 'kategori-editor-baru',
            'translations' => [['language_id' => $idLang, 'name' => 'Kategori Editor Baru']],
        ])
        ->assertRedirect();

    $this->actingAs($editor)
        ->delete("/admin/categories/{$category->id}")
        ->assertRedirect();

    expect(Category::find($category->id))->toBeNull();
});

it('POST /admin/categories membuat kategori beserta translation per bahasa', function () {
    $idLang = Language::idFor('id');
    $enLang = Language::idFor('en');

    $this->actingAs(categoryAdmin())
        ->post('/admin/categories', [
            'sort_order' => 1,
            'translations' => [
                ['language_id' => $idLang, 'name' => 'Pengumuman'],
                ['language_id' => $enLang, 'name' => 'Announcement'],
            ],
        ])
        ->assertRedirect();

    $category = Category::query()->where('slug', 'pengumuman')->first();

    expect($category)->not->toBeNull()
        ->and($category->translations()->count())->toBe(2)
        ->and(CategoryTranslation::query()->where('category_id', $category->id)->where('language_id', $idLang)->value('name'))->toBe('Pengumuman')
        ->and(CategoryTranslation::query()->where('category_id', $category->id)->where('language_id', $enLang)->value('name'))->toBe('Announcement');
});

it('PUT /admin/categories/{category} memperbarui kategori dan translation', function () {
    $idLang = Language::idFor('id');
    $category = Category::factory()->withTranslation('id', $idLang, ['name' => 'Lama'])->create(['slug' => 'lama']);

    $this->actingAs(categoryAdmin())
        ->put("/admin/categories/{$category->id}", [
            'slug' => 'baru',
            'sort_order' => 5,
            'translations' => [
                ['language_id' => $idLang, 'name' => 'Baru'],
            ],
        ])
        ->assertRedirect();

    $fresh = $category->fresh();

    expect($fresh->slug)->toBe('baru')
        ->and($fresh->sort_order)->toBe(5)
        ->and($fresh->translations()->where('language_id', $idLang)->value('name'))->toBe('Baru');
});

it('DELETE ditolak bila kategori masih memiliki post', function () {
    $idLang = Language::idFor('id');
    $category = Category::factory()->withTranslation('id', $idLang)->create();
    $type = ContentType::where('slug', 'berita')->first();
    Post::factory()->create(['type_id' => $type->id, 'category_id' => $category->id]);

    $this->actingAs(categoryAdmin())
        ->delete("/admin/categories/{$category->id}")
        ->assertRedirect();

    expect(Category::find($category->id))->not->toBeNull();
});

it('DELETE menghapus kategori tanpa post terkait', function () {
    $idLang = Language::idFor('id');
    $category = Category::factory()->withTranslation('id', $idLang)->create();

    $this->actingAs(categoryAdmin())
        ->delete("/admin/categories/{$category->id}")
        ->assertRedirect();

    expect(Category::find($category->id))->toBeNull();
});

it('User tanpa categories permissions mendapat 403', function () {
    $user = User::factory()->create()->givePermissionTo('access-admin');
    $idLang = Language::idFor('id');
    $category = Category::factory()->withTranslation('id', $idLang)->create();

    $this->actingAs($user)->get('/admin/categories')->assertForbidden();
    $this->actingAs($user)->post('/admin/categories', [
        'translations' => [['language_id' => $idLang, 'name' => 'X']],
    ])->assertForbidden();
    $this->actingAs($user)->put("/admin/categories/{$category->id}", [
        'translations' => [['language_id' => $idLang, 'name' => 'X']],
    ])->assertForbidden();
    $this->actingAs($user)->delete("/admin/categories/{$category->id}")->assertForbidden();
});
