<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Policies;

use App\Enums\Permission;
use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Conversation $conversation): bool
    {
        // Customer yang terlibat
        if ($user->id === $conversation->user_id) {
            return true;
        }

        // Store owner yang memiliki toko
        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            return $user->shops()->where('id', $conversation->shop_id)->exists();
        }

        // Staff yang bekerja di toko tersebut
        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            return $user->shop_id === $conversation->shop_id;
        }

        return false;
    }

    public function create(User $user, ?int $shopId = null): bool
    {
        // Semua user yang login bisa membuat percakapan
        // Tapi pengecekan tambahan (tidak boleh chat dengan toko sendiri) dilakukan di controller
        return true;
    }
}
