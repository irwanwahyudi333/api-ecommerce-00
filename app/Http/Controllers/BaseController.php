<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseController extends Controller
{
    /**
     * Success response tanpa paginasi
     */
    protected function sendSuccess(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200
    ): JsonResponse {
        return response()->json([
            'status' => 'SUCCESS',
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->format('Y-m-d\TH:i:s.u\Z'),
        ], $code);
    }

    /**
     * Error response
     */
    protected function sendError(
        string $message,
        int $code = 400,
        mixed $errors = null,
        ?string $errorCode = null
    ): JsonResponse {
        $response = [
            'status' => 'ERROR',
            'code' => $code,
            'error_code' => $errorCode ?? 'API-ERR-'.$code,
            'message' => $message,
            'path' => '/'.ltrim(request()->path(), '/'),
            'timestamp' => now()->format('Y-m-d\TH:i:s.u\Z'),
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Response dengan paginasi
     */
    protected function sendPaginated(
        LengthAwarePaginator $paginator,
        mixed $data,
        string $message = 'Data berhasil diambil.',
        int $code = 200
    ): JsonResponse {
        return response()->json([
            'status' => 'SUCCESS',
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'totalPages' => $paginator->lastPage(),
                'totalItems' => $paginator->total(),
                'itemsPerPage' => $paginator->perPage(),
            ],
            'timestamp' => now()->format('Y-m-d\TH:i:s.u\Z'),
        ], $code);
    }
}
