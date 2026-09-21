<?php

declare(strict_types=1);

namespace App\Modules\Tag\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Tag;
use App\Models\User;
use App\Modules\Tag\DTO\TagData;
use App\Modules\Tag\Http\Requests\TagCreateRequest;
use App\Modules\Tag\Http\Requests\TagUpdateRequest;
use App\Modules\Tag\Http\Resources\TagResource;
use App\Modules\Tag\Services\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TagController extends BaseController
{
    public function __construct(private TagService $tagService) {}

    public function index(Request $request): JsonResponse
    {
        $langRaw = $request->get('language', config('shop.default_language', 'id'));
        $language = is_string($langRaw) ? $langRaw : 'id';

        $limitRaw = $request->get('limit', 15);
        $limit = is_numeric($limitRaw) ? (int) $limitRaw : 15;

        /** @var LengthAwarePaginator<int, Tag> $tags */
        $tags = $this->tagService->getTags($language, $limit);

        return $this->sendPaginated(
            $tags,
            TagResource::collection($tags->getCollection()),
            'Tags retrieved successfully'
        );
    }

    public function store(TagCreateRequest $request): JsonResponse
    {
        $this->authorize('create', Tag::class);

        $data = TagData::fromRequest($request);
        /** @var User $user */
        $user = $request->user();
        $tag = $this->tagService->createTag($data, $user);

        return $this->sendSuccess(
            new TagResource($tag),
            'Tag created successfully',
            201
        );
    }

    public function show(Request $request, string $param): JsonResponse
    {
        $langRaw = $request->get('language', config('shop.default_language', 'id'));
        $language = is_string($langRaw) ? $langRaw : 'id';
        $tag = $this->tagService->getTagByIdOrSlug($param, $language);

        return $this->sendSuccess(
            new TagResource($tag),
            'Tag retrieved successfully'
        );
    }

    public function update(TagUpdateRequest $request, int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);
        $this->authorize('update', $tag);

        $data = TagData::fromRequest($request);
        /** @var User $user */
        $user = $request->user();
        $updated = $this->tagService->updateTag($tag, $data, $user);

        return $this->sendSuccess(
            new TagResource($updated),
            'Tag updated successfully'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);
        $this->authorize('delete', $tag);

        /** @var User $user */
        $user = $request->user();
        $this->tagService->deleteTag($tag, $user);

        return $this->sendSuccess(
            null,
            'Tag deleted successfully'
        );
    }
}
