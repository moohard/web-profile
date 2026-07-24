<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\RatingCriterion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    $this->withoutVite();
});

function ratingCriteriaAdmin(): User
{
    return User::query()->where('email', config('admin.email'))->firstOrFail();
}

it('menampilkan kriteria rating dan bahasa untuk admin', function () {
    $this->actingAs(ratingCriteriaAdmin())
        ->get('/admin/rating-criteria')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/rating-criteria/index')
            ->has('criteria')
            ->has('languages')
        );
});

it('admin dapat membuat dan memperbarui kriteria i18n beserta status dan urutannya', function () {
    $idLanguage = Language::idFor('id');
    $enLanguage = Language::idFor('en');

    $this->actingAs(ratingCriteriaAdmin())
        ->post('/admin/rating-criteria', [
            'is_active' => true,
            'sort_order' => 10,
            'translations' => [
                ['language_id' => $idLanguage, 'name' => 'Kejelasan layanan'],
                ['language_id' => $enLanguage, 'name' => 'Service clarity'],
            ],
        ])
        ->assertRedirect();

    $criterion = RatingCriterion::query()
        ->whereHas('translations', fn ($query) => $query->where('name', 'Kejelasan layanan'))
        ->firstOrFail();

    $this->actingAs(ratingCriteriaAdmin())
        ->put("/admin/rating-criteria/{$criterion->id}", [
            'is_active' => false,
            'sort_order' => 3,
            'translations' => [
                ['language_id' => $idLanguage, 'name' => 'Kejelasan informasi'],
                ['language_id' => $enLanguage, 'name' => 'Information clarity'],
            ],
        ])
        ->assertRedirect();

    expect($criterion->fresh())
        ->is_active->toBeFalse()
        ->sort_order->toBe(3)
        ->translations()->where('language_id', $idLanguage)->value('name')->toBe('Kejelasan informasi');
});

it('pengguna tanpa permission rating criteria mendapat 403', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $criterion = RatingCriterion::query()->firstOrFail();

    $this->actingAs($editor)->get('/admin/rating-criteria')->assertForbidden();
    $this->actingAs($editor)->post('/admin/rating-criteria', [])->assertForbidden();
    $this->actingAs($editor)->put("/admin/rating-criteria/{$criterion->id}", [])->assertForbidden();
    $this->actingAs($editor)->delete("/admin/rating-criteria/{$criterion->id}")->assertForbidden();
});

it('Author tidak dapat mengakses rating criteria', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);

    $this->actingAs($author)->get('/admin/rating-criteria')->assertForbidden();
});
