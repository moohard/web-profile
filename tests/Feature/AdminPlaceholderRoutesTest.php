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

it('route Interaksi dan Galeri ter-tolak untuk Author', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);

    $this->actingAs($author)->get('/admin/contact-messages')->assertForbidden();
    $this->actingAs($author)->get('/admin/testimonials')->assertForbidden();
    $this->actingAs($author)->get('/admin/ratings')->assertForbidden();
    $this->actingAs($author)->get('/admin/galleries')->assertForbidden();
});

it('route Interaksi dan Galeri dapat diakses Editor', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);

    $this->actingAs($editor)->get('/admin/contact-messages')->assertOk();
    $this->actingAs($editor)->get('/admin/testimonials')->assertOk();
    $this->actingAs($editor)->get('/admin/ratings')->assertOk();
    $this->actingAs($editor)->get('/admin/galleries')->assertOk();
});
