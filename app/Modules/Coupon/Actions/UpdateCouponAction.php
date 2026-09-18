<?php

declare(strict_types=1);

namespace App\Modules\Coupon\Actions;

use App\Models\Coupon;
use App\Modules\Coupon\DTO\CouponData;

class UpdateCouponAction
{
    public function execute(Coupon $coupon, CouponData $data): Coupon
    {
        $coupon->update($data->toArray());

        return clone $coupon->refresh();
    }
}
