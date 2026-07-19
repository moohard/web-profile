<?php

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
});

it('props auth.user.roles diisi untuk user login', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('auth.user.roles.0', UserRole::Admin->value)
        ->where('auth.user.canUseCodeMode', true)
        ->has('auth.user.permissions')
        ->has('contentTypes')
        ->etc()
    );
});

it('contentTypes berisi 3 tipe seeder', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('contentTypes', function ($types) {
            $items = collect($types);
            $slugs = $items->pluck('slug')->all();

            return count(array_intersect(['artikel', 'berita', 'pengumuman'], $slugs)) === 3;
        })
        ->etc()
    );
});

it('guest mendapat auth.user null dan contentTypes kosong', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('auth.user', null)
        ->where('contentTypes', [])
        ->etc()
    );
});
