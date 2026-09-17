<?php

declare(strict_types=1);

namespace App\Modules\Attachment\Services;

use App\Models\Attachment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AttachmentQueryService
{
    /**
     * @return LengthAwarePaginator<int, Attachment>
     */
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return Attachment::paginate($perPage);
    }

    public function find(int $id): Attachment
    {
        return Attachment::findOrFail($id);
    }

    public function findOrFail(int $id): Attachment
    {
        return Attachment::findOrFail($id);
    }
}
