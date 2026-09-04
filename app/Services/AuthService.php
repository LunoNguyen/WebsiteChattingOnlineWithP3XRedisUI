<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * AuthService — đăng ký, đăng nhập, quản lý session
 *
 * Session key: user:session:{token}  — Hash + TTL 24h
 */
class AuthService
{
    private const SESSION_TTL = 86400; // 24 giờ

    public function __construct(private UserRepository $users) {}

    /* ─── Register ─── */

    /**
     * @throws \RuntimeException nếu username/email đã tồn tại
     */
    public function register(array $data): User
    {
        if ($this->users->usernameExists($data['username'])) {
            throw new \RuntimeException('Tên đăng nhập đã tồn tại.');
        }
        if ($this->users->emailExists($data['email'])) {
            throw new \RuntimeException('Email đã được sử dụng.');
        }

        $user = $this->users->create([
            'username'      => strtolower(trim($data['username'])),
            'display_name'  => $data['display_name'] ?? $data['username'],
            'password_hash' => bcrypt($data['password']),
            'email'         => strtolower(trim($data['email'])),
            'avatar_url'    => $data['avatar_url'] ?? '',
            'bio'           => '',
            'role'          => $data['role'] ?? 'user',
        ]);

        return $user;
    }

    /* ─── Login ─── */

    /**
     * Xác thực tài khoản
     * @throws \RuntimeException nếu sai thông tin hoặc bị ban
     */
    public function login(string $username, string $password): User
    {
        $user = $this->users->findByUsername(strtolower(trim($username)));

        if (!$user) {
            throw new \RuntimeException('Tên đăng nhập không tồn tại.');
        }
        if (!password_verify($password, $user->password_hash)) {
            throw new \RuntimeException('Mật khẩu không chính xác.');
        }
        if ($user->isBanned()) {
            throw new \RuntimeException('Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }
        if ($user->status === 'deleted') {
            throw new \RuntimeException('Tài khoản không tồn tại.');
        }

        return $user;
    }

    /* ─── Session management ─── */

    /**
     * Tạo session token mới sau khi login thành công
     */
    public function createSession(string $userId, string $ip = '', string $userAgent = ''): string
    {
        $token = Str::random(64);
        $now   = (int)(microtime(true) * 1000);

        Redis::hMSet("user:session:{$token}", [
            'user_id'    => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => $now,
        ]);
        Redis::expire("user:session:{$token}", self::SESSION_TTL);

        // Mark user online
        $this->users->setOnline($userId);

        return $token;
    }

    /**
     * Xác thực token và trả về User, hoặc null nếu hết hạn
     */
    public function validateSession(string $token): ?User
    {
        if (empty($token)) return null;

        $session = Redis::hGetAll("user:session:{$token}");
        if (!$session || empty($session['user_id'])) return null;

        $user = $this->users->findById($session['user_id']);
        if (!$user || $user->isBanned() || $user->status === 'deleted') return null;

        // Cập nhật last_seen
        $this->users->touchLastSeen($user->user_id);

        return $user;
    }

    /**
     * Hủy session khi logout
     */
    public function destroySession(string $token, string $userId): void
    {
        Redis::del("user:session:{$token}");
        $this->users->setOffline($userId);
    }
}
