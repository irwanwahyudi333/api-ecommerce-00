<?php

declare(strict_types=1);

namespace App\Modules\Refund\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\RefundPolicy;
use App\Models\User;
use App\Modules\Refund\DTO\RefundPolicyData;
use App\Modules\Refund\Http\Requests\RefundPolicyStoreRequest;
use App\Modules\Refund\Http\Requests\RefundPolicyUpdateRequest;
use App\Modules\Refund\Http\Resources\RefundPolicyResource;
use App\Modules\Refund\Services\RefundPolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefundPolicyController extends BaseController
{
    public function __construct(private RefundPolicyService $policyService) {}

    public function index(Request $request): JsonResponse
    {
        $rawLimit = $request->get('limit', 15);
        $limit = is_numeric($rawLimit) ? (int) $rawLimit : 15;

        /** @var User|null $user */
        $user = $request->user();
        $policies = $this->policyService->getPoliciesQuery($request, $user)->paginate($limit);

        return $this->sendPaginated(
            $policies,
            RefundPolicyResource::collection($policies->getCollection()),
            'Refund policies retrieved successfully'
        );
    }

    public function store(RefundPolicyStoreRequest $request): JsonResponse
    {
        $this->authorize('create', RefundPolicy::class);

        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $data = RefundPolicyData::fromRequest($request);
        $policy = $this->policyService->createPolicy($data, $user);

        return $this->sendSuccess(
            new RefundPolicyResource($policy),
            'Refund policy created successfully',
            201
        );
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $defaultLang = config('shop.default_language', 'id');
        $rawLanguage = $request->get('language', $defaultLang);
        $language = is_string($rawLanguage) ? $rawLanguage : 'id';
        $policy = $this->policyService->findPolicy($slug, $language);

        return $this->sendSuccess(
            new RefundPolicyResource($policy),
            'Refund policy retrieved successfully'
        );
    }

    public function update(RefundPolicyUpdateRequest $request, int $id): JsonResponse
    {
        $policy = RefundPolicy::findOrFail($id);
        $this->authorize('update', $policy);

        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $data = RefundPolicyData::fromRequest($request);
        $updated = $this->policyService->updatePolicy($policy, $data, $user);

        return $this->sendSuccess(
            new RefundPolicyResource($updated),
            'Refund policy updated successfully'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $policy = RefundPolicy::findOrFail($id);
        $this->authorize('delete', $policy);

        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $this->policyService->deletePolicy($policy, $user);

        return $this->sendSuccess(
            null,
            'Refund policy deleted successfully'
        );
    }
}
