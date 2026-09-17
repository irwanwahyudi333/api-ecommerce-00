<?php

declare(strict_types=1);

namespace App\Modules\Attribute\Services;

use App\Models\Attribute;
use App\Models\User;
use App\Modules\Attribute\Actions\ExportAttributesAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final class AttributeQueryService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private readonly ExportAttributesAction $exportAttributesAction
    ) {}

    /**
     * @return Collection<int, Attribute>
     */
    public function getAttributesByLanguage(string $language): Collection
    {
        $cacheKey = "attributes_{$language}";

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($language) {
                return Attribute::where('language', $language)->with(['values', 'shop'])->get();
            }
        );
    }

    public function getAttributeByIdOrSlug(string $identifier, string $language): Attribute
    {
        if (is_numeric($identifier)) {
            return Attribute::with('values')->where('id', (int) $identifier)->firstOrFail();
        }

        return Attribute::with('values')->where('slug', $identifier)->where('language', $language)->firstOrFail();
    }

    public function findOrFail(int $id): Attribute
    {
        return Attribute::findOrFail($id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportAttributes(int $shopId, User $user): array
    {
        return $this->exportAttributesAction->execute($shopId, $user);
    }
}
