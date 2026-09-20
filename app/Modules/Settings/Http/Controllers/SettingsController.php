<?php

declare(strict_types=1);

namespace App\Modules\Settings\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Settings;
use App\Modules\Settings\DTO\SettingsData;
use App\Modules\Settings\Http\Requests\SettingsRequest;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends BaseController
{
    public function __construct(private SettingsService $settingsService) {}

    public function index(Request $request): JsonResponse
    {
        $languageInput = $request->get('language', config('shop.default_language', 'id'));
        $language = is_string($languageInput) ? $languageInput : '';
        $settings = $this->settingsService->getSettingsWithMaintenance($language);

        return $this->sendSuccess($settings, 'Settings retrieved successfully');
    }

    public function store(SettingsRequest $request): JsonResponse
    {
        $this->authorize('create', Settings::class);

        $data = SettingsData::fromRequest($request);
        $settings = $this->settingsService->storeOrUpdate($data, true, $request->user());

        return $this->sendSuccess($settings, 'Settings created successfully', 201);
    }

    public function show(int $id): JsonResponse
    {
        $settings = Settings::findOrFail($id); // Assuming ID is unique for settings

        return $this->sendSuccess($settings, 'Settings detail retrieved successfully');
    }

    public function update(SettingsRequest $request, int $id): JsonResponse
    {
        $settings = Settings::findOrFail($id); // Assuming ID is unique for settings
        $this->authorize('update', $settings);

        $data = SettingsData::fromRequest($request);
        $updatedSettings = $this->settingsService->storeOrUpdate($data, false, $request->user());

        return $this->sendSuccess($updatedSettings, 'Settings updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        throw new \Exception('Deleting settings is not allowed. Use update to modify existing settings.');
    }
}
