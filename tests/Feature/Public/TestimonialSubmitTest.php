<?php

declare(strict_types=1);

use App\Enums\TestimonialStatus;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed();
    RateLimiter::clear(md5('testimonial-submit127.0.0.1'));
});

it('menyimpan submission publik sebagai Pending', function (): void {
    $this->post(route('testimonial.store'), [
        'author_name' => 'Budi Santoso',
        'author_title' => 'Warga Penajam',
        'content' => 'Pelayanan publik sangat membantu.',
    ])->assertRedirect();

    $this->assertDatabaseHas('testimonials', [
        'author_name' => 'Budi Santoso',
        'status' => TestimonialStatus::Pending->value,
    ]);
});

it('hanya mengirim testimoni Approved ke halaman publik', function (): void {
    $approved = Testimonial::factory()->create(['status' => TestimonialStatus::Approved]);
    Testimonial::factory()->create(['status' => TestimonialStatus::Pending]);

    $this->get('/testimoni')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/page-show')
            ->has('testimonials', 1)
            ->where('testimonials.0.id', $approved->id)
        );
});

it('membatasi submission publik menjadi tiga per menit', function (): void {
    $payload = [
        'author_name' => 'Budi Santoso',
        'content' => 'Pelayanan publik sangat membantu.',
    ];

    foreach (range(1, 3) as $attempt) {
        $this->post(route('testimonial.store'), [...$payload, 'author_name' => "Budi {$attempt}"])
            ->assertRedirect();
    }

    $this->post(route('testimonial.store'), $payload)->assertTooManyRequests();
});
