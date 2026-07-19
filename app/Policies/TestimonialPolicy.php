<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Testimonial;
use App\Models\User;

class TestimonialPolicy
{
    /**
     * Admin dan Editor boleh melihat daftar testimoni.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('testimonials.viewAny');
    }

    /**
     * Admin dan Editor boleh melihat detail testimoni.
     */
    public function view(User $user, Testimonial $testimonial): bool
    {
        return $user->can('testimonials.viewAny') || $user->can('testimonials.update');
    }

    /**
     * Admin dan Editor boleh membuat testimoni.
     */
    public function create(User $user): bool
    {
        return $user->can('testimonials.create');
    }

    /**
     * Admin dan Editor boleh memperbarui testimoni.
     */
    public function update(User $user, Testimonial $testimonial): bool
    {
        if ($user->hasRole(UserRole::Admin->value) || $user->hasRole(UserRole::Editor->value)) {
            return true;
        }

        return $user->can('testimonials.update');
    }

    /**
     * Hapus testimoni mengikuti aturan update / permission delete.
     */
    public function delete(User $user, Testimonial $testimonial): bool
    {
        if ($user->hasRole(UserRole::Admin->value) || $user->hasRole(UserRole::Editor->value)) {
            return true;
        }

        return $user->can('testimonials.delete');
    }
}
