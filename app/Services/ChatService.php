<?php

namespace App\Services;

use App\Models\Message;
use App\Repositories\MessageRepository;
use App\Repositories\GroupRepository;
use App\Repositories\FriendRepository;

/**
 * ChatService — gửi/sửa/xóa tin nhắn (DM + Group)
 */
class ChatService
{
    public function __construct(
        private MessageRepository $messages,
        private GroupRepository   $groups,
        private FriendRepository  $friends,
    ) {}

    /* ─── Direct Message ─── */

    /**
     * Gửi tin nhắn 1-1
     * @throws \RuntimeException nếu bị block hoặc không phải bạn bè
     */
    public function sendDirectMessage(
        string $senderId,
        string $receiverId,
        string $content,
        string $type     = 'text',
        string $replyTo  = ''
    ): Message {
        if ($this->friends->isBlockedEither($senderId, $receiverId)) {
            throw new \RuntimeException('Không thể gửi tin nhắn: người dùng đã bị chặn.');
        }

        $conv = $this->messages->findOrCreateConversation($senderId, $receiverId);

        return $this->messages->createDirectMessage([
            'conv_id'   => $conv->conv_id,
            'sender_id' => $senderId,
            'type'      => $type,
            'content'   => trim($content),
            'reply_to'  => $replyTo,
        ]);
    }

    /* ─── Group Message ─── */

    /**
     * Gửi tin nhắn vào nhóm
     * @throws \RuntimeException nếu không phải thành viên nhóm
     */
    public function sendGroupMessage(
        string $senderId,
        string $groupId,
        string $content,
        string $type    = 'text',
        string $replyTo = ''
    ): Message {
        if (!$this->groups->isMember($groupId, $senderId)) {
            throw new \RuntimeException('Bạn không phải thành viên của nhóm này.');
        }

        return $this->groups->createMessage([
            'conv_id'   => $groupId,
            'sender_id' => $senderId,
            'type'      => $type,
            'content'   => trim($content),
            'reply_to'  => $replyTo,
        ]);
    }

    /* ─── Edit / Delete ─── */

    /**
     * Sửa tin nhắn — chỉ người gửi mới được sửa
     * @throws \RuntimeException
     */
    public function editMessage(string $userId, string $msgId, string $newContent): bool
    {
        $msg = $this->messages->findMessageById($msgId);
        if (!$msg) throw new \RuntimeException('Tin nhắn không tồn tại.');
        if ($msg->sender_id !== $userId) throw new \RuntimeException('Bạn không có quyền sửa tin nhắn này.');
        if ($msg->isDeleted()) throw new \RuntimeException('Không thể sửa tin nhắn đã xóa.');

        return $this->messages->editMessage($msgId, trim($newContent));
    }

    /**
     * Xóa tin nhắn — người gửi hoặc admin nhóm có thể xóa
     * @throws \RuntimeException
     */
    public function deleteMessage(string $userId, string $msgId, bool $isAdmin = false): bool
    {
        $msg = $this->messages->findMessageById($msgId);
        if (!$msg) throw new \RuntimeException('Tin nhắn không tồn tại.');

        $canDelete = $msg->sender_id === $userId || $isAdmin;
        if (!$canDelete) throw new \RuntimeException('Bạn không có quyền xóa tin nhắn này.');

        return $this->messages->deleteMessage($msgId);
    }

    /* ─── Get Messages ─── */

    /** @return Message[] */
    public function getDirectMessages(string $user1Id, string $user2Id, int $page = 1): array
    {
        $conv = $this->messages->findOrCreateConversation($user1Id, $user2Id);
        $msgs = $this->messages->getConversationMessages($conv->conv_id, $page);
        // Đánh dấu đã đọc
        $this->messages->markConversationRead($conv->conv_id, $user1Id);
        return $msgs;
    }

    /** @return Message[] */
    public function getGroupMessages(string $groupId, string $userId, int $page = 1): array
    {
        $msgs = $this->groups->getMessages($groupId, $page);
        // Đánh dấu đã đọc
        $this->messages->markConversationRead($groupId, $userId);
        return $msgs;
    }

    /** Lấy danh sách cuộc trò chuyện gần nhất (DM + Group) */
    public function getUserChatList(string $userId): array
    {
        return $this->messages->getUserConversations($userId);
    }
}
