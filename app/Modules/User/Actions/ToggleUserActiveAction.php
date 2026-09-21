<?php

declare(strict_types=1);

namespace App\Modules\User\Actions;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ToggleUserActiveAction
{
    public function execute(User $target, bool $active): User
    {
        return DB::transaction(function () use ($target, $active): User {
            $target->update(['is_active' => $active]);

            if (! $active) {
                $shopIds = Shop::where('owner_id', $target->id)->pluck('id');
                Shop::whereIn('id', $shopIds)->update(['is_active' => false]);
                Product::whereIn('shop_id', $shopIds)->update(['status' => 'draft']);
            }

            $freshUser = $target->fresh();
            assert($freshUser instanceof User);

            return $freshUser;
        });
    }
}
