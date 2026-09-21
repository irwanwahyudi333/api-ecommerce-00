<?php

declare(strict_types=1);

namespace App\Modules\Shop\Actions;

use App\Models\Shop;
use App\Modules\Shop\DTO\ShopData;

final class UpdateShopAction
{
    public function execute(Shop $shop, ShopData $data): Shop
    {
        $shop->update($data->toAttributes());

        if ($data->categories !== null) {
            $shop->categories()->sync($data->categories);
        }

        $shop->load(['categories', 'owner']);

        return $shop;
    }
}
