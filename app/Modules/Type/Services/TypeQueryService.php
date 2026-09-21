<?php

declare(strict_types=1);

namespace App\Modules\Type\Services;

use App\Models\Type;
use Illuminate\Support\Facades\Cache;

final class TypeQueryService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    /**
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Type>
     */
    public function getTypesByLanguage(string $language, int $limit): \Illuminate\Pagination\LengthAwarePaginator
    {
        $cacheKey = "types_{$language}_{$limit}";

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($language, $limit) {
                return Type::where('language', $language)->paginate($limit);
            }
        );
    }

    public function getTypeByIdOrSlug(string $identifier, string $language): Type
    {
        if (is_numeric($identifier)) {
            return Type::with('banners')->findOrFail((int) $identifier);
        }

        return Type::with('banners')
            ->where('slug', $identifier)
            ->where('language', $language)
            ->firstOrFail();
    }

    public function findOrFail(int $id): Type
    {
        return Type::findOrFail($id);
    }
}
