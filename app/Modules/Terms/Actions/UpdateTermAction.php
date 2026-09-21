<?php

declare(strict_types=1);

namespace App\Modules\Terms\Actions;

use App\Models\TermsAndConditions;
use App\Modules\Terms\DTO\TermsData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class UpdateTermAction
{
    public function execute(TermsAndConditions $term, TermsData $data): TermsAndConditions
    {
        $attributes = array_filter([
            'title' => $data->title,
            'description' => $data->description,
            'language' => $data->language,
            'slug' => ($data->slug && $data->slug !== $term->slug) ? $data->slug : ($data->title ? Str::slug((string) $data->title) : null),
            'shop_id' => $data->shop_id,
            'user_id' => $data->user_id,
        ], fn ($v) => ! is_null($v));

        $term->update($attributes);

        Cache::forget("terms_{$term->language}_*"); // Invalidate cache

        return $term->refresh();
    }
}
