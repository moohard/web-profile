<?php

use App\Enums\UserRole;
use App\Models\User;

beforeEach(function () {
    $this->seed();
});

it('semua route placeholder mengembalikan 200 untuk Admin', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();

    $routes = [
        '/admin/posts',
        '/admin/pages',
        '/admin/menus',
        '/admin/widgets',
        '/admin/contact-messages',
        '/admin/testimonials',
        '/admin/ratings',
        '/admin/users',
        '/admin/settings',
        '/admin/settings/ai',
        '/admin/settings/languages',
        '/admin/content-types',
        '/admin/categories',
        '/admin/tags',
        '/admin/galleries',
        '/admin/writing-styles',
        '/admin/rating-criteria',
        '/admin/media',
    ];

    foreach ($routes as $route) {
        $this->actingAs($admin)->get($route)->assertOk();
    }
});

it('route Tampilan ter-tolak untuk Editor', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);

    $this->actingAs($editor)->get('/admin/menus')->assertForbidden();
    $this->actingAs($editor)->get('/admin/widgets')->assertForbidden();
});

it('route Sistem ter-tolak untuk Editor', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);

    $this->actingAs($editor)->get('/admin/users')->assertForbidden();
    $this->actingAs($editor)->get('/admin/settings')->assertForbidden();
});
