<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

class MenuPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('menus.viewAny');
    }

    public function view(User $user, Menu $menu): bool
    {
        return $user->can('menus.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->can('menus.create');
    }

    public function update(User $user, Menu $menu): bool
    {
        return $user->can('menus.update');
    }

    public function delete(User $user, Menu $menu): bool
    {
        return $user->can('menus.delete');
    }
}
