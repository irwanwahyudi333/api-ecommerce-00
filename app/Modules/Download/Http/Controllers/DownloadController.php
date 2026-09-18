<?php

declare(strict_types=1);

namespace App\Modules\Download\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\DigitalFile;
use App\Models\DownloadToken;
use App\Models\OrderedFile;
use App\Models\Product;
use App\Models\Variation;
use App\Modules\Download\Actions\GenerateDownloadTokenAction;
use App\Modules\Download\Actions\GetFileByTokenAction;
use App\Modules\Download\Http\Requests\GenerateDownloadUrlRequest;
use App\Modules\Download\Http\Resources\DownloadableFileResource;
use App\Modules\Download\Services\DownloadQueryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DownloadController extends BaseController
{
    public function __construct(
        private DownloadQueryService $downloadQueryService,
        private GenerateDownloadTokenAction $generateDownloadTokenAction,
        private GetFileByTokenAction $getFileByTokenAction
    ) {}

    public function fetchDownloadableFiles(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $this->authorize('viewAny', OrderedFile::class);

        $limit = is_numeric($request->limit) ? (int) $request->limit : 15;

        /** @var Builder<OrderedFile> $query */
        $query = $this->downloadQueryService->getDownloadableFilesQuery($user);

        // Load morph relations: file.fileable (product/variation with shop)
        $query->with(['file.fileable' => function ($morphTo) {
            /** @var MorphTo<Model, DigitalFile> $morphTo */
            $morphTo->morphWith([
                Product::class => ['shop'],
                Variation::class => ['product'],
            ]);
        }]);

        $files = $query->paginate($limit);

        return DownloadableFileResource::collection($files);
    }

    public function generateDownloadableUrl(GenerateDownloadUrlRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $digitalFileId = is_numeric($request->digital_file_id) ? (int) $request->digital_file_id : 0;

        $digitalFile = DigitalFile::findOrFail($digitalFileId);
        $this->authorize('download', $digitalFile);

        /** @var DownloadToken $token */
        $token = $this->generateDownloadTokenAction->execute($digitalFileId, $user->id);

        return response()->json([
            'url' => route('download_url.token', ['token' => $token->token]),
        ]);
    }

    public function downloadFile(string $token): mixed
    {
        $digitalFile = $this->getFileByTokenAction->execute($token);
        if (! $digitalFile) {
            $msg1 = config('notice.TOKEN_NOT_FOUND', 'Token not found');
            throw new HttpException(404, is_string($msg1) ? $msg1 : 'Token not found');
        }

        $attachmentId = (int) $digitalFile->attachment_id;
        $mediaItem = $this->downloadQueryService->getMediaItem($attachmentId);
        if (! $mediaItem) {
            $msg2 = config('notice.NOT_FOUND', 'File not found');
            throw new HttpException(404, is_string($msg2) ? $msg2 : 'File not found');
        }

        // Return file download response (Spatie MediaLibrary)
        return $mediaItem;
    }
}
