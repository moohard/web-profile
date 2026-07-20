<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    /**
     * Hanya user dengan permission content-types.viewAny yang boleh melihat daftar tag.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('content-types.viewAny');
    }

    /**
     * Boleh membuat tag bila punya permission content-types.create.
     */
    public function create(User $user): bool
    {
        return $user->can('content-types.create');
    }

    /**
     * Boleh memperbarui tag bila punya permission content-types.update.
     */
    public function update(User $user, Tag $tag): bool
    {
        return $user->can('content-types.update');
    }

    /**
     * Boleh menghapus tag bila punya permission content-types.delete.
     */
    public function delete(User $user, Tag $tag): bool
    {
        return $user->can('content-types.delete');
    }
}
