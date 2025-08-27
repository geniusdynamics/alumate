<?php

namespace App\Services;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MessagingService
{
    /**
     * Create a new direct conversation between two users
     */
    public function createDirectConversation(User $user1, User $user2): Conversation
    {
        // Check if conversation already exists
        $existingConversation = $this->findDirectConversation($user1, $user2);
        if ($existingConversation) {
            return $existingConversation;
        }

        return DB::transaction(function () use ($user1, $user2) {
            $conversation = Conversation::create([
                'type' => 'direct',
                'created_by' => $user1->id,
            ]);

            // Add both users as participants
            $conversation->addParticipant($user1, 'participant');
            $conversation->addParticipant($user2, 'participant');

            return $conversation;
        });
    }

    /**
     * Create a group conversation
     */
    public function createGroupConversation(User $creator, array $participantIds, ?string $title = null, ?string $description = null): Conversation
    {
        return DB::transaction(function () use ($creator, $participantIds, $title, $description) {
            $conversation = Conversation::create([
                'type' => 'group',
                'title' => $title,
                'description' => $description,
                'created_by' => $creator->id,
            ]);

            // Add creator as admin
            $conversation->addParticipant($creator, 'admin');

            // Add other participants
            foreach ($participantIds as $participantId) {
                if ($participantId !== $creator->id) {
                    $participant = User::find($participantId);
                    if ($participant) {
                        $conversation->addParticipant($participant, 'participant');
                    }
                }
            }

            return $conversation;
        });
    }

    /**
     * Create a circle-based conversation
     */
    public function createCircleConversation(User $creator, int $circleId, ?string $title = null): Conversation
    {
        return DB::transaction(function () use ($creator, $circleId, $title) {
            $conversation = Conversation::create([
                'type' => 'circle',
                'title' => $title,
                'circle_id' => $circleId,
                'created_by' => $creator->id,
            ]);

            // Add all circle members as participants
            $circle = \App\Models\Circle::find($circleId);
            if ($circle) {
                foreach ($circle->activeMembers as $member) {
                    $conversation->addParticipant($member, 'participant');
                }
            }

            return $conversation;
        });
    }

    /**
     * Send a message in a conversation
     */
    public function sendMessage(User $sender, int $conversationId, string $content, string $type = 'text', ?array $attachments = null, ?int $replyToId = null): Message
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is a participant
        if (! $conversation->hasParticipant($sender)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        return DB::transaction(function () use ($sender, $conversation, $content, $type, $attachments, $replyToId) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $sender->id,
                'content' => $content,
                'type' => $type,
                'attachments' => $attachments,
                'reply_to_id' => $replyToId,
            ]);

            // Update conversation's last message timestamp
            $conversation->updateLastMessageTime();

            // Broadcast the message
            broadcast(new MessageSent($message, $sender));

