<?php

declare(strict_types=1);

namespace App\Modules\Language\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Language;
use App\Modules\Language\DTO\LanguageData;
use App\Modules\Language\Http\Requests\LanguageRequest;
use App\Modules\Language\Http\Resources\LanguageResource;
use App\Modules\Language\Services\LanguageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LanguageController extends BaseController
{
    public function __construct(private LanguageService $languageService) {}

    public function index(): JsonResponse
    {
        $languages = Cache::rememberForever('languages_all', function () {
            return $this->languageService->getAll();
        });

        return $this->sendSuccess(LanguageResource::collection($languages), 'Languages retrieved');
    }

    public function store(LanguageRequest $request): JsonResponse
    {
        $this->authorize('create', Language::class);
        $data = LanguageData::fromRequest($request->validated());
        $language = $this->languageService->create($data);
        Cache::forget('languages_all');

        return $this->sendSuccess(new LanguageResource($language), 'Language created', 201);
    }

    public function show(int $id): JsonResponse
    {
        $language = $this->languageService->find($id);

        return $this->sendSuccess(new LanguageResource($language), 'Language detail');
    }

    public function update(LanguageRequest $request, int $id): JsonResponse
    {
        $language = Language::findOrFail($id);
        $this->authorize('update', $language);

        $data = LanguageData::fromRequest($request->validated());
        $updated = $this->languageService->update($language, $data);
        Cache::forget('languages_all');

        return $this->sendSuccess(new LanguageResource($updated), 'Language updated');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $language = Language::findOrFail($id);
        $this->authorize('delete', $language);
        $this->languageService->delete($language);
        Cache::forget('languages_all');

        return $this->sendSuccess(null, 'Language deleted');
    }
}
