<?php

namespace App\Modules\Shipping\Services;

use App\Models\Shipping;
use App\Modules\Shipping\Actions\CreateShippingAction;
use App\Modules\Shipping\Actions\UpdateShippingAction;
use App\Modules\Shipping\DTO\ShippingData;
use Illuminate\Database\Eloquent\Collection;

class ShippingService
{
    public function __construct(
        private CreateShippingAction $createAction,
        private UpdateShippingAction $updateAction,
    ) {}

    /**
     * @return Collection<int, Shipping>
     */
    public function getAll(): Collection
    {
        return Shipping::all();
    }

    public function findOrFail(int $id): Shipping
    {
        return Shipping::findOrFail($id);
    }

    public function create(ShippingData $data): Shipping
    {
        return $this->createAction->execute($data);
    }

    public function update(Shipping $shipping, ShippingData $data): Shipping
    {
        return $this->updateAction->execute($shipping, $data);
    }

    public function delete(Shipping $shipping): void
    {
        $shipping->delete();
    }
}
