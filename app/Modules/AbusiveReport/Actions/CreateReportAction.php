<?php

declare(strict_types=1);

namespace App\Modules\AbusiveReport\Actions;

use App\Models\AbusiveReport;
use App\Modules\AbusiveReport\DTO\AbusiveReportData;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use RuntimeException;

class CreateReportAction
{
    public function execute(AbusiveReportData $data, Authenticatable $user): AbusiveReport
    {
        $modelClass = $data->getModelClass();
        // Pastikan model yang dilaporkan ada
        $model = $modelClass::findOrFail($data->model_id);

        try {
            /** @var AbusiveReport $report */
            $report = $model->abusive_reports()->create($data->toArray());

            return $report;
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                $msg = config('notice.YOU_HAVE_ALREADY_GIVEN_ABUSIVE_REPORT_FOR_THIS', 'Anda sudah melaporkan ini sebelumnya.');
                throw new RuntimeException(is_string($msg) ? $msg : 'Anda sudah melaporkan ini sebelumnya.');
            }
            throw $e;
        }
    }
}
