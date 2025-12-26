<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    public function createConversation(User $user1, User $user2, ?string $subject = null): Conversation
    {
        return DB::transaction(function () use ($user1, $user2, $subject) {
            $conversation = Conversation::create([
                'subject' => $subject ?? "{$user1->username}, {$user2->username}",
            ]);

            $conversation->participants()->attach([$user1->id, $user2->id]);

            return $conversation;
        });
    }

    public function getOrCreateConversation(User $user1, User $user2): Conversation
    {
        $existing = $this->findExistingConversation($user1, $user2);
        
        if ($existing) {
            return $existing;
        }

        return $this->createConversation($user1, $user2);
    }

    public function findExistingConversation(User $user1, User $user2): ?Conversation
    {
        $user1ConversationIds = $user1->conversations()
            ->whereHas('participants', fn($q) => $q->where('user_id', $user2->id))
            ->pluck('conversations.id');

        return Conversation::whereIn('id', $user1ConversationIds)
            ->whereHas('participants', fn($q) => $q->where('user_id', $user2->id))
            ->first();
    }

    public function getUserConversations(User $user, int $limit = 20, ?int $afterId = null): Collection
    {
        $query = Conversation::query()
            ->with(['participants' => fn($q) => $q->where('user_id', '!=', $user->id)])
            ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->whereNull('conversation_participants.left_at');

        if ($afterId) {
            $conversation = Conversation::find($afterId);
            if ($conversation) {
                $query->where('last_message_at', '<', $conversation->last_message_at);
            }
        }

        return $query->orderBy('last_message_at', 'desc')->limit($limit)->get();
    }

    public function getConversation(Conversation $conversation, User $user): Conversation
    {
        if (!$conversation->isParticipant($user->id)) {
            throw new \UnauthorizedException('You are not a participant in this conversation');
        }

        return $conversation->load(['participants', 'lastMessage']);
    }

    public function sendMessage(Conversation $conversation, User $sender, string $content, string $type = 'text', array $attachments = []): Message
    {
        if (!$conversation->isParticipant($sender->id)) {
            throw new \UnauthorizedException('You are not a participant in this conversation');
        }

        return DB::transaction(function () use ($conversation, $sender, $content, $type, $attachments) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $sender->id,
                'content' => $content,
                'type' => $type,
                'attachments' => $attachments,
            ]);

            $conversation->update([
                'last_message_preview' => \Str::limit($content, 100),
                'last_message_at' => now(),
            ]);

            return $message;
        });
    }

    public function startNewConversation(User $sender, User $recipient, string $initialMessage): Message
    {
        $conversation = $this->getOrCreateConversation($sender, $recipient);
        
        return $this->sendMessage($conversation, $sender, $initialMessage);
    }

    public function getMessages(Conversation $conversation, User $user, int $limit = 50, ?int $beforeId = null): Collection
    {
        if (!$conversation->isParticipant($user->id)) {
            throw new \UnauthorizedException('You are not a participant in this conversation');
        }

        $query = $conversation->messages()
            ->with('user:id,uuid,username,full_name,profile_image_url')
            ->where('conversation_id', $conversation->id);

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        return $query->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    public function markConversationAsRead(Conversation $conversation, User $user): void
    {
        if (!$conversation->isParticipant($user->id)) {
            throw new \UnauthorizedException('You are not a participant in this conversation');
        }

        $conversation->markAsReadForUser($user);

        $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markMessageAsRead(Message $message, User $user): void
    {
        if ($message->conversation->isParticipant($user->id)) {
            $message->markAsRead();
        }
    }

    public function leaveConversation(Conversation $conversation, User $user): void
    {
        if (!$conversation->isParticipant($user->id)) {
            throw new \UnauthorizedException('You are not a participant in this conversation');
        }

        $conversation->removeParticipant($user);
    }

    public function addParticipant(Conversation $conversation, User $user): void
    {
        $conversation->addParticipant($user);
    }

    public function getTotalUnreadCount(User $user): int
    {
        $total = 0;

        $conversations = $user->conversations()
            ->whereNull('conversation_participants.left_at')
            ->get();

        foreach ($conversations as $conversation) {
            $total += $conversation->getUnreadCountForUser($user->id);
        }

        return $total;
    }

    public function searchMessages(User $user, string $query, int $limit = 20): Collection
    {
        return Message::query()
            ->with(['conversation.participants' => fn($q) => $q->where('user_id', $user->id)])
            ->whereHas('conversation.participants', fn($q) => $q->where('user_id', $user->id))
            ->where('content', 'like', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function deleteMessage(Message $message, User $user): void
    {
        if ($message->user_id !== $user->id) {
            throw new \UnauthorizedException('You can only delete your own messages');
        }

        $message->delete();
    }

    public function getMessagePreview(Message $message): string
    {
        return match ($message->type) {
            'text' => \Str::limit($message->content, 100),
            'image' => 'Sent an image',
            'file' => 'Sent a file',
            default => 'Sent a message',
        };
    }
}
