<?php

declare(strict_types=1);

namespace App\Modules\FlashSale\Services;

use App\Models\FlashSale;
use App\Modules\FlashSale\Actions\CreateFlashSaleAction;
use App\Modules\FlashSale\Actions\UpdateFlashSaleAction;
use App\Modules\FlashSale\DTO\FlashSaleData;

final class FlashSaleWriteService
{
    public function __construct(
        private readonly CreateFlashSaleAction $createFlashSaleAction,
        private readonly UpdateFlashSaleAction $updateFlashSaleAction,
    ) {}

    public function createFlashSale(FlashSaleData $data): FlashSale
    {
        return $this->createFlashSaleAction->execute($data);
    }

    public function updateFlashSale(FlashSale $flashSale, FlashSaleData $data): FlashSale
    {
        return $this->updateFlashSaleAction->execute($flashSale, $data);
    }

    public function deleteFlashSale(FlashSale $flashSale): bool
    {
        return $flashSale->delete() ?? false;
    }
}
