<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\RatingCriterion;
use App\Models\User;
use Database\Factories\RatingFactory;
use Database\Factories\RatingScoreFactory;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    $this->withoutVite();
});

it('menampilkan agregasi dan submission terbaru kepada pengguna dengan permission ratings viewAny', function () {
    $admin = User::query()->where('email', config('admin.email'))->firstOrFail();
    $criterion = RatingCriterion::query()->firstOrFail();
    $rating = RatingFactory::new()->create(['comment' => 'Sangat baik.']);
    RatingScoreFactory::new()->create([
        'rating_id' => $rating->id,
        'criterion_id' => $criterion->id,
        'score' => 5,
    ]);

    $this->actingAs($admin)
        ->get('/admin/ratings')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/ratings/index')
            ->where('totalRespondents', 1)
            ->where('criteria.0.id', $criterion->id)
            ->where('criteria.0.average', 5)
            ->where('ratings.0.id', $rating->id)
            ->where('ratings.0.comment', 'Sangat baik.')
        );
});

it('menolak pengguna tanpa permission ratings viewAny', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);

    $this->actingAs($author)->get('/admin/ratings')->assertForbidden();
});

it('Editor dapat mengakses /admin/ratings karena memiliki ratings.viewAny per seeder', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);

    $this->actingAs($editor)->get('/admin/ratings')->assertOk();
});
