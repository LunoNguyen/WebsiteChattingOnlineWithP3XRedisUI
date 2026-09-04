<?php

namespace App\Http\Controllers;

use App\Services\GroupService;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(
        private GroupService    $service,
        private GroupRepository $groups,
        private UserRepository  $users,
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
        $friends  = app(\App\Services\FriendService::class)->getFriendsWithInfo($authUser->user_id);
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

    /** POST — thêm thành viên */
    public function addMember(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['username' => 'required|string']);

        $target = $this->users->findByUsername($request->username);
        if (!$target) return back()->with('error', 'Không tìm thấy người dùng.');

        try {
            $this->service->addMember($authUser->user_id, $groupId, $target->user_id);
            return back()->with('success', "Đã thêm {$target->getName()} vào nhóm.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** POST — kick thành viên */
    public function removeMember(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['user_id' => 'required|string']);

        try {
            $this->service->removeMember($authUser->user_id, $groupId, $request->user_id);
            return back()->with('success', 'Đã xóa thành viên khỏi nhóm.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** POST — rời nhóm */
    public function leave(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        try {
            $this->service->leaveGroup($authUser->user_id, $groupId);
            return redirect()->route('chat.index')->with('success', 'Đã rời nhóm.');
        } catch (\RuntimeException $e) {
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
        return back()->with('success', 'Đã cập nhật nickname.');
    }

    /** POST — thăng admin */
    public function promoteAdmin(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['user_id' => 'required|string']);

        try {
            $this->service->promoteToAdmin($authUser->user_id, $groupId, $request->user_id);
            return back()->with('success', 'Đã thăng cấp thành viên lên Admin.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
