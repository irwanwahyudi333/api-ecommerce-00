<?php

declare(strict_types=1);

namespace App\Modules\NotifyLogs\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\NotifyLogs;
use App\Models\User;
use App\Modules\NotifyLogs\Http\Requests\MarkAllAsReadRequest;
use App\Modules\NotifyLogs\Http\Requests\MarkAsReadRequest;
use App\Modules\NotifyLogs\Http\Resources\NotifyLogResource;
use App\Modules\NotifyLogs\Services\NotifyLogsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotifyLogsController extends BaseController
{
    public function __construct(private NotifyLogsService $notifyService) {}

    /**
     * GET /notify-logs
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User|null $user */
        $user = $request->user();
        $limit = $request->integer('limit', 10);
        $logs = $this->notifyService->getNotifyLogsQuery($request, $user)->paginate($limit);

        return NotifyLogResource::collection($logs);
    }

    /**
     * GET /notify-logs/{id}
     */
    public function show(Request $request, int $id): NotifyLogResource
    {
        $log = $this->notifyService->findOrFail($id);
        $this->authorize('view', $log);

        return new NotifyLogResource($log);
    }

    /**
     * DELETE /notify-logs/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $log = $this->notifyService->findOrFail($id);
        $this->authorize('delete', $log);

        $this->notifyService->deleteNotifyLog($log);

        return $this->sendSuccess(null, 'Notify log deleted successfully');
    }

    /**
     * POST /notify-logs/read
     */
    public function readNotifyLogs(MarkAsReadRequest $request): NotifyLogResource
    {
        $log = $this->notifyService->findOrFail($request->integer('id'));
        $this->authorize('markAsRead', $log);

        $updated = $this->notifyService->markAsRead($log);

        return new NotifyLogResource($updated);
    }

    /**
     * POST /notify-logs/read-all
     */
    public function readAllNotifyLogs(MarkAllAsReadRequest $request): AnonymousResourceCollection
    {
        $this->authorize('markAllAsRead', NotifyLogs::class);

        $logs = $this->notifyService->markAllAsRead(
            $request->has('notify_type') ? $request->string('notify_type')->toString() : null,
            $request->integer('receiver')
        );

        return NotifyLogResource::collection($logs);
    }
}
