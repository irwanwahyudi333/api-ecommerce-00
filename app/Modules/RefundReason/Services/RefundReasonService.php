<?php

declare(strict_types=1);

namespace App\Modules\RefundReason\Services;

use App\Models\RefundReason;
use App\Modules\RefundReason\DTO\RefundReasonData;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class RefundReasonService
{
    /**
     * @return LengthAwarePaginator<int, RefundReason>
     */
    public function getRefundReasons(string $language, int $perPage = 15): LengthAwarePaginator
    {
        return RefundReason::where('language', $language)->paginate($perPage);
    }

    public function find(string $params, string $language): RefundReason
    {
        if (is_numeric($params)) {
            return RefundReason::where('id', (int) $params)->firstOrFail();
        }

        return RefundReason::where('slug', $params)->where('language', $language)->firstOrFail();
    }

    public function create(RefundReasonData $data): RefundReason
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

        return RefundReason::create($attributes);
    }

    public function update(RefundReason $reason, RefundReasonData $data): RefundReason
    {
        $attributes = array_filter([
            'name' => $data->name,
            'slug' => ($data->slug && $data->slug !== $reason->slug) ? $data->slug : ($data->name ? Str::slug($data->name) : null),
            'language' => $data->language,
        ], fn ($v) => ! is_null($v));

        $reason->update($attributes);

        return $reason->fresh() ?? $reason;
    }

    public function delete(RefundReason $reason): void
    {
        $reason->delete();
    }
}
