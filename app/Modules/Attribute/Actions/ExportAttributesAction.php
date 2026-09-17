<?php

declare(strict_types=1);

namespace App\Modules\Attribute\Actions;

use App\Models\Attribute;
use App\Models\User;
use Illuminate\Support\Arr;

final class ExportAttributesAction
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(int $shopId, User $user): array
    {
        $attributes = Attribute::where('shop_id', $shopId)->with('values')->get();
        /** @var array<int, array<string, mixed>> $list */
        $list = $attributes->toArray();
        if (empty($list)) {
            return [];
        }
        foreach ($list as &$attr) {
            if (isset($attr['values']) && is_array($attr['values'])) {
                /** @var array<int|string, mixed> $plucked */
                $plucked = Arr::pluck($attr['values'], 'value');
                $attr['values'] = implode(',', array_map(fn ($v) => (is_scalar($v) || $v === null) ? (string) $v : '', $plucked));
            }
            unset($attr['id'], $attr['created_at'], $attr['updated_at'], $attr['slug'], $attr['translated_languages']);
        }

        return $list;
    }
}
