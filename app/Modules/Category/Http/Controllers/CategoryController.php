<?php

declare(strict_types=1);

namespace App\Modules\Category\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Category;
use App\Modules\Category\DTO\CategoryData;
use App\Modules\Category\Http\Requests\CategoryCreateRequest;
use App\Modules\Category\Http\Requests\CategoryUpdateRequest;
use App\Modules\Category\Http\Resources\CategoryResource;
use App\Modules\Category\Services\CategoryQueryService;
use App\Modules\Category\Services\CategoryWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends BaseController
{
    public function __construct(
        private readonly CategoryQueryService $categoryQueryService,
        private readonly CategoryWriteService $categoryWriteService
    ) {}

    public function index(Request $request): JsonResponse
    {

        $defaultLang = is_string(config('shop.default_language')) ? config('shop.default_language') : 'id';
        $language = is_string($request->language) ? $request->language : (string) $defaultLang;
        $parent = is_string($request->parent) || is_numeric($request->parent) ? (string) $request->parent : null;
        $selfId = is_numeric($request->self) ? (int) $request->self : null;
        $limit = is_numeric($request->limit) ? (int) $request->limit : 15;

        $parentStr = $parent ?? 'null';
        $selfIdStr = $selfId ?? 'null';
        $cacheKey = "categories_{$language}_{$parentStr}_{$selfIdStr}_{$limit}";
        $categories = Cache::remember($cacheKey, 3600, function () use ($language, $parent, $selfId, $limit) {
            return $this->categoryQueryService->getCategories($language, $parent, $selfId, $limit);
        });

        return $this->sendPaginated(
            $categories,
            CategoryResource::collection($categories->getCollection()),
            'Daftar category berhasil diambil.'
        );
    }

    public function store(CategoryCreateRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $data = CategoryData::fromRequest($request->validated());
        $category = $this->categoryWriteService->createCategory($data);

        $this->clearCategoryCache($data->language);

        return $this->sendSuccess(new CategoryResource($category), 'Category created', 201);
    }

    public function show(Request $request, string $params): JsonResponse
    {
        $defaultLang = is_string(config('shop.default_language')) ? config('shop.default_language') : 'id';
        $language = is_string($request->language) ? $request->language : (string) $defaultLang;

        $cacheKey = "category_{$params}_{$language}";
        $category = Cache::remember($cacheKey, 3600, function () use ($params, $language) {
            return $this->categoryQueryService->getCategoryByIdOrSlug($params, $language);
        });

        return $this->sendSuccess(new CategoryResource($category), 'Category detail');
    }

    public function update(CategoryUpdateRequest $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $this->authorize('update', $category);

        $data = CategoryData::fromRequest($request->validated());
        $updated = $this->categoryWriteService->updateCategory($category, $data);

        $this->clearCategoryCache($data->language ?? $category->language);

        return $this->sendSuccess(new CategoryResource($updated), 'Category updated');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $this->authorize('delete', $category);

        $language = $category->language;
        $this->categoryWriteService->deleteCategory($category);

        $this->clearCategoryCache($language);

        return $this->sendSuccess(null, 'Category deleted');
    }

    public function fetchFeaturedCategories(Request $request): JsonResponse
    {
        $perPage = is_numeric($request->limit) ? (int) $request->limit : 3;
        $cacheKey = "featured_categories_{$perPage}";
        $categories = Cache::remember($cacheKey, 3600, function () use ($perPage) {
            return $this->categoryQueryService->fetchFeaturedCategories($perPage);
        });

        return $this->sendSuccess($categories, 'Featured categories');
    }

    /**
     * Helper untuk menghapus cache terkait kategori
     */
    private function clearCategoryCache(?string $language = null): void
    {
        if ($language) {
            Cache::forget("categories_{$language}_*");
            Cache::forget("category_*_{$language}");
        }
        Cache::forget('featured_categories_*');
    }
}
