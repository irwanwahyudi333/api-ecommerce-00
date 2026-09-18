<?php

declare(strict_types=1);

namespace App\Modules\Category\Services;

use App\Models\Category;
use App\Modules\Category\Actions\CreateCategoryAction;
use App\Modules\Category\Actions\UpdateCategoryAction;
use App\Modules\Category\DTO\CategoryData;

final class CategoryWriteService
{
    public function __construct(
        private readonly CreateCategoryAction $createCategoryAction,
        private readonly UpdateCategoryAction $updateCategoryAction,
    ) {}

    public function createCategory(CategoryData $data): Category
    {
        return $this->createCategoryAction->execute($data);
    }

    public function updateCategory(Category $category, CategoryData $data): Category
    {
        return $this->updateCategoryAction->execute($category, $data);
    }

    public function deleteCategory(Category $category): void
    {
        $category->delete();
    }
}
