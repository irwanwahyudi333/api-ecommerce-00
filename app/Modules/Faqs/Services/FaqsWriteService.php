<?php

declare(strict_types=1);

namespace App\Modules\Faqs\Services;

use App\Models\Faqs;
use App\Modules\Faqs\Actions\CreateFaqsAction;
use App\Modules\Faqs\Actions\DeleteFaqsAction;
use App\Modules\Faqs\Actions\UpdateFaqsAction;
use App\Modules\Faqs\DTO\FaqsData;

final class FaqsWriteService
{
    public function __construct(
        private readonly CreateFaqsAction $createFaqsAction,
        private readonly UpdateFaqsAction $updateFaqsAction,
        private readonly DeleteFaqsAction $deleteFaqsAction,
    ) {}

    public function create(FaqsData $data): Faqs
    {
        return $this->createFaqsAction->execute($data);
    }

    public function update(Faqs $faqs, FaqsData $data): Faqs
    {
        return $this->updateFaqsAction->execute($faqs, $data);
    }

    public function delete(Faqs $faqs): void
    {
        $this->deleteFaqsAction->execute($faqs);
    }
}
