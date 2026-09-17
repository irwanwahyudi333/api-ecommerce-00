<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Policies;

use App\Enums\Permission;
use App\Models\Feedback;
use App\Models\User;

class FeedbackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::SUPER_ADMIN->value)
            || $user->hasPermissionTo(Permission::STORE_OWNER->value)
            || $user->hasPermissionTo(Permission::STAFF->value);
    }

    public function view(User $user, Feedback $feedback): bool
    {
        return $user->id === $feedback->user_id || $user->hasPermissionTo('super_admin');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Feedback $feedback): bool
    {
        return $user->id === $feedback->user_id || $user->hasPermissionTo('super_admin');
    }

    public function delete(User $user, Feedback $feedback): bool
    {
        return $this->update($user, $feedback);
    }

    public function toggle(User $user, Feedback $feedback): bool
    {
        return $this->update($user, $feedback);
    }
}
