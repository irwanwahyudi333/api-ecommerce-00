<?php

declare(strict_types=1);

namespace App\Modules\Webhook\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WebhookController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $webhooks = Webhook::where('user_id', $user->id)->paginate(15);

        return $this->sendPaginated($webhooks, $webhooks->getCollection(), 'Webhooks retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'events' => 'required|array',
            'events.*' => 'string',
        ]);

        /** @var User $user */
        $user = $request->user();
        $webhook = $user->webhooks()->create($data);

        return $this->sendSuccess($webhook, 'Webhook created successfully', 201);
    }

    public function show(Webhook $webhook, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($webhook->user_id !== $user->id) {
            return $this->sendError('Unauthorized', 403);
        }

        return $this->sendSuccess($webhook, 'Webhook retrieved successfully');
    }

    public function update(Request $request, Webhook $webhook): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($webhook->user_id !== $user->id) {
            return $this->sendError('Unauthorized', 403);
        }

        /** @var array<string, mixed> $data */
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url',
            'events' => 'sometimes|array',
            'events.*' => 'string',
            'is_active' => 'sometimes|boolean',
        ]);

        $webhook->update($data);

        return $this->sendSuccess($webhook, 'Webhook updated successfully');
    }

    public function destroy(Webhook $webhook, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($webhook->user_id !== $user->id) {
            return $this->sendError('Unauthorized', 403);
        }
        $webhook->delete();

        return $this->sendSuccess(null, 'Webhook deleted successfully');
    }
}
