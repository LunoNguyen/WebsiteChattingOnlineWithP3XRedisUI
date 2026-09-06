<?php

namespace App\Repositories;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * MessageRepository
 *
 * Key patterns:
 *   msg:{msg_id}             — Hash  — nội dung tin nhắn
 *   conv:{id}                — Hash  — metadata cuộc trò chuyện DM
 *   conv:idx:{u1}:{u2}       — String — index tra nhanh conv từ cặp user
 *   conv:{id}:msgs           — ZSet  — timeline tin nhắn DM (score = timestamp)
 *   user:{id}:convs          — ZSet  — danh sách chat của user (score = last msg time)
 *   user:{id}:unread         — Hash  — số tin chưa đọc theo conv
 */
class MessageRepository
{
    /* ─── Key generators ─── */
    private function msgKey(string $id): string           { return "msg:{$id}"; }
    private function convKey(string $id): string          { return "conv:{$id}"; }
    private function convIdxKey(string $u1, string $u2): string
    {
        // Sắp xếp để đảm bảo nhất quán: nhỏ hơn trước
        $sorted = [$u1, $u2];
        sort($sorted);
        return "conv:idx:{$sorted[0]}:{$sorted[1]}";
    }
    private function convMsgsKey(string $convId): string  { return "conv:{$convId}:msgs"; }
    private function userConvsKey(string $uid): string    { return "user:{$uid}:convs"; }
    private function unreadKey(string $uid): string       { return "user:{$uid}:unread"; }

    /* ─────────── CONVERSATION ─────────── */

    /** Tìm hoặc tạo cuộc trò chuyện DM giữa 2 user */
    public function findOrCreateConversation(string $user1Id, string $user2Id): Conversation
    {
        $idxKey = $this->convIdxKey($user1Id, $user2Id);
        $convId = Redis::get($idxKey);

        if ($convId) {
            $data = Redis::hGetAll($this->convKey($convId));
            return new Conversation($data);
        }

        // Tạo mới
        $convId = Str::uuid()->toString();
        $now    = (int)(microtime(true) * 1000);

        $sorted = [$user1Id, $user2Id];
        sort($sorted);

        $conv = new Conversation([
            'conv_id'     => $convId,
            'user1_id'    => $sorted[0],
            'user2_id'    => $sorted[1],
            'created_at'  => $now,
            'last_msg_id' => '',
            'last_msg_at' => 0,
        ]);

        Redis::hMSet($this->convKey($convId), $conv->toArray());
        Redis::set($idxKey, $convId);

        return $conv;
    }

    public function findConversationById(string $convId): ?Conversation
    {
        $data = Redis::hGetAll($this->convKey($convId));
        return $data ? new Conversation($data) : null;
    }

    /** Lấy danh sách conv của user, sắp xếp theo tin mới nhất */
    public function getUserConversations(string $userId): array
    {
        // ZREVRANGE: lấy 30 cuộc chat gần nhất
        $ids = Redis::zRevRange($this->userConvsKey($userId), 0, 29);
        return $ids ?? [];
    }

    /* ─────────── MESSAGES ─────────── */

    /** Lưu tin nhắn mới vào DM conversation */
    public function createDirectMessage(array $data): Message
    {
        $msgId = Str::uuid()->toString();
        $now   = (int)(microtime(true) * 1000);

        $msg = new Message(array_merge($data, [
            'msg_id'           => $msgId,
            'conv_type'        => 'dm',
            'status'           => 'sent',
            'created_at'       => $now,
            'edited_at'        => 0,
            'original_content' => '',
            'reply_to'         => $data['reply_to'] ?? '',
        ]));

        // Lưu hash chi tiết
        Redis::hMSet($this->msgKey($msgId), $msg->toArray());
        // Thêm vào timeline conversation (ZSet, score = timestamp)
        Redis::zAdd($this->convMsgsKey($data['conv_id']), $now, $msgId);
        // Cập nhật last_msg của conversation
        Redis::hMSet($this->convKey($data['conv_id']), [
            'last_msg_id' => $msgId,
            'last_msg_at' => $now,
        ]);
        // Cập nhật danh sách conv của từng user (ZSet, score = now)
        $conv = $this->findConversationById($data['conv_id']);
        if ($conv) {
            Redis::zAdd($this->userConvsKey($conv->user1_id), $now, $data['conv_id']);
            Redis::zAdd($this->userConvsKey($conv->user2_id), $now, $data['conv_id']);
            // Tăng unread cho người nhận
            $receiverId = $conv->getOtherUserId($data['sender_id']);
            Redis::hIncrBy($this->unreadKey($receiverId), $data['conv_id'], 1);
        }

        return $msg;
    }

    public function findMessageById(string $msgId): ?Message
    {
        $data = Redis::hGetAll($this->msgKey($msgId));
        return $data ? new Message($data) : null;
    }

    public function findById(string $msgId): ?Message
    {
        return $this->findMessageById($msgId);
    }

    public function updateMessageContent(string $msgId, string $newContent): bool
    {
        Redis::hSet($this->msgKey($msgId), 'content', $newContent);
        return true;
    }

    /**
     * Lấy tin nhắn theo trang (phân trang ngược — mới nhất trước)
     * @return Message[]
     */
    public function getConversationMessages(string $convId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        // ZREVRANGE: lấy từ mới nhất đến cũ hơn
        $msgIds = Redis::zRevRange($this->convMsgsKey($convId), $offset, $offset + $perPage - 1);
        $messages = [];
        foreach (array_reverse($msgIds ?? []) as $id) {
            $msg = $this->findMessageById($id);
            if ($msg) $messages[] = $msg;
        }
        return $messages;
    }

    /** Sửa tin nhắn */
    public function editMessage(string $msgId, string $newContent): bool
    {
        $msg = $this->findMessageById($msgId);
        if (!$msg || $msg->isDeleted()) return false;

        Redis::hMSet($this->msgKey($msgId), [
            'original_content' => $msg->content,
            'content'          => $newContent,
            'status'           => 'edited',
            'edited_at'        => (int)(microtime(true) * 1000),
        ]);
        return true;
    }

    /** Xóa mềm tin nhắn */
    public function deleteMessage(string $msgId): bool
    {
        $msg = $this->findMessageById($msgId);
        if (!$msg) return false;

        Redis::hMSet($this->msgKey($msgId), [
            'status'  => 'deleted',
            'content' => '',
        ]);
        return true;
    }

    /** Đánh dấu đã đọc — reset unread count */
    public function markConversationRead(string $convId, string $userId): void
    {
        Redis::hSet($this->unreadKey($userId), $convId, 0);
    }

    /** Lấy số tin chưa đọc của tất cả conv */
    public function getUnreadCounts(string $userId): array
    {
        return Redis::hGetAll($this->unreadKey($userId)) ?? [];
    }

    /** Tổng tin chưa đọc */
    public function getTotalUnread(string $userId): int
    {
        $counts = $this->getUnreadCounts($userId);
        return array_sum(array_map('intval', $counts));
    }

    /**
     * Lấy các tin nhắn mới hơn timestamp $afterTimestamp (dùng cho polling không cần reload)
     * @return Message[]
     */
    public function getNewMessages(string $convId, int $afterTimestamp): array
    {
        $msgIds = Redis::zRangeByScore($this->convMsgsKey($convId), '(' . $afterTimestamp, '+inf');
        $messages = [];
        foreach ($msgIds ?? [] as $id) {
            $msg = $this->findMessageById($id);
            if ($msg) $messages[] = $msg;
        }
        return $messages;
    }
}
