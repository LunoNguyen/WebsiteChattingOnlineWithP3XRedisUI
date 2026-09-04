<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private UserRepository $users,
        private AuthService    $auth,
    ) {}

    public function show(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        return view('profile.index', compact('authUser'));
    }

    public function update(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate([
            'display_name' => 'required|string|max:50',
            'bio'          => 'nullable|string|max:300',
            'avatar_url'   => 'nullable|url|max:500',
        ]);

        $this->users->update($authUser->user_id, [
            'display_name' => $request->display_name,
            'bio'          => $request->bio ?? '',
            'avatar_url'   => $request->avatar_url ?? '',
        ]);

        return back()->with('success', 'Đã cập nhật thông tin cá nhân.');
    }

    public function changePassword(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate([
            'current_password'  => 'required|string',
            'password'          => 'required|string|min:6|confirmed',
        ]);

        if (!password_verify($request->current_password, $authUser->password_hash)) {
            return back()->with('error', 'Mật khẩu hiện tại không đúng.');
        }

        $this->users->update($authUser->user_id, [
            'password_hash' => bcrypt($request->password),
        ]);

        return back()->with('success', 'Đã đổi mật khẩu thành công.');
    }
}
