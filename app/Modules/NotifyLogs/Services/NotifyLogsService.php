<?php

declare(strict_types=1);

namespace App\Modules\NotifyLogs\Services;

use App\Models\NotifyLogs;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NotifyLogsService
{
    /**
     * Get query builder for notifications.
     *
     * @return Builder<NotifyLogs>
     */
    public function getNotifyLogsQuery(Request $request, ?Authenticatable $user): Builder
    {
        $query = NotifyLogs::with('senderUser')
            ->where('receiver', $user?->getAuthIdentifier())
            ->orderBy('created_at', 'desc');

        if ($request->filled('notify_type')) {
            $query->where('notify_type', $request->notify_type);
        }

        return $query;
    }

    /**
     * Find a notification by ID.
     */
    public function findOrFail(int $id): NotifyLogs
    {
        return NotifyLogs::with('senderUser')->findOrFail($id);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(NotifyLogs $log): NotifyLogs
    {
        $log->is_read = true;
        $log->save();

        /** @var NotifyLogs $freshLog */
        $freshLog = $log->fresh();

        return $freshLog;
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @return Collection<int, NotifyLogs>
     */
    public function markAllAsRead(?string $notifyType, int $receiverId): Collection
    {
        $query = NotifyLogs::where('receiver', $receiverId);

        if ($notifyType) {
            $query->where('notify_type', $notifyType);
        }

        $logs = $query->get();

        foreach ($logs as $log) {
            $log->is_read = true;
            $log->save();
        }

        return $logs;
    }

    /**
     * Delete a notification (soft delete).
     */
    public function deleteNotifyLog(NotifyLogs $log): void
    {
        $log->delete();
    }
}
