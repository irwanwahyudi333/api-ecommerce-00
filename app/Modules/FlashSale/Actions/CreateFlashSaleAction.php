<?php

declare(strict_types=1);

namespace App\Modules\FlashSale\Actions;

use App\Models\FlashSale;
use App\Models\Product;
use App\Modules\FlashSale\DTO\FlashSaleData;

class CreateFlashSaleAction
{
    public function execute(FlashSaleData $data): FlashSale
    {
        $flashSale = FlashSale::create($data->toArray());

        if (! empty($data->sale_builder['product_ids']) && is_array($data->sale_builder['product_ids'])) {
            /** @var array<int> $productIds */
            $productIds = $data->sale_builder['product_ids'];
            $flashSale->products()->attach($productIds);
            $this->setProductInFlashSale($productIds);
        }

        return $flashSale;
    }

    /**
     * @param  array<int>  $productIds
     */
    private function setProductInFlashSale(array $productIds): void
    {
        Product::whereIn('id', $productIds)->update(['in_flash_sale' => true]);
    }
}
