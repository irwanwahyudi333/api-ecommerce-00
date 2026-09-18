<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Services;

use App\Models\Feedback;
use App\Models\Question;
use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

final class FeedbackQueryService
{
    /**
     * @return class-string<Model>
     */
    private function resolveModelClass(string $type): string
    {
        $map = [
            'Review' => Review::class,
            'Question' => Question::class,
        ];

        /** @var class-string<Model> $class */
        $class = $map[$type] ?? 'App\\Models\\'.$type;

        return $class;
    }

    public function findTargetModel(string $type, int $id): Model
    {
        $class = $this->resolveModelClass($type);

        return $class::findOrFail($id);
    }

    public function getExistingFeedback(Model $target, int $userId): ?Feedback
    {
        if ($target instanceof Review || $target instanceof Question) {
            return $target->feedbacks()->where('user_id', $userId)->first();
        }

        return null;
    }

    /**
     * @return LengthAwarePaginator<int, Feedback>
     */
    public function getFeedbackWithUser(int $perPage = 15): LengthAwarePaginator
    {
        return Feedback::with('user')->paginate($perPage);
    }

    public function findFeedbackOrFail(int $id): Feedback
    {
        return Feedback::findOrFail($id);
    }
}
