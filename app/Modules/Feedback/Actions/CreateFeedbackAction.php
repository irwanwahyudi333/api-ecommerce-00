<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Actions;

use App\Models\Feedback;
use App\Models\Question;
use App\Models\Review;
use App\Modules\Feedback\DTO\FeedbackData;
use Illuminate\Database\Eloquent\Model;

class CreateFeedbackAction
{
    public function execute(Model $target, FeedbackData $data): Feedback
    {
        if ($target instanceof Review || $target instanceof Question) {
            return $target->feedbacks()->create($data->toArray());
        }

        throw new \InvalidArgumentException('Target model does not support feedbacks.');
    }
}
