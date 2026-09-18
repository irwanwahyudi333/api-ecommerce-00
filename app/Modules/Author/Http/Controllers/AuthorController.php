<?php

declare(strict_types=1);

namespace App\Modules\Author\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Author;
use App\Modules\Author\DTO\AuthorData;
use App\Modules\Author\Http\Requests\AuthorRequest;
use App\Modules\Author\Http\Resources\AuthorResource;
use App\Modules\Author\Services\AuthorQueryService;
use App\Modules\Author\Services\AuthorWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AuthorController extends BaseController
{
    public function __construct(
        private readonly AuthorQueryService $authorQueryService,
        private readonly AuthorWriteService $authorWriteService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $defaultLang = is_string(config('shop.default_language')) ? config('shop.default_language') : 'id';
        $limit = $request->integer('limit', 15);
        $language = $request->string('language', $defaultLang)->toString();
        $cacheKey = "authors_{$language}_{$limit}";
        $authors = Cache::remember($cacheKey, 3600, function () use ($language, $limit) {
            return $this->authorQueryService->getAuthorsByLanguage($language, $limit);
        });

        return $this->sendPaginated(
            $authors,
            AuthorResource::collection($authors->items()),
            'Daftar author berhasil diambil.'
        );
    }

    public function store(AuthorRequest $request): JsonResponse
    {
        $this->authorize('create', Author::class);
        $data = AuthorData::fromRequest($request->validated());
        $author = $this->authorWriteService->createAuthor($data);
        Cache::forget("authors_{$data->language}_*");

        return $this->sendSuccess(new AuthorResource($author), 'Author created', 201);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $defaultLang = is_string(config('shop.default_language')) ? config('shop.default_language') : 'id';
        $language = $request->string('language', $defaultLang)->toString();
        $author = $this->authorQueryService->getAuthorBySlug($slug, $language);

        return $this->sendSuccess(new AuthorResource($author), 'Author detail');
    }

    public function update(AuthorRequest $request, int $id): JsonResponse
    {
        $author = Author::findOrFail($id);
        $this->authorize('update', $author);
        $data = AuthorData::fromRequest($request->validated());
        $updated = $this->authorWriteService->updateAuthor($author, $data);
        Cache::forget("authors_{$data->language}_*");

        return $this->sendSuccess(new AuthorResource($updated), 'Author updated');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $author = Author::findOrFail($id);
        $this->authorize('delete', $author);
        $language = $author->language;
        $this->authorWriteService->deleteAuthor($author);
        Cache::forget("authors_{$language}_*");

        return $this->sendSuccess(null, 'Author deleted');
    }

    public function topAuthor(Request $request): JsonResponse
    {
        $defaultLang = is_string(config('shop.default_language')) ? config('shop.default_language') : 'id';
        $language = $request->string('language', $defaultLang)->toString();
        $limit = $request->integer('limit', 10);
        $cacheKey = "top_authors_{$language}_{$limit}";
        $authors = Cache::remember($cacheKey, 3600, function () use ($language, $limit) {
            return $this->authorQueryService->getTopAuthors($language, $limit);
        });

        return $this->sendSuccess(AuthorResource::collection($authors), 'Top authors');
    }
}
