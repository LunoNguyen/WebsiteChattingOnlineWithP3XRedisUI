<?php

namespace App\Http\Controllers;

use App\Services\FriendService;
use App\Repositories\FriendRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function __construct(
        private FriendService    $service,
        private FriendRepository $friendRepo,
        private UserRepository   $users,
    ) {}

    /** Trang danh sách bạn bè */
    public function index(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $friends  = $this->service->getFriendsWithInfo($authUser->user_id);
        $requests = $this->service->getPendingRequestsWithInfo($authUser->user_id);
        $blocked  = $this->friendRepo->getBlockedIds($authUser->user_id);

        $blockedUsers = [];
        foreach ($blocked as $bid) {
            $u = $this->users->findById($bid);
            if ($u) $blockedUsers[] = $u;
        }

        // ── Friend Suggestions (friends-of-friends) ──
        $myFriendIds = $this->friendRepo->getFriendIds($authUser->user_id);
        $suggestMap  = [];   // user_id => mutual count

        foreach ($myFriendIds as $friendId) {
            $friendsOfFriend = $this->friendRepo->getFriendIds($friendId);
            foreach ($friendsOfFriend as $fof) {
                if ($fof === $authUser->user_id) continue;                          // skip self
                if (in_array($fof, $myFriendIds)) continue;                        // already friend
                if ($this->friendRepo->isBlocked($authUser->user_id, $fof)) continue;  // blocked
                if ($this->friendRepo->isBlocked($fof, $authUser->user_id)) continue;  // blocked_by
                $suggestMap[$fof] = ($suggestMap[$fof] ?? 0) + 1;
            }
        }

        arsort($suggestMap);   // sort by mutual count descending
        $suggestions = [];
        foreach (array_slice(array_keys($suggestMap), 0, 5) as $uid) {
            $u = $this->users->findById($uid);
            if ($u) {
                $hasSent     = $this->friendRepo->hasPendingRequest($authUser->user_id, $uid);
                $hasReceived = $this->friendRepo->hasPendingRequest($uid, $authUser->user_id);
                $suggestions[] = [
                    'user'         => $u,
                    'mutual'       => $suggestMap[$uid],
                    'has_sent'     => $hasSent,
                    'has_received' => $hasReceived,
                ];
            }
        }

        return view('friends.index', compact('authUser', 'friends', 'requests', 'blockedUsers', 'suggestions'));
    }

    /** GET — tìm kiếm user để xem hồ sơ trước khi kết bạn */
    public function searchUser(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $username = trim($request->query('username', ''));

        if (!$username) {
            return response()->json(['success' => false, 'error' => 'Vui lòng nhập tên đăng nhập.']);
        }

        $target = $this->users->findByUsername($username);
        if (!$target) {
            return response()->json(['success' => false, 'error' => "Không tìm thấy người dùng \"{$username}\"."]);
        }

        $isMe               = $target->user_id === $authUser->user_id;
        $isFriend           = $this->friendRepo->isFriend($authUser->user_id, $target->user_id);
        $hasPendingSent     = $this->friendRepo->hasPendingRequest($authUser->user_id, $target->user_id);
        $hasPendingReceived = $this->friendRepo->hasPendingRequest($target->user_id, $authUser->user_id);
        $isBlocked          = $this->friendRepo->isBlocked($authUser->user_id, $target->user_id);
        $isBlockedBy        = $this->friendRepo->isBlocked($target->user_id, $authUser->user_id);

        return response()->json([
            'success'  => true,
            'user'     => [
                'user_id'      => $target->user_id,
                'username'     => $target->username,
                'name'         => $target->getName(),
                'display_name' => $target->display_name,
                'avatar_url'   => $target->avatar_url,
                'bio'          => $target->bio ?: 'Chưa cập nhật tiểu sử.',
                'role'         => $target->role,
                'is_online'    => (bool) $target->is_online,
                'created_at'   => $target->created_at ? \Carbon\Carbon::createFromTimestampMs($target->created_at)->format('d/m/Y') : '',
            ],
            'relation' => [
                'is_me'                => $isMe,
                'is_friend'            => $isFriend,
                'has_pending_sent'     => $hasPendingSent,
                'has_pending_received' => $hasPendingReceived,
                'is_blocked'           => $isBlocked,
                'is_blocked_by'        => $isBlockedBy,
                'can_send_request'     => !$isMe && !$isFriend && !$hasPendingSent && !$hasPendingReceived && !$isBlocked && !$isBlockedBy,
            ],
        ]);
    }

    /** GET — xem profile của user theo ID */
    public function getUserProfile(Request $request, string $userId)
    {
        $authUser = $request->attributes->get('auth_user');
        $target   = $this->users->findById($userId);
        if (!$target) {
            return response()->json(['success' => false, 'error' => 'Người dùng không tồn tại.']);
        }

        $isMe               = $target->user_id === $authUser->user_id;
        $isFriend           = $this->friendRepo->isFriend($authUser->user_id, $target->user_id);
        $hasPendingSent     = $this->friendRepo->hasPendingRequest($authUser->user_id, $target->user_id);
        $hasPendingReceived = $this->friendRepo->hasPendingRequest($target->user_id, $authUser->user_id);
        $isBlocked          = $this->friendRepo->isBlocked($authUser->user_id, $target->user_id);
        $isBlockedBy        = $this->friendRepo->isBlocked($target->user_id, $authUser->user_id);
        $nickname           = $this->friendRepo->getNickname($authUser->user_id, $target->user_id);

        return response()->json([
            'success'  => true,
            'user'     => [
                'user_id'      => $target->user_id,
                'username'     => $target->username,
                'name'         => $target->getName(),
                'display_name' => $target->display_name,
                'avatar_url'   => $target->avatar_url,
                'bio'          => $target->bio ?: 'Chưa cập nhật tiểu sử.',
                'role'         => $target->role,
                'is_online'    => (bool) $target->is_online,
                'created_at'   => $target->created_at ? \Carbon\Carbon::createFromTimestampMs($target->created_at)->format('d/m/Y') : '',
                'nickname'     => $nickname,
            ],
            'relation' => [
                'is_me'                => $isMe,
                'is_friend'            => $isFriend,
                'has_pending_sent'     => $hasPendingSent,
                'has_pending_received' => $hasPendingReceived,
                'is_blocked'           => $isBlocked,
                'is_blocked_by'        => $isBlockedBy,
                'can_send_request'     => !$isMe && !$isFriend && !$hasPendingSent && !$hasPendingReceived && !$isBlocked && !$isBlockedBy,
            ],
        ]);
    }

    /** POST — tìm user và gửi lời mời kết bạn */
    public function sendRequest(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');

        $target = null;
        if ($request->filled('user_id')) {
            $target = $this->users->findById($request->user_id);
        } elseif ($request->filled('username')) {
            $target = $this->users->findByUsername($request->username);
        }

        if (!$target) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Không tìm thấy người dùng.'], 404);
            }
            return back()->with('error', "Không tìm thấy người dùng.");
        }

        try {
            $this->service->sendRequest($authUser->user_id, $target->user_id, $request->message ?? '');
            $msg = "Đã gửi lời mời kết bạn đến {$target->getName()}.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return back()->with('success', $msg);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /** POST — chấp nhận lời mời */
    public function acceptRequest(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['from_user_id' => 'required|string']);

        try {
            $this->service->acceptRequest($request->from_user_id, $authUser->user_id);
            return back()->with('success', 'Đã chấp nhận lời mời kết bạn.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** POST — từ chối lời mời */
    public function rejectRequest(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['from_user_id' => 'required|string']);

        $this->service->rejectRequest($request->from_user_id, $authUser->user_id);
        return back()->with('success', 'Đã từ chối lời mời kết bạn.');
    }

    /** POST — xóa bạn */
    public function removeFriend(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['friend_id' => 'required|string']);

        try {
            $this->service->removeFriend($authUser->user_id, $request->friend_id);
            return back()->with('success', 'Đã xóa bạn bè.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** POST — chặn người dùng */
    public function blockUser(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $targetId = $request->target_id;

        if (!$targetId && $request->filled('username')) {
            $u = $this->users->findByUsername(strtolower(trim($request->username)));
            $targetId = $u?->user_id;
        }

        if (!$targetId) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Không tìm thấy người dùng để chặn.'], 404);
            }
            return back()->with('error', 'Không tìm thấy người dùng để chặn.');
        }

        try {
            $this->service->blockUser($authUser->user_id, $targetId);
            $targetUser = $this->users->findById($targetId);
            $msg = "Đã chặn người dùng " . ($targetUser?->getName() ?? '') . ".";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return back()->with('success', $msg);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /** POST — bỏ chặn */
    public function unblockUser(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['target_id' => 'required|string']);

        $this->service->unblockUser($authUser->user_id, $request->target_id);
        $targetUser = $this->users->findById($request->target_id);
        $msg = "Đã gỡ chặn thành công cho " . ($targetUser?->getName() ?? 'người dùng') . ".";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return back()->with('success', $msg);
    }

    /** POST — đặt nickname */
    public function setNickname(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate([
            'friend_id' => 'required|string',
            'nickname'  => 'nullable|string|max:50',
        ]);

        $this->service->setNickname($authUser->user_id, $request->friend_id, $request->nickname ?? '');
        return back()->with('success', 'Đã cập nhật nickname.');
    }
}
