<?php

declare(strict_types=1);

namespace App\Modules\Attachment\Actions;

use App\Models\Attachment;
use App\Modules\Attachment\DTO\AttachmentData;
use Illuminate\Support\Facades\Storage;

class CreateAttachmentAction
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(AttachmentData $data): array
    {
        $results = [];
        foreach ($data->files as $file) {
            $path = $file->store('attachments', 'public');

            if (! is_string($path)) {
                continue;
            }

            $attachment = Attachment::create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
            $attachment->addMedia($file)->toMediaCollection();
            $results[] = [
                'id' => $attachment->id,
                'url' => Storage::url($path),
            ];
        }

        return $results;
    }
}
