<?php

declare(strict_types=1);

use App\Enums\TestimonialStatus;
use App\Enums\UserRole;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed();
});

function testimonialModerator(): User
{
    return User::query()->where('email', config('admin.email'))->firstOrFail();
}

it('mengizinkan Admin dan Editor serta menolak Author sesuai TestimonialPolicy', function (): void {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $author = User::factory()->create()->assignRole(UserRole::Author->value);

    $this->actingAs(testimonialModerator())->get('/admin/testimonials')->assertOk();
    $this->actingAs($editor)->get('/admin/testimonials')->assertOk();
    $this->actingAs($author)->get('/admin/testimonials')->assertForbidden();
});

it('menyetujui testimonial Pending dan menampilkannya di publik', function (): void {
    $testimonial = Testimonial::factory()->create(['status' => TestimonialStatus::Pending]);

    $this->actingAs(testimonialModerator())
        ->patch("/admin/testimonials/{$testimonial->id}/approve")
        ->assertRedirect();

    $this->assertDatabaseHas('testimonials', [
        'id' => $testimonial->id,
        'status' => TestimonialStatus::Approved->value,
    ]);

    $this->get('/testimoni')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('testimonials', 1)
            ->where('testimonials.0.id', $testimonial->id)
        );
});

it('menolak testimonial dengan menghapusnya permanen', function (): void {
    $testimonial = Testimonial::factory()->create(['status' => TestimonialStatus::Pending]);

    $this->actingAs(testimonialModerator())
        ->delete("/admin/testimonials/{$testimonial->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
});
