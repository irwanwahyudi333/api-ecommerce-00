<?php

declare(strict_types=1);

namespace App\Modules\Product\DTO;

use Illuminate\Http\Request;

class ProductQueryData
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $typeId = null,
        public readonly ?int $shopId = null,
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null,
        public readonly ?bool $isRental = null,
        public readonly ?bool $isDigital = null,
        public readonly ?string $sortBy = 'created_at',
        public readonly ?string $sortOrder = 'desc',
        public readonly ?int $limit = 15,
        public readonly ?int $page = 1,
        public readonly ?string $language = 'id'
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            search: is_string($request->get('search')) ? $request->get('search') : null,
            status: is_string($request->get('status')) ? $request->get('status') : null,
            categoryId: $request->filled('category_id') && is_numeric($request->get('category_id')) ? (int) $request->get('category_id') : null,
            typeId: $request->filled('type_id') && is_numeric($request->get('type_id')) ? (int) $request->get('type_id') : null,
            shopId: $request->filled('shop_id') && is_numeric($request->get('shop_id')) ? (int) $request->get('shop_id') : null,
            minPrice: $request->filled('min_price') && is_numeric($request->get('min_price')) ? (float) $request->get('min_price') : null,
            maxPrice: $request->filled('max_price') && is_numeric($request->get('max_price')) ? (float) $request->get('max_price') : null,
            isRental: $request->filled('is_rental') ? filter_var($request->get('is_rental'), FILTER_VALIDATE_BOOLEAN) : null,
            isDigital: $request->filled('is_digital') ? filter_var($request->get('is_digital'), FILTER_VALIDATE_BOOLEAN) : null,
            sortBy: is_string($request->get('sort_by')) ? $request->get('sort_by') : 'created_at',
            sortOrder: is_string($request->get('sort_order')) ? $request->get('sort_order') : 'desc',
            limit: is_numeric($request->get('limit')) ? (int) $request->get('limit') : 15,
            page: is_numeric($request->get('page')) ? (int) $request->get('page') : 1,
            language: is_string($request->get('language')) ? $request->get('language') : 'id'
        );
    }

    public function hasFilters(): bool
    {
        return ! empty(array_filter([
            $this->search,
            $this->status,
            $this->categoryId,
            $this->typeId,
            $this->shopId,
            $this->minPrice,
            $this->maxPrice,
            $this->isRental,
            $this->isDigital,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->status,
            'category_id' => $this->categoryId,
            'type_id' => $this->typeId,
            'shop_id' => $this->shopId,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'is_rental' => $this->isRental,
            'is_digital' => $this->isDigital,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
            'limit' => $this->limit,
            'page' => $this->page,
            'language' => $this->language,
        ];
    }
}
