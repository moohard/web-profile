<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Semua role di area admin bisa melihat daftar post.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Admin, Editor, dan Author boleh membuat post.
     */
    public function create(User $user): bool
    {
        return in_array($user->roles->first()?->name, [
            UserRole::Admin->value,
            UserRole::Editor->value,
            UserRole::Author->value,
        ], true);
    }

    /**
     * Admin dan Editor boleh update semua post.
     * Author hanya miliknya sendiri (author_id ditambah di fase fitur).
     */
    public function update(User $user, Post $post): bool
    {
        if ($user->hasRole(UserRole::Admin->value) || $user->hasRole(UserRole::Editor->value)) {
            return true;
        }

        // TODO fase fitur: return $post->author_id === $user->id;
        return false;
    }

    /**
     * Hapus post mengikuti aturan update.
     */
    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }

    /**
     * Hapus post milik sendiri — diisi di fase fitur.
     */
    public function deleteOwn(User $user, Post $post): bool
    {
        return false;
    }
}
