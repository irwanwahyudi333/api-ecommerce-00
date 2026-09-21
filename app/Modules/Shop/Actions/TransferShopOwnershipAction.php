<?php

declare(strict_types=1);

namespace App\Modules\Shop\Actions;

use App\Enums\DefaultStatusType;
use App\Models\OwnershipTransfer;
use App\Models\Shop;
use App\Models\User;
use App\Modules\Shop\Events\ProcessOwnershipTransition;

final class TransferShopOwnershipAction
{
    public function execute(
        Shop $shop,
        User $newOwner,
        User $initiator,
        ?string $message,
        ?string $vendorMessage,
    ): void {
        OwnershipTransfer::updateOrCreate(
            ['shop_id' => $shop->id],
            [
                'from' => $shop->owner_id,
                'to' => $newOwner->id,
                'message' => $message,
                'created_by' => $initiator->id,
                'status' => DefaultStatusType::PENDING,
            ]
        );

        $owner = $shop->owner;
        if (! $owner instanceof User) {
            throw new \RuntimeException('Shop does not have a valid owner.');
        }

        // Event listener disarankan implement ShouldQueue karena proses ini
        // kemungkinan mengirim notifikasi/email ke vendor baru (I/O eksternal).
        event(new ProcessOwnershipTransition(
            $shop,
            $owner,
            $newOwner,
            ['message' => $vendorMessage]
        ));
    }
}
