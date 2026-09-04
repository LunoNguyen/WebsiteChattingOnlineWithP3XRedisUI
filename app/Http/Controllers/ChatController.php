<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use App\Services\FriendService;
use App\Services\GroupService;
use App\Repositories\MessageRepository;
use App\Repositories\UserRepository;
use App\Repositories\GroupRepository;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private ChatService        $chat,
        private FriendService      $friends,
        private GroupService       $groupService,
        private MessageRepository  $messages,
        private UserRepository     $users,
        private GroupRepository    $groups,
    ) {}

    /** Trang chính — danh sách chat */
    public function index(Request $request)
    {
        $authUser  = $request->attributes->get('auth_user');
        $convIds   = $this->chat->getUserChatList($authUser->user_id);
        $chatList  = [];
        $unreadMap = $this->messages->getUnreadCounts($authUser->user_id);

        foreach ($convIds as $id) {
            // Kiểm tra là DM hay Group
            $convData = \Illuminate\Support\Facades\Redis::hGetAll("conv:{$id}");
            $groupData = null;
            if ($convData) {
                // DM conversation
                $otherId   = $convData['user1_id'] === $authUser->user_id
                    ? $convData['user2_id'] : $convData['user1_id'];
                $otherUser = $this->users->findById($otherId);
                $chatList[] = [
                    'type'          => 'dm',
                    'id'            => $id,
                    'other_user_id' => $otherId,
                    'name'          => $this->friends->getNickname($authUser->user_id, $otherId)
                                    ?: ($otherUser?->getName() ?? 'Unknown'),
                    'avatar'        => $otherUser?->avatar_url ?? '',
                    'is_online'     => (bool)($otherUser?->is_online ?? 0),
                    'last_msg_at'   => (int)($convData['last_msg_at'] ?? 0),
                    'unread'        => (int)($unreadMap[$id] ?? 0),
                ];
            } else {
                // Group
                $group = $this->groups->findById($id);
                if ($group) {
                    $chatList[] = [
                        'type'       => 'group',
                        'id'         => $id,
                        'name'       => $group->name,
                        'avatar'     => $group->avatar_url,
                        'is_online'  => false,
                        'last_msg_at'=> $group->last_msg_at,
                        'unread'     => (int)($unreadMap[$id] ?? 0),
                    ];
                }
            }
        }

        // Sắp xếp theo tin nhắn mới nhất
        usort($chatList, fn($a, $b) => $b['last_msg_at'] <=> $a['last_msg_at']);

        $totalUnread = $this->messages->getTotalUnread($authUser->user_id);

        return view('chat.index', compact('chatList', 'authUser', 'totalUnread'));
    }

    /** Mở cuộc trò chuyện DM với 1 người */
    public function showDM(Request $request, string $userId)
    {
        $authUser  = $request->attributes->get('auth_user');
        $otherUser = $this->users->findById($userId);

        if (!$otherUser) {
            // Trường hợp truyền vào conv_id thay vì user_id
            $convData = \Illuminate\Support\Facades\Redis::hGetAll("conv:{$userId}");
            if ($convData && isset($convData['user1_id'], $convData['user2_id'])) {
                $actualOtherId = $convData['user1_id'] === $authUser->user_id
                    ? $convData['user2_id'] : $convData['user1_id'];
                return redirect()->route('chat.dm', $actualOtherId);
            }
            abort(404, 'Người dùng không tồn tại.');
        }

        $conv     = $this->messages->findOrCreateConversation($authUser->user_id, $userId);
        $messages = $this->chat->getDirectMessages($authUser->user_id, $userId);
        $nickname = app(\App\Repositories\FriendRepository::class)->getNickname($authUser->user_id, $userId);
        $isFriend = app(\App\Repositories\FriendRepository::class)->isFriend($authUser->user_id, $userId);
        $isBlocked= app(\App\Repositories\FriendRepository::class)->isBlocked($authUser->user_id, $userId);

        return view('chat.show', compact(
            'authUser', 'otherUser', 'conv', 'messages',
            'nickname', 'isFriend', 'isBlocked'
        ));
    }

    /** Mở group chat */
    public function showGroup(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $groupInfo = $this->groupService->getGroupWithMembers($groupId);

        if (!$groupInfo) abort(404, 'Nhóm không tồn tại.');
        if (!$this->groups->isMember($groupId, $authUser->user_id)) {
            abort(403, 'Bạn không phải thành viên nhóm này.');
        }

        $messages = $this->chat->getGroupMessages($groupId, $authUser->user_id);
        $myRole   = $this->groups->getMemberRole($groupId, $authUser->user_id);

        return view('chat.group', compact('authUser', 'groupInfo', 'messages', 'myRole'));
    }

    /** POST — gửi tin nhắn DM */
    public function sendDM(Request $request, string $userId)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['content' => 'required|string|max:5000']);

        try {
            $msg = $this->chat->sendDirectMessage(
                $authUser->user_id,
                $userId,
                $request->content,
                'text',
                $request->reply_to ?? ''
            );
            return response()->json(['success' => true, 'message' => $msg->toArray()]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** POST — gửi tin nhắn Group */
    public function sendGroup(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['content' => 'required|string|max:5000']);

        try {
            $msg = $this->chat->sendGroupMessage(
                $authUser->user_id, $groupId, $request->content
            );
            return response()->json(['success' => true, 'message' => $msg->toArray()]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** PUT — sửa tin nhắn */
    public function editMessage(Request $request, string $msgId)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['content' => 'required|string|max:5000']);

        try {
            $this->chat->editMessage($authUser->user_id, $msgId, $request->content);
            return response()->json(['success' => true]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** DELETE — xóa tin nhắn */
    public function deleteMessage(Request $request, string $msgId)
    {
        $authUser = $request->attributes->get('auth_user');

        try {
            $this->chat->deleteMessage($authUser->user_id, $msgId, $authUser->isAdmin());
            return response()->json(['success' => true]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** GET — load thêm tin nhắn cũ (phân trang) */
    public function loadMoreDM(Request $request, string $userId)
    {
        $authUser = $request->attributes->get('auth_user');
        $page = (int) $request->query('page', 2);
        $msgs = $this->chat->getDirectMessages($authUser->user_id, $userId, $page);

        return response()->json([
            'messages' => array_map(fn($m) => $m->toArray(), $msgs),
            'has_more' => count($msgs) === 20,
        ]);
    }
}
