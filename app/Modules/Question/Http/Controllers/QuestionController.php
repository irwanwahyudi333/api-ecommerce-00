<?php

declare(strict_types=1);

namespace App\Modules\Question\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Question;
use App\Models\User;
use App\Modules\Question\DTO\QuestionData;
use App\Modules\Question\Http\Requests\QuestionCreateRequest;
use App\Modules\Question\Http\Requests\QuestionUpdateRequest;
use App\Modules\Question\Http\Resources\QuestionResource;
use App\Modules\Question\Services\QuestionQueryService;
use App\Modules\Question\Services\QuestionWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class QuestionController extends BaseController
{
    public function __construct(
        private readonly QuestionQueryService $questionQueryService,
        private readonly QuestionWriteService $questionWriteService,
    ) {}

    /**
     * GET /questions
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = is_numeric($request->input('limit')) ? (int) $request->input('limit') : 15;
        $questions = $this->questionQueryService->getQuestionsQuery($request)->paginate($limit);

        return QuestionResource::collection($questions);
    }

    /**
     * POST /questions
     */
    public function store(QuestionCreateRequest $request): QuestionResource
    {
        $this->authorize('create', Question::class);

        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $userId = (int) $user->id;
        $validated = $request->validated();
        $productId = is_numeric($validated['product_id'] ?? null) ? (int) $validated['product_id'] : 0;
        $shopId = is_numeric($validated['shop_id'] ?? null) ? (int) $validated['shop_id'] : 0;

        $userQuestionCount = $this->questionQueryService->countUserQuestionsForProduct($userId, $productId, $shopId);
        $maxLimit = $this->questionQueryService->getMaximumQuestionLimit();

        if ($userQuestionCount >= $maxLimit) {
            $noticeMessage = config('notice.MAXIMUM_QUESTION_LIMIT_EXCEEDED');
            $message = is_string($noticeMessage) ? $noticeMessage : 'Maximum question limit exceeded';
            throw new HttpException(400, $message);
        }

        $data = QuestionData::fromRequest($validated, $userId);
        $question = $this->questionWriteService->createQuestion($data);

        return new QuestionResource($question);
    }

    /**
     * GET /questions/{id}
     */
    public function show(int $id): QuestionResource
    {
        $question = $this->questionQueryService->findOrFail($id);
        $this->authorize('view', $question);

        return new QuestionResource($question);
    }

    /**
     * PUT /questions/{id}
     */
    public function update(QuestionUpdateRequest $request, int $id): QuestionResource
    {
        $question = $this->questionQueryService->findOrFail($id);
        $this->authorize('update', $question);

        $data = QuestionData::fromRequest($request->validated(), $question->user_id);
        $updated = $this->questionWriteService->updateQuestion($question, $data);

        return new QuestionResource($updated);
    }

    /**
     * DELETE /questions/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $question = $this->questionQueryService->findOrFail($id);
        $this->authorize('delete', $question);

        $this->questionWriteService->deleteQuestion($question);

        return response()->json(['message' => 'Question deleted successfully']);
    }

    /**
     * GET /my-questions
     */
    public function myQuestions(Request $request): AnonymousResourceCollection
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $limit = is_numeric($request->input('limit')) ? (int) $request->input('limit') : 15;
        $questions = $this->questionQueryService->getUserQuestions((int) $user->id, $limit);

        return QuestionResource::collection($questions);
    }
}
