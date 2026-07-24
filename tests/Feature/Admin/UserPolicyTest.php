<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('Admin boleh mengelola user lain yang bukan Admin', function () {
    $admin = User::factory()->create()->assignRole(UserRole::Admin->value);
    $target = User::factory()->create()->assignRole(UserRole::Author->value);

    expect($admin->can('viewAny', User::class))->toBeTrue()
        ->and($admin->can('view', $target))->toBeTrue()
        ->and($admin->can('create', User::class))->toBeTrue()
        ->and($admin->can('update', $target))->toBeTrue()
        ->and($admin->can('delete', $target))->toBeTrue();
});

it('Admin tidak boleh menghapus dirinya sendiri', function () {
    $admin = User::factory()->create()->assignRole(UserRole::Admin->value);
    User::factory()->create()->assignRole(UserRole::Admin->value);

    expect($admin->can('delete', $admin))->toBeFalse();
});

it('Admin terakhir tidak boleh dihapus', function () {
    $actingAdmin = User::factory()->create()->assignRole(UserRole::Admin->value);
    $targetAdmin = User::factory()->create()->assignRole(UserRole::Admin->value);

    $actingAdmin->removeRole(UserRole::Admin->value);
    $actingAdmin->givePermissionTo('users.delete');

    expect($actingAdmin->can('delete', $targetAdmin))->toBeFalse();
});

it('Admin boleh menghapus Admin lain bila masih ada Admin tersisa', function () {
    $actingAdmin = User::factory()->create()->assignRole(UserRole::Admin->value);
    $targetAdmin = User::factory()->create()->assignRole(UserRole::Admin->value);

    expect($actingAdmin->can('delete', $targetAdmin))->toBeTrue();
});

it('Editor dan Author tidak boleh mengakses manajemen user', function (UserRole $role) {
    $user = User::factory()->create()->assignRole($role->value);
    $target = User::factory()->create();

    expect($user->can('viewAny', User::class))->toBeFalse()
        ->and($user->can('view', $target))->toBeFalse()
        ->and($user->can('create', User::class))->toBeFalse()
        ->and($user->can('update', $target))->toBeFalse()
        ->and($user->can('delete', $target))->toBeFalse();
})->with([
    'Editor' => UserRole::Editor,
    'Author' => UserRole::Author,
]);
