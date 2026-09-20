<?php

declare(strict_types=1);

namespace App\Modules\Refund\Services;

use App\Enums\Permission;
use App\Enums\RefundStatus;
use App\Models\Balance;
use App\Models\Order;
use App\Models\Refund;
use App\Models\Shop;
use App\Models\User;
use App\Modules\Refund\DTO\RefundData;
use App\Modules\Shop\Events\CommissionRateUpdateEvent;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(private WalletService $walletService) {}

    /**
     * @return Builder<Refund>
     */
    public function getRefundsQuery(Request $request, User $user): Builder
    {
        $defaultLang = config('shop.default_language', 'id');
        $language = is_string($request->get('language')) ? $request->get('language') : (is_string($defaultLang) ? $defaultLang : 'id');
        $query = Refund::with(['order', 'shop', 'customer', 'refundPolicy', 'refundReason'])
            ->whereHas('order', fn ($q) => $q->where('language', $language));

        // Super Admin can see all refunds, optionally filtered by shop_id
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            if ($request->has('shop_id')) {
                return $query->where('shop_id', $request->get('shop_id'));
            }

            return $query; // All refunds
        }

        // Store Owner can see refunds for their shops
        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            $shopIds = $user->shops()->pluck('id')->toArray();

            return $query->whereIn('shop_id', $shopIds);
        }

        // Staff can see refunds for their assigned shop
        if ($user->hasPermissionTo(Permission::STAFF->value) && $user->shop_id) {
            return $query->where('shop_id', $user->shop_id);
        }

        // Regular customer can only see their own refunds
        return $query->where('customer_id', $user->id);
    }

    public function storeRefund(RefundData $data, User $user): Refund
    {
        $refundData = $data->withCustomerId((int) $user->id);

        /** @var Refund $refund */
        $refund = Refund::create($refundData->toArray());

        return $refund;
    }

    public function updateRefund(Refund $refund, RefundData $data, User $user): Refund
    {
        $refund->update($data->toArray());

        if ($refund->status === RefundStatus::APPROVED->value) {
            $this->processApprovedRefund($refund);
        }

        $fresh = $refund->fresh();

        return $fresh instanceof Refund ? $fresh : $refund;
    }

    protected function processApprovedRefund(Refund $refund): void
    {
        DB::transaction(function () use ($refund) {
            $order = Order::with('children')->find($refund->order_id);

            if (! $order) {
                return;
            }

            foreach ($order->children as $child) {
                Balance::where('shop_id', $child->shop_id)
                    ->decrement('total_earnings', $child->amount);
                $updatedBalance = Balance::where('shop_id', $child->shop_id);
                $updatedBalance->decrement('current_balance', $child->amount);

                $shop = Shop::find($child->shop_id);
                $balance = $updatedBalance->first();
                if ($shop instanceof Shop && $balance instanceof Balance) {
                    event(new CommissionRateUpdateEvent($shop, $balance));
                }
            }

            $walletPoints = $this->walletService->currencyToWalletPoints($refund->amount);

            if ($walletPoints > 0 && $refund->customer_id !== null) {
                $this->walletService->addPoints((int) $refund->customer_id, $walletPoints);
            }
        });
    }

    public function deleteRefund(Refund $refund, User $user): void
    {
        $refund->delete();
    }
}
