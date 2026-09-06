<?php

namespace App\Providers;

use App\Repositories\UserRepository;
use App\Repositories\MessageRepository;
use App\Repositories\FriendRepository;
use App\Repositories\GroupRepository;
use App\Repositories\AdminRepository;
use App\Services\AuthService;
use App\Services\ChatService;
use App\Services\FriendService;
use App\Services\GroupService;
use Illuminate\Support\ServiceProvider;

/**
 * RepositoryServiceProvider — bind tất cả Repositories và Services vào IoC Container
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories (singleton — dùng chung instance)
        $this->app->singleton(UserRepository::class);
        $this->app->singleton(MessageRepository::class);
        $this->app->singleton(FriendRepository::class);
        $this->app->singleton(GroupRepository::class);
        $this->app->singleton(AdminRepository::class);

        // Services
        $this->app->singleton(AuthService::class, fn($app) =>
            new AuthService($app->make(UserRepository::class))
        );

        $this->app->singleton(FriendService::class, fn($app) =>
            new FriendService(
                $app->make(FriendRepository::class),
                $app->make(UserRepository::class),
            )
        );

        $this->app->singleton(ChatService::class, fn($app) =>
            new ChatService(
                $app->make(MessageRepository::class),
                $app->make(GroupRepository::class),
                $app->make(FriendRepository::class),
            )
        );

        $this->app->singleton(GroupService::class, fn($app) =>
            new GroupService(
                $app->make(GroupRepository::class),
                $app->make(UserRepository::class),
            )
        );

        $this->app->singleton(\App\Services\MinioStorageService::class, fn() =>
            new \App\Services\MinioStorageService()
        );
    }

    public function boot(): void {}
}
