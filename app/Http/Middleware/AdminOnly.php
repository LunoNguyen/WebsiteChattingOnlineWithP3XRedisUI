<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * AdminOnly — chỉ cho phép user có role = 'admin' truy cập
 * Phải dùng sau middleware RedisAuth
 */
class AdminOnly
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->attributes->get('auth_user');

        if (!$user || !$user->isAdmin()) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        return $next($request);
    }
}
