<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Redis;

/**
 * FriendRepository — namespace user:{id}:friends, user:{id}:blocked, ...
 *
 * Key patterns:
 *   user:{id}:friends              — Set    — danh sách bạn
 *   user:{id}:blocked              — Set    — danh sách bị block
 *   user:{id}:friend_requests      — Set    — lời mời nhận vào
 *   user:{id}:friend_req:{from}    — Hash   — chi tiết lời mời
 *   user:{id}:nicknames            — Hash   — nickname đặt cho bạn
 */
class FriendRepository
{
    private function friendsKey(string $id): string       { return "user:{$id}:friends"; }
    private function blockedKey(string $id): string       { return "user:{$id}:blocked"; }
    private function requestsKey(string $id): string      { return "user:{$id}:friend_requests"; }
    private function reqDetailKey(string $to, string $from): string { return "user:{$to}:friend_req:{$from}"; }
    private function nicknamesKey(string $id): string     { return "user:{$id}:nicknames"; }

    /* ─── Friend Requests ─── */

    public function sendRequest(string $fromId, string $toId, string $message = ''): void
    {
        $now = (int)(microtime(true) * 1000);
        // Thêm fromId vào set lời mời của toId
        Redis::sAdd($this->requestsKey($toId), $fromId);
        // Lưu chi tiết lời mời
        Redis::hMSet($this->reqDetailKey($toId, $fromId), [
            'from_user_id' => $fromId,
            'to_user_id'   => $toId,
            'message'      => $message,
            'status'       => 'pending',
            'created_at'   => $now,
        ]);
    }

    public function acceptRequest(string $fromId, string $toId): void
    {
        // Thêm bạn 2 chiều
        Redis::sAdd($this->friendsKey($toId), $fromId);
        Redis::sAdd($this->friendsKey($fromId), $toId);
        // Xóa lời mời
        Redis::sRem($this->requestsKey($toId), $fromId);
        Redis::del($this->reqDetailKey($toId, $fromId));
    }

    public function rejectRequest(string $fromId, string $toId): void
    {
        Redis::sRem($this->requestsKey($toId), $fromId);
        Redis::del($this->reqDetailKey($toId, $fromId));
    }

    public function cancelRequest(string $fromId, string $toId): void
    {
        $this->rejectRequest($fromId, $toId);
    }

    /** Lấy danh sách user_id đã gửi lời mời đến $userId */
    public function getPendingRequests(string $userId): array
    {
        $fromIds = Redis::sMembers($this->requestsKey($userId)) ?? [];
        $requests = [];
        foreach ($fromIds as $fromId) {
            $detail = Redis::hGetAll($this->reqDetailKey($userId, $fromId));
            if ($detail) $requests[] = $detail;
        }
        return $requests;
    }

    public function hasPendingRequest(string $fromId, string $toId): bool
    {
        return (bool) Redis::sIsMember($this->requestsKey($toId), $fromId);
    }

    /* ─── Friends ─── */

    public function getFriendIds(string $userId): array
    {
        return Redis::sMembers($this->friendsKey($userId)) ?? [];
    }

    public function isFriend(string $userId, string $friendId): bool
    {
        return (bool) Redis::sIsMember($this->friendsKey($userId), $friendId);
    }

    public function removeFriend(string $userId, string $friendId): void
    {
        // Xóa 2 chiều
        Redis::sRem($this->friendsKey($userId), $friendId);
        Redis::sRem($this->friendsKey($friendId), $userId);
    }

    /* ─── Block ─── */

    public function blockUser(string $userId, string $targetId): void
    {
        Redis::sAdd($this->blockedKey($userId), $targetId);
        // Tự động xóa khỏi bạn bè nếu đang là bạn
        $this->removeFriend($userId, $targetId);
    }

    public function unblockUser(string $userId, string $targetId): void
    {
        Redis::sRem($this->blockedKey($userId), $targetId);
    }

    public function isBlocked(string $userId, string $targetId): bool
    {
        return (bool) Redis::sIsMember($this->blockedKey($userId), $targetId);
    }

    /** Kiểm tra 2 chiều: userId block targetId HOẶC targetId block userId */
    public function isBlockedEither(string $userId, string $targetId): bool
    {
        return $this->isBlocked($userId, $targetId) || $this->isBlocked($targetId, $userId);
    }

    public function getBlockedIds(string $userId): array
    {
        return Redis::sMembers($this->blockedKey($userId)) ?? [];
    }

    /* ─── Nicknames ─── */

    public function setNickname(string $userId, string $friendId, string $nickname): void
    {
        if ($nickname === '') {
            Redis::hDel($this->nicknamesKey($userId), $friendId);
        } else {
            Redis::hSet($this->nicknamesKey($userId), $friendId, $nickname);
        }
    }

    public function getNickname(string $userId, string $friendId): string
    {
        return Redis::hGet($this->nicknamesKey($userId), $friendId) ?? '';
    }

    public function getAllNicknames(string $userId): array
    {
        return Redis::hGetAll($this->nicknamesKey($userId)) ?? [];
    }
}
