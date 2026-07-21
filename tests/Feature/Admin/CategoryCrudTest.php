<?php

declare(strict_types=1);

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

it('DELETE ditolak bila kategori masih memiliki post yang sudah di-trash (soft-deleted)', function () {
    // Post trashed pun MENGUNCI penghapusan kategori (sampai di-forceDelete)
    // demi menjaga garansi restore — lihat catatan di ContentTypeCrudTest.
    $idLang = Language::idFor('id');
    $category = Category::factory()->withTranslation('id', $idLang)->create();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::factory()->create(['type_id' => $type->id, 'category_id' => $category->id]);
    $post->delete();

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

it('GET /admin/categories mengirim parent_id anak untuk membangun tampilan hierarkis di klien', function () {
    // D5(C): daftar admin kini dirender sebagai tree (indent) berdasarkan
    // parent_id — dibangun di klien (categories/index.tsx). Test ini
    // memastikan kontrak data yang dibutuhkan (parent_id + urutan sort_order)
    // tetap terkirim dengan benar dari server.
    $idLang = Language::idFor('id');
    $parent = Category::factory()->withTranslation('id', $idLang, ['name' => 'Wisata'])->create(['sort_order' => 1]);
    $child = Category::factory()->withTranslation('id', $idLang, ['name' => 'Pantai'])->create([
        'parent_id' => $parent->id,
        'sort_order' => 2,
    ]);

    $this->actingAs(categoryAdmin())
        ->get('/admin/categories')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 2)
            ->where('categories.0.id', $parent->id)
            ->where('categories.0.parent_id', null)
            ->where('categories.1.id', $child->id)
            ->where('categories.1.parent_id', $parent->id)
        );
});

it('User tanpa content-types.viewAny mendapat 403', function () {
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
