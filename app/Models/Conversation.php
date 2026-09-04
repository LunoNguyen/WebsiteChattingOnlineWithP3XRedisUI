<?php

namespace App\Models;

/**
 * Conversation model — Redis Hash: conv:{conv_id}
 * Cuộc trò chuyện 1-1 giữa 2 người dùng
 */
class Conversation
{
    public string $conv_id;
    public string $user1_id;
    public string $user2_id;
    public int    $created_at;
    public string $last_msg_id;
    public int    $last_msg_at;

    public function __construct(array $data = [])
    {
        $this->conv_id     = $data['conv_id']     ?? '';
        $this->user1_id    = $data['user1_id']    ?? '';
        $this->user2_id    = $data['user2_id']    ?? '';
        $this->created_at  = (int) ($data['created_at']  ?? 0);
        $this->last_msg_id = $data['last_msg_id'] ?? '';
        $this->last_msg_at = (int) ($data['last_msg_at'] ?? 0);
    }

    /** Lấy ID của người còn lại trong cuộc trò chuyện */
    public function getOtherUserId(string $myId): string
    {
        return $this->user1_id === $myId ? $this->user2_id : $this->user1_id;
    }

    public function toArray(): array
    {
        return [
            'conv_id'     => $this->conv_id,
            'user1_id'    => $this->user1_id,
            'user2_id'    => $this->user2_id,
            'created_at'  => $this->created_at,
            'last_msg_id' => $this->last_msg_id,
            'last_msg_at' => $this->last_msg_at,
        ];
    }
}
