<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RatingCriterion;
use App\Models\User;

class RatingCriterionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('rating-criteria.viewAny');
    }

    public function view(User $user, RatingCriterion $ratingCriterion): bool
    {
        return $user->can('rating-criteria.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->can('rating-criteria.create');
    }

    public function update(User $user, RatingCriterion $ratingCriterion): bool
    {
        return $user->can('rating-criteria.update');
    }

    public function delete(User $user, RatingCriterion $ratingCriterion): bool
    {
        return $user->can('rating-criteria.delete');
    }
}
