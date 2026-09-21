<?php

namespace App\Modules\Withdraw\Http\Controllers;

use App\Enums\WithdrawStatus;
use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Models\Withdraw;
use App\Modules\Withdraw\Actions\ApproveWithdrawAction;
use App\Modules\Withdraw\Actions\CreateWithdrawAction;
use App\Modules\Withdraw\Actions\DeleteWithdrawAction;
use App\Modules\Withdraw\DTO\WithdrawData;
use App\Modules\Withdraw\Http\Requests\WithdrawRequest;
use App\Modules\Withdraw\Http\Requests\WithdrawUpdateRequest;
use App\Modules\Withdraw\Http\Resources\WithdrawResource;
use App\Modules\Withdraw\Services\WithdrawQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WithdrawController extends BaseController
{
    public function __construct(
        private readonly WithdrawQueryService $queryService,
        private readonly CreateWithdrawAction $createAction,
        private readonly DeleteWithdrawAction $deleteAction,
        private readonly ApproveWithdrawAction $approveAction,
    ) {}

    /**
     * GET /withdraws
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Withdraw::class);

        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            throw new HttpException(401, 'Unauthorized');
        }

        $limit = is_numeric($request->limit) ? (int) $request->limit : 15;
        $withdraws = $this->queryService->getWithdrawsQuery($request, $user)->paginate($limit);

        return WithdrawResource::collection($withdraws);
    }

    /**
     * POST /withdraws
     */
    public function store(WithdrawRequest $request): WithdrawResource
    {
        $this->authorize('create', Withdraw::class);

        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            throw new HttpException(401, 'Unauthorized');
        }

        $data = WithdrawData::fromRequest($request->validated());
        $withdraw = $this->createAction->execute($data, $user);

        return new WithdrawResource($withdraw);
    }

    /**
     * GET /withdraws/{id}
     */
    public function show(Request $request, int $id): WithdrawResource
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            throw new HttpException(401, 'Unauthorized');
        }

        $withdraw = $this->queryService->findWithdraw($id, $user);
        $this->authorize('view', $withdraw);

        return new WithdrawResource($withdraw);
    }

    /**
     * PUT /withdraws/{id} (not allowed)
     */
    public function update(WithdrawUpdateRequest $request, string $id): WithdrawResource
    {
        $msg = config('notice.ACTION_NOT_VALID');
        throw new HttpException(400, is_string($msg) ? $msg : 'Action not valid');
    }

    /**
     * DELETE /withdraws/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            throw new HttpException(401, 'Unauthorized');
        }

        $withdraw = $this->queryService->findWithdraw($id, $user);
        $this->authorize('delete', $withdraw);

        $this->deleteAction->execute($withdraw, $user);

        return $this->sendSuccess(null, 'Withdraw deleted');
    }

    /**
     * POST /withdraws/approve
     */
    public function approveWithdraw(Request $request): WithdrawResource
    {
        $this->authorize('approve', Withdraw::class);

        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            throw new HttpException(401, 'Unauthorized');
        }

        $values = WithdrawStatus::getValues();
        $stringValues = array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $values);
        $request->validate([
            'id' => 'required|exists:withdraws,id',
            'status' => 'required|string|in:'.implode(',', $stringValues),
        ]);

        $reqId = is_numeric($request->id) ? (int) $request->id : 0;
        $reqStatus = is_string($request->status) ? (string) $request->status : '';

        $withdraw = $this->queryService->findOrFail($reqId);
        $updatedWithdraw = $this->approveAction->execute($withdraw, $reqStatus, $user);

        return new WithdrawResource($updatedWithdraw);
    }
}
