<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Actions;

use App\Models\Feedback;

class ToggleFeedbackAction
{
    public function execute(Feedback $feedback, bool $positive, bool $negative): Feedback
    {
        $feedback->update([
            'positive' => $positive ? true : null,
            'negative' => $negative ? true : null,
        ]);

        /** @var Feedback $fresh */
        $fresh = $feedback->fresh();

        return $fresh;
    }
}
