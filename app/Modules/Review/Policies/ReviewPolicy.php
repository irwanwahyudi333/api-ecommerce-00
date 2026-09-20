<?php

declare(strict_types=1);

namespace App\Modules\Review\Policies;

use App\Enums\Permission;
use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true; // All users can view reviews (publicly)
    }

    public function view(?User $user, Review $review): bool
    {
        return true; // All users can view a specific review
    }

    public function create(User $user): bool
    {
        return true; // Only logged-in users can create reviews
    }

    public function update(User $user, Review $review): bool
    {
        // Only the author of the review or a super admin can update it
        return $user->id === $review->user_id
            || $user->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    public function delete(User $user, Review $review): bool
    {
        // Only the author of the review or a super admin can delete it
        return $user->id === $review->user_id
            || $user->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }
}
