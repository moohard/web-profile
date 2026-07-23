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
        return $user->hasAnyRole([
            UserRole::Admin->value,
            UserRole::Editor->value,
            UserRole::Author->value,
        ]);
    }

    /**
     * Admin dan Editor boleh update semua post.
     * Author hanya boleh mengubah post miliknya sendiri.
     */
    public function update(User $user, Post $post): bool
    {
        if ($user->hasRole(UserRole::Admin->value) || $user->hasRole(UserRole::Editor->value)) {
            return true;
        }

        return $this->owns($user, $post);
    }

    /**
     * Hapus post mengikuti aturan update.
     */
    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }

    /**
     * Hapus post milik sendiri (khusus pemilik).
     */
    public function deleteOwn(User $user, Post $post): bool
    {
        return $this->owns($user, $post);
    }

    public function viewTrash(User $user): bool
    {
        return $user->can('posts.viewAny');
    }

    public function restore(User $user, Post $post): bool
    {
        if ($user->hasAnyRole([UserRole::Admin->value, UserRole::Editor->value])) {
            return $user->can('posts.update');
        }

        return $user->can('posts.update') && $this->owns($user, $post);
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->can('posts.delete');
    }

    /**
     * Apakah user adalah pemilik (author) dari post ini.
     */
    private function owns(User $user, Post $post): bool
    {
        return $post->author_id !== null && $post->author_id === $user->id;
    }
}
