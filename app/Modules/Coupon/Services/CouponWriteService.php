<?php

declare(strict_types=1);

namespace App\Modules\Coupon\Services;

use App\Models\Coupon;
use App\Modules\Coupon\Actions\ApproveCouponAction;
use App\Modules\Coupon\Actions\CreateCouponAction;
use App\Modules\Coupon\Actions\DisapproveCouponAction;
use App\Modules\Coupon\Actions\UpdateCouponAction;
use App\Modules\Coupon\DTO\CouponData;

final class CouponWriteService
{
    public function __construct(
        private readonly CreateCouponAction $createCouponAction,
        private readonly UpdateCouponAction $updateCouponAction,
        private readonly ApproveCouponAction $approveCouponAction,
        private readonly DisapproveCouponAction $disapproveCouponAction,
    ) {}

    public function createCoupon(CouponData $data, bool $isSuperAdmin): Coupon
    {
        // override is_approve sesuai role
        $data = new CouponData(
            code: $data->code,
            language: $data->language,
            description: $data->description,
            image: $data->image,
            type: $data->type,
            amount: $data->amount,
            minimum_cart_amount: $data->minimum_cart_amount,
            active_from: $data->active_from,
            expire_at: $data->expire_at,
            target: $data->target,
            is_approve: $isSuperAdmin,
            user_id: $data->user_id,
            shop_id: $data->shop_id,
            // Assuming all properties are passed, ensure no data loss during re-creation of DTO
        );

        return $this->createCouponAction->execute($data);
    }

    public function updateCoupon(Coupon $coupon, CouponData $data, bool $isSuperAdmin): Coupon
    {
        if (! $isSuperAdmin) {
            // Non-admin update akan mereset is_approve menjadi false
            $data = new CouponData(
                code: $data->code,
                language: $data->language,
                description: $data->description,
                image: $data->image,
                type: $data->type,
                amount: $data->amount,
                minimum_cart_amount: $data->minimum_cart_amount,
                active_from: $data->active_from,
                expire_at: $data->expire_at,
                target: $data->target,
                is_approve: false,
                user_id: $data->user_id,
                shop_id: $data->shop_id,
                // Assuming all properties are passed, ensure no data loss during re-creation of DTO
            );
        }

        return $this->updateCouponAction->execute($coupon, $data);
    }

    public function deleteCoupon(Coupon $coupon): bool
    {
        return (bool) $coupon->delete();
    }

    public function approveCoupon(Coupon $coupon): Coupon
    {
        return $this->approveCouponAction->execute($coupon);
    }

    public function disapproveCoupon(Coupon $coupon): Coupon
    {
        return $this->disapproveCouponAction->execute($coupon);
    }
}
