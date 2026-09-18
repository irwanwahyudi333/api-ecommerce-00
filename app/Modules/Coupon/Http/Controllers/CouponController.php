<?php

declare(strict_types=1);

namespace App\Modules\Coupon\Http\Controllers;

use App\Enums\Permission;
use App\Http\Controllers\BaseController;
use App\Models\Coupon;
use App\Modules\Coupon\DTO\CouponData;
use App\Modules\Coupon\Http\Requests\CouponCreateRequest;
use App\Modules\Coupon\Http\Requests\CouponUpdateRequest;
use App\Modules\Coupon\Http\Resources\CouponResource;
use App\Modules\Coupon\Services\CouponQueryService;
use App\Modules\Coupon\Services\CouponWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CouponController extends BaseController
{
    public function __construct(
        private readonly CouponQueryService $couponQueryService,
        private readonly CouponWriteService $couponWriteService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $limitInput = $request->input('limit', 15);
        $limit = is_numeric($limitInput) ? (int) $limitInput : 15;
        $query = $this->couponQueryService->getCouponsQuery($request, $request->user());
        $coupons = $query->paginate($limit);

        return CouponResource::collection($coupons);
    }

    public function store(CouponCreateRequest $request): CouponResource
    {
        $user = $request->user();
        $shopId = $request->shop_id;
        $this->authorize('create', [Coupon::class, $shopId]);

        $isSuperAdmin = $user && $user->hasPermissionTo(Permission::SUPER_ADMIN->value);
        $data = CouponData::fromRequest($request->validated(), $user?->id);
        $coupon = $this->couponWriteService->createCoupon($data, $isSuperAdmin);

        return new CouponResource($coupon);
    }

    public function show(Request $request, string $params): CouponResource
    {
        $langInput = $request->input('language', config('shop.default_language', 'id'));
        $language = is_string($langInput) ? $langInput : 'id';
        $coupon = $this->couponQueryService->findCoupon($params, $language);
        $this->authorize('view', $coupon);

        return new CouponResource($coupon);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'sub_total' => 'required|numeric',
        ]);

        $codeInput = $request->input('code');
        $subTotalInput = $request->input('sub_total');
        $itemInput = $request->input('item');

        /** @var array<int, array<string, mixed>>|null $items */
        $items = is_array($itemInput) ? $itemInput : null;

        $result = $this->couponQueryService->verifyCoupon(
            is_string($codeInput) ? $codeInput : '',
            is_numeric($subTotalInput) ? (float) $subTotalInput : 0.0,
            $items,
            $request->user()
        );

        return response()->json($result);
    }

    public function update(CouponUpdateRequest $request, int $id): CouponResource
    {
        $coupon = Coupon::findOrFail($id);
        $this->authorize('update', $coupon);

        $user = $request->user();
        $isSuperAdmin = $user && $user->hasPermissionTo(Permission::SUPER_ADMIN->value);
        $data = CouponData::fromRequest($request->validated(), $user?->id);
        $updated = $this->couponWriteService->updateCoupon($coupon, $data, $isSuperAdmin);

        return new CouponResource($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $this->authorize('delete', $coupon);
        $this->couponWriteService->deleteCoupon($coupon);

        return $this->sendSuccess(null, 'Coupon deleted successfully');
    }

    public function approveCoupon(Request $request): CouponResource
    {
        $this->authorize('approve', Coupon::class);

        $request->validate(['id' => 'required|exists:coupons,id']);
        /** @var Coupon $coupon */
        $coupon = Coupon::where('id', $request->input('id'))->firstOrFail();
        $this->couponWriteService->approveCoupon($coupon);

        return new CouponResource($coupon);
    }

    public function disApproveCoupon(Request $request): CouponResource
    {
        $this->authorize('disapprove', Coupon::class);

        $request->validate(['id' => 'required|exists:coupons,id']);
        /** @var Coupon $coupon */
        $coupon = Coupon::where('id', $request->input('id'))->firstOrFail();
        $this->couponWriteService->disapproveCoupon($coupon);

        return new CouponResource($coupon);
    }
}
