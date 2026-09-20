<?php

declare(strict_types=1);

namespace App\Modules\Question\Services;

use App\Models\Question;
use App\Models\Settings;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class QuestionQueryService
{
    /**
     * Get a query builder for questions based on request parameters.
     *
     * @return Builder<Question>
     */
    public function getQuestionsQuery(Request $request): Builder
    {
        $query = Question::query();

        $productId = $request->input('product_id');
        if ($productId) {
            $query->where('product_id', $productId)->whereNotNull('answer');

            return $query;
        }

        $answerParam = $request->input('answer');
        if ($answerParam === 'null') { // Assuming 'null' string means unanswered questions
            return $query->whereNull('answer');
        }

        // Default to questions with answers if no specific product_id or 'answer' filter
        return $query->whereNotNull('answer');
    }

    public function findOrFail(int $id): Question
    {
        return Question::findOrFail($id);
    }

    /**
     * Get paginated questions by user ID.
     *
     * @return LengthAwarePaginator<int, Question>
     */
    public function getUserQuestions(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Question::where('user_id', $userId)->with('product')->paginate($perPage);
    }

    public function countUserQuestionsForProduct(int $userId, int $productId, int $shopId): int
    {
        return Question::where('product_id', $productId)
            ->where('user_id', $userId)
            ->where('shop_id', $shopId)
            ->count();
    }

    public function getMaximumQuestionLimit(): int
    {
        $settings = Settings::getData(); // Assuming getData() is a static method returning settings object

        return (int) ($settings->options['maximumQuestionLimit'] ?? 5);
    }
}
