<?php

use App\Enums\ContactStatus;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
});

it('GET /admin menampilkan props statistik dashboard', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->has('stats', fn (Assert $stats) => $stats
                ->has('posts')
                ->has('pages')
                ->has('media')
                ->has('contactNew')
                ->etc()
            )
            ->has('draftPosts')
            ->has('newContactMessages')
            ->etc()
        );
});

it('Dashboard tidak crash bila tidak ada data draft/pesan', function () {
    Post::query()->delete();
    ContactMessage::query()->delete();

    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('stats.posts', 0)
            ->where('stats.contactNew', 0)
            ->has('draftPosts', 0)
            ->has('newContactMessages', 0)
            ->etc()
        );
});

it('Dashboard memuat draft dan pesan kontak baru', function () {
    ContactMessage::factory()->create([
        'name' => 'Budi Santoso',
        'subject' => 'Tanya produk',
        'status' => ContactStatus::New,
    ]);

    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('stats.contactNew', fn ($count) => $count >= 1)
            ->has('newContactMessages')
            ->etc()
        );
});
