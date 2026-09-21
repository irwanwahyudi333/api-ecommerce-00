<?php

declare(strict_types=1);

namespace App\Modules\Shop\Actions;

use App\Models\Shop;
use App\Modules\Shop\DTO\ShopData;

final class CreateShopAction
{
    public function execute(ShopData $data): Shop
    {
        $shop = Shop::create($data->toAttributes());

        if ($data->categories !== null && $data->categories !== []) {
            $shop->categories()->attach($data->categories);
        }

        $shop->load(['categories', 'owner']);

        return $shop;
    }
}
