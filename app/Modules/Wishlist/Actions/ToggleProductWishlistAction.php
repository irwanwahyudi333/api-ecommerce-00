<?php

declare(strict_types=1);

namespace App\Modules\Wishlist\Actions;

use App\Models\User;
use App\Models\Wishlist;
use App\Modules\Wishlist\DTO\WishlistData;

final class ToggleProductWishlistAction
{
    private readonly RemoveProductFromWishlistAction $removeAction;

    private readonly AddProductToWishlistAction $addAction;

    public function __construct(
        RemoveProductFromWishlistAction $removeAction,
        AddProductToWishlistAction $addAction
    ) {
        $this->removeAction = $removeAction;
        $this->addAction = $addAction;
    }

    /**
     * Toggle wishlist: add if not present, remove if present.
     *
     * @return array{toggled: bool, added: bool, removed: bool}
     */
    public function execute(WishlistData $data): array
    {
        $exists = Wishlist::where('user_id', $data->user_id)
            ->where('product_id', $data->product_id)
            ->exists();

        if ($exists) {
            $user = User::find($data->user_id);
            if (! $user) {
                throw new \InvalidArgumentException('User not found');
            }
            $this->removeAction->execute($user, $data->product_id);

            return ['toggled' => true, 'added' => false, 'removed' => true];
        } else {
            $wishlist = $this->addAction->execute($data);

            return ['toggled' => true, 'added' => true, 'removed' => false];
        }
    }
}
