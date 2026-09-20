<?php

declare(strict_types=1);

namespace App\Modules\Review\DTO;

use App\Models\User;
use Illuminate\Http\Request;

class ReviewData
{
    /**
     * @param  array<string>|null  $photos
     */
    public function __construct(
        public readonly int $orderId,
        public readonly int $productId,
        public readonly ?int $variationOptionId,
        public readonly int $userId,
        public readonly int $shopId,
        public readonly ?string $comment,
        public readonly int $rating,
        public readonly ?array $photos,
    ) {}

    public static function fromRequest(Request $request): self
    {
        /** @var User $user */
        $user = $request->user();

        /** @var string|null $comment */
        $comment = $request->input('comment');

        /** @var array<string>|null $photos */
        $photos = $request->input('photos');

        return new self(
            orderId: $request->integer('order_id'),
            productId: $request->integer('product_id'),
            variationOptionId: $request->filled('variation_option_id') ? $request->integer('variation_option_id') : null,
            userId: (int) $user->id,
            shopId: $request->integer('shop_id'),
            comment: $comment,
            rating: $request->integer('rating'),
            photos: $photos,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'variation_option_id' => $this->variationOptionId,
            'user_id' => $this->userId,
            'shop_id' => $this->shopId,
            'comment' => $this->comment,
            'rating' => $this->rating,
            'photos' => $this->photos,
        ];
    }
}
