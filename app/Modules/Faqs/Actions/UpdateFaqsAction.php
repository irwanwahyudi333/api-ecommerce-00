<?php

declare(strict_types=1);

namespace App\Modules\Faqs\Actions;

use App\Models\Faqs;
use App\Modules\Faqs\DTO\FaqsData;

class UpdateFaqsAction
{
    public function execute(Faqs $faqs, FaqsData $data): Faqs
    {
        $faqs->update($data->toArray());

        return $faqs->refresh();
    }
}
