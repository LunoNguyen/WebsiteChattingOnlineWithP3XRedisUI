<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\MinioStorageService;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private UserRepository      $users,
        private AuthService         $auth,
        private MinioStorageService $minio,
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
            'avatar_url'   => 'nullable|string|max:1000',
            'avatar_file'  => 'nullable|image|max:5120', // Tải ảnh đại diện lên MinIO tối đa 5MB
        ]);

        $avatarUrl = $request->avatar_url ?? $authUser->avatar_url ?? '';

        // Nếu người dùng tải ảnh từ máy lên, lưu vào MinIO
        if ($request->hasFile('avatar_file')) {
            try {
                $uploaded = $this->minio->uploadFile($request->file('avatar_file'), 'avatars');
                $avatarUrl = $uploaded['url'];
            } catch (\Throwable $e) {
                return back()->with('error', 'Lỗi tải ảnh đại diện lên MinIO: ' . $e->getMessage());
            }
        }

        $this->users->update($authUser->user_id, [
            'display_name' => $request->display_name,
            'bio'          => $request->bio ?? '',
            'avatar_url'   => $avatarUrl,
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
