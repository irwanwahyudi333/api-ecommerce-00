<?php

declare(strict_types=1);

namespace App\Modules\RefundReason\Services;

use App\Models\RefundReason;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final class RefundReasonQueryService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    /**
     * @return LengthAwarePaginator<int, RefundReason>
     */
    public function getRefundReasons(string $language, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = "refund_reasons_{$language}_{$perPage}";

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($language, $perPage) {
                return RefundReason::where('language', $language)->paginate($perPage);
            }
        );
    }

    public function find(string $params, string $language): RefundReason
    {
        if (is_numeric($params)) {
            return RefundReason::where('id', (int) $params)->firstOrFail();
        }

        return RefundReason::where('slug', $params)->where('language', $language)->firstOrFail();
    }

    public function findOrFail(int $id): RefundReason
    {
        return RefundReason::findOrFail($id);
    }
}
