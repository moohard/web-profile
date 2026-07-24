<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contact-messages.viewAny');
    }

    public function view(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('contact-messages.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->can('contact-messages.create');
    }

    public function update(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('contact-messages.update');
    }

    public function delete(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('contact-messages.delete');
    }
}
