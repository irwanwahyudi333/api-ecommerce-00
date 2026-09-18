<?php

declare(strict_types=1);

namespace App\Modules\Download\Services;

use App\Models\OrderedFile;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class DownloadQueryService
{
    /**
     * Get query for downloadable files for a user
     *
     * @return Builder<OrderedFile>
     */
    public function getDownloadableFilesQuery(Authenticatable $user): Builder
    {
        return OrderedFile::where('customer_id', $user->getAuthIdentifier())
            ->with(['order', 'file.fileable']); // Eager load relations for resource
    }

    public function findOrFail(int $id): OrderedFile
    {
        return OrderedFile::findOrFail($id);
    }

    /**
     * Get media item by attachment_id (from Spatie MediaLibrary)
     */
    public function getMediaItem(int $attachmentId): ?Media
    {
        return Media::find($attachmentId);
    }
}
