<?php

declare(strict_types=1);

namespace App\Modules\AbusiveReport\Services;

use App\Models\AbusiveReport;
use Illuminate\Pagination\LengthAwarePaginator;

class AbusiveReportService
{
    /**
     * @return LengthAwarePaginator<int, AbusiveReport>
     */
    public function getReports(int $perPage = 15): LengthAwarePaginator
    {
        return AbusiveReport::query()
            ->with('user')
            ->latest('id')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): AbusiveReport
    {
        return AbusiveReport::query()
            ->with('user')
            ->findOrFail($id);
    }

    /**
     * @return LengthAwarePaginator<int, AbusiveReport>
     */
    public function getUserReports(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return AbusiveReport::query()
            ->with('user')
            ->where('user_id', $userId)
            ->latest('id')
            ->paginate($perPage);
    }
}
