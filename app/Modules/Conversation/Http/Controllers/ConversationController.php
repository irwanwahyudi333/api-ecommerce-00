<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Conversation;
use App\Models\Shop;
use App\Models\User;
use App\Modules\Conversation\Actions\CreateConversationAction;
use App\Modules\Conversation\Http\Requests\ConversationCreateRequest;
use App\Modules\Conversation\Http\Resources\ConversationResource;
use App\Modules\Conversation\Services\ConversationQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConversationController extends BaseController
{
    public function __construct(
        private readonly ConversationQueryService $conversationQueryService,
        private readonly CreateConversationAction $createConversationAction
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Conversation::class);

        /** @var User $user */
        $user = $request->user();
        $limitInput = $request->input('limit', 15);
        $limit = is_numeric($limitInput) ? (int) $limitInput : 15;

        $conversations = $this->conversationQueryService->getUserConversations($user)->paginate($limit);

        return ConversationResource::collection($conversations);
    }

    public function show(Request $request, int $conversation_id): ConversationResource
    {
        /** @var User $user */
        $user = $request->user();
        $conversation = $this->conversationQueryService->findConversationById($conversation_id);
        $this->authorize('view', $conversation);

        return new ConversationResource($conversation);
    }

    public function store(ConversationCreateRequest $request): ConversationResource
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('create', [Conversation::class, $request->shop_id]);

        // Cek apakah user mencoba chat dengan tokonya sendiri
        /** @var Shop $shop */
        $shop = Shop::findOrFail($request->shop_id);
        if ($shop->owner_id === $user->id || ($user->shop_id && $user->shop_id === $shop->id)) {
            $message = config('notice.YOU_CAN_NOT_SEND_MESSAGE_TO_YOUR_OWN_SHOP');
            throw new \Exception(is_string($message) ? $message : 'You cannot send a message to your own shop.');
        }

        $shopIdInput = $request->shop_id;
        $shopId = is_numeric($shopIdInput) ? (int) $shopIdInput : 0;
        $conversation = $this->createConversationAction->execute($user->id, $shopId);

        return new ConversationResource($conversation);
    }
}
