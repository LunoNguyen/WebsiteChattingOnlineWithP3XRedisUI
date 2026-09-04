<?php

namespace App\Providers;

use App\Http\Middleware\RedisAuth;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

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
    }
}
