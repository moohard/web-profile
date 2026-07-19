<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    /**
     * Admin dan Editor boleh melihat daftar halaman.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('pages.viewAny');
    }

    /**
     * Admin dan Editor boleh melihat detail halaman.
     */
    public function view(User $user, Page $page): bool
    {
        return $user->can('pages.viewAny') || $user->can('pages.update');
    }

    /**
     * Admin dan Editor boleh membuat halaman.
     */
    public function create(User $user): bool
    {
        return $user->can('pages.create');
    }

    /**
     * Admin dan Editor boleh memperbarui halaman (Author tidak punya pages.*).
     */
    public function update(User $user, Page $page): bool
    {
        if ($user->hasRole(UserRole::Admin->value) || $user->hasRole(UserRole::Editor->value)) {
            return true;
        }

        return $user->can('pages.update');
    }

    /**
     * Hapus halaman mengikuti aturan update / permission delete.
     */
    public function delete(User $user, Page $page): bool
    {
        if ($user->hasRole(UserRole::Admin->value) || $user->hasRole(UserRole::Editor->value)) {
            return true;
        }

        return $user->can('pages.delete');
    }
}
