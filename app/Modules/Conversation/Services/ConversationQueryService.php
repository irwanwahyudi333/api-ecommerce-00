<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Services;

use App\Enums\Permission;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

// Used for pagination

final class ConversationQueryService
{
    /**
     * Get conversations for the given user.
     *
     * @return Builder<Conversation>
     */
    public function getUserConversations(User $user): Builder
    {
        // Ambil ID toko yang dimiliki (store owner) atau tempat staff bekerja
        $shopIds = [];
        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            $shopIds = $user->shops()->pluck('id')->toArray();
        } elseif ($user->hasPermissionTo(Permission::STAFF->value) && $user->shop_id) {
            $shopIds = [$user->shop_id];
        }

        return Conversation::where(function ($query) use ($user, $shopIds) {
            $query->where('user_id', $user->id);
            if (! empty($shopIds)) {
                $query->orWhereIn('shop_id', $shopIds);
            }
        })
            ->with(['user', 'shop']) // cukup load user dan shop
            ->orderBy('updated_at', 'desc');
    }

    /**
     * Find a conversation by ID.
     */
    public function findConversationById(int $id): Conversation
    {
        return Conversation::with(['user', 'shop'])->findOrFail($id);
    }

    public function findOrFail(int $id): Conversation
    {
        return Conversation::findOrFail($id);
    }
}
