<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->idLang = Language::idFor('id');
});

function pageTrashEditor(): User
{
    return User::factory()->create()->assignRole(UserRole::Editor->value);
}

it('DELETE halaman melakukan soft delete: deleted_at terisi, hilang dari index, translations tetap ada', function () {
    $admin = User::where('email', config('admin.email'))->firstOrFail();
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->create(['language_id' => $this->idLang]);

    $this->actingAs($admin)
        ->delete("/admin/pages/{$page->id}")
        ->assertRedirect();

    expect(Page::find($page->id))->toBeNull()
        ->and(Page::withTrashed()->find($page->id))->not->toBeNull()
        ->and(Page::withTrashed()->find($page->id)->deleted_at)->not->toBeNull()
        ->and(PageTranslation::where('page_id', $page->id)->count())->toBe(1);

    // Index hanya menghitung halaman aktif (default query Eloquent mengecualikan trashed).
    $this->actingAs($admin)
        ->get('/admin/pages')
        ->assertInertia(fn (Assert $page) => $page->has('pages.data', Page::query()->count()));
});

it('GET /admin/pages/trash menampilkan hanya halaman yang sudah di-trash', function () {
    $admin = User::where('email', config('admin.email'))->firstOrFail();

    $active = Page::factory()->create();
    PageTranslation::factory()->for($active)->create(['language_id' => $this->idLang, 'title' => 'Aktif']);

    $trashed = Page::factory()->create();
    PageTranslation::factory()->for($trashed)->create(['language_id' => $this->idLang, 'title' => 'Terhapus']);
    $trashed->delete();

    $this->actingAs($admin)
        ->get('/admin/pages/trash')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/pages/trash')
            ->has('pages.data', 1)
            ->where('pages.data.0.title', 'Terhapus')
        );
});

it('PATCH restore mengembalikan halaman dari trash', function () {
    $admin = User::where('email', config('admin.email'))->firstOrFail();
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->create(['language_id' => $this->idLang]);
    $page->delete();

    $this->actingAs($admin)
        ->patch("/admin/pages/{$page->id}/restore")
        ->assertRedirect();

    expect(Page::find($page->id))->not->toBeNull()
        ->and(Page::find($page->id)->deleted_at)->toBeNull();
});

it('Editor boleh restore halaman', function () {
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->create(['language_id' => $this->idLang]);
    $page->delete();

    $this->actingAs(pageTrashEditor())
        ->patch("/admin/pages/{$page->id}/restore")
        ->assertRedirect();

    expect(Page::find($page->id))->not->toBeNull();
});

it('User tanpa role Admin/Editor (hanya permission pages.delete ad hoc) tidak boleh restore halaman', function () {
    $user = User::factory()->create()->givePermissionTo(['access-admin', 'pages.viewAny', 'pages.delete']);
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->create(['language_id' => $this->idLang]);
    $page->delete();

    $this->actingAs($user)
        ->patch("/admin/pages/{$page->id}/restore")
        ->assertForbidden();

    expect(Page::withTrashed()->find($page->id)->trashed())->toBeTrue();
});

it('DELETE force-delete menghapus halaman permanen beserta translations', function () {
    $admin = User::where('email', config('admin.email'))->firstOrFail();
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->create(['language_id' => $this->idLang]);
    $page->delete();

    $this->actingAs($admin)
        ->delete("/admin/pages/{$page->id}/force-delete")
        ->assertRedirect();

    expect(Page::withTrashed()->find($page->id))->toBeNull()
        ->and(PageTranslation::where('page_id', $page->id)->count())->toBe(0);
});

it('User tanpa role Admin/Editor tidak boleh forceDelete halaman', function () {
    $user = User::factory()->create()->givePermissionTo(['access-admin', 'pages.viewAny', 'pages.delete']);
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->create(['language_id' => $this->idLang]);
    $page->delete();

    $this->actingAs($user)
        ->delete("/admin/pages/{$page->id}/force-delete")
        ->assertForbidden();

    expect(Page::withTrashed()->find($page->id))->not->toBeNull();
});
