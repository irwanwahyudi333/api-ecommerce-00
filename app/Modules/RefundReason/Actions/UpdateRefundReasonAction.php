<?php

declare(strict_types=1);

namespace App\Modules\RefundReason\Actions;

use App\Models\RefundReason;
use App\Modules\RefundReason\DTO\RefundReasonData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class UpdateRefundReasonAction
{
    public function execute(RefundReason $reason, RefundReasonData $data): RefundReason
    {
        $attributes = array_filter([
            'name' => $data->name,
            'slug' => ($data->slug && $data->slug !== $reason->slug) ? $data->slug : ($data->name ? Str::slug($data->name) : null),
            'language' => $data->language,
        ], fn ($v) => ! is_null($v));

        $reason->update($attributes);

        Cache::forget("refund_reasons_{$reason->language}_*"); // Invalidate cache

        return $reason->fresh() ?? $reason;
    }
}
