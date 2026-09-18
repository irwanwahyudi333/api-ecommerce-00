<?php

declare(strict_types=1);

namespace App\Modules\Download\DTO;

class DownloadData
{
    public function __construct(
        public readonly int $digital_file_id,
        public readonly int $user_id,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data, int $userId): self
    {
        return new self(
            digital_file_id: is_numeric($data['digital_file_id']) ? (int) $data['digital_file_id'] : 0,
            user_id: $userId,
        );
    }
}
