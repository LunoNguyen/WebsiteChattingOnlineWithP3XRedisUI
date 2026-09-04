<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\BackupController;
use Illuminate\Support\Facades\Route;

/* ─── Public routes (không cần đăng nhập) ─── */
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',   [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register',[AuthController::class, 'register'])->name('register.post');

/* ─── Authenticated routes ─── */
Route::middleware('redis.auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /* Profile */
    Route::get('/profile',        [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile',        [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password',[ProfileController::class, 'changePassword'])->name('profile.password');

    /* Chat — danh sách và DM */
    Route::get('/chat',                       [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/dm/{userId}',           [ChatController::class, 'showDM'])->name('chat.dm');
    Route::post('/chat/dm/{userId}/send',     [ChatController::class, 'sendDM'])->name('chat.dm.send');
    Route::get('/chat/dm/{userId}/more',      [ChatController::class, 'loadMoreDM'])->name('chat.dm.more');

    /* Chat — Group */
    Route::get('/chat/group/{groupId}',       [ChatController::class, 'showGroup'])->name('chat.group');
    Route::post('/chat/group/{groupId}/send', [ChatController::class, 'sendGroup'])->name('chat.group.send');

    /* Message CRUD */
    Route::put('/message/{msgId}',            [ChatController::class, 'editMessage'])->name('message.edit');
    Route::delete('/message/{msgId}',         [ChatController::class, 'deleteMessage'])->name('message.delete');

    /* Friends */
    Route::get('/friends',                    [FriendController::class, 'index'])->name('friends.index');
    Route::get('/friends/search',             [FriendController::class, 'searchUser'])->name('friends.search');
    Route::get('/friends/user/{userId}',      [FriendController::class, 'getUserProfile'])->name('friends.user.profile');
    Route::post('/friends/request',           [FriendController::class, 'sendRequest'])->name('friends.request');
    Route::post('/friends/accept',            [FriendController::class, 'acceptRequest'])->name('friends.accept');
    Route::post('/friends/reject',            [FriendController::class, 'rejectRequest'])->name('friends.reject');
    Route::post('/friends/remove',            [FriendController::class, 'removeFriend'])->name('friends.remove');
    Route::post('/friends/block',             [FriendController::class, 'blockUser'])->name('friends.block');
    Route::post('/friends/unblock',           [FriendController::class, 'unblockUser'])->name('friends.unblock');
    Route::post('/friends/nickname',          [FriendController::class, 'setNickname'])->name('friends.nickname');

    /* Groups */
    Route::get('/groups',                     [GroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create',              [GroupController::class, 'create'])->name('groups.create');
    Route::post('/groups',                    [GroupController::class, 'store'])->name('groups.store');
    Route::post('/groups/{groupId}/member',   [GroupController::class, 'addMember'])->name('groups.member.add');
    Route::delete('/groups/{groupId}/member', [GroupController::class, 'removeMember'])->name('groups.member.remove');
    Route::post('/groups/{groupId}/leave',    [GroupController::class, 'leave'])->name('groups.leave');
    Route::post('/groups/{groupId}/nickname', [GroupController::class, 'setNickname'])->name('groups.nickname');
    Route::post('/groups/{groupId}/promote',  [GroupController::class, 'promoteAdmin'])->name('groups.promote');

    /* ─── Admin routes ─── */
    Route::middleware('admin.only')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/',              [AdminUserController::class, 'index'])->name('index');
        Route::get('/users',         [AdminUserController::class, 'index'])->name('users');
        Route::post('/users/ban',    [AdminUserController::class, 'ban'])->name('users.ban');
        Route::post('/users/unban',  [AdminUserController::class, 'unban'])->name('users.unban');
        Route::delete('/users',      [AdminUserController::class, 'destroy'])->name('users.delete');
        Route::get('/logs',          [AdminUserController::class, 'logs'])->name('logs');
        Route::get('/backup',                          [BackupController::class, 'index'])->name('backup');
        Route::post('/backup',                         [BackupController::class, 'create'])->name('backup.create');
        Route::get('/backup/{backupId}/download',      [BackupController::class, 'download'])->name('backup.download');
        Route::delete('/backup/{backupId}',            [BackupController::class, 'destroy'])->name('backup.destroy');
        Route::post('/restore',                        [BackupController::class, 'restore'])->name('backup.restore');
        Route::get('/redis-info',                      [BackupController::class, 'redisInfo'])->name('redis_info');
    });
});
