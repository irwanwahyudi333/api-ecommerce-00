<?php

declare(strict_types=1);

namespace App\Modules\Coupon\Http\Resources;

use App\Models\Coupon; // Use for type hinting if necessary
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array{
 *     is_valid: bool,
 *     message?: string,
 *     coupon?: Coupon,
 * } $resource
 */
final class CouponVerifyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $coupon = $this->resource['coupon'] ?? null;

        return [
            'is_valid' => $this->resource['is_valid'],
            'message' => $this->resource['message'] ?? null,
            'coupon' => $this->when($coupon !== null, new CouponResource($coupon)),
        ];
    }
}