            return $message;
        });
    }

    /**
     * Mark a message as read by a user
     */
    public function markMessageAsRead(User $user, int $messageId): void
    {
        $message = Message::findOrFail($messageId);

        // Check if user is a participant in the conversation
        if (! $message->conversation->hasParticipant($user)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        // Don't mark own messages as read
        if ($message->user_id === $user->id) {
            return;
        }

        $messageRead = $message->markAsReadBy($user);

        // Update participant's last read timestamp
        $message->conversation->markAsReadForUser($user);

        // Broadcast the read receipt
        broadcast(new MessageRead($message, $user));
    }

    /**
     * Mark all messages in a conversation as read by a user
     */
    public function markConversationAsRead(User $user, int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is a participant
        if (! $conversation->hasParticipant($user)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        DB::transaction(function () use ($user, $conversation) {
            // Get all unread messages in the conversation
            $unreadMessages = $conversation->messages()
                ->where('user_id', '!=', $user->id)
                ->whereDoesntHave('reads', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->get();

            // Mark each message as read
            foreach ($unreadMessages as $message) {
                $message->markAsReadBy($user);
                broadcast(new MessageRead($message, $user));
            }

            // Update participant's last read timestamp
            $conversation->markAsReadForUser($user);
        });
    }

    /**
     * Send typing indicator
     */
    public function sendTypingIndicator(User $user, int $conversationId, bool $isTyping = true): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is a participant
        if (! $conversation->hasParticipant($user)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        broadcast(new UserTyping($user, $conversationId, $isTyping));
    }

    /**
     * Get conversations for a user
     */
    public function getUserConversations(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return Conversation::forUser($user)
            ->with(['participants', 'latestMessage.user'])
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get messages in a conversation
     */
    public function getConversationMessages(User $user, int $conversationId, int $perPage = 50): LengthAwarePaginator
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is a participant
        if (! $conversation->hasParticipant($user)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        return $conversation->messages()
            ->with(['user', 'replyTo.user', 'reads.user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Search messages
     */
    public function searchMessages(User $user, string $query, ?int $conversationId = null, int $perPage = 20): LengthAwarePaginator
    {
        $messagesQuery = Message::whereHas('conversation', function ($q) use ($user) {
            $q->whereHas('participants', function ($participantQuery) use ($user) {
                $participantQuery->where('user_id', $user->id);
            });
        })
            ->where('content', 'LIKE', "%{$query}%")
            ->with(['user', 'conversation']);

        if ($conversationId) {
            $messagesQuery->where('conversation_id', $conversationId);
        }

        return $messagesQuery->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Find direct conversation between two users
     */
    public function findDirectConversation(User $user1, User $user2): ?Conversation
    {
        return Conversation::direct()
            ->whereHas('participants', function ($query) use ($user1) {
                $query->where('user_id', $user1->id);
            })
            ->whereHas('participants', function ($query) use ($user2) {
                $query->where('user_id', $user2->id);
            })
            ->first();
    }

    /**
     * Add participant to conversation
     */
    public function addParticipant(User $admin, int $conversationId, int $userId, string $role = 'participant'): ConversationParticipant
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check if admin has permission to add participants
        $adminParticipant = $conversation->participantDetails()
            ->where('user_id', $admin->id)
            ->first();

        if (! $adminParticipant || ! in_array($adminParticipant->role, ['admin', 'moderator'])) {
            throw new \Exception('User does not have permission to add participants');
        }

        $user = User::findOrFail($userId);

        return $conversation->addParticipant($user, $role);
    }

    /**
     * Remove participant from conversation
     */
    public function removeParticipant(User $admin, int $conversationId, int $userId): bool
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check if admin has permission to remove participants
        $adminParticipant = $conversation->participantDetails()
            ->where('user_id', $admin->id)
            ->first();

        if (! $adminParticipant || ! in_array($adminParticipant->role, ['admin', 'moderator'])) {
            throw new \Exception('User does not have permission to remove participants');
        }

        $user = User::findOrFail($userId);

        return $conversation->removeParticipant($user);
    }

    /**
     * Leave conversation
     */
    public function leaveConversation(User $user, int $conversationId): bool
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Can't leave direct conversations
        if ($conversation->type === 'direct') {
            throw new \Exception('Cannot leave direct conversations');
        }

        return $conversation->removeParticipant($user);
    }

    /**
     * Archive conversation for user
     */
    public function archiveConversation(User $user, int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is a participant
        if (! $conversation->hasParticipant($user)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        // Update participant settings to mark as archived
        $participant = $conversation->participantDetails()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->setSetting('archived', true);
        }
    }

    /**
     * Mute/unmute conversation for user
     */
    public function toggleMuteConversation(User $user, int $conversationId): bool
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is a participant
        if (! $conversation->hasParticipant($user)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        $participant = $conversation->participantDetails()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            if ($participant->is_muted) {
                $participant->unmute();

                return false; // Now unmuted
            } else {
                $participant->mute();

                return true; // Now muted
            }
        }

        return false;
    }

    /**
     * Pin/unpin conversation for user
     */
    public function togglePinConversation(User $user, int $conversationId): bool
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is a participant
        if (! $conversation->hasParticipant($user)) {
            throw new \Exception('User is not a participant in this conversation');
        }

        $participant = $conversation->participantDetails()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            if ($participant->is_pinned) {
                $participant->unpin();

                return false; // Now unpinned
            } else {
                $participant->pin();

                return true; // Now pinned
            }
        }

        return false;
    }

    /**
     * Get unread message count for user
     */
    public function getUnreadCount(User $user): int
    {
        return Conversation::forUser($user)
            ->get()
            ->sum(function ($conversation) use ($user) {
                return $conversation->getUnreadCountForUser($user);
            });
    }

    /**
     * Delete message (soft delete)
     */
    public function deleteMessage(User $user, int $messageId): bool
    {
        $message = Message::findOrFail($messageId);

        // Check if user owns the message or is admin/moderator
        if ($message->user_id !== $user->id) {
            $participant = $message->conversation->participantDetails()
                ->where('user_id', $user->id)
                ->first();

            if (! $participant || ! in_array($participant->role, ['admin', 'moderator'])) {
                throw new \Exception('User does not have permission to delete this message');
            }
        }

        return $message->delete();
    }

    /**
     * Edit message
     */
    public function editMessage(User $user, int $messageId, string $newContent): Message
    {
        $message = Message::findOrFail($messageId);

        // Check if user owns the message
        if ($message->user_id !== $user->id) {
            throw new \Exception('User can only edit their own messages');
        }

        // Check if message is not too old (e.g., 24 hours)
        if ($message->created_at->diffInHours(now()) > 24) {
            throw new \Exception('Message is too old to edit');
        }

        $message->editContent($newContent);

        return $message->fresh();
    }
}
