<?php

declare(strict_types=1);

namespace App\Modules\Message\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Participant;
use App\Models\User;
use App\Modules\Message\Actions\CreateMessageAction;
use App\Modules\Message\DTO\MessageData;
use App\Modules\Message\Events\MessageSent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageService
{
    public function __construct(private CreateMessageAction $createMessage) {}

    /**
     * Check if user has access to conversation.
     */
    public function hasAccess(User $user, Conversation $conversation): bool
    {
        // Customer yang memulai percakapan
        if ($user->id === $conversation->user_id) {
            return true;
        }

        // Store owner atau staff
        $shopIds = $user->shops()->pluck('id')->toArray();
        if (in_array($conversation->shop_id, $shopIds)) {
            return true;
        }

        // Staff dengan managed shop
        if ($user->shop_id && $user->shop_id === $conversation->shop_id) {
            return true;
        }

        return false;
    }

    /**
     * Get messages for a conversation with pagination.
     *
     * @return HasMany<Message, Conversation>
     */
    public function getMessages(Conversation $conversation, int $perPage = 15): HasMany
    {
        return $conversation->messages()
            ->with(['user'])
            ->orderBy('id', 'desc');
    }

    /**
     * Mark messages as seen (update participant).
     */
    public function markAsSeen(Conversation $conversation, User $user): int
    {
        $updated = 0;

        // If user is customer
        $participant = Participant::where('conversation_id', $conversation->id)
            ->whereNull('last_read')
            ->where('user_id', $user->id)
            ->where('type', 'user')
            ->first();

        if ($participant) {
            $participant->update(['last_read' => Carbon::now()]);
            $updated++;
        }

        // If user is shop owner or staff
        $shopIds = $user->shops()->pluck('id')->toArray();
        if (in_array($conversation->shop_id, $shopIds) || $user->shop_id === $conversation->shop_id) {
            $participant = Participant::where('conversation_id', $conversation->id)
                ->whereNull('last_read')
                ->where('shop_id', $conversation->shop_id)
                ->where('type', 'shop')
                ->first();

            if ($participant) {
                $participant->update(['last_read' => Carbon::now()]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Store a new message.
     *
     * @throws \Exception
     */
    public function storeMessage(Conversation $conversation, MessageData $data, User $user): Message
    {
        // Determine recipient type
        $type = '';
        if ($user->id === $conversation->user_id) {
            $type = 'shop'; // customer sends to shop
        } elseif (in_array($conversation->shop_id, $user->shops()->pluck('id')->toArray()) || $user->shop_id === $conversation->shop_id) {
            $type = 'user'; // shop sends to customer
        } else {
            $notice = config('notice.NOT_AUTHORIZED');
            assert(is_string($notice));
            throw new \Exception($notice);
        }

        $message = $this->createMessage->execute($data);
        $conversation->update(['updated_at' => now()]);

        event(new MessageSent($message, $conversation, $type, $user));

        return $message;
    }

    /**
     * Get conversation by id with access check.
     */
    public function getConversationForUser(int $conversationId, User $user): Conversation
    {
        $shopIds = $user->shops()->pluck('id')->toArray();

        return Conversation::where('id', $conversationId)
            ->where(function ($q) use ($user, $shopIds) {
                $q->where('user_id', $user->id)
                    ->orWhereIn('shop_id', $shopIds)
                    ->orWhere('shop_id', $user->shop_id);
            })
            ->firstOrFail();
    }
}
