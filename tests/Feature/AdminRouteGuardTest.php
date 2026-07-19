<?php

use App\Enums\UserRole;
use App\Models\User;

beforeEach(function () {
    $this->seed();
});

it('guest tidak bisa akses /admin — redirect login', function () {
    $this->get('/admin')->assertRedirect('/login');
});

it('Admin bisa akses /admin', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();

    $this->actingAs($admin)->get('/admin')->assertOk();
});

it('Editor bisa akses /admin', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);

    $this->actingAs($editor)->get('/admin')->assertOk();
});

it('Author bisa akses /admin', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);

    $this->actingAs($author)->get('/admin')->assertOk();
});

it('User tanpa role apapun tidak bisa akses /admin', function () {
    $plain = User::factory()->create();

    $this->actingAs($plain)->get('/admin')->assertForbidden();
});
