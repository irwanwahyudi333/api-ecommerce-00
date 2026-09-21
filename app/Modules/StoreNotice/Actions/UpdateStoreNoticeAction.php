<?php

declare(strict_types=1);

namespace App\Modules\StoreNotice\Actions;

use App\Models\StoreNotice;
use App\Modules\StoreNotice\DTO\StoreNoticeData;

final class UpdateStoreNoticeAction
{
    public function execute(StoreNotice $storeNotice, StoreNoticeData $data): StoreNotice
    {
        $attributes = array_filter([
            'priority' => $data->priority,
            'notice' => $data->notice,
            'description' => $data->description,
            'effective_from' => $data->effective_from,
            'expired_at' => $data->expired_at,
            'type' => $data->type,
        ], fn ($v) => ! is_null($v));

        $storeNotice->update($attributes);
        $fresh = $storeNotice->fresh();

        if (! $fresh instanceof StoreNotice) {
            throw new \RuntimeException('Failed to refresh StoreNotice.');
        }

        return $fresh;
    }
}
