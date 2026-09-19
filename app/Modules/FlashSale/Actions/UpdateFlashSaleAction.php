<?php

declare(strict_types=1);

namespace App\Modules\FlashSale\Actions;

use App\Models\FlashSale;
use App\Models\Product;
use App\Modules\FlashSale\DTO\FlashSaleData;

class UpdateFlashSaleAction
{
    public function execute(FlashSale $flashSale, FlashSaleData $data): FlashSale
    {
        /** @var array<int> $oldProductIds */
        $oldProductIds = is_array($flashSale->sale_builder['product_ids'] ?? null) ? $flashSale->sale_builder['product_ids'] : [];
        /** @var array<int> $newProductIds */
        $newProductIds = is_array($data->sale_builder['product_ids'] ?? null) ? $data->sale_builder['product_ids'] : [];

        if (! empty($newProductIds)) {
            $flashSale->products()->sync($newProductIds);
            $this->setProductInFlashSale($newProductIds);

            $removedIds = array_diff($oldProductIds, $newProductIds);
            if (! empty($removedIds)) {
                $this->unsetProductFromFlashSale($removedIds);
            }
        }

        $flashSale->update($data->toArray());

        $flashSale->refresh();

        return $flashSale;
    }

    /**
     * @param  array<int>  $productIds
     */
    private function setProductInFlashSale(array $productIds): void
    {
        Product::whereIn('id', $productIds)->update(['in_flash_sale' => true]);
    }

    /**
     * @param  array<int>  $productIds
     */
    private function unsetProductFromFlashSale(array $productIds): void
    {
        Product::whereIn('id', $productIds)->update(['in_flash_sale' => false]);
    }
}
