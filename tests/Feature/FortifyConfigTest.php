<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::Admin->value, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => UserRole::Editor->value, 'guard_name' => 'web']);
});

it('registration disabled — GET /register returns 404', function () {
    $response = $this->get('/register');

    expect(in_array($response->status(), [404, 405], true))->toBeTrue();
});

it('Gate use-page-code-mode hanya untuk Admin', function () {
    $admin = User::factory()->create()->assignRole(UserRole::Admin->value);
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);

    expect(Gate::forUser($admin)->allows('use-page-code-mode'))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('use-page-code-mode'))->toBeFalse();
});

it('Login dengan admin → redirect ke /admin', function () {
    // Hindari migrate:fresh di SQLite :memory: (VACUUM error); seed saja
    $this->seed();

    // Baca dari config admin (sumber yang sama dengan AdminUserSeeder) agar
    // konsisten walau ADMIN_PASSWORD di .env kosong (config sudah pakai `?:`).
    $admin = User::where('email', config('admin.email'))->first();

    expect($admin)->not->toBeNull();

    $response = $this->post('/login', [
        'email' => $admin->email,
        'password' => config('admin.password'),
    ]);

    $response->assertRedirect('/admin');
});
