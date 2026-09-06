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
     * Gửi tin nhắn thông báo hệ thống vào nhóm
     */
    public function sendSystemMessage(string $groupId, string $content): void
    {
        try {
            $this->groups->createMessage([
                'conv_id'   => $groupId,
                'sender_id' => 'system',
                'content'   => $content,
                'type'      => 'system',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Lỗi gửi tin nhắn hệ thống vào nhóm: ' . $e->getMessage());
        }
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

        // Gửi tin nhắn hệ thống
        $reqUser = $this->users->findById($requesterId);
        $reqName = $reqUser ? $reqUser->getName() : 'Quản trị viên';
        $targetUser = $this->users->findById($userId);
        $targetName = $targetUser ? $targetUser->getName() : 'Thành viên mới';
        $this->sendSystemMessage($groupId, "{$reqName} đã thêm {$targetName} vào nhóm.");
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

        $reqUser = $this->users->findById($requesterId);
        $reqName = $reqUser ? $reqUser->getName() : 'Quản trị viên';
        $targetUser = $this->users->findById($userId);
        $targetName = $targetUser ? $targetUser->getName() : 'Thành viên';

        $this->groups->removeMember($groupId, $userId);

        // Gửi tin nhắn hệ thống
        $this->sendSystemMessage($groupId, "{$reqName} đã xóa {$targetName} khỏi nhóm.");
    }

    /** Thành viên tự rời nhóm */
    public function leaveGroup(string $userId, string $groupId): void
    {
        $role = $this->groups->getMemberRole($groupId, $userId);
        if ($role === 'owner') {
            throw new \RuntimeException('Chủ nhóm không thể rời nhóm. Hãy chuyển quyền trước.');
        }

        $user = $this->users->findById($userId);
        $userName = $user ? $user->getName() : 'Thành viên';

        $this->groups->removeMember($groupId, $userId);

        // Gửi tin nhắn hệ thống
        $this->sendSystemMessage($groupId, "{$userName} đã rời khỏi nhóm.");
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

        $targetUser = $this->users->findById($userId);
        $targetName = $targetUser ? $targetUser->getName() : 'Thành viên';
        $this->sendSystemMessage($groupId, "{$targetName} đã được thăng chức làm Phó nhóm (Admin).");
    }

    /** Hạ cấp admin → member */
    public function demoteAdmin(string $ownerId, string $groupId, string $userId): void
    {
        if ($this->groups->getMemberRole($groupId, $ownerId) !== 'owner') {
            throw new \RuntimeException('Chỉ chủ nhóm mới được thay đổi quyền.');
        }
        $this->groups->setMemberRole($groupId, $userId, 'member');

        $targetUser = $this->users->findById($userId);
        $targetName = $targetUser ? $targetUser->getName() : 'Thành viên';
        $this->sendSystemMessage($groupId, "{$targetName} đã được chuyển về làm thành viên thông thường.");
    }

    /** Đặt nickname trong nhóm */
    public function setGroupNickname(string $groupId, string $userId, string $nickname): void
    {
        $this->groups->setNickname($groupId, $userId, $nickname);

        $targetUser = $this->users->findById($userId);
        $targetName = $targetUser ? $targetUser->getName() : 'Thành viên';
        if ($nickname !== '') {
            $this->sendSystemMessage($groupId, "Biệt danh của {$targetName} đã được đổi thành \"{$nickname}\".");
        } else {
            $this->sendSystemMessage($groupId, "Biệt danh của {$targetName} đã bị xóa.");
        }
    }

    /**
     * Chuyển quyền trưởng nhóm (chỉ owner mới có quyền)
     * @throws \RuntimeException
     */
    public function transferOwnership(string $ownerId, string $groupId, string $newOwnerId): void
    {
        if ($ownerId === $newOwnerId) {
            throw new \RuntimeException('Bạn đã là chủ nhóm này rồi.');
        }
        if ($this->groups->getMemberRole($groupId, $ownerId) !== 'owner') {
            throw new \RuntimeException('Chỉ trưởng nhóm mới có quyền chuyển giao quyền quản lý nhóm.');
        }
        if (!$this->groups->isMember($groupId, $newOwnerId)) {
            throw new \RuntimeException('Người nhận quyền phải là thành viên hiện tại của nhóm.');
        }

        $this->groups->transferOwnership($groupId, $ownerId, $newOwnerId);

        $newOwner = $this->users->findById($newOwnerId);
        $newOwnerName = $newOwner ? $newOwner->getName() : 'Thành viên';
        $this->sendSystemMessage($groupId, "Quyền Trưởng nhóm đã được chuyển giao cho {$newOwnerName}.");
    }

    /**
     * Cập nhật thông tin nhóm (Tên nhóm, Ảnh đại diện nhóm)
     * Chỉ owner hoặc admin mới có quyền
     * @throws \RuntimeException
     */
    public function updateGroup(string $requesterId, string $groupId, array $data): Group
    {
        $role = $this->groups->getMemberRole($groupId, $requesterId);
        if (!in_array($role, ['owner', 'admin'])) {
            throw new \RuntimeException('Chỉ Trưởng nhóm hoặc Phó nhóm mới có quyền thay đổi thông tin nhóm.');
        }

        $group = $this->groups->findById($groupId);
        if (!$group) throw new \RuntimeException('Nhóm không tồn tại.');

        $updates = [];
        $reqUser = $this->users->findById($requesterId);
        $reqName = $reqUser ? $reqUser->getName() : 'Quản trị viên';

        if (isset($data['name']) && trim($data['name']) !== '' && trim($data['name']) !== $group->name) {
            $oldName = $group->name;
            $newName = trim($data['name']);
            $updates['name'] = $newName;
            $this->sendSystemMessage($groupId, "{$reqName} đã đổi tên nhóm thành \"{$newName}\".");
        }

        if (isset($data['avatar_url']) && $data['avatar_url'] !== $group->avatar_url) {
            $updates['avatar_url'] = $data['avatar_url'];
            $this->sendSystemMessage($groupId, "{$reqName} đã cập nhật ảnh đại diện của nhóm.");
        }

        if (isset($data['description'])) {
            $updates['description'] = trim($data['description']);
        }

        if (!empty($updates)) {
            $this->groups->update($groupId, $updates);
        }

        return $this->groups->findById($groupId);
    }

    /**
     * Xóa nhóm hoàn toàn (chỉ owner mới có quyền)
     * @throws \RuntimeException
     */
    public function deleteGroup(string $ownerId, string $groupId): void
    {
        if ($this->groups->getMemberRole($groupId, $ownerId) !== 'owner') {
            throw new \RuntimeException('Chỉ trưởng nhóm mới có quyền giải tán / xóa nhóm.');
        }

        $this->groups->deleteGroup($groupId);
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
