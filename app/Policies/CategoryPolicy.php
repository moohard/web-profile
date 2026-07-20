<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Hanya user dengan permission content-types.viewAny yang boleh melihat daftar kategori.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('content-types.viewAny');
    }

    /**
     * Boleh membuat kategori bila punya permission content-types.create.
     */
    public function create(User $user): bool
    {
        return $user->can('content-types.create');
    }

    /**
     * Boleh memperbarui kategori bila punya permission content-types.update.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->can('content-types.update');
    }

    /**
     * Boleh menghapus kategori bila punya permission content-types.delete.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->can('content-types.delete');
    }
}
