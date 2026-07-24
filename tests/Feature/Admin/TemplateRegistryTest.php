<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Pages\PageTemplateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

function registryAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

it('GET /admin/templates render read-only registry untuk admin dengan permission', function () {
    $this->actingAs(registryAdmin())
        ->get('/admin/templates')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/templates/index')
            ->has('templates')
        );
});

it('daftar template di admin konsisten dengan PageTemplateRegistry dan menyertakan contact serta testimonials', function () {
    $expected = PageTemplateRegistry::options();

    $response = $this->actingAs(registryAdmin())
        ->get('/admin/templates')
        ->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/templates/index')
        ->where('templates', $expected)
    );

    $keys = collect($expected)->pluck('key')->all();
    expect($keys)->toContain('contact');
    expect($keys)->toContain('testimonials');
    expect($keys)->toContain('default');
});

it('non-admin tanpa permission ditolak akses template registry', function () {
    $editor = User::factory()->create()->assignRole('Editor');

    $this->actingAs($editor)
        ->get('/admin/templates')
        ->assertForbidden();
});
