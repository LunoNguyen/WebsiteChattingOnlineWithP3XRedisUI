<?php

namespace App\Models;

/**
 * User model — plain PHP class (không dùng Eloquent).
 * Dữ liệu lưu trong Redis Hash: user:{user_id}
 */
class User
{
    public string $user_id;
    public string $username;
    public string $display_name;
    public string $password_hash;
    public string $email;
    public string $avatar_url;
    public string $bio;
    public string $status;   // active | banned | deleted
    public string $role;     // user | admin
    public int    $last_seen;
    public int    $created_at;
    public int    $is_online; // 1 | 0

    public function __construct(array $data = [])
    {
        $this->user_id      = $data['user_id']      ?? '';
        $this->username     = $data['username']     ?? '';
        $this->display_name = $data['display_name'] ?? '';
        $this->password_hash= $data['password_hash']?? '';
        $this->email        = $data['email']        ?? '';
        $this->avatar_url   = $data['avatar_url']   ?? '';
        $this->bio          = $data['bio']           ?? '';
        $this->status       = $data['status']       ?? 'active';
        $this->role         = $data['role']         ?? 'user';
        $this->last_seen    = (int) ($data['last_seen']  ?? 0);
        $this->created_at   = (int) ($data['created_at'] ?? 0);
        $this->is_online    = (int) ($data['is_online']  ?? 0);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBanned(): bool
    {
        return $this->status === 'banned';
    }

    public function toArray(): array
    {
        return [
            'user_id'      => $this->user_id,
            'username'     => $this->username,
            'display_name' => $this->display_name,
            'password_hash'=> $this->password_hash,
            'email'        => $this->email,
            'avatar_url'   => $this->avatar_url,
            'bio'          => $this->bio,
            'status'       => $this->status,
            'role'         => $this->role,
            'last_seen'    => $this->last_seen,
            'created_at'   => $this->created_at,
            'is_online'    => $this->is_online,
        ];
    }

    /** Trả về display name hoặc username nếu chưa đặt */
    public function getName(): string
    {
        return $this->display_name ?: $this->username;
    }
}
