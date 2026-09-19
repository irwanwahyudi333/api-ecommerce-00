<?php

declare(strict_types=1);

namespace App\Modules\Message\DTO;

final class MessageData
{
    public function __construct(
        public readonly string $body,
        public readonly int $conversation_id,
        public readonly int $user_id,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data, int $conversationId, int $userId): self
    {
        $body = $data['message'];
        assert(is_string($body));

        return new self(
            body: $body,
            conversation_id: $conversationId,
            user_id: $userId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'body' => $this->body,
            'conversation_id' => $this->conversation_id,
            'user_id' => $this->user_id,
        ];
    }
}
