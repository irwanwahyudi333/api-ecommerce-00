<?php

declare(strict_types=1);

namespace App\Modules\BecameSeller\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\BecameSeller;
use App\Modules\BecameSeller\DTO\BecameSellerData;
use App\Modules\BecameSeller\Http\Requests\BecameSellersRequest;
use App\Modules\BecameSeller\Services\BecameSellerService;
use App\Modules\BecameSeller\Services\CommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BecameSellerController extends BaseController
{
    public function __construct(
        private BecameSellerService $becameSellerService,
        private CommissionService $commissionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $lang = $request->language ?? config('shop.default_language', 'id');
        $language = is_scalar($lang) ? (string) $lang : 'id';
        $cacheKey = 'cached_became_seller_'.$language;
        $data = Cache::rememberForever($cacheKey, function () use ($language) {
            return [
                'page_options' => $this->becameSellerService->getData($language),
                'commissions' => $this->commissionService->getAll(),
            ];
        });

        return $this->sendSuccess($data, 'Became seller data');
    }

    public function store(BecameSellersRequest $request): JsonResponse
    {
        $this->authorize('create', BecameSeller::class);
        $lang = $request->language ?? config('shop.default_language', 'id');
        $language = is_scalar($lang) ? (string) $lang : 'id';
        $cacheKey = 'cached_became_seller_'.$language;
        Cache::forget($cacheKey);

        if ($request->has('commissions')) {
            /** @var array<int, array<string, mixed>> $commissions */
            $commissions = $request->commissions;
            $this->commissionService->storeCommissions($commissions, $language);
        }

        /** @var array<string, mixed> $reqData */
        $reqData = $request->only(['page_options', 'language']);
        $data = BecameSellerData::fromRequest($reqData);
        $becomeSeller = $this->becameSellerService->storeOrUpdate($data);

        return $this->sendSuccess($becomeSeller, 'Became seller data saved', 201);
    }

    public function show(int|string $id): JsonResponse
    {
        $settings = $this->becameSellerService->getFirst();
        if (! $settings) {
            return $this->sendError('Settings not found', 404);
        }

        return $this->sendSuccess($settings, 'Became seller detail');
    }

    public function update(BecameSellersRequest $request, int|string $id): JsonResponse
    {
        $this->authorize('update', BecameSeller::class);
        $lang = $request->language ?? config('shop.default_language', 'id');
        $language = is_scalar($lang) ? (string) $lang : 'id';

        /** @var array<string, mixed> $reqData */
        $reqData = $request->only(['page_options', 'language']);
        $data = BecameSellerData::fromRequest($reqData);
        $updated = $this->becameSellerService->storeOrUpdate($data);
        Cache::forget('cached_became_seller_'.$language);

        return $this->sendSuccess($updated, 'Became seller data updated');
    }

    public function destroy(int|string $id): JsonResponse
    {
        $msg = config('notice.ACTION_NOT_VALID');
        throw new \Exception(is_scalar($msg) ? (string) $msg : 'Action not valid');
    }
}
