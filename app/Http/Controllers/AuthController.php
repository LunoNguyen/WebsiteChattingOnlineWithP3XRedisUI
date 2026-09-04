<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    /* ─── Login ─── */

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string|min:6',
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min'      => 'Mật khẩu tối thiểu 6 ký tự.',
        ]);

        try {
            $user  = $this->auth->login($request->username, $request->password);
            $token = $this->auth->createSession(
                $user->user_id,
                $request->ip(),
                $request->userAgent()
            );

            return redirect()->route('chat.index')
                ->cookie('chat_token', $token, 1440, '/', null, false, true); // httpOnly

        } catch (\RuntimeException $e) {
            return back()->withInput(['username' => $request->username])
                         ->with('error', $e->getMessage());
        }
    }

    /* ─── Register ─── */

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'username'     => 'required|string|min:3|max:30|regex:/^[a-zA-Z0-9_]+$/',
            'display_name' => 'required|string|max:50',
            'email'        => 'required|email|max:100',
            'password'     => 'required|string|min:6|confirmed',
        ], [
            'username.required'     => 'Vui lòng nhập tên đăng nhập.',
            'username.regex'        => 'Tên đăng nhập chỉ chứa chữ, số và dấu gạch dưới.',
            'username.min'          => 'Tên đăng nhập tối thiểu 3 ký tự.',
            'email.required'        => 'Vui lòng nhập email.',
            'email.email'           => 'Email không hợp lệ.',
            'password.confirmed'    => 'Mật khẩu xác nhận không khớp.',
            'display_name.required' => 'Vui lòng nhập tên hiển thị.',
        ]);

        try {
            $user  = $this->auth->register($request->all());
            $token = $this->auth->createSession(
                $user->user_id,
                $request->ip(),
                $request->userAgent()
            );

            return redirect()->route('chat.index')
                ->cookie('chat_token', $token, 1440, '/', null, false, true)
                ->with('success', "Chào mừng, {$user->getName()}!");

        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /* ─── Logout ─── */

    public function logout(Request $request)
    {
        $token  = $request->cookie('chat_token');
        $userId = $request->attributes->get('auth_user')?->user_id;

        if ($token && $userId) {
            $this->auth->destroySession($token, $userId);
        }

        return redirect()->route('login')
            ->withCookie(cookie()->forget('chat_token'))
            ->with('success', 'Đã đăng xuất thành công.');
    }
}
