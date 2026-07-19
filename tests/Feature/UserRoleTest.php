<?php

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;

it('User bisa di-assign role dan dicek', function () {
    Role::firstOrCreate(['name' => UserRole::Admin->value, 'guard_name' => 'web']);
    $user = User::factory()->create()->assignRole(UserRole::Admin->value);

    expect($user->hasRole(UserRole::Admin->value))->toBeTrue();
});

it('Role kelas custom dipakai', function () {
    expect(config('permission.models.role'))->toBe(Role::class)
        ->and(is_subclass_of(Role::class, Spatie\Permission\Models\Role::class))->toBeTrue();

    $role = Role::create(['name' => 'tester', 'guard_name' => 'web']);

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role)->toBeInstanceOf(Spatie\Permission\Models\Role::class);
});
