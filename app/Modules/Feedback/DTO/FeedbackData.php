<?php

declare(strict_types=1);

namespace App\Modules\Feedback\DTO;

final class FeedbackData
{
    public function __construct(
        public readonly int $model_id,
        public readonly string $model_type,
        public readonly ?bool $positive,
        public readonly ?bool $negative,
        public readonly ?int $user_id,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data, ?int $userId = null): self
    {
        return new self(
            model_id: (int) $data['model_id'],
            model_type: $data['model_type'],
            positive: isset($data['positive']) ? (bool) $data['positive'] : null,
            negative: isset($data['negative']) ? (bool) $data['negative'] : null,
            user_id: $userId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'model_id' => $this->model_id,
            'model_type' => $this->model_type,
            'positive' => $this->positive,
            'negative' => $this->negative,
            'user_id' => $this->user_id,
        ], fn ($v) => ! is_null($v));
    }
}
