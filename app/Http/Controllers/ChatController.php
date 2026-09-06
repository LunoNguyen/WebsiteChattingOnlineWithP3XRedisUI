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
        $request->validate([
            'content' => 'required|string|max:20000',
            'type'    => 'nullable|string|in:text,poll,file',
        ]);

        $type = $request->input('type', 'text') ?: 'text';

        // Không dùng throttle cưỡng bức để giảm độ trễ tối đa khi gửi tin nhắn

        try {
            $finalContent = ($type === 'text') ? $this->wrapChatMessage($request->content, 75) : $request->content;
            $msg = $this->chat->sendDirectMessage(
                $authUser->user_id,
                $userId,
                $finalContent,
                $type,
                $request->reply_to ?? ''
            );
            $msgArr = $msg->toArray();
            $msgArr['formatted_time'] = \Carbon\Carbon::createFromTimestampMs($msg->created_at)->format('H:i');
            $msgArr['sender_name'] = 'Bạn';

            // Thêm thông tin reply nếu có
            if (!empty($msg->reply_to)) {
                $replyMsg = $this->messages->findById($msg->reply_to);
                if ($replyMsg) {
                    $otherUser = $this->users->findById($userId);
                    $otherName = $otherUser ? $otherUser->getName() : 'Người dùng';
                    $nickname = $this->friends->getNickname($authUser->user_id, $userId);
                    if ($nickname) {
                        $otherName = $nickname;
                    }
                    $msgArr['reply_sender'] = $replyMsg->sender_id === $authUser->user_id ? 'Bạn' : $otherName;
                    if ($replyMsg->isDeleted()) {
                        $msgArr['reply_content'] = 'Tin nhắn đã bị xóa';
                    } elseif ($replyMsg->type === 'poll') {
                        $pd = json_decode($replyMsg->content, true);
                        $msgArr['reply_content'] = '[Bình chọn] ' . ($pd['question'] ?? '');
                    } elseif ($replyMsg->type === 'file') {
                        $msgArr['reply_content'] = '[Tệp đính kèm]';
                    } else {
                        $msgArr['reply_content'] = $replyMsg->content;
                    }
                }
            }

            return response()->json(['success' => true, 'message' => $msgArr], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('sendDM error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage() ?: 'Lỗi gửi tin nhắn.'], 422, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /** POST — gửi tin nhắn Group */
    public function sendGroup(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate([
            'content' => 'required|string|max:20000',
            'type'    => 'nullable|string|in:text,poll,file',
        ]);

        $type = $request->input('type', 'text') ?: 'text';

        try {
            $finalContent = ($type === 'text') ? $this->wrapChatMessage($request->content, 75) : $request->content;
            $msg = $this->chat->sendGroupMessage(
                $authUser->user_id,
                $groupId,
                $finalContent,
                $type,
                $request->reply_to ?? ''
            );
            $msgArr = $msg->toArray();
            $msgArr['formatted_time'] = \Carbon\Carbon::createFromTimestampMs($msg->created_at)->format('H:i');
            $msgArr['sender_name'] = 'Bạn';

            // Thêm thông tin reply nếu có
            if (!empty($msg->reply_to)) {
                $replyMsg = $this->messages->findById($msg->reply_to);
                if ($replyMsg) {
                    $repSender = $replyMsg->sender_id === $authUser->user_id ? 'Bạn' : 'Thành viên';
                    $groupInfo = $this->groupService->getGroupWithMembers($groupId);
                    if ($groupInfo) {
                        foreach ($groupInfo['members'] as $m) {
                            if ($m['user']->user_id === $replyMsg->sender_id) {
                                $repSender = $m['nickname'] ?: $m['user']->getName();
                                break;
                            }
                        }
                    }
                    $msgArr['reply_sender'] = $repSender;
                    if ($replyMsg->isDeleted()) {
                        $msgArr['reply_content'] = 'Tin nhắn đã bị xóa';
                    } elseif ($replyMsg->type === 'poll') {
                        $pd = json_decode($replyMsg->content, true);
                        $msgArr['reply_content'] = '[Bình chọn] ' . ($pd['question'] ?? '');
                    } elseif ($replyMsg->type === 'file') {
                        $msgArr['reply_content'] = '[Tệp đính kèm]';
                    } else {
                        $msgArr['reply_content'] = $replyMsg->content;
                    }
                }
            }

            return response()->json(['success' => true, 'message' => $msgArr], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('sendGroup error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage() ?: 'Lỗi gửi tin nhắn.'], 422, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /** PUT — sửa tin nhắn */
    public function editMessage(Request $request, string $msgId)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['content' => 'required|string|max:5000']);

        try {
            $finalContent = $this->wrapChatMessage($request->content, 75);
            $this->chat->editMessage($authUser->user_id, $msgId, $finalContent);
            return response()->json(['success' => true], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /** DELETE — xóa tin nhắn */
    public function deleteMessage(Request $request, string $msgId)
    {
        $authUser = $request->attributes->get('auth_user');

        try {
            $this->chat->deleteMessage($authUser->user_id, $msgId, $authUser->isAdmin());
            return response()->json(['success' => true], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /** POST — bình chọn trong tin nhắn poll */
    public function votePoll(Request $request, string $msgId)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate([
            'option_index' => 'required|integer|min:0|max:20',
        ]);

        $msg = $this->messages->findById($msgId);
        if (!$msg) {
            return response()->json(['success' => false, 'error' => 'Không tìm thấy cuộc bình chọn.'], 404, [], JSON_UNESCAPED_UNICODE);
        }

        $pollData = json_decode($msg->content, true);
        if (!$pollData || !isset($pollData['options'])) {
            return response()->json(['success' => false, 'error' => 'Dữ liệu bình chọn không hợp lệ.'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        $optionIdx = (int) $request->option_index;
        if (!isset($pollData['options'][$optionIdx])) {
            return response()->json(['success' => false, 'error' => 'Lựa chọn không tồn tại.'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        if (!isset($pollData['votes']) || !is_array($pollData['votes'])) {
            $pollData['votes'] = [];
        }

        foreach ($pollData['options'] as $i => $opt) {
            if (!isset($pollData['votes'][$i]) || !is_array($pollData['votes'][$i])) {
                $pollData['votes'][$i] = [];
            }
        }

        $hasVotedThis = in_array($authUser->user_id, $pollData['votes'][$optionIdx]);

        // Xóa vote của user ở tất cả các option
        foreach ($pollData['votes'] as $i => $voters) {
            $pollData['votes'][$i] = array_values(array_filter($voters, fn($uid) => $uid !== $authUser->user_id));
        }

        if (!$hasVotedThis) {
            $pollData['votes'][$optionIdx][] = $authUser->user_id;
        }

        $newContent = json_encode($pollData, JSON_UNESCAPED_UNICODE);
        $this->messages->updateMessageContent($msgId, $newContent);

        // Gửi tin nhắn thông báo hệ thống về lượt bình chọn
        try {
            $userName = $authUser->getName();
            $chosenOption = $pollData['options'][$optionIdx] ?? '';
            $sysText = !$hasVotedThis
                ? "{$userName} đã bình chọn cho: \"{$chosenOption}\""
                : "{$userName} đã hủy bình chọn cho: \"{$chosenOption}\"";

            if ($msg->conv_type === 'group' || !empty($msg->conv_id)) {
                if ($this->groups->findById($msg->conv_id)) {
                    $this->groupService->sendSystemMessage($msg->conv_id, $sysText);
                } else {
                    // Trong cuộc trò chuyện DM
                    $this->chat->sendDirectMessage($authUser->user_id, $msg->conv_id, $sysText, 'system');
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Lỗi gửi thông báo hệ thống bình chọn: ' . $e->getMessage());
        }

        return response()->json([
            'success'   => true,
            'poll_data' => $pollData,
        ], 200, [], JSON_UNESCAPED_UNICODE);
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
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /** GET — lấy các tin nhắn mới hơn timestamp $after (Polling không delay) */
    public function getNewDM(Request $request, string $userId)
    {
        $authUser = $request->attributes->get('auth_user');
        $after = (int) $request->query('after', 0);

        $conv = $this->messages->findOrCreateConversation($authUser->user_id, $userId);
        $newMsgs = $this->messages->getNewMessages($conv->conv_id, $after);

        // Đánh dấu đã đọc
        $this->messages->markConversationRead($conv->conv_id, $authUser->user_id);

        $otherUser = $this->users->findById($userId);
        $otherName = $otherUser ? $otherUser->getName() : 'Người dùng';
        $nickname = $this->friends->getNickname($authUser->user_id, $userId);
        if ($nickname) {
            $otherName = $nickname;
        }

        $data = array_map(function ($m) use ($authUser, $otherName) {
            $arr = $m->toArray();
            $arr['formatted_time'] = \Carbon\Carbon::createFromTimestampMs($m->created_at)->format('H:i');
            $arr['sender_name'] = $m->sender_id === $authUser->user_id ? 'Bạn' : $otherName;

            if (!empty($m->reply_to)) {
                $rep = $this->messages->findById($m->reply_to);
                if ($rep) {
                    $arr['reply_sender'] = $rep->sender_id === $authUser->user_id ? 'Bạn' : $otherName;
                    if ($rep->isDeleted()) {
                        $arr['reply_content'] = 'Tin nhắn đã bị xóa';
                    } elseif ($rep->type === 'poll') {
                        $pd = json_decode($rep->content, true);
                        $arr['reply_content'] = '[Bình chọn] ' . ($pd['question'] ?? '');
                    } elseif ($rep->type === 'file') {
                        $arr['reply_content'] = '[Tệp đính kèm]';
                    } else {
                        $arr['reply_content'] = $rep->content;
                    }
                }
            }

            if ($m->type === 'file' && !$m->isDeleted()) {
                $fd = json_decode($m->content, true) ?: [];
                if (!empty($fd['object_key'])) {
                    $fd['url'] = '/files/serve?key=' . urlencode($fd['object_key']);
                } elseif (!empty($fd['url']) && str_contains($fd['url'], 'files/serve')) {
                    $parsed = parse_url($fd['url']);
                    if (!empty($parsed['query'])) {
                        $fd['url'] = '/files/serve?' . $parsed['query'];
                    }
                }
                $arr['file_data'] = $fd;
            }

            return $arr;
        }, $newMsgs);

        return response()->json([
            'success'  => true,
            'messages' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /** GET — lấy tin nhắn nhóm mới hơn timestamp $after (Polling không delay) */
    public function getNewGroup(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $after = (int) $request->query('after', 0);

        if (!$this->groups->isMember($groupId, $authUser->user_id)) {
            return response()->json(['success' => false, 'error' => 'Không phải thành viên'], 403, [], JSON_UNESCAPED_UNICODE);
        }

        $newMsgs = $this->groups->getNewMessages($groupId, $after);
        $this->messages->markConversationRead($groupId, $authUser->user_id);

        $groupInfo = $this->groupService->getGroupWithMembers($groupId);
        $nicknames = [];
        $names = [];
        if ($groupInfo) {
            foreach ($groupInfo['members'] as $m) {
                $uid = $m['user']->user_id;
                $nicknames[$uid] = $m['nickname'] ?: $m['user']->getName();
                $names[$uid] = $m['user']->getName();
            }
        }

        $data = array_map(function ($m) use ($authUser, $nicknames, $names) {
            $arr = $m->toArray();
            $arr['formatted_time'] = \Carbon\Carbon::createFromTimestampMs($m->created_at)->format('H:i');
            $arr['sender_name'] = $m->sender_id === $authUser->user_id ? 'Bạn' : ($nicknames[$m->sender_id] ?? ($names[$m->sender_id] ?? 'Thành viên'));

            if (!empty($m->reply_to)) {
                $rep = $this->messages->findById($m->reply_to);
                if ($rep) {
                    $repSender = $rep->sender_id === $authUser->user_id ? 'Bạn' : ($nicknames[$rep->sender_id] ?? ($names[$rep->sender_id] ?? 'Thành viên'));
                    $arr['reply_sender'] = $repSender;
                    if ($rep->isDeleted()) {
                        $arr['reply_content'] = 'Tin nhắn đã bị xóa';
                    } elseif ($rep->type === 'poll') {
                        $pd = json_decode($rep->content, true);
                        $arr['reply_content'] = '[Bình chọn] ' . ($pd['question'] ?? '');
                    } elseif ($rep->type === 'file') {
                        $arr['reply_content'] = '[Tệp đính kèm]';
                    } else {
                        $arr['reply_content'] = $rep->content;
                    }
                }
            }

            if ($m->type === 'file' && !$m->isDeleted()) {
                $fd = json_decode($m->content, true) ?: [];
                if (!empty($fd['object_key'])) {
                    $fd['url'] = '/files/serve?key=' . urlencode($fd['object_key']);
                } elseif (!empty($fd['url']) && str_contains($fd['url'], 'files/serve')) {
                    $parsed = parse_url($fd['url']);
                    if (!empty($parsed['query'])) {
                        $fd['url'] = '/files/serve?' . $parsed['query'];
                    }
                }
                $arr['file_data'] = $fd;
            }

            return $arr;
        }, $newMsgs);

        return response()->json([
            'success'  => true,
            'messages' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /** GET — Polling đồng bộ trạng thái toàn cục mỗi 10 giây (dùng chung cho chat, friends, group, profile) */
    public function pollStatus(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        if (!$authUser) {
            return response()->json(['success' => false, 'error' => 'Chưa đăng nhập'], 401);
        }

        $userId = $authUser->user_id;
        $totalUnread = $this->messages->getTotalUnread($userId);
        $pendingRequests = (int) \Illuminate\Support\Facades\Redis::sCard("user:{$userId}:friend_requests");

        // Lấy timestamp tin nhắn mới nhất trong tất cả cuộc trò chuyện của user
        $latestConv = \Illuminate\Support\Facades\Redis::zRevRange("user:{$userId}:convs", 0, 0, ['WITHSCORES' => true]);
        $lastMsgAt = 0;
        if (!empty($latestConv)) {
            $lastMsgAt = (int) reset($latestConv);
        }

        return response()->json([
            'success'                 => true,
            'total_unread'            => $totalUnread,
            'pending_friend_requests' => $pendingRequests,
            'last_msg_at'             => $lastMsgAt,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Tự động ngắt dòng văn bản tin nhắn khi đạt khoảng 75 ký tự:
     * Nếu vị trí ngắt nằm giữa một từ, đưa cả từ đó xuống dòng mới.
     */
    private function wrapChatMessage(string $text, int $limit = 75): string
    {
        $rawLines = explode("\n", $text);
        $resultLines = [];

        foreach ($rawLines as $rawLine) {
            $rawLine = rtrim($rawLine, "\r");
            if (mb_strlen($rawLine, 'UTF-8') <= $limit) {
                $resultLines[] = $rawLine;
                continue;
            }

            // Tách thành các từ và khoảng trắng bằng unicode regex
            $tokens = preg_split('/(\s+)/u', $rawLine, -1, PREG_SPLIT_DELIM_CAPTURE);
            $currentLine = '';

            foreach ($tokens as $token) {
                if ($token === '') continue;

                if ($currentLine === '') {
                    // Nếu token đầu tiên dài hơn cả limit (ví dụ chuỗi liên tục không dấu cách)
                    if (mb_strlen($token, 'UTF-8') > $limit) {
                        while (mb_strlen($token, 'UTF-8') > $limit) {
                            $resultLines[] = mb_substr($token, 0, $limit, 'UTF-8');
                            $token = mb_substr($token, $limit, null, 'UTF-8');
                        }
                        $currentLine = $token;
                    } else {
                        $currentLine = $token;
                    }
                } else {
                    $combined = $currentLine . $token;
                    if (mb_strlen($combined, 'UTF-8') <= $limit) {
                        $currentLine = $combined;
                    } else {
                        // Xuống dòng: đẩy dòng hiện tại vào danh sách
                        $resultLines[] = rtrim($currentLine);
                        // Đưa từ đang xét sang dòng mới
                        $trimmedToken = ltrim($token);
                        if (mb_strlen($trimmedToken, 'UTF-8') > $limit) {
                            while (mb_strlen($trimmedToken, 'UTF-8') > $limit) {
                                $resultLines[] = mb_substr($trimmedToken, 0, $limit, 'UTF-8');
                                $trimmedToken = mb_substr($trimmedToken, $limit, null, 'UTF-8');
                            }
                            $currentLine = $trimmedToken;
                        } else {
                            $currentLine = $trimmedToken;
                        }
                    }
                }
            }

            if ($currentLine !== '') {
                $resultLines[] = rtrim($currentLine);
            }
        }

        return implode("\n", $resultLines);
    }
}

