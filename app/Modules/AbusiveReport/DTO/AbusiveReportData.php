<?php

declare(strict_types=1);

namespace App\Modules\AbusiveReport\DTO;

use App\Models\Question;
use App\Models\Review;
use App\Modules\AbusiveReport\Enums\AbusiveReportType;

final readonly class AbusiveReportData
{
    public function __construct(
        public int $model_id,
        public string $model_type,
        public string $message,
        public int $user_id,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data, int $userId): self
    {
        $modelId = $data['model_id'] ?? null;
        $modelType = $data['model_type'] ?? null;
        $message = $data['message'] ?? null;

        if (! is_numeric($modelId) || ! is_string($modelType) || ! is_string($message)) {
            throw new \InvalidArgumentException('Invalid data');
        }

        return new self(
            model_id: (int) $modelId,
            model_type: $modelType,
            message: $message,
            user_id: $userId,
        );
    }

    /**
     * @return class-string<Question|Review>
     */
    public function getModelClass(): string
    {
        return AbusiveReportType::from($this->model_type)->modelClass();
    }

    /**
     * @return array{model_id:int, model_type:class-string, message:string, user_id:int}
     */
    public function toArray(): array
    {
        return [
            'model_id' => $this->model_id,
            'model_type' => $this->getModelClass(),
            'message' => $this->message,
            'user_id' => $this->user_id,
        ];
    }
}
