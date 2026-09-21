<?php

declare(strict_types=1);

namespace App\Modules\Tag\Services;

use App\Models\Tag;
use App\Models\User;
use App\Modules\Tag\DTO\TagData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TagService
{
    /**
     * @return LengthAwarePaginator<int, Tag>
     */
    public function getTags(string $language, int $perPage = 15): LengthAwarePaginator
    {
        return Tag::where('language', $language)
            ->with('type')
            ->paginate($perPage);
    }

    public function getTagByIdOrSlug(string $param, string $language): Tag
    {
        if (is_numeric($param)) {
            return Tag::where('id', (int) $param)->with('type')->firstOrFail();
        }

        return Tag::where('slug', $param)
            ->where('language', $language)
            ->with('type')
            ->firstOrFail();
    }

    public function createTag(TagData $data, User $user): Tag
    {
        return Tag::create($data->toArray());
    }

    public function updateTag(Tag $tag, TagData $data, User $user): Tag
    {
        $tag->update($data->toArray());

        /** @var Tag $freshTag */
        $freshTag = $tag->fresh();

        return $freshTag;
    }

    public function deleteTag(Tag $tag, User $user): void
    {
        $tag->delete();
    }
}
