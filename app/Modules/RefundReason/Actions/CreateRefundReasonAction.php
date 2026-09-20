<?php

declare(strict_types=1);

namespace App\Modules\RefundReason\Actions;

use App\Models\RefundReason;
use App\Modules\RefundReason\DTO\RefundReasonData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class CreateRefundReasonAction
{
    public function execute(RefundReasonData $data): RefundReason
    {
        $slug = $data->slug;
        if ($slug === null && $data->name !== null && $data->name !== '') {
            $slug = Str::slug($data->name);
        }

        $attributes = array_filter([
            'name' => $data->name,
            'slug' => $slug,
            'language' => $data->language,
        ], fn ($v) => ! is_null($v));

        $reason = RefundReason::create($attributes);

        Cache::forget("refund_reasons_{$reason->language}_*"); // Invalidate cache

        return $reason;
    }
}
