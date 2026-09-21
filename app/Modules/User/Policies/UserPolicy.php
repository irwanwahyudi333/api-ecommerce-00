<?php

declare(strict_types=1);

namespace App\Modules\User\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * Pusat aturan otorisasi seputar entitas User.
 * Dipakai di FormRequest::authorize() DAN di Controller (defense in depth),
 * sehingga aturan "siapa boleh apa" hanya didefinisikan SATU KALI di sini.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo(Permission::SUPER_ADMIN->value) ||
               $actor->hasPermissionTo(Permission::STORE_OWNER->value) ||
               $actor->hasPermissionTo(Permission::STAFF->value);
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->id === $target->id
            || $actor->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->id === $target->id
            || $actor->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->id !== $target->id
            && $actor->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    public function toggleActive(User $actor, User $target): bool
    {
        return $actor->id !== $target->id
            && $actor->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    public function toggleAdmin(User $actor, User $target): bool
    {
        return $actor->id !== $target->id
            && $actor->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    public function viewShopAssignment(User $actor, User $target): bool
    {
        return $actor->id === $target->id
            || $actor->hasPermissionTo(Permission::SUPER_ADMIN->value)
            || $actor->hasPermissionTo(Permission::STORE_OWNER->value);
    }

    public function changePassword(User $actor, User $target): bool
    {
        // Users can change their own password
        // Super admins can change any password (password reset feature)
        return $actor->id === $target->id
            || $actor->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    public function viewSessions(User $actor, User $target): bool
    {
        // Users can view their own active sessions
        // Super admins can view any user sessions (security audit)
        return $actor->id === $target->id
            || $actor->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    public function revokeSessions(User $actor, User $target): bool
    {
        // Users can revoke their own sessions (logout from all devices)
        // Super admins can revoke any user sessions (security incident response)
        return $actor->id === $target->id
            || $actor->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    public function viewAuditLogs(User $actor, User $target): bool
    {
        // Only super admins can view user audit logs
        return $actor->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    public function updateSecuritySettings(User $actor, User $target): bool
    {
        // Users can update their own security settings (2FA, etc.)
        // Super admins can update any user's security settings
        return $actor->id === $target->id
            || $actor->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    public function verify(User $actor): bool
    {
        return true;
    }
}
