<?php

declare(strict_types=1);

namespace App\Modules\Question\DTO;

final class QuestionData
{
    public function __construct(
        public readonly ?int $product_id,
        public readonly ?int $shop_id,
        public readonly ?int $user_id,
        public readonly ?string $question,
        public readonly ?string $answer,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data, ?int $userId = null): self
    {
        $productId = $data['product_id'] ?? null;
        $shopId = $data['shop_id'] ?? null;
        $question = $data['question'] ?? null;
        $answer = $data['answer'] ?? null;

        return new self(
            product_id: is_numeric($productId) ? (int) $productId : null,
            shop_id: is_numeric($shopId) ? (int) $shopId : null,
            user_id: $userId,
            question: is_string($question) ? $question : null,
            answer: is_string($answer) ? $answer : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'product_id' => $this->product_id,
            'shop_id' => $this->shop_id,
            'user_id' => $this->user_id,
            'question' => $this->question,
            'answer' => $this->answer,
        ], fn ($v) => ! is_null($v));
    }
}
