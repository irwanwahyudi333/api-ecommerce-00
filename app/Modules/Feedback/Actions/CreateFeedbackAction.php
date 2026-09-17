<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Actions;

use App\Models\Feedback;
use App\Modules\Feedback\DTO\FeedbackData;
use Illuminate\Database\Eloquent\Model;

class CreateFeedbackAction
{
    public function execute(Model $target, FeedbackData $data): Feedback
    {
        /** @phpstan-ignore method.notFound */
        return $target->feedbacks()->create($data->toArray());
    }
}
