<?php

namespace App\Providers;

use App\Http\Middleware\RedisAuth;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Redis;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Đăng ký toàn bộ Repositories và Services
        $this->app->register(RepositoryServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Đăng ký middleware alias
        Route::aliasMiddleware('redis.auth',  RedisAuth::class);
        Route::aliasMiddleware('admin.only',  AdminOnly::class);

        // Chia sẻ thông báo (lời mời kết bạn + tin nhắn chưa đọc) cho tất cả Views
        View::composer('*', function ($view) {
            $user = request()->attributes->get('auth_user');
            if ($user) {
                try {
                    $pendingCount = (int) Redis::sCard("user:{$user->user_id}:friend_requests");
                    $unreadMap = Redis::hGetAll("user:{$user->user_id}:unread") ?: [];
                    $totalUnread = array_sum(array_map('intval', $unreadMap));
                    $view->with('pendingFriendRequestsCount', $pendingCount);
                    $view->with('totalUnread', $totalUnread);
                } catch (\Throwable $e) {
                    $view->with('pendingFriendRequestsCount', 0);
                    $view->with('totalUnread', 0);
                }
            } else {
                $view->with('pendingFriendRequestsCount', 0);
                $view->with('totalUnread', 0);
            }
        });
    }
}
