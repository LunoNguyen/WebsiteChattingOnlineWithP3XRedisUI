<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use App\Services\MinioStorageService;
use App\Repositories\MessageRepository;
use App\Repositories\UserRepository;
use App\Repositories\GroupRepository;
use App\Repositories\FriendRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(
        private MinioStorageService $minio,
        private ChatService         $chat,
        private MessageRepository   $messages,
        private UserRepository      $users,
        private GroupRepository     $groups,
        private FriendRepository    $friends,
    ) {}

    /**
     * Upload file trong chat cá nhân (1-1)
     * POST /chat/dm/{userId}/file
     */
    public function uploadDM(Request $request, string $userId)
    {
        $authUser = $request->attributes->get('auth_user');

        $request->validate([
            'file'     => 'required|file|max:5120', // Giới hạn 5MB
            'reply_to' => 'nullable|string',
        ]);

        try {
            $uploaded = $this->minio->uploadFile($request->file('file'), 'dm');

            // Lưu message với type = 'file', content là JSON chứa thông tin file
            $content = json_encode([
                'object_key' => $uploaded['object_key'],
                'name'       => $uploaded['name'],
                'size'       => $uploaded['formatted_size'],
                'size_bytes' => $uploaded['size'],
                'mime'       => $uploaded['mime'],
                'url'        => $uploaded['url'],
            ], JSON_UNESCAPED_UNICODE);

            $msg = $this->chat->sendDirectMessage(
                $authUser->user_id,
                $userId,
                $content,
                'file',
                $request->reply_to ?? ''
            );

            $msgArr = $msg->toArray();
            $msgArr['formatted_time'] = \Carbon\Carbon::createFromTimestampMs($msg->created_at)->format('H:i');
            $msgArr['sender_name']    = 'Bạn';
            $msgArr['file_data']      = $uploaded;

            // Xử lý thông tin reply preview nếu có
            if (!empty($msg->reply_to)) {
                $replyMsg = $this->messages->findById($msg->reply_to);
                if ($replyMsg) {
                    $otherUser = $this->users->findById($userId);
                    $otherName = $this->friends->getNickname($authUser->user_id, $userId) ?: ($otherUser?->getName() ?? 'Người dùng');
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

            return response()->json([
                'success' => true,
                'message' => $msgArr,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('File upload DM error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage() ?: 'Lỗi tải tệp lên server.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Upload file trong nhóm
     * POST /chat/group/{groupId}/file
     */
    public function uploadGroup(Request $request, string $groupId)
    {
        $authUser = $request->attributes->get('auth_user');

        $request->validate([
            'file'     => 'required|file|max:5120', // Giới hạn 5MB
            'reply_to' => 'nullable|string',
        ]);

        if (!$this->groups->isMember($groupId, $authUser->user_id)) {
            return response()->json(['success' => false, 'error' => 'Bạn không phải thành viên nhóm này.'], 403, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $uploaded = $this->minio->uploadFile($request->file('file'), 'group');

            $content = json_encode([
                'object_key' => $uploaded['object_key'],
                'name'       => $uploaded['name'],
                'size'       => $uploaded['formatted_size'],
                'size_bytes' => $uploaded['size'],
                'mime'       => $uploaded['mime'],
                'url'        => $uploaded['url'],
            ], JSON_UNESCAPED_UNICODE);

            $msg = $this->chat->sendGroupMessage(
                $authUser->user_id,
                $groupId,
                $content,
                'file',
                $request->reply_to ?? ''
            );

            $msgArr = $msg->toArray();
            $msgArr['formatted_time'] = \Carbon\Carbon::createFromTimestampMs($msg->created_at)->format('H:i');
            $msgArr['sender_name']    = 'Bạn';
            $msgArr['file_data']      = $uploaded;

            if (!empty($msg->reply_to)) {
                $replyMsg = $this->messages->findById($msg->reply_to);
                if ($replyMsg) {
                    $repSender = $replyMsg->sender_id === $authUser->user_id ? 'Bạn' : 'Thành viên';
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

            return response()->json([
                'success' => true,
                'message' => $msgArr,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('File upload Group error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage() ?: 'Lỗi tải tệp lên server.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Phục vụ tải / xem tệp từ MinIO
     * GET /files/serve?key=...
     */
    public function serve(Request $request)
    {
        $key = $request->query('key');
        if (!$key) {
            abort(404, 'Không tìm thấy tệp.');
        }

        $file = $this->minio->getFile($key);
        if (!$file) {
            abort(404, 'Tệp không tồn tại hoặc đã bị xóa.');
        }

        $fileName = basename($key);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // MIME types map chuẩn xác
        $mimeTypes = [
            'mp4'  => 'video/mp4',
            'webm' => 'video/webm',
            'mov'  => 'video/quicktime',
            'm4v'  => 'video/mp4',
            'mp3'  => 'audio/mpeg',
            'wav'  => 'audio/wav',
            'ogg'  => 'audio/ogg',
            'm4a'  => 'audio/mp4',
            'txt'  => 'text/plain; charset=utf-8',
            'log'  => 'text/plain; charset=utf-8',
            'csv'  => 'text/csv; charset=utf-8',
            'json' => 'application/json',
            'md'   => 'text/markdown; charset=utf-8',
            'pdf'  => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc'  => 'application/msword',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
        ];

        $mime = $mimeTypes[$ext] ?? ($file['mime'] ?: 'application/octet-stream');

        // Các tệp cho phép xem inline (preview trên web)
        $isInline = str_starts_with($mime, 'image/') 
            || str_starts_with($mime, 'video/') 
            || str_starts_with($mime, 'audio/') 
            || str_starts_with($mime, 'text/') 
            || in_array($mime, [
                'application/pdf',
                'application/json',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword'
            ])
            || in_array($ext, ['mp4', 'webm', 'mov', 'mp3', 'txt', 'csv', 'log', 'json', 'md', 'pdf', 'docx', 'doc']);

        $disposition = $isInline ? 'inline; filename="' . rawurlencode($fileName) . '"' : 'attachment; filename="' . rawurlencode($fileName) . '"';

        return response($file['content'], 200, [
            'Content-Type'        => $mime,
            'Content-Length'      => $file['size'],
            'Content-Disposition' => $disposition,
            'Cache-Control'       => 'public, max-age=86400',
        ]);
    }
}