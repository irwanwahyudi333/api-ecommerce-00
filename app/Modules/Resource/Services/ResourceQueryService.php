<?php

declare(strict_types=1);

namespace App\Modules\Resource\Services;

use App\Models\Resource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final class ResourceQueryService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    /**
     * @return LengthAwarePaginator<int, resource>
     */
    public function getResources(string $language, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = "resources_{$language}_{$perPage}";

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($language, $perPage) {
                return Resource::where('language', $language)->paginate($perPage);
            }
        );
    }

    public function find(string $params, string $language): Resource
    {
        if (is_numeric($params)) {
            return Resource::where('id', (int) $params)->firstOrFail();
        }

        return Resource::where('slug', $params)->where('language', $language)->firstOrFail();
    }

    public function findOrFail(int $id): Resource
    {
        return Resource::findOrFail($id);
    }
}
