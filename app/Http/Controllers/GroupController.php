<?php

namespace App\Http\Controllers;

use App\Services\GroupService;
use App\Services\FriendService;
use App\Services\MinioStorageService;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(
        private GroupService        $service,
        private GroupRepository     $groups,
        private UserRepository      $users,
        private FriendService       $friends,
        private MinioStorageService $minio,
    ) {}

    /** Trang danh sách nhóm */
    public function index(Request $request)
    {
        $authUser  = $request->attributes->get('auth_user');
        $myGroups  = $this->service->getUserGroups($authUser->user_id);
        return view('groups.index', compact('authUser', 'myGroups'));
    }

    /** Form tạo nhóm */
    public function create(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        // Gợi ý danh sách bạn bè để thêm vào nhóm
        $friends  = $this->friends->getFriendsWithInfo($authUser->user_id);
        return view('groups.create', compact('authUser', 'friends'));
    }

    /** POST — tạo nhóm */
    public function store(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'member_ids'  => 'nullable|array',
            'member_ids.*'=> 'string',
        ]);

        try {
            $group = $this->service->createGroup($authUser->user_id, $request->all());
            return redirect()->route('chat.group', $group->group_id)
                ->with('success', "Đã tạo nhóm \"{$group->name}\".");
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /** Lấy danh sách bạn bè chưa có trong nhóm để mời vào */
    public function getCandidates(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $allFriends = $this->friends->getFriendsWithInfo($authUser->user_id);
        $members = $this->groups->getAllMembers($groupId);

        $candidates = [];
        foreach ($allFriends as $f) {
            $fid = $f['user']->user_id;
            if (!isset($members[$fid])) {
                $candidates[] = [
                    'user_id'   => $fid,
                    'name'      => $f['nickname'] ?: $f['user']->getName(),
                    'username'  => $f['user']->username,
                    'avatar_url'=> $f['user']->avatar_url,
                    'is_online' => $f['is_online'],
                ];
            }
        }

        return response()->json(['success' => true, 'candidates' => $candidates]);
    }

    /** POST — thêm thành viên */
    public function addMember(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $targetUserId = $request->user_id;

        if (!$targetUserId && $request->username) {
            $target = $this->users->findByUsername($request->username);
            $targetUserId = $target?->user_id;
        }

        if (!$targetUserId) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'Không tìm thấy người dùng.'], 404);
            }
            return back()->with('error', 'Không tìm thấy người dùng.');
        }

        try {
            $this->service->addMember($authUser->user_id, $groupId, $targetUserId);
            $targetUser = $this->users->findById($targetUserId);
            $msg = "Đã thêm " . ($targetUser?->getName() ?? 'thành viên') . " vào nhóm.";

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return back()->with('success', $msg);
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /** DELETE / POST — kick thành viên */
    public function removeMember(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $userId = $request->user_id;

        try {
            $this->service->removeMember($authUser->user_id, $groupId, $userId);
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Đã xóa thành viên khỏi nhóm.']);
            }
            return back()->with('success', 'Đã xóa thành viên khỏi nhóm.');
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /** POST — rời nhóm */
    public function leave(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        try {
            $this->service->leaveGroup($authUser->user_id, $groupId);
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'redirect' => route('chat.index')]);
            }
            return redirect()->route('chat.index')->with('success', 'Đã rời khỏi nhóm.');
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /** POST — thăng phó nhóm (Admin) */
    public function promoteAdmin(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['user_id' => 'required|string']);

        try {
            $this->service->promoteToAdmin($authUser->user_id, $groupId, $request->user_id);
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Đã thăng cấp thành viên lên Phó nhóm.']);
            }
            return back()->with('success', 'Đã thăng cấp thành viên lên Phó nhóm.');
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /** POST — hạ cấp phó nhóm về thành viên */
    public function demoteAdmin(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['user_id' => 'required|string']);

        try {
            $this->service->demoteAdmin($authUser->user_id, $groupId, $request->user_id);
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Đã hạ cấp Phó nhóm về thành viên.']);
            }
            return back()->with('success', 'Đã hạ cấp Phó nhóm về thành viên.');
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /** POST — chuyển quyền trưởng nhóm */
    public function transferOwnership(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['new_owner_id' => 'required|string']);

        try {
            $this->service->transferOwnership($authUser->user_id, $groupId, $request->new_owner_id);
            $newOwner = $this->users->findById($request->new_owner_id);
            $msg = "Đã chuyển quyền Trưởng nhóm cho " . ($newOwner?->getName() ?? 'thành viên');

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return back()->with('success', $msg);
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /** DELETE — xóa nhóm hoàn toàn */
    public function destroy(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        try {
            $this->service->deleteGroup($authUser->user_id, $groupId);
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'redirect' => route('chat.index'), 'message' => 'Đã giải tán nhóm thành công.']);
            }
            return redirect()->route('chat.index')->with('success', 'Đã giải tán nhóm thành công.');
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /** POST — đặt nickname trong nhóm */
    public function setNickname(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate([
            'target_user_id' => 'required|string',
            'nickname'       => 'nullable|string|max:50',
        ]);

        $this->service->setGroupNickname($groupId, $request->target_user_id, $request->nickname ?? '');
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã cập nhật biệt danh.']);
        }
        return back()->with('success', 'Đã cập nhật biệt danh.');
    }

    /** PUT / POST — Cập nhật tên nhóm & ảnh đại diện nhóm */
    public function update(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');

        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'avatar_file' => 'nullable|image|max:5120', // Tối đa 5MB
            'avatar_url'  => 'nullable|string|max:1000',
        ]);

        try {
            $group = $this->groups->findById($groupId);
            if (!$group) throw new \RuntimeException('Nhóm không tồn tại.');

            $avatarUrl = $request->avatar_url ?? $group->avatar_url ?? '';

            // Nếu người dùng tải ảnh đại diện nhóm mới lên từ máy tính
            if ($request->hasFile('avatar_file')) {
                $uploaded = $this->minio->uploadFile($request->file('avatar_file'), 'group-avatars');
                $avatarUrl = $uploaded['url'];
            }

            $updatedGroup = $this->service->updateGroup($authUser->user_id, $groupId, [
                'name'        => $request->name,
                'description' => $request->description ?? '',
                'avatar_url'  => $avatarUrl,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã cập nhật thông tin nhóm thành công.',
                    'group'   => $updatedGroup->toArray(),
                ]);
            }
            return back()->with('success', 'Đã cập nhật thông tin nhóm thành công.');
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }
}
