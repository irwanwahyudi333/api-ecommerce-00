<?php

namespace App\Modules\Terms\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\TermsAndConditions;
use App\Modules\Terms\Actions\ApproveTermAction;
use App\Modules\Terms\Actions\CreateTermAction;
use App\Modules\Terms\Actions\DeleteTermAction;
use App\Modules\Terms\Actions\DisapproveTermAction;
use App\Modules\Terms\Actions\UpdateTermAction;
use App\Modules\Terms\DTO\TermsData;
use App\Modules\Terms\Http\Requests\TermsAndConditionsCreateRequest;
use App\Modules\Terms\Http\Requests\TermsAndConditionsUpdateRequest;
use App\Modules\Terms\Http\Resources\TermsConditionResource;
use App\Modules\Terms\Services\TermsQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TermsAndConditionsController extends BaseController
{
    public function __construct(
        private readonly TermsQueryService $queryService,
        private readonly CreateTermAction $createAction,
        private readonly UpdateTermAction $updateAction,
        private readonly DeleteTermAction $deleteAction,
        private readonly ApproveTermAction $approveAction,
        private readonly DisapproveTermAction $disapproveAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $terms = $this->queryService->getTermsQuery($request, $request->user());

        return $this->sendPaginated(
            $terms,
            TermsConditionResource::collection($terms->getCollection()),
            'Daftar terms berhasil diambil.'
        );
    }

    public function store(TermsAndConditionsCreateRequest $request): JsonResponse
    {
        $this->authorize('create', TermsAndConditions::class);

        $userId = $request->user()?->id;
        $data = TermsData::fromRequest($request->validated(), $userId);
        $term = $this->createAction->execute($data);

        return $this->sendSuccess(new TermsConditionResource($term), 'Terms created', 201);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $defaultLang = config('shop.default_language', 'id');
        $defaultLang = is_scalar($defaultLang) ? (string) $defaultLang : 'id';
        $language = isset($request->language) && is_scalar($request->language) ? (string) $request->language : $defaultLang;
        $term = $this->queryService->find($slug, $language);

        return $this->sendSuccess(new TermsConditionResource($term), 'Terms detail');
    }

    public function update(TermsAndConditionsUpdateRequest $request, string $id): JsonResponse
    {
        $term = $this->queryService->findOrFail((int) $id);
        $this->authorize('update', $term);

        $data = TermsData::fromRequest($request->validated());
        $updated = $this->updateAction->execute($term, $data);

        return $this->sendSuccess(new TermsConditionResource($updated), 'Terms updated');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $term = $this->queryService->findOrFail((int) $id);
        $this->authorize('delete', $term);

        $this->deleteAction->execute($term);

        return $this->sendSuccess(null, 'Terms deleted');
    }

    public function approveTerm(Request $request): JsonResponse
    {
        $id = is_scalar($request->id) ? (int) $request->id : 0;
        $term = $this->queryService->findOrFail($id);
        $this->authorize('approve', $term);

        $this->approveAction->execute($term);

        return $this->sendSuccess(new TermsConditionResource($term), 'Term approved');
    }

    public function disApproveTerm(Request $request): JsonResponse
    {
        $id = is_scalar($request->id) ? (int) $request->id : 0;
        $term = $this->queryService->findOrFail($id);
        $this->authorize('disapprove', $term);

        $this->disapproveAction->execute($term);

        return $this->sendSuccess(new TermsConditionResource($term), 'Term disapproved');
    }
}
