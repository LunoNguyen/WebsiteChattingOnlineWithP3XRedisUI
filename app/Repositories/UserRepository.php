<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * UserRepository — Tương tác với Redis cho toàn bộ namespace user:*
 *
 * Key patterns sử dụng:
 *   user:{id}                  — Hash   — profile
 *   user:idx:username:{name}   — String — index tra nhanh
 *   user:idx:email:{email}     — String — index email
 *   user:online                — Set    — danh sách đang online
 *   user:all                   — Set    — tập hợp tất cả user_id
 */
class UserRepository
{
    /* ─── Key generators ─── */
    private function profileKey(string $id): string         { return "user:{$id}"; }
    private function usernameIdxKey(string $u): string      { return "user:idx:username:{$u}"; }
    private function emailIdxKey(string $e): string         { return "user:idx:email:{$e}"; }
    private function onlineKey(): string                    { return 'user:online'; }
    private function allUsersKey(): string                  { return 'user:all'; }

    /* ─── Create ─── */
    public function create(array $data): User
    {
        $id   = $data['user_id'] ?? Str::uuid()->toString();
        $now  = (int) (microtime(true) * 1000);

        $user = new User(array_merge($data, [
            'user_id'    => $id,
            'status'     => $data['status']  ?? 'active',
            'role'       => $data['role']    ?? 'user',
            'created_at' => $now,
            'last_seen'  => $now,
            'is_online'  => 0,
        ]));

        Redis::hMSet($this->profileKey($id), $user->toArray());
        Redis::set($this->usernameIdxKey($user->username), $id);
        Redis::set($this->emailIdxKey($user->email), $id);
        Redis::sAdd($this->allUsersKey(), $id);

        return $user;
    }

    /* ─── Find ─── */
    public function findById(string $id): ?User
    {
        $data = Redis::hGetAll($this->profileKey($id));
        return $data ? new User($data) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $id = Redis::get($this->usernameIdxKey($username));
        return $id ? $this->findById($id) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $id = Redis::get($this->emailIdxKey($email));
        return $id ? $this->findById($id) : null;
    }

    /* ─── Update ─── */
    public function update(string $id, array $fields): bool
    {
        $existing = $this->findById($id);
        if (!$existing) return false;

        // Cập nhật index username nếu thay đổi
        if (isset($fields['username']) && $fields['username'] !== $existing->username) {
            Redis::del($this->usernameIdxKey($existing->username));
            Redis::set($this->usernameIdxKey($fields['username']), $id);
        }
        // Cập nhật index email nếu thay đổi
        if (isset($fields['email']) && $fields['email'] !== $existing->email) {
            Redis::del($this->emailIdxKey($existing->email));
            Redis::set($this->emailIdxKey($fields['email']), $id);
        }

        Redis::hMSet($this->profileKey($id), $fields);
        return true;
    }

    /* ─── Delete (soft delete) ─── */
    public function delete(string $id): bool
    {
        return (bool) $this->update($id, ['status' => 'deleted']);
    }

    /* ─── Ban / Unban ─── */
    public function ban(string $id): bool
    {
        return (bool) $this->update($id, ['status' => 'banned']);
    }

    public function unban(string $id): bool
    {
        return (bool) $this->update($id, ['status' => 'active']);
    }

    /* ─── Online status ─── */
    public function setOnline(string $id): void
    {
        $now = (int) (microtime(true) * 1000);
        Redis::hMSet($this->profileKey($id), ['is_online' => 1, 'last_seen' => $now]);
        Redis::sAdd($this->onlineKey(), $id);
    }

    public function setOffline(string $id): void
    {
        $now = (int) (microtime(true) * 1000);
        Redis::hMSet($this->profileKey($id), ['is_online' => 0, 'last_seen' => $now]);
        Redis::sRem($this->onlineKey(), $id);
    }

    /** Lấy danh sách user đang online */
    public function getOnlineUserIds(): array
    {
        return Redis::sMembers($this->onlineKey()) ?? [];
    }

    /* ─── List all users ─── */
    /** @return User[] */
    public function getAllUsers(): array
    {
        $ids = Redis::sMembers($this->allUsersKey()) ?? [];
        $users = [];
        foreach ($ids as $id) {
            $user = $this->findById($id);
            if ($user) $users[] = $user;
        }
        return $users;
    }

    /* ─── Existence checks ─── */
    public function usernameExists(string $username): bool
    {
        return (bool) Redis::exists($this->usernameIdxKey($username));
    }

    public function emailExists(string $email): bool
    {
        return (bool) Redis::exists($this->emailIdxKey($email));
    }

    /* ─── Update last seen ─── */
    public function touchLastSeen(string $id): void
    {
        Redis::hSet($this->profileKey($id), 'last_seen', (int)(microtime(true)*1000));
    }
}
