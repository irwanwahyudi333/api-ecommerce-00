<?php

declare(strict_types=1);

namespace App\Modules\Shop\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Shop\Http\Resources\ShopResource;
use App\Modules\Shop\Services\ShopQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ShopQueryController extends BaseController
{
    public function __construct(private readonly ShopQueryService $shopQueryService) {}

    public function search(Request $request): AnonymousResourceCollection
    {
        $q = $request->get('q', '');
        $query = is_string($q) ? $q : '';
        $limitInput = $request->get('limit', 15);
        $limit = is_numeric($limitInput) ? (int) $limitInput : 15;

        $shops = $this->shopQueryService->search($query, $limit, $request->all());

        return ShopResource::collection($shops);
    }
}
