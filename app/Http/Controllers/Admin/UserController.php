<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Repositories\AdminRepository;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserRepository  $users,
        private AdminRepository $admin,
    ) {}

    /** Danh sách tất cả người dùng */
    public function index(Request $request)
    {
        $authUser  = $request->attributes->get('auth_user');
        $allUsers  = $this->users->getAllUsers();
        $onlineIds = $this->users->getOnlineUserIds();
        $stats     = $this->admin->getSystemStats();

        return view('admin.users', compact('authUser', 'allUsers', 'onlineIds', 'stats'));
    }

    /** POST — ban user */
    public function ban(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate([
            'user_id' => 'required|string',
            'reason'  => 'nullable|string|max:200',
        ]);

        $target = $this->users->findById($request->user_id);
        if (!$target) return back()->with('error', 'Người dùng không tồn tại.');
        if ($target->isAdmin()) return back()->with('error', 'Không thể ban admin khác.');

        $this->users->ban($request->user_id);
        $this->admin->log($authUser->user_id, 'ban_user', $request->user_id, $request->reason ?? '');

        return back()->with('success', "Đã khóa tài khoản {$target->username}.");
    }

    /** POST — unban user */
    public function unban(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['user_id' => 'required|string']);

        $target = $this->users->findById($request->user_id);
        if (!$target) return back()->with('error', 'Người dùng không tồn tại.');

        $this->users->unban($request->user_id);
        $this->admin->log($authUser->user_id, 'unban_user', $request->user_id, '');

        return back()->with('success', "Đã mở khóa tài khoản {$target->username}.");
    }

    /** POST — xóa user (soft delete) */
    public function destroy(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['user_id' => 'required|string']);

        $this->users->delete($request->user_id);
        $this->admin->log($authUser->user_id, 'delete_user', $request->user_id, '');

        return back()->with('success', 'Đã xóa người dùng.');
    }

    /** GET — nhật ký admin */
    public function logs(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $logs     = $this->admin->getLogs(50);
        return view('admin.logs', compact('authUser', 'logs'));
    }
}
