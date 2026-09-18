<?php

declare(strict_types=1);

namespace App\Modules\DeliveryTime\Services;

use App\Models\DeliveryTime;
use App\Modules\DeliveryTime\Actions\CreateDeliveryTimeAction;
use App\Modules\DeliveryTime\Actions\DeleteDeliveryTimeAction;
use App\Modules\DeliveryTime\Actions\UpdateDeliveryTimeAction;
use App\Modules\DeliveryTime\DTO\DeliveryTimeData;

final class DeliveryTimeWriteService
{
    public function __construct(
        private readonly CreateDeliveryTimeAction $createAction,
        private readonly UpdateDeliveryTimeAction $updateAction,
        private readonly DeleteDeliveryTimeAction $deleteAction,
    ) {}

    public function create(DeliveryTimeData $data): DeliveryTime
    {
        return $this->createAction->execute($data);
    }

    public function update(DeliveryTime $deliveryTime, DeliveryTimeData $data): DeliveryTime
    {
        return $this->updateAction->execute($deliveryTime, $data);
    }

    public function delete(DeliveryTime $deliveryTime): void
    {
        $this->deleteAction->execute($deliveryTime);
    }
}
