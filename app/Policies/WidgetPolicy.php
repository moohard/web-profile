<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Widget;

class WidgetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('widgets.viewAny');
    }

    public function view(User $user, Widget $widget): bool
    {
        return $user->can('widgets.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->can('widgets.create');
    }

    public function update(User $user, Widget $widget): bool
    {
        return $user->can('widgets.update');
    }

    public function delete(User $user, Widget $widget): bool
    {
        return $user->can('widgets.delete');
    }
}
