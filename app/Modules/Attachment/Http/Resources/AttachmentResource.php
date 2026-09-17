<?php

declare(strict_types=1);

namespace App\Modules\Attachment\Http\Resources;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin Attachment
 */
class AttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Attachment $attachment */
        $attachment = $this->resource;

        /** @var Media|null $media */
        $media = $attachment->getMedia()->first();
        $isImage = $media ? strpos($media->mime_type, 'image/') !== false : false;

        return [
            'id' => $attachment->id,
            'thumbnail' => $media && $isImage ? $media->getUrl('thumbnail') : '',
            'original' => $media ? $media->getUrl() : null,
            'created_at' => $attachment->created_at?->toISOString(),
        ];
    }
}
