<?php

namespace App\Modules\Type\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Type;
use App\Modules\Type\Actions\CreateTypeAction;
use App\Modules\Type\Actions\DeleteTypeAction;
use App\Modules\Type\Actions\UpdateTypeAction;
use App\Modules\Type\DTO\TypeData;
use App\Modules\Type\Http\Requests\TypeRequest;
use App\Modules\Type\Http\Resources\TypeResource;
use App\Modules\Type\Services\TypeQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TypeController extends BaseController
{
    public function __construct(
        private readonly TypeQueryService $queryService,
        private readonly CreateTypeAction $createAction,
        private readonly UpdateTypeAction $updateAction,
        private readonly DeleteTypeAction $deleteAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var string $defaultLanguage */
        $defaultLanguage = config('shop.default_language', 'id');
        $language = is_string($request->language) ? $request->language : $defaultLanguage;
        $limit = is_numeric($request->limit) ? (int) $request->limit : 15;
        $types = $this->queryService->getTypesByLanguage($language, $limit);

        /** @var LengthAwarePaginator<int, Type> $paginator */
        $paginator = $types;

        return $this->sendPaginated(
            $paginator,
            TypeResource::collection($paginator->getCollection()),
            'Daftar type berhasil diambil.'
        );
    }

    public function store(TypeRequest $request): JsonResponse
    {
        $this->authorize('create', Type::class);

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $data = TypeData::fromRequest($validated);
        $type = $this->createAction->execute($data);

        return $this->sendSuccess(new TypeResource($type->load('banners')), 'Type created', 201);
    }

    public function show(Request $request, string $params): JsonResponse
    {
        /** @var string $defaultLanguage */
        $defaultLanguage = config('shop.default_language', 'id');
        $language = is_string($request->language) ? $request->language : $defaultLanguage;
        $type = $this->queryService->getTypeByIdOrSlug($params, $language);

        return $this->sendSuccess(new TypeResource($type), 'Type detail');
    }

    public function update(TypeRequest $request, int $id): JsonResponse
    {
        $type = $this->queryService->findOrFail($id);
        $this->authorize('update', $type);

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $data = TypeData::fromRequest($validated);
        $updated = $this->updateAction->execute($type, $data);

        return $this->sendSuccess(new TypeResource($updated), 'Type updated');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $type = $this->queryService->findOrFail($id);
        $this->authorize('delete', $type);

        $this->deleteAction->execute($type);

        return $this->sendSuccess(null, 'Type deleted');
    }
}
