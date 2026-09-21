<?php

declare(strict_types=1);

namespace App\Modules\Withdraw\Services;

use App\Enums\Permission;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class WithdrawQueryService
{
    /**
     * Query for listing withdraws based on request and user permissions.
     *
     * @return Builder<Withdraw>
     */
    public function getWithdrawsQuery(Request $request, User $user): Builder
    {
        $query = Withdraw::with('shop');
        $shopId = $request->shop_id;
        $shopId = is_numeric($shopId) ? (int) $shopId : null;

        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            if ($shopId) {
                $query->where('shop_id', $shopId);
            }

            return $query;
        }

        // Non-admin: only for owned shops
        // The policy should prevent access if not authorized
        return $query->where('shop_id', (int) $shopId);
    }

    /**
     * Find single withdraw with permission check.
     */
    public function findWithdraw(int $id, User $user): Withdraw
    {
        $withdraw = Withdraw::with('shop')->findOrFail($id);

        // The policy will handle authorization in the controller
        return $withdraw;
    }

    public function findOrFail(int $id): Withdraw
    {
        return Withdraw::findOrFail($id);
    }
}
