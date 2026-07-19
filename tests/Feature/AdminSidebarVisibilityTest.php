<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
});

it('Admin mendapat permission Tampilan/Sistem untuk filter sidebar', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/placeholder')
            ->where('auth.user.permissions', function ($permissions) {
                $p = collect($permissions);

                // Permission yang membuka grup Tampilan, Sistem, dan item Pengguna
                return $p->contains('admin.access-appearance')
                    && $p->contains('admin.access-system')
                    && $p->contains('users.viewAny');
            })
            ->etc()
        );
});

it('Editor tidak mendapat permission Tampilan/Sistem', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);

    $this->actingAs($editor)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.permissions', function ($permissions) {
                $p = collect($permissions);

                return ! $p->contains('admin.access-appearance')
                    && ! $p->contains('admin.access-system')
                    && ! $p->contains('users.viewAny');
            })
            ->etc()
        );
});

it('Author tidak mendapat permission pesan kontak; punya media', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);

    $this->actingAs($author)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.permissions', function ($permissions) {
                $p = collect($permissions);

                // Sidebar menyembunyikan "Pesan kontak"; Media tanpa permission gate di config
                return ! $p->contains('contact-messages.viewAny')
                    && $p->contains('media.viewAny')
                    && $p->contains('posts.viewAny');
            })
            ->etc()
        );
});

it('Sidebar props contentTypes berisi Artikel, Berita, Pengumuman', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();

    $response = $this->actingAs($admin)->get('/admin');
    $html = $response->getContent();

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('contentTypes', function ($types) {
                $names = collect($types)->pluck('name')->all();

                return count(array_intersect(['Artikel', 'Berita', 'Pengumuman'], $names)) === 3;
            })
            ->etc()
        );

    // Nama tipe juga ter-serialize di data-page (tanpa SSR penuh)
    expect($html)->toContain('Artikel')->toContain('Berita')->toContain('Pengumuman');
});
