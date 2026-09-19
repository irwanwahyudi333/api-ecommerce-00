<?php

declare(strict_types=1);

namespace App\Modules\FlashSaleRequest\Actions;

use App\Models\FlashSaleRequest;
use App\Modules\FlashSaleRequest\DTO\FlashSaleRequestData;

class UpdateFlashSaleRequestAction
{
    public function execute(FlashSaleRequest $request, FlashSaleRequestData $data): FlashSaleRequest
    {
        $request->update($data->toArray());

        if (! empty($data->requested_product_ids)) {
            $request->products()->sync($data->requested_product_ids);
        }

        /** @var FlashSaleRequest $freshRequest */
        $freshRequest = $request->fresh();

        return $freshRequest;
    }
}
