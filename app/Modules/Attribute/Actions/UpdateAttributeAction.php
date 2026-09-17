<?php

declare(strict_types=1);

namespace App\Modules\Attribute\Actions;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Modules\Attribute\DTO\AttributeData;
use Illuminate\Support\Str;

class UpdateAttributeAction
{
    public function execute(Attribute $attribute, AttributeData $data): Attribute
    {
        // Update values: delete removed, update existing, create new
        if ($data->values !== null) {
            /** @var array<int> $existingIds */
            $existingIds = $attribute->values->pluck('id')->toArray();
            /** @var array<int> $newIds */
            $newIds = [];
            foreach ($data->values as $value) {
                if (isset($value['id']) && is_numeric($value['id']) && in_array($value['id'], $existingIds)) {
                    AttributeValue::where('id', $value['id'])->update($value);
                    $newIds[] = (int) $value['id'];
                } elseif (! isset($value['id'])) {
                    $value['attribute_id'] = $attribute->id;
                    $newVal = AttributeValue::create($value);
                    $newIds[] = (int) $newVal->id;
                }
            }
            $toDelete = array_diff($existingIds, $newIds);
            if (! empty($toDelete)) {
                AttributeValue::whereIn('id', $toDelete)->delete();
            }
        }

        $updateData = array_filter([
            'name' => $data->name,
            'slug' => ($data->slug && $data->slug !== $attribute->slug) ? $data->slug : ($data->name ? Str::slug($data->name) : null),
            'shop_id' => $data->shop_id,
            'language' => $data->language,
        ], fn ($v) => ! is_null($v));

        $attribute->update($updateData);

        return $attribute->load('values');
    }
}
