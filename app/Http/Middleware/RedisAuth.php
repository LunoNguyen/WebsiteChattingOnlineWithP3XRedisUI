<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;

/**
 * RedisAuth — xác thực session token từ cookie 'chat_token'
 * Gắn user vào request attributes nếu hợp lệ
 */
class RedisAuth
{
    public function __construct(private AuthService $auth) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->cookie('chat_token') ?? $request->bearerToken();

        if (!$token) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Vui lòng đăng nhập để tiếp tục.'], 401);
            }
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để tiếp tục.');
        }

        $user = $this->auth->validateSession($token);

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Phiên đăng nhập đã hết hạn.'], 401);
            }
            return redirect()->route('login')
                ->withCookie(cookie()->forget('chat_token'))
                ->with('error', 'Phiên đăng nhập đã hết hạn.');
        }

        // Gắn user vào request để dùng trong controller
        $request->attributes->set('auth_user', $user);
        $request->attributes->set('auth_token', $token);

        return $next($request);
    }
}
