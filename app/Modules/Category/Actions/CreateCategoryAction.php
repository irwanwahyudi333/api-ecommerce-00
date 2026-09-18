<?php

declare(strict_types=1);

namespace App\Modules\Category\Actions;

use App\Models\Category;
use App\Modules\Category\DTO\CategoryData;
use Illuminate\Support\Str;

class CreateCategoryAction
{
    public function execute(CategoryData $data): Category
    {
        $attributes = array_filter([
            'name' => $data->name,
            'slug' => $data->slug ?? Str::slug((string) $data->name),
            'type_id' => $data->type_id,
            'icon' => $data->icon,
            'image' => $data->image,
            'details' => $data->details,
            'banner_image' => $data->banner_image,
            'language' => $data->language,
            'parent' => $data->parent,
        ], fn ($v) => ! is_null($v));

        return Category::create($attributes);
    }
}
