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

    /**
     * Kembalikan post dari trash — Admin/Editor saja (Author tidak, meski miliknya sendiri).
     */
    public function restore(User $user, Post $post): bool
    {
        return $user->hasAnyRole([UserRole::Admin->value, UserRole::Editor->value]);
    }

    /**
     * Hapus post permanen mengikuti aturan restore.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        return $this->restore($user, $post);
    }

    /**
     * Apakah user adalah pemilik (author) dari post ini.
     */
    private function owns(User $user, Post $post): bool
    {
        return $post->author_id !== null && $post->author_id === $user->id;
    }
}
