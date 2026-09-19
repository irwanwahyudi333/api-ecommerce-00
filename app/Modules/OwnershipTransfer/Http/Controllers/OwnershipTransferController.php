<?php

declare(strict_types=1);

namespace App\Modules\OwnershipTransfer\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\OwnershipTransfer;
use App\Models\User;
use App\Modules\OwnershipTransfer\DTO\OwnershipTransferData;
use App\Modules\OwnershipTransfer\Http\Requests\OwnershipTransferRequest;
use App\Modules\OwnershipTransfer\Http\Resources\OwnershipTransferResource;
use App\Modules\OwnershipTransfer\Services\OwnershipTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OwnershipTransferController extends BaseController
{
    public function __construct(private OwnershipTransferService $transferService) {}

    /**
     * GET /ownership-transfers
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('viewAny', OwnershipTransfer::class);

        $limitValue = $request->input('limit', 15);
        $limit = is_numeric($limitValue) ? (int) $limitValue : 15;
        $histories = $this->transferService->getTransferHistoriesQuery($request, $user)->paginate($limit);

        return OwnershipTransferResource::collection($histories);
    }

    /**
     * POST /ownership-transfers
     */
    public function store(OwnershipTransferRequest $request): OwnershipTransferResource
    {
        $this->authorize('create', OwnershipTransfer::class);

        /** @var User $user */
        $user = $request->user();

        $data = OwnershipTransferData::fromRequest($request->validated(), $user->id);
        $transfer = $this->transferService->createTransfer($data);

        return new OwnershipTransferResource($transfer);
    }

    /**
     * GET /ownership-transfers/{transaction_identifier}
     */
    public function show(Request $request, string $transaction_identifier): OwnershipTransferResource
    {
        $viewType = is_string($request->request_view_type) ? $request->request_view_type : null;
        $transfer = $this->transferService->getTransferDetail($transaction_identifier, $viewType);
        $this->authorize('view', $transfer);

        return new OwnershipTransferResource($transfer);
    }

    /**
     * PUT /ownership-transfers/{id}
     */
    public function update(Request $request, int $id): OwnershipTransferResource
    {
        /** @var OwnershipTransfer $transfer */
        $transfer = OwnershipTransfer::findOrFail($id);
        $this->authorize('update', $transfer);

        $request->validate([
            'status' => 'required|string|in:pending,approved,rejected',
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var string $status */
        $status = $request->status;
        $updated = $this->transferService->updateTransferStatus($id, $status, $user);

        return new OwnershipTransferResource($updated);
    }

    /**
     * DELETE /ownership-transfers/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $transfer = OwnershipTransfer::findOrFail($id);
        $this->authorize('delete', $transfer);

        /** @var User $user */
        $user = $request->user();
        $this->transferService->deleteTransfer($id, $user);

        return $this->sendSuccess(null, 'Transfer record deleted successfully');
    }
}
