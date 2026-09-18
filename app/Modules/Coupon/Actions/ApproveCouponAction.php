<?php

declare(strict_types=1);

namespace App\Modules\Coupon\Actions;

use App\Models\Coupon;
use Illuminate\Support\Facades\Cache;

final class ApproveCouponAction
{
    public function execute(Coupon $coupon): Coupon
    {
        $coupon->is_approve = true;
        $coupon->save();

        Cache::forget("coupons_{$coupon->language}_*"); // Invalidate cache

        return clone $coupon->refresh();
    }
}
