<?php

declare(strict_types=1);

namespace App\Modules\Review\Services;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Modules\Review\DTO\ReviewData;
use App\Modules\Review\Events\ReviewCreated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReviewService
{
    /**
     * @return Builder<Review>
     */
    public function getReviews(Request $request, ?User $user = null): Builder
    {
        $query = Review::query()->with(['user', 'product', 'order', 'shop']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->has('shop_id')) {
            $query->where('shop_id', $request->integer('shop_id'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        return $query;
    }

    public function validateProductInOrder(int $orderId, int $productId): bool
    {
        return Order::where('id', $orderId)
            ->whereHas('products', fn ($q) => $q->where('product_id', $productId))
            ->exists();
    }

    public function reviewExistsForOrder(int $userId, int $orderId, int $productId, ?int $shopId, ?int $variationOptionId = null): bool
    {
        $query = Review::where('user_id', $userId)
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->where('shop_id', $shopId);

        if ($variationOptionId) {
            $query->where('variation_option_id', $variationOptionId);
        }

        return $query->exists();
    }

    public function createReview(ReviewData $data, User $user): Review
    {
        $review = Review::create($data->toArray());
        event(new ReviewCreated($review));

        return $review;
    }

    public function updateReview(Review $review, ReviewData $data, User $user): Review
    {
        $review->update($data->toArray());

        /** @var Review $freshReview */
        $freshReview = $review->fresh();

        return $freshReview;
    }

    public function deleteReview(Review $review, User $user): void
    {
        $review->delete();
    }
}
