<?php

declare(strict_types=1);

namespace App\Modules\Attribute\Actions;

use App\Models\Attribute;
use App\Modules\Attribute\DTO\AttributeData;
use Illuminate\Support\Str;

class CreateAttributeAction
{
    public function execute(AttributeData $data): Attribute
    {
        $attributes = array_filter([
            'name' => $data->name,
            'slug' => $data->slug ?? Str::slug((string) $data->name),
            'shop_id' => $data->shop_id,
            'language' => $data->language,
        ], fn ($v) => ! is_null($v));

        $attribute = Attribute::create($attributes);

        if ($data->values) {
            foreach ($data->values as $valueData) {
                $valueData['attribute_id'] = $attribute->id;
                $attribute->values()->create($valueData);
            }
        }

        return $attribute->load('values');
    }
}
