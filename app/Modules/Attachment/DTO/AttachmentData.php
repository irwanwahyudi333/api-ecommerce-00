<?php

declare(strict_types=1);

namespace App\Modules\Attachment\DTO;

use Illuminate\Http\UploadedFile;

final class AttachmentData
{
    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function __construct(
        public readonly array $files,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRequest(array $data): self
    {
        /** @var array<int, UploadedFile> $files */
        $files = $data['attachment'] ?? [];

        return new self(
            files: $files,
        );
    }
}
