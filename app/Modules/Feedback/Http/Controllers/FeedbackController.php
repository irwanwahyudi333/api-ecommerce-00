<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Feedback;
use App\Models\User;
use App\Modules\Feedback\Actions\CreateFeedbackAction;
use App\Modules\Feedback\Actions\DeleteFeedbackAction;
use App\Modules\Feedback\Actions\ToggleFeedbackAction;
use App\Modules\Feedback\DTO\FeedbackData;
use App\Modules\Feedback\Http\Requests\FeedbackCreateRequest;
use App\Modules\Feedback\Http\Resources\FeedbackResource;
use App\Modules\Feedback\Services\FeedbackQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedbackController extends BaseController
{
    public function __construct(
        private readonly FeedbackQueryService $feedbackQueryService,
        private readonly CreateFeedbackAction $createFeedbackAction,
        private readonly DeleteFeedbackAction $deleteFeedbackAction,
        private readonly ToggleFeedbackAction $toggleFeedbackAction
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Feedback::class);
        $feedbacks = $this->feedbackQueryService->getFeedbackWithUser();

        return FeedbackResource::collection($feedbacks);
    }

    public function store(FeedbackCreateRequest $request): FeedbackResource
    {
        $this->authorize('create', Feedback::class);
        /** @var User $user */
        $user = $request->user();
        $userId = (int) $user->id;
        $data = FeedbackData::fromRequest($request->validated(), $userId);

        $target = $this->feedbackQueryService->findTargetModel($data->model_type, $data->model_id);
        $existing = $this->feedbackQueryService->getExistingFeedback($target, $userId);

        if (! $existing) {
            $feedback = $this->createFeedbackAction->execute($target, $data);
        } else {
            $positive = $data->positive;
            $negative = $data->negative;

            if ($positive && $existing->negative === true) {
                $feedback = $this->toggleFeedbackAction->execute($existing, true, false);
            } elseif ($negative && $existing->positive === true) {
                $feedback = $this->toggleFeedbackAction->execute($existing, false, true);
            } else {
                $feedback = $existing;
            }
        }

        return new FeedbackResource($feedback);
    }

    public function show(int $id): FeedbackResource
    {
        $feedback = $this->feedbackQueryService->findFeedbackOrFail($id);
        $this->authorize('view', $feedback);

        return new FeedbackResource($feedback);
    }

    public function destroy(int $id): JsonResponse
    {
        $feedback = $this->feedbackQueryService->findFeedbackOrFail($id);
        $this->authorize('delete', $feedback);
        $this->deleteFeedbackAction->execute($feedback);

        return $this->sendSuccess(null, 'Feedback deleted successfully');
    }
}
