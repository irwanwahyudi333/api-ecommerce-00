<?php

declare(strict_types=1);

namespace App\Modules\Author\Services;

use App\Models\Author;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final class AuthorQueryService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    /**
     * @return LengthAwarePaginator<int, Author>
     */
    public function getAuthorsByLanguage(string $language, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = "authors_{$language}_{$perPage}";

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($language, $perPage) {
                return Author::query()
                    ->where('language', $language)
                    ->withCount('products')
                    ->paginate($perPage);
            }
        );
    }

    public function getAuthorBySlug(string $slug, string $language): Author
    {
        return Author::where('slug', $slug)
            ->where('language', $language)
            ->firstOrFail();
    }

    public function findOrFail(int $id): Author
    {
        return Author::findOrFail($id);
    }

    /**
     * @return Collection<int, Author>
     */
    public function getTopAuthors(string $language, int $limit = 10): Collection
    {
        $cacheKey = "top_authors_{$language}_{$limit}";

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($language, $limit) {
                return Author::query()
                    ->where('language', $language)
                    ->withCount('products')
                    ->orderByDesc('products_count')
                    ->limit($limit)
                    ->get();
            }
        );
    }
}
