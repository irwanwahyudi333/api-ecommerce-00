<?php

declare(strict_types=1);

namespace App\Modules\Coupon\Services;

use App\Enums\CouponType;
use App\Enums\Permission;
use App\Models\Coupon;
use App\Models\Settings;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class CouponQueryService
{
    /**
     * @return Builder<Coupon>
     */
    public function getCouponsQuery(Request $request, ?Authenticatable $user): Builder
    {
        $language = $request->language ?? config('shop.default_language', 'id');
        $query = Coupon::with('shop')->where('language', $language);

        if ($user) {
            if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
                // admin bisa lihat semua
            } elseif ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
                $reqShopId = $request->input('shop_id');
                $shopId = is_numeric($reqShopId) ? (int) $reqShopId : 0;
                if ($shopId && $this->userHasPermissionToShop($user, $shopId)) {
                    $query->where('shop_id', $shopId);
                } else {
                    /** @var User $user */
                    $query->whereIn('shop_id', $user->shops()->pluck('id'));
                }
            } elseif ($user->hasPermissionTo(Permission::STAFF->value)) {
                /** @var User $user */
                $query->where('shop_id', (int) $user->shop_id);
                $reqShopId = $request->input('shop_id');
                $shopId = is_numeric($reqShopId) ? (int) $reqShopId : 0;
                if ($shopId && $shopId !== (int) $user->shop_id) {
                    $query->whereRaw('1 = 0'); // user has no access to other shops
                }
            } else {
                // customer: hanya lihat coupon yang approved
                $query->where('is_approve', true);
            }
        } else {
            // guest: hanya lihat coupon yang approved
            $query->where('is_approve', true);
        }

        return $query;
    }

    public function findCoupon(string $params, string $language): Coupon
    {
        if (is_numeric($params)) {
            return Coupon::where('id', (int) $params)->firstOrFail();
        }

        return Coupon::where('code', $params)->where('language', $language)->firstOrFail();
    }

    public function findOrFail(int $id): Coupon
    {
        return Coupon::findOrFail($id);
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $items
     * @return array{is_valid: bool, message?: string, coupon?: Coupon}
     */
    public function verifyCoupon(string $code, float $subTotal, ?array $items = null, ?Authenticatable $user = null): array
    {
        $coupon = Coupon::where('code', $code)->first();
        if (! $coupon) {
            $msg = config('notice.INVALID_COUPON_CODE');

            return ['is_valid' => false, 'message' => is_string($msg) ? $msg : ''];
        }

        $settings = Settings::getData();
        $isFreeShippingEnabled = $settings->options['freeShipping'] ?? false;
        $freeShippingAmount = $settings->options['freeShippingAmount'] ?? 0;
        $useFreeShipping = $isFreeShippingEnabled && $freeShippingAmount <= $subTotal;

        if (! $coupon->is_approve) {
            $msg = config('notice.THIS_COUPON_CODE_IS_NOT_APPROVED');

            return ['is_valid' => false, 'message' => is_string($msg) ? $msg : ''];
        }

        if ($coupon->target && ! $user) {
            $msg = config('notice.THIS_COUPON_CODE_IS_ONLY_FOR_VERIFIED_USERS');

            return ['is_valid' => false, 'message' => is_string($msg) ? $msg : ''];
        }

        if ($subTotal < $coupon->minimum_cart_amount) {
            $msg = config('notice.COUPON_CODE_IS_NOT_APPLICABLE');

            return ['is_valid' => false, 'message' => is_string($msg) ? $msg : ''];
        }

        if ($coupon->type === CouponType::FREE_SHIPPING_COUPON->value && $useFreeShipping) {
            $msg = config('notice.ALREADY_FREE_SHIPPING_ACTIVATED');

            return ['is_valid' => false, 'message' => is_string($msg) ? $msg : ''];
        }

        // Shop-specific validation
        if ($coupon->shop_id && $items) {
            $totalForShop = 0;
            foreach ($items as $item) {
                if (($item['shop_id'] ?? null) == $coupon->shop_id) {
                    $p = $item['price'] ?? $item['unit_price'] ?? 0;
                    $price = is_numeric($p) ? (float) $p : 0.0;
                    $q = $item['quantity'] ?? $item['order_quantity'] ?? 1;
                    $quantity = is_numeric($q) ? (float) $q : 1.0;
                    $totalForShop += $price * $quantity;
                }
            }

            $isValidForShop = $totalForShop >= $coupon->minimum_cart_amount;
            if ($coupon->type === CouponType::FIXED_COUPON->value) {
                $isValidForShop = $isValidForShop && $totalForShop > $coupon->amount;
            } elseif ($coupon->type === CouponType::PERCENTAGE_COUPON->value) {
                $discountAmount = ($totalForShop * $coupon->amount) / 100;
                $isValidForShop = $isValidForShop && $totalForShop > $discountAmount;
            }
            if (! $isValidForShop) {
                $msg = config('notice.COUPON_CODE_IS_NOT_APPLICABLE_IN_THIS_SHOP_PRODUCT');

                return ['is_valid' => false, 'message' => is_string($msg) ? $msg : ''];
            }
        }

        if (! $coupon->is_valid) {
            $msg = config('notice.INVALID_COUPON_CODE');

            return ['is_valid' => false, 'message' => is_string($msg) ? $msg : ''];
        }

        return ['is_valid' => true, 'coupon' => $coupon];
    }

    private function userHasPermissionToShop(?Authenticatable $user, int $shopId): bool
    {
        if (! $user) {
            return false;
        }
        $shop = Shop::find($shopId);
        if (! $shop) {
            return false;
        }

        /** @var User $user */
        return $shop->owner_id === $user->id;
    }
}
