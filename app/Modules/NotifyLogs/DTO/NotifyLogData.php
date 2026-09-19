<?php

declare(strict_types=1);

namespace App\Modules\NotifyLogs\DTO;

final class NotifyLogData
{
    public function __construct(
        public readonly int $receiver,
        public readonly ?int $sender,
        public readonly string $notify_type,
        public readonly string $notify_receiver_type,
        public readonly bool $is_read,
        public readonly string $notify_text,
        public readonly string $notify_tracker,
    ) {}

    /**
     * @param array{
     *     receiver: int|string,
     *     sender?: int|string|null,
     *     notify_type: string,
     *     notify_receiver_type: string,
     *     is_read?: bool,
     *     notify_text: string,
     *     notify_tracker: string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            receiver: (int) $data['receiver'],
            sender: isset($data['sender']) ? (int) $data['sender'] : null,
            notify_type: (string) $data['notify_type'],
            notify_receiver_type: (string) $data['notify_receiver_type'],
            is_read: (bool) ($data['is_read'] ?? false),
            notify_text: (string) $data['notify_text'],
            notify_tracker: (string) $data['notify_tracker'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'receiver' => $this->receiver,
            'sender' => $this->sender,
            'notify_type' => $this->notify_type,
            'notify_receiver_type' => $this->notify_receiver_type,
            'is_read' => $this->is_read,
            'notify_text' => $this->notify_text,
            'notify_tracker' => $this->notify_tracker,
        ], fn ($v) => ! is_null($v));
    }
}
