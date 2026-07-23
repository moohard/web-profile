<?php

use App\Actions\Languages\DeleteLanguage;
use App\Actions\Languages\SaveLanguage;
use App\Enums\UserRole;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    Language::flushCache();

    $this->admin = User::query()
        ->where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))
        ->firstOrFail();
});

it('hanya Admin dapat mengelola Languages', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);

    $this->actingAs($this->admin)
        ->get('/admin/settings/languages')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/languages/index')
            ->has('languages', 2)
        );

    $this->actingAs($editor)
        ->get('/admin/settings/languages')
        ->assertForbidden();

    $this->actingAs($editor)
        ->post('/admin/settings/languages', [
            'code' => 'fr',
            'name' => 'Français',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 3,
        ])
        ->assertForbidden();
});

it('memvalidasi kode dua huruf lowercase dan unik', function (string $code) {
    $this->actingAs($this->admin)
        ->post('/admin/settings/languages', [
            'code' => $code,
            'name' => 'Bahasa Uji',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 3,
        ])
        ->assertSessionHasErrors('code');
})->with(['ID', 'eng', 'i', '1d', 'id', 'up']);

it('membuat bahasa aktif non-default', function () {
    $this->actingAs($this->admin)
        ->post('/admin/settings/languages', [
            'code' => 'fr',
            'name' => 'Français',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 3,
        ])
        ->assertRedirect('/admin/settings/languages');

    $language = Language::query()->where('code', 'fr')->firstOrFail();

    expect($language->name)->toBe('Français')
        ->and($language->is_active)->toBeTrue()
        ->and($language->is_default)->toBeFalse();
});

it('menjadikan bahasa pertama sebagai default aktif', function () {
    Language::query()->delete();
    Language::flushCache();

    $this->actingAs($this->admin)
        ->post('/admin/settings/languages', [
            'code' => 'fr',
            'name' => 'Français',
            'is_active' => false,
            'is_default' => false,
            'sort_order' => 1,
        ])
        ->assertRedirect('/admin/settings/languages');

    $language = Language::query()->sole();

    expect($language->is_default)->toBeTrue()
        ->and($language->is_active)->toBeTrue();
});

it('memindahkan default secara atomik dan membersihkan cache bahasa', function () {
    Cache::put('language.default_code', 'id');
    $english = Language::query()->where('code', 'en')->firstOrFail();

    $this->actingAs($this->admin)
        ->put("/admin/settings/languages/{$english->id}", [
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 2,
        ])
        ->assertRedirect('/admin/settings/languages');

    expect(Language::query()->where('is_default', true)->count())->toBe(1)
        ->and(Language::defaultModel()->code)->toBe('en')
        ->and(Language::query()->where('code', 'id')->value('is_default'))->toBeFalse();
});

it('menolak default yang inactive dan menolak menonaktifkan bahasa default', function () {
    $indonesian = Language::query()->where('code', 'id')->firstOrFail();

    $this->actingAs($this->admin)
        ->put("/admin/settings/languages/{$indonesian->id}", [
            'code' => 'id',
            'name' => 'Bahasa Indonesia',
            'is_active' => false,
            'is_default' => true,
            'sort_order' => 1,
        ])
        ->assertSessionHasErrors('is_active');

    $this->actingAs($this->admin)
        ->put("/admin/settings/languages/{$indonesian->id}", [
            'code' => 'id',
            'name' => 'Bahasa Indonesia',
            'is_active' => false,
            'is_default' => false,
            'sort_order' => 1,
        ])
        ->assertSessionHasErrors('is_default');
});

it('mengizinkan perubahan kode sebelum dipakai dan menolaknya setelah dipakai', function () {
    $language = Language::factory()->create(['code' => 'fr']);

    $this->actingAs($this->admin)
        ->put("/admin/settings/languages/{$language->id}", [
            'code' => 'de',
            'name' => 'Deutsch',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 3,
        ])
        ->assertRedirect('/admin/settings/languages');

    $language->refresh();
    $page = Page::factory()->create();
    PageTranslation::factory()->create([
        'page_id' => $page->id,
        'language_id' => $language->id,
    ]);

    $this->actingAs($this->admin)
        ->put("/admin/settings/languages/{$language->id}", [
            'code' => 'fr',
            'name' => 'Français',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 3,
        ])
        ->assertSessionHasErrors('code');

    expect($language->fresh()->code)->toBe('de');
});

it('menolak menghapus bahasa default atau bahasa yang sudah dipakai', function () {
    $indonesian = Language::query()->where('code', 'id')->firstOrFail();
    $english = Language::query()->where('code', 'en')->firstOrFail();
    $page = Page::factory()->create();

    PageTranslation::factory()->create([
        'page_id' => $page->id,
        'language_id' => $english->id,
    ]);

    $this->actingAs($this->admin)
        ->delete("/admin/settings/languages/{$indonesian->id}")
        ->assertSessionHasErrors('language');

    $this->actingAs($this->admin)
        ->delete("/admin/settings/languages/{$english->id}")
        ->assertSessionHasErrors('language');

    expect(Language::query()->count())->toBe(2);
});

it('menghapus bahasa non-default yang belum dipakai', function () {
    $language = Language::factory()->create(['code' => 'fr']);

    $this->actingAs($this->admin)
        ->delete("/admin/settings/languages/{$language->id}")
        ->assertRedirect('/admin/settings/languages');

    $this->assertModelMissing($language);
});

it('LanguageSeeder idempotent dan tidak menghapus translation yang sudah ada', function () {
    $english = Language::query()->where('code', 'en')->firstOrFail();
    $translation = PageTranslation::factory()->create([
        'page_id' => Page::factory()->create()->id,
        'language_id' => $english->id,
    ]);

    $this->seed(LanguageSeeder::class);

    expect(Language::query()->where('code', 'id')->count())->toBe(1)
        ->and(Language::query()->where('code', 'en')->count())->toBe(1);
    $this->assertModelExists($translation);
});

it('Action menyegel pemeriksaan penggunaan bahasa di dalam boundary mutasi', function () {
    $language = Language::factory()->create(['code' => 'fr']);
    PageTranslation::factory()->create([
        'page_id' => Page::factory()->create()->id,
        'language_id' => $language->id,
    ]);

    expect(fn () => app(SaveLanguage::class)->handle([
        'code' => 'de',
        'name' => 'Deutsch',
        'is_active' => true,
        'is_default' => false,
        'sort_order' => 3,
    ], $language))->toThrow(ValidationException::class)
        ->and(fn () => app(DeleteLanguage::class)->handle($language))
        ->toThrow(ValidationException::class);

    expect($language->fresh()->code)->toBe('fr');
    $this->assertModelExists($language);
});
