<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\FriendRepository;
use App\Repositories\UserRepository;

/**
 * FriendService — kết bạn, xóa bạn, block, nickname
 */
class FriendService
{
    public function __construct(
        private FriendRepository $friends,
        private UserRepository   $users,
    ) {}

    /**
     * Gửi lời mời kết bạn
     * @throws \RuntimeException
     */
    public function sendRequest(string $fromId, string $toId, string $message = ''): void
    {
        if ($fromId === $toId) throw new \RuntimeException('Không thể tự kết bạn với mình.');

        $target = $this->users->findById($toId);
        if (!$target) throw new \RuntimeException('Người dùng không tồn tại.');

        if ($this->friends->isFriend($fromId, $toId)) {
            throw new \RuntimeException('Hai người đã là bạn bè.');
        }
        if ($this->friends->isBlocked($toId, $fromId)) {
            throw new \RuntimeException('Không thể gửi lời mời kết bạn.');
        }
        if ($this->friends->hasPendingRequest($fromId, $toId)) {
            throw new \RuntimeException('Lời mời kết bạn đã được gửi, chờ xác nhận.');
        }

        $this->friends->sendRequest($fromId, $toId, $message);
    }

    public function acceptRequest(string $fromId, string $toId): void
    {
        if (!$this->friends->hasPendingRequest($fromId, $toId)) {
            throw new \RuntimeException('Không có lời mời kết bạn này.');
        }
        $this->friends->acceptRequest($fromId, $toId);
    }

    public function rejectRequest(string $fromId, string $toId): void
    {
        $this->friends->rejectRequest($fromId, $toId);
    }

    public function removeFriend(string $userId, string $friendId): void
    {
        if (!$this->friends->isFriend($userId, $friendId)) {
            throw new \RuntimeException('Người này chưa phải bạn bè của bạn.');
        }
        $this->friends->removeFriend($userId, $friendId);
    }

    public function blockUser(string $userId, string $targetId): void
    {
        if ($userId === $targetId) throw new \RuntimeException('Không thể tự chặn mình.');
        $this->friends->blockUser($userId, $targetId);
    }

    public function unblockUser(string $userId, string $targetId): void
    {
        $this->friends->unblockUser($userId, $targetId);
    }

    public function setNickname(string $userId, string $friendId, string $nickname): void
    {
        $this->friends->setNickname($userId, $friendId, $nickname);
    }

    public function getNickname(string $userId, string $friendId): string
    {
        return $this->friends->getNickname($userId, $friendId);
    }

    /**
     * Lấy danh sách bạn bè với đầy đủ thông tin User
     * @return array [['user' => User, 'nickname' => string, 'is_online' => bool], ...]
     */
    public function getFriendsWithInfo(string $userId): array
    {
        $friendIds = $this->friends->getFriendIds($userId);
        $nicknames = $this->friends->getAllNicknames($userId);
        $result    = [];

        foreach ($friendIds as $fid) {
            $user = $this->users->findById($fid);
            if (!$user) continue;
            $result[] = [
                'user'      => $user,
                'nickname'  => $nicknames[$fid] ?? '',
                'is_online' => (bool) $user->is_online,
            ];
        }

        // Sắp xếp: online trước, sau đó theo tên
        usort($result, fn($a, $b) =>
            $b['is_online'] <=> $a['is_online'] ?: strcmp($a['user']->getName(), $b['user']->getName())
        );

        return $result;
    }

    /** @return array lời mời kèm thông tin người gửi */
    public function getPendingRequestsWithInfo(string $userId): array
    {
        $requests = $this->friends->getPendingRequests($userId);
        $result   = [];

        foreach ($requests as $req) {
            $fromUser = $this->users->findById($req['from_user_id']);
            if ($fromUser) {
                $result[] = array_merge($req, ['from_user' => $fromUser]);
            }
        }

        return $result;
    }
}
