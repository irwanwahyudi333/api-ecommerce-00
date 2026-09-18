<?php

declare(strict_types=1);

namespace App\Modules\Category\Services;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final class CategoryQueryService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    /**
     * @return LengthAwarePaginator<int, Category>
     */
    public function getCategories(string $language, ?string $parent = null, ?int $selfId = null, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = "categories_{$language}_{$parent}_{$selfId}_{$perPage}";

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($language, $parent, $selfId, $perPage) {
                $query = Category::with(['type', 'parentCategory', 'children'])
                    ->where('language', $language)
                    ->withCount('products');

                if ($parent === 'null') {
                    $query->whereNull('parent');
                }
                if ($selfId) {
                    $query->where('id', '!=', $selfId);
                }

                return $query->paginate($perPage);
            }
        );
    }

    public function getCategoryByIdOrSlug(string $param, string $language): Category
    {
        $cacheKey = "category_{$param}_{$language}";

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($param, $language) {
                $query = Category::with(['type', 'parentCategory', 'children'])
                    ->where('language', $language);

                if (is_numeric($param)) {
                    return $query
                        ->where('id', (int) $param)
                        ->firstOrFail();
                }

                return $query
                    ->where('slug', $param)
                    ->firstOrFail();
            }
        );
    }

    public function findOrFail(int $id): Category
    {
        return Category::findOrFail($id);
    }

    /**
     * @return LengthAwarePaginator<int, Category>
     */
    public function fetchFeaturedCategories(int $perPage = 3): LengthAwarePaginator
    {
        $cacheKey = "featured_categories_{$perPage}";

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($perPage) {
                return Category::with('products')->paginate($perPage);
            }
        );
    }
}
