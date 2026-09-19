<?php

declare(strict_types=1);

namespace App\Modules\OwnershipTransfer\Services;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Models\Balance;
use App\Models\Order;
use App\Models\OwnershipTransfer;
use App\Models\Shop;
use App\Models\Withdraw;
use App\Modules\OwnershipTransfer\DTO\OwnershipTransferData;
use App\Modules\Shop\Events\OwnershipTransferStatusControl;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnershipTransferService
{
    /**
     * Check if user has permission for ownership transfer operations.
     */
    public function hasPermission(?Authenticatable $user, ?int $shopId = null): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($shopId) {
            $shop = Shop::find($shopId);

            return $shop && $shop->owner_id === $user->getAuthIdentifier();
        }

        return false;
    }

    /**
     * Get transfer histories query based on user role.
     *
     * @return Builder<OwnershipTransfer>
     */
    public function getTransferHistoriesQuery(Request $request, Authenticatable $user)
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return OwnershipTransfer::query();
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            if ($request->type === 'from') {
                return OwnershipTransfer::where('from', $user->getAuthIdentifier());
            }

            return OwnershipTransfer::where('to', $user->getAuthIdentifier());
        }

        abort(403, is_string($msg = config('notice.NOT_AUTHORIZED')) ? $msg : 'Forbidden');
    }

    /**
     * Get transfer detail with additional info.
     */
    public function getTransferDetail(string $transactionId, ?string $viewType = null): OwnershipTransfer
    {
        $transfer = OwnershipTransfer::with('shop')
            ->where('transaction_identifier', $transactionId)
            ->firstOrFail();

        if ($viewType === 'detail') {
            $transfer->setRelation('order_info', $this->getOrderInfo($transfer->shop_id));
            $transfer->setRelation('balance_info', $this->getBalanceInfo($transfer->shop_id));
            $transfer->setRelation('refund_info', $this->getRefundInfo($transfer->shop_id));
            $transfer->setRelation('withdrawal_info', $this->getWithdrawInfo($transfer->shop_id));
        }

        return $transfer;
    }

    /**
     * Create a transfer request.
     */
    public function createTransfer(OwnershipTransferData $data): OwnershipTransfer
    {
        /** @var OwnershipTransfer $transfer */
        $transfer = OwnershipTransfer::create($data->toArray());

        return $transfer;
    }

    /**
     * Update transfer status (approve/reject).
     *
     * @throws \Exception
     */
    public function updateTransferStatus(int $id, string $status, Authenticatable $user): OwnershipTransfer
    {
        if (! $user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            throw new \Exception(is_string($msg = config('notice.NOT_AUTHORIZED')) ? $msg : 'Not authorized');
        }

        $transfer = OwnershipTransfer::findOrFail($id);
        $shop = $transfer->shop;

        if ($status === 'approved') {
            /** @var Shop $shop */
            $shop = $transfer->shop;
            $this->validateTransferConditions($shop);
        }

        $transfer->status = $status;
        $transfer->save();

        event(new OwnershipTransferStatusControl($transfer));

        /** @var OwnershipTransfer $fresh */
        $fresh = $transfer->fresh();

        return $fresh;
    }

    /**
     * Validate if shop is eligible for ownership transfer.
     *
     * @throws \Exception
     */
    protected function validateTransferConditions(Shop $shop): void
    {
        $incompleteOrders = Order::where('shop_id', $shop->id)
            ->whereIn('order_status', [
                OrderStatus::PENDING->value,
                OrderStatus::PROCESSING->value,
                OrderStatus::AT_LOCAL_FACILITY->value,
                OrderStatus::OUT_FOR_DELIVERY->value,
            ])->count();

        /** @var Balance|null $balance */
        $balance = $shop->balance;
        $currentBalance = $balance ? $balance->current_balance : 0;

        /** @var Collection<int, Withdraw> $withdraws */
        $withdraws = $shop->withdraws;
        $pendingWithdrawals = $withdraws->filter(fn ($w) => $w->status !== 'approved')->count();

        if ($incompleteOrders > 0 || $currentBalance > 1.00 || $pendingWithdrawals > 0) {
            throw new \Exception(is_string($msg = config('notice.COULD_NOT_SETTLE_THE_TRANSITION')) ? $msg : 'Could not settle transition');
        }
    }

    /**
     * Delete a transfer request.
     *
     * @throws \Exception
     */
    public function deleteTransfer(int $id, Authenticatable $user): void
    {
        $transfer = OwnershipTransfer::findOrFail($id);

        if (! $this->hasPermission($user, $transfer->shop_id)) {
            throw new \Exception(is_string($msg = config('notice.NOT_AUTHORIZED')) ? $msg : 'Not authorized');
        }

        $transfer->delete();
    }

    /**
     * Get order info for a shop.
     *
     * @return array<string, mixed>
     */
    protected function getOrderInfo(int $shopId): array
    {
        $query = DB::table('orders')
            ->whereNotNull('parent_id')
            ->whereDate('created_at', '<=', Carbon::now())
            ->where('shop_id', $shopId)
            ->select('order_status', DB::raw('count(*) as order_count'))
            ->groupBy('order_status')
            ->pluck('order_count', 'order_status')
            ->toArray();

        return [
            'pending' => $query[OrderStatus::PENDING->value] ?? 0,
            'processing' => $query[OrderStatus::PROCESSING->value] ?? 0,
            'complete' => $query[OrderStatus::COMPLETED->value] ?? 0,
            'cancelled' => $query[OrderStatus::CANCELLED->value] ?? 0,
            'refunded' => $query[OrderStatus::REFUNDED->value] ?? 0,
            'failed' => $query[OrderStatus::FAILED->value] ?? 0,
            'localFacility' => $query[OrderStatus::AT_LOCAL_FACILITY->value] ?? 0,
            'outForDelivery' => $query[OrderStatus::OUT_FOR_DELIVERY->value] ?? 0,
        ];
    }

    /**
     * Get balance info for a shop.
     */
    protected function getBalanceInfo(int $shopId): ?object
    {
        return DB::table('balances')->where('shop_id', $shopId)->first();
    }

    /**
     * Get refund info for a shop.
     *
     * @return array<mixed>
     */
    protected function getRefundInfo(int $shopId): array
    {
        return DB::table('refunds')->where('shop_id', $shopId)->get()->toArray();
    }

    /**
     * Get withdrawal info for a shop.
     *
     * @return array<mixed>
     */
    protected function getWithdrawInfo(int $shopId): array
    {
        return DB::table('withdraws')->where('shop_id', $shopId)->get()->toArray();
    }
}
