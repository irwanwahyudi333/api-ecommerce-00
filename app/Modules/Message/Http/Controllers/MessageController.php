<?php

declare(strict_types=1);

namespace App\Modules\Message\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Modules\Message\DTO\MessageData;
use App\Modules\Message\Http\Requests\MessageCreateRequest;
use App\Modules\Message\Http\Resources\MessageResource;
use App\Modules\Message\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MessageController extends BaseController
{
    public function __construct(private MessageService $messageService) {}

    /**
     * GET /conversations/{conversation_id}/messages
     */
    public function index(Request $request, int $conversation_id): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $conversation = $this->messageService->getConversationForUser($conversation_id, $user);
        $this->authorize('viewAny', [Message::class, $conversation]);

        $limitInput = $request->input('limit', 15);
        assert(is_numeric($limitInput));
        $limit = (int) $limitInput;
        $messages = $this->messageService->getMessages($conversation, $limit)->paginate($limit);

        return MessageResource::collection($messages);
    }

    /**
     * POST /conversations/{conversation_id}/messages
     */
    public function store(MessageCreateRequest $request, int $conversation_id): MessageResource
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Conversation $conversation */
        $conversation = Conversation::findOrFail($conversation_id);
        $this->authorize('create', [Message::class, $conversation]);

        $data = MessageData::fromRequest($request->validated(), $conversation_id, $user->id);
        $message = $this->messageService->storeMessage($conversation, $data, $user);

        return new MessageResource($message);
    }

    /**
     * PUT /conversations/{conversation_id}/seen
     */
    public function seenMessage(Request $request): JsonResponse
    {
        $request->validate(['conversation_id' => 'required|exists:conversations,id']);

        $conversationId = $request->input('conversation_id');
        /** @var Conversation $conversation */
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('markAsSeen', [Message::class, $conversation]);

        /** @var User $user */
        $user = $request->user();
        $updated = $this->messageService->markAsSeen($conversation, $user);

        return response()->json(['updated' => $updated]);
    }
}
