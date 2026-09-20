<?php

declare(strict_types=1);

namespace App\Modules\Review\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Review;
use App\Models\User;
use App\Modules\Review\DTO\ReviewData;
use App\Modules\Review\Http\Requests\ReviewCreateRequest;
use App\Modules\Review\Http\Requests\ReviewUpdateRequest;
use App\Modules\Review\Http\Resources\ReviewResource;
use App\Modules\Review\Services\ReviewService; // Use modular SettingsService
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReviewController extends BaseController
{
    public function __construct(
        private ReviewService $reviewService,
        private SettingsService $settingsService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 15);
        $reviews = $this->reviewService->getReviews($request, $request->user())->paginate($limit);

        return $this->sendPaginated(
            $reviews,
            ReviewResource::collection($reviews->getCollection()),
            'Reviews retrieved successfully'
        );
    }

    public function store(ReviewCreateRequest $request): JsonResponse
    {
        $this->authorize('create', Review::class);

        /** @var string $language */
        $language = config('shop.default_language', 'id');
        $settings = $this->settingsService->getSettings($language);
        $settingsOptions = $settings->options ?? [];

        $productId = $request->integer('product_id');
        $orderId = $request->integer('order_id');

        /** @var User $user */
        $user = $request->user();
        $userId = (int) $user->id;

        $shopId = $request->integer('shop_id');
        $variationOptionId = $request->filled('variation_option_id') ? $request->integer('variation_option_id') : null;

        if (! $this->reviewService->validateProductInOrder($orderId, $productId)) {
            throw new HttpException(404, 'Product not found in the given order.');
        }

        $reviewSystem = $settingsOptions['reviewSystem']['value'] ?? null;
        if ($reviewSystem === 'review_single_time') {
            $exists = $this->reviewService->reviewExistsForOrder(
                $userId, $orderId, $productId, $shopId, $variationOptionId
            );
            if ($exists) {
                throw new HttpException(400, 'You have already reviewed this product for this order.');
            }
        }

        $data = ReviewData::fromRequest($request);
        $review = $this->reviewService->createReview($data, $user);

        return $this->sendSuccess(
            new ReviewResource($review),
            'Review created successfully',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $review = Review::findOrFail($id);

        return $this->sendSuccess(
            new ReviewResource($review),
            'Review retrieved successfully'
        );
    }

    public function update(ReviewUpdateRequest $request, int $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $this->authorize('update', $review);

        /** @var User $user */
        $user = $request->user();

        $data = ReviewData::fromRequest($request);
        $updated = $this->reviewService->updateReview($review, $data, $user);

        return $this->sendSuccess(
            new ReviewResource($updated),
            'Review updated successfully'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $this->authorize('delete', $review);

        /** @var User $user */
        $user = $request->user();

        $this->reviewService->deleteReview($review, $user);

        return $this->sendSuccess(
            null,
            'Review deleted successfully'
        );
    }
}
