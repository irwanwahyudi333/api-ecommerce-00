<?php

declare(strict_types=1);

namespace App\Modules\AttributeValue\Actions;

use App\Models\AttributeValue;
use App\Modules\AttributeValue\DTO\AttributeValueData;

class UpdateAttributeValueAction
{
    public function execute(AttributeValue $attributeValue, AttributeValueData $data): AttributeValue
    {
        $updateData = array_filter([
            'value' => $data->value,
            'meta' => $data->meta,
            'price' => $data->price,
            'shop_id' => $data->shop_id,
            'attribute_id' => $data->attribute_id,
            'language' => $data->language,
        ], fn ($v) => ! is_null($v));

        $attributeValue->update($updateData);

        return $attributeValue->refresh();
    }
}
