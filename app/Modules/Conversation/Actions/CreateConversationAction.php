<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Actions;

use App\Models\Conversation;
use App\Models\Shop;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CreateConversationAction
{
    public function execute(int $userId, int $shopId): Conversation
    {
        // Cek apakah user mencoba chat dengan tokonya sendiri
        $shop = Shop::findOrFail($shopId);
        // This logic was originally in the controller, it belongs here as a business rule.
        // It assumes the current user is $userId.
        $user = User::find($userId); // Get user model for shop owner/staff check
        if ($user && ($shop->owner_id === $user->id || ($user->shop_id && $user->shop_id === $shop->id))) {
            $message = config('notice.YOU_CAN_NOT_SEND_MESSAGE_TO_YOUR_OWN_SHOP');
            throw new BadRequestHttpException(is_string($message) ? $message : 'You cannot send a message to your own shop.');
        }

        // Cek apakah sudah ada
        $existing = Conversation::where('user_id', $userId)
            ->where('shop_id', $shopId)
            ->first();
        if ($existing) {
            return $existing;
        }

        return Conversation::create([
            'user_id' => $userId,
            'shop_id' => $shopId,
        ]);
    }
}
