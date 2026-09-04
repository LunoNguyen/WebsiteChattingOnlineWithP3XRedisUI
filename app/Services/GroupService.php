<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;

/**
 * GroupService — tạo nhóm, quản lý thành viên, phân quyền
 */
class GroupService
{
    public function __construct(
        private GroupRepository $groups,
        private UserRepository  $users,
    ) {}

    /**
     * Tạo nhóm mới
     * @throws \RuntimeException
     */
    public function createGroup(string $ownerId, array $data): Group
    {
        if (empty(trim($data['name'] ?? ''))) {
            throw new \RuntimeException('Tên nhóm không được để trống.');
        }

        $group = $this->groups->create($ownerId, [
            'name'        => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'avatar_url'  => $data['avatar_url'] ?? '',
            'max_members' => (int) ($data['max_members'] ?? 50),
        ]);

        // Thêm members ban đầu nếu có
        if (!empty($data['member_ids'])) {
            foreach ($data['member_ids'] as $memberId) {
                if ($memberId !== $ownerId) {
                    $this->groups->addMember($group->group_id, $memberId, 'member');
                }
            }
        }

        return $group;
    }

    /**
     * Thêm thành viên — chỉ owner/admin mới được thêm
     * @throws \RuntimeException
     */
    public function addMember(string $requesterId, string $groupId, string $userId): void
    {
        $requesterRole = $this->groups->getMemberRole($groupId, $requesterId);
        if (!in_array($requesterRole, ['owner', 'admin'])) {
            throw new \RuntimeException('Bạn không có quyền thêm thành viên.');
        }

        $group = $this->groups->findById($groupId);
        if (!$group) throw new \RuntimeException('Nhóm không tồn tại.');

        $currentCount = $this->groups->getMemberCount($groupId);
        if ($currentCount >= $group->max_members) {
            throw new \RuntimeException("Nhóm đã đạt giới hạn {$group->max_members} thành viên.");
        }

        $this->groups->addMember($groupId, $userId, 'member');
    }

    /**
     * Kick thành viên — chỉ owner/admin, không thể kick owner
     * @throws \RuntimeException
     */
    public function removeMember(string $requesterId, string $groupId, string $userId): void
    {
        $requesterRole = $this->groups->getMemberRole($groupId, $requesterId);
        $targetRole    = $this->groups->getMemberRole($groupId, $userId);

        if (!in_array($requesterRole, ['owner', 'admin'])) {
            throw new \RuntimeException('Bạn không có quyền xóa thành viên.');
        }
        if ($targetRole === 'owner') {
            throw new \RuntimeException('Không thể xóa chủ nhóm.');
        }
        if ($requesterRole === 'admin' && $targetRole === 'admin') {
            throw new \RuntimeException('Admin không thể xóa admin khác.');
        }

        $this->groups->removeMember($groupId, $userId);
    }

    /** Thành viên tự rời nhóm */
    public function leaveGroup(string $userId, string $groupId): void
    {
        $role = $this->groups->getMemberRole($groupId, $userId);
        if ($role === 'owner') {
            throw new \RuntimeException('Chủ nhóm không thể rời nhóm. Hãy chuyển quyền trước.');
        }
        $this->groups->removeMember($groupId, $userId);
    }

    /**
     * Thăng cấp admin — chỉ owner mới được
     * @throws \RuntimeException
     */
    public function promoteToAdmin(string $ownerId, string $groupId, string $userId): void
    {
        if ($this->groups->getMemberRole($groupId, $ownerId) !== 'owner') {
            throw new \RuntimeException('Chỉ chủ nhóm mới được thay đổi quyền.');
        }
        $this->groups->setMemberRole($groupId, $userId, 'admin');
    }

    /** Hạ cấp admin → member */
    public function demoteAdmin(string $ownerId, string $groupId, string $userId): void
    {
        if ($this->groups->getMemberRole($groupId, $ownerId) !== 'owner') {
            throw new \RuntimeException('Chỉ chủ nhóm mới được thay đổi quyền.');
        }
        $this->groups->setMemberRole($groupId, $userId, 'member');
    }

    /** Đặt nickname trong nhóm */
    public function setGroupNickname(string $groupId, string $userId, string $nickname): void
    {
        $this->groups->setNickname($groupId, $userId, $nickname);
    }

    /**
     * Lấy thông tin nhóm kèm danh sách thành viên đầy đủ
     */
    public function getGroupWithMembers(string $groupId): ?array
    {
        $group = $this->groups->findById($groupId);
        if (!$group) return null;

        $membersRaw = $this->groups->getAllMembers($groupId);
        $nicknames  = $this->groups->getAllNicknames($groupId);
        $members    = [];

        foreach ($membersRaw as $uid => $role) {
            $user = $this->users->findById($uid);
            if ($user) {
                $members[] = [
                    'user'     => $user,
                    'role'     => $role,
                    'nickname' => $nicknames[$uid] ?? '',
                ];
            }
        }

        return ['group' => $group, 'members' => $members];
    }

    /**
     * Lấy danh sách nhóm của user kèm thông tin nhóm
     * @return Group[]
     */
    public function getUserGroups(string $userId): array
    {
        $groupIds = $this->groups->getUserGroupIds($userId);
        $result   = [];
        foreach ($groupIds as $gid) {
            $group = $this->groups->findById($gid);
            if ($group) $result[] = $group;
        }
        return $result;
    }
}
