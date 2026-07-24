<?php

declare(strict_types=1);

use App\Enums\TestimonialStatus;
use App\Models\Rating;
use App\Models\RatingCriterion;
use App\Models\Testimonial;
use App\Models\User;
use Database\Factories\RatingScoreFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    $this->withoutVite();
});

function dashboardAdmin(): User
{
    return User::query()->where('email', config('admin.email'))->firstOrFail();
}

it('mengirim statistik testimoni pending dan rating ke dashboard admin', function (): void {
    $criterion = RatingCriterion::factory()->create();

    // Dua penilai dengan skor 4 dan 5 → rata-rata 4.5 dari 2 penilai.
    foreach ([4, 5] as $score) {
        RatingScoreFactory::new()->create([
            'rating_id' => Rating::factory()->create()->id,
            'criterion_id' => $criterion->id,
            'score' => $score,
        ]);
    }

    Testimonial::factory()->count(2)->create(['status' => TestimonialStatus::Pending]);
    Testimonial::factory()->create(['status' => TestimonialStatus::Approved]);

    $this->actingAs(dashboardAdmin())
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('stats.testimonialsPending', 2)
            ->where('stats.ratingAverage', 4.5)
            ->where('stats.ratingTotal', 2)
        );
});

it('mengirim statistik rating kosong ketika belum ada penilai', function (): void {
    $this->actingAs(dashboardAdmin())
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('stats.ratingAverage', null)
            ->where('stats.ratingTotal', 0)
        );
});
