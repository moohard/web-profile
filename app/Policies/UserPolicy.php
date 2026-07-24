<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Admin boleh melihat daftar user.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.viewAny');
    }

    /**
     * Admin boleh melihat detail user.
     */
    public function view(User $user, User $_target): bool
    {
        return $user->can('users.viewAny');
    }

    /**
     * Admin boleh membuat user.
     */
    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    /**
     * Admin boleh memperbarui user.
     */
    public function update(User $user, User $_target): bool
    {
        return $user->can('users.update');
    }

    /**
     * Admin tidak boleh menghapus diri sendiri atau Admin terakhir.
     */
    public function delete(User $user, User $target): bool
    {
        if (! $user->can('users.delete') || $user->is($target)) {
            return false;
        }

        if (! $target->hasRole(UserRole::Admin)) {
            return true;
        }

        return User::role(UserRole::Admin)->count() > 1;
    }
}
