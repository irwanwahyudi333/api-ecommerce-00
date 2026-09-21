<?php

namespace App\Modules\StoreNotice\DTO;

class StoreNoticeData
{
    /**
     * @param  array<int>|null  $received_by
     */
    public function __construct(
        public readonly ?string $priority,
        public readonly ?string $notice,
        public readonly ?string $description,
        public readonly ?string $effective_from,
        public readonly ?string $expired_at,
        public readonly ?string $type,
        public readonly ?array $received_by,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        /** @var array<int>|null $receivedBy */
        $receivedBy = isset($data['received_by']) && is_array($data['received_by']) ? $data['received_by'] : null;

        return new self(
            priority: isset($data['priority']) && is_string($data['priority']) ? $data['priority'] : null,
            notice: isset($data['notice']) && is_string($data['notice']) ? $data['notice'] : null,
            description: isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
            effective_from: isset($data['effective_from']) && is_string($data['effective_from']) ? $data['effective_from'] : null,
            expired_at: isset($data['expired_at']) && is_string($data['expired_at']) ? $data['expired_at'] : null,
            type: isset($data['type']) && is_string($data['type']) ? $data['type'] : null,
            received_by: $receivedBy,
        );
    }
}
