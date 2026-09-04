<?php

namespace App\Repositories;

use App\Models\Group;
use App\Models\Message;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * GroupRepository
 *
 * Key patterns:
 *   group:{id}              — Hash  — metadata nhóm
 *   group:{id}:members      — Hash  — {user_id} => role
 *   group:{id}:nicknames    — Hash  — {user_id} => nickname
 *   group:{id}:msgs         — ZSet  — timeline tin nhắn (score=timestamp)
 *   user:{id}:convs         — ZSet  — cũng chứa group_id (score = last_msg_at)
 *   user:{id}:unread        — Hash  — unread count cho group
 *   user:{id}:groups        — Set   — tập nhóm user đang tham gia
 */
class GroupRepository
{
    private function groupKey(string $id): string         { return "group:{$id}"; }
    private function membersKey(string $id): string       { return "group:{$id}:members"; }
    private function nicknamesKey(string $id): string     { return "group:{$id}:nicknames"; }
    private function msgsKey(string $id): string          { return "group:{$id}:msgs"; }
    private function userConvsKey(string $uid): string    { return "user:{$uid}:convs"; }
    private function unreadKey(string $uid): string       { return "user:{$uid}:unread"; }
    private function userGroupsKey(string $uid): string   { return "user:{$uid}:groups"; }

    /* ─── Create Group ─── */
    public function create(string $ownerId, array $data): Group
    {
        $groupId = Str::uuid()->toString();
        $now     = (int)(microtime(true) * 1000);

        $group = new Group(array_merge($data, [
            'group_id'   => $groupId,
            'owner_id'   => $ownerId,
            'created_at' => $now,
            'last_msg_at'=> 0,
        ]));

        Redis::hMSet($this->groupKey($groupId), $group->toArray());
        // Thêm owner làm thành viên đầu tiên với role 'owner'
        Redis::hSet($this->membersKey($groupId), $ownerId, 'owner');
        Redis::sAdd($this->userGroupsKey($ownerId), $groupId);
        Redis::zAdd($this->userConvsKey($ownerId), $now, $groupId);

        return $group;
    }

    /* ─── Find ─── */
    public function findById(string $groupId): ?Group
    {
        $data = Redis::hGetAll($this->groupKey($groupId));
        return $data ? new Group($data) : null;
    }

    /* ─── Members ─── */

    public function addMember(string $groupId, string $userId, string $role = 'member'): void
    {
        $now = (int)(microtime(true) * 1000);
        Redis::hSet($this->membersKey($groupId), $userId, $role);
        Redis::sAdd($this->userGroupsKey($userId), $groupId);
        Redis::zAdd($this->userConvsKey($userId), $now, $groupId);
    }

    public function removeMember(string $groupId, string $userId): void
    {
        Redis::hDel($this->membersKey($groupId), $userId);
        Redis::sRem($this->userGroupsKey($userId), $groupId);
        Redis::zRem($this->userConvsKey($userId), $groupId);
        Redis::hDel($this->unreadKey($userId), $groupId);
    }

    public function getMemberRole(string $groupId, string $userId): ?string
    {
        return Redis::hGet($this->membersKey($groupId), $userId) ?: null;
    }

    public function isMember(string $groupId, string $userId): bool
    {
        return Redis::hExists($this->membersKey($groupId), $userId);
    }

    /** @return array [user_id => role, ...] */
    public function getAllMembers(string $groupId): array
    {
        return Redis::hGetAll($this->membersKey($groupId)) ?? [];
    }

    public function setMemberRole(string $groupId, string $userId, string $role): void
    {
        Redis::hSet($this->membersKey($groupId), $userId, $role);
    }

    public function getMemberCount(string $groupId): int
    {
        return (int) Redis::hLen($this->membersKey($groupId));
    }

    /* ─── Nicknames ─── */

    public function setNickname(string $groupId, string $userId, string $nickname): void
    {
        if ($nickname === '') {
            Redis::hDel($this->nicknamesKey($groupId), $userId);
        } else {
            Redis::hSet($this->nicknamesKey($groupId), $userId, $nickname);
        }
    }

    public function getNickname(string $groupId, string $userId): string
    {
        return Redis::hGet($this->nicknamesKey($groupId), $userId) ?? '';
    }

    public function getAllNicknames(string $groupId): array
    {
        return Redis::hGetAll($this->nicknamesKey($groupId)) ?? [];
    }

    /* ─── Messages ─── */

    public function createMessage(array $data): Message
    {
        $msgId  = Str::uuid()->toString();
        $now    = (int)(microtime(true) * 1000);
        $groupId = $data['conv_id'];

        $msg = new Message(array_merge($data, [
            'msg_id'           => $msgId,
            'conv_type'        => 'group',
            'status'           => 'sent',
            'created_at'       => $now,
            'edited_at'        => 0,
            'original_content' => '',
            'reply_to'         => $data['reply_to'] ?? '',
        ]));

        Redis::hMSet("msg:{$msgId}", $msg->toArray());
        Redis::zAdd($this->msgsKey($groupId), $now, $msgId);
        Redis::hSet($this->groupKey($groupId), 'last_msg_at', $now);

        // Tăng unread cho tất cả thành viên (trừ người gửi)
        $members = array_keys($this->getAllMembers($groupId));
        foreach ($members as $memberId) {
            if ($memberId !== $data['sender_id']) {
                Redis::hIncrBy($this->unreadKey($memberId), $groupId, 1);
            }
            Redis::zAdd($this->userConvsKey($memberId), $now, $groupId);
        }

        return $msg;
    }

    /** @return Message[] */
    public function getMessages(string $groupId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $msgIds = Redis::zRevRange($this->msgsKey($groupId), $offset, $offset + $perPage - 1);
        $messages = [];
        foreach (array_reverse($msgIds ?? []) as $id) {
            $data = Redis::hGetAll("msg:{$id}");
            if ($data) $messages[] = new Message($data);
        }
        return $messages;
    }

    /* ─── User's groups ─── */
    public function getUserGroupIds(string $userId): array
    {
        return Redis::sMembers($this->userGroupsKey($userId)) ?? [];
    }
}
