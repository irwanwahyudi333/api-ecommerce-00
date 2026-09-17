<?php

declare(strict_types=1);

namespace App\Modules\Attachment\Services;

use App\Models\Attachment;
use App\Modules\Attachment\Actions\CreateAttachmentAction;
use App\Modules\Attachment\DTO\AttachmentData;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // Tambahkan ini

final class AttachmentWriteService
{
    public function __construct(private readonly CreateAttachmentAction $createAction) {}

    /**
     * @param AttachmentData $data
     * @return array<int, mixed>
     */
    public function upload(AttachmentData $data): array
    {
        return $this->createAction->execute($data);
    }

    public function delete(int $id): bool
    {
        $attachment = Attachment::findOrFail($id);
        $path = $attachment->getAttribute('path');
        if (is_string($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return $attachment->delete() === true;
    }

    public function deleteByUrl(string $url): bool
    {
        $parsedUrl = parse_url($url);
        $path = ltrim($parsedUrl['path'] ?? '', '/');

        // Remove the 'storage/' prefix to get the path relative to the public disk root
        $pathInStorage = Str::after($path, 'storage/');

        if (Storage::disk('public')->exists($pathInStorage)) {
            return Storage::disk('public')->delete($pathInStorage);
        }

        return false;
    }

    public function deleteByPath(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }
}
