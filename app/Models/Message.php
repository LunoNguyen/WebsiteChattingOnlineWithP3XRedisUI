<?php

namespace App\Models;

/**
 * Message model — Redis Hash: msg:{msg_id}
 */
class Message
{
    public string $msg_id;
    public string $conv_id;        // conv_id hoặc group_id
    public string $conv_type;      // 'dm' | 'group'
    public string $sender_id;
    public string $type;           // text | image | file | sticker
    public string $content;
    public string $reply_to;       // msg_id được reply (rỗng nếu không)
    public string $status;         // sent | seen | deleted | edited
    public int    $created_at;     // Unix ms
    public int    $edited_at;      // Unix ms (0 nếu chưa sửa)
    public string $original_content;

    public function __construct(array $data = [])
    {
        $this->msg_id           = $data['msg_id']           ?? '';
        $this->conv_id          = $data['conv_id']          ?? '';
        $this->conv_type        = $data['conv_type']        ?? 'dm';
        $this->sender_id        = $data['sender_id']        ?? '';
        $this->type             = $data['type']             ?? 'text';
        $this->content          = $data['content']          ?? '';
        $this->reply_to         = $data['reply_to']         ?? '';
        $this->status           = $data['status']           ?? 'sent';
        $this->created_at       = (int) ($data['created_at']        ?? 0);
        $this->edited_at        = (int) ($data['edited_at']         ?? 0);
        $this->original_content = $data['original_content'] ?? '';
    }

    public function isDeleted(): bool
    {
        return $this->status === 'deleted';
    }

    public function isEdited(): bool
    {
        return $this->status === 'edited';
    }

    public function toArray(): array
    {
        return [
            'msg_id'           => $this->msg_id,
            'conv_id'          => $this->conv_id,
            'conv_type'        => $this->conv_type,
            'sender_id'        => $this->sender_id,
            'type'             => $this->type,
            'content'          => $this->content,
            'reply_to'         => $this->reply_to,
            'status'           => $this->status,
            'created_at'       => $this->created_at,
            'edited_at'        => $this->edited_at,
            'original_content' => $this->original_content,
        ];
    }
}
