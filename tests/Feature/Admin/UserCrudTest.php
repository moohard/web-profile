<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed();
});

function userCrudAdmin(): User
{
    return User::query()->where('email', config('admin.email'))->firstOrFail();
}

it('Admin dapat melihat daftar pengguna', function () {
    $user = User::factory()->create(['name' => 'Zainab'])->assignRole(UserRole::Editor->value);

    $this->actingAs(userCrudAdmin())
        ->get('/admin/users')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/index')
            ->has('users', 2)
            ->where('users.1.id', $user->id)
            ->where('users.1.role', UserRole::Editor->value)
        );
});

it('Admin dapat membuat pengguna dan menetapkan role', function () {
    $this->actingAs(userCrudAdmin())
        ->post('/admin/users', [
            'name' => 'Budi',
            'email' => 'budi@example.test',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
            'role' => UserRole::Editor->value,
        ])
        ->assertRedirect('/admin/users');

    $user = User::query()->where('email', 'budi@example.test')->firstOrFail();

    expect($user->name)->toBe('Budi')
        ->and(Hash::check('password-baru', $user->password))->toBeTrue()
        ->and($user->hasRole(UserRole::Editor))->toBeTrue();
});

it('memvalidasi email unik dan password saat membuat pengguna', function () {
    $existingUser = User::factory()->create();

    $this->actingAs(userCrudAdmin())
        ->from('/admin/users')
        ->post('/admin/users', [
            'name' => '',
            'email' => $existingUser->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => 'Tidak Ada',
        ])
        ->assertRedirect('/admin/users')
        ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
});

it('Admin dapat memperbarui pengguna tanpa mengganti password kosong', function () {
    $user = User::factory()->create([
        'email' => 'lama@example.test',
        'password' => 'password-lama',
    ])->assignRole(UserRole::Author->value);
    $passwordHash = $user->password;

    $this->actingAs(userCrudAdmin())
        ->put("/admin/users/{$user->id}", [
            'name' => 'Nama Baru',
            'email' => 'baru@example.test',
            'password' => '',
            'password_confirmation' => '',
            'role' => UserRole::Editor->value,
        ])
        ->assertRedirect('/admin/users');

    $user->refresh();

    expect($user->name)->toBe('Nama Baru')
        ->and($user->email)->toBe('baru@example.test')
        ->and($user->password)->toBe($passwordHash)
        ->and($user->hasRole(UserRole::Editor))->toBeTrue();
});

it('mengizinkan email sendiri ketika memperbarui pengguna', function () {
    $user = User::factory()->create(['email' => 'tetap@example.test'])
        ->assignRole(UserRole::Author->value);

    $this->actingAs(userCrudAdmin())
        ->put("/admin/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => UserRole::Author->value,
        ])
        ->assertRedirect('/admin/users');
});

it('Admin dapat menghapus pengguna non-Admin', function () {
    $user = User::factory()->create()->assignRole(UserRole::Author->value);

    $this->actingAs(userCrudAdmin())
        ->delete("/admin/users/{$user->id}")
        ->assertRedirect('/admin/users');

    $this->assertModelMissing($user);
});

it('hanya Admin yang dapat mengakses manajemen pengguna', function (UserRole $role) {
    $user = User::factory()->create()->assignRole($role->value);

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertForbidden();
})->with([
    UserRole::Editor,
    UserRole::Author,
]);

it('menolak penghapusan Admin terakhir', function () {
    $lastAdmin = userCrudAdmin();
    $actor = User::factory()->create();
    $actor->givePermissionTo(['admin.access-system', 'users.delete']);

    $this->actingAs($actor)
        ->delete("/admin/users/{$lastAdmin->id}")
        ->assertForbidden();

    $this->assertModelExists($lastAdmin);
});

it('menolak penghapusan diri sendiri', function () {
    $admin = userCrudAdmin();

    $this->actingAs($admin)
        ->delete("/admin/users/{$admin->id}")
        ->assertForbidden();

    $this->assertModelExists($admin);
});
