<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContentType;
use App\Models\User;

class ContentTypePolicy
{
    /**
     * Hanya user dengan permission content-types.viewAny yang boleh melihat daftar jenis konten.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('content-types.viewAny');
    }

    /**
     * Boleh membuat jenis konten bila punya permission content-types.create.
     */
    public function create(User $user): bool
    {
        return $user->can('content-types.create');
    }

    /**
     * Boleh memperbarui jenis konten bila punya permission content-types.update.
     */
    public function update(User $user, ContentType $contentType): bool
    {
        return $user->can('content-types.update');
    }

    /**
     * Boleh menghapus jenis konten bila punya permission content-types.delete.
     */
    public function delete(User $user, ContentType $contentType): bool
    {
        return $user->can('content-types.delete');
    }
}
