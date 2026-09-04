@extends('layouts.app')
@section('title', 'Hồ sơ cá nhân')

@section('content')
<div class="flex-1 overflow-y-auto p-6 bg-gray-50">
  <div class="max-w-lg mx-auto space-y-5">

    @if(session('success'))
      <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">❌ {{ session('error') }}</div>
    @endif

    {{-- Profile Info --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center gap-4 mb-5">
        <div class="w-16 h-16 rounded-full bg-sky-500 flex items-center justify-center text-white text-2xl font-bold">
          {{ strtoupper(substr($authUser->getName(), 0, 1)) }}
        </div>
        <div>
          <h2 class="font-bold text-gray-900 text-lg">{{ $authUser->getName() }}</h2>
          <p class="text-gray-500 text-sm">@{{ $authUser->username }}</p>
          <span class="inline-flex mt-1 items-center px-2 py-0.5 rounded-full text-xs font-medium
            {{ $authUser->role === 'admin' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700' }}">
            {{ $authUser->role === 'admin' ? '🛡️ Admin' : 'User' }}
          </span>
        </div>
      </div>

      <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf @method('PUT')
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tên hiển thị</label>
          <input type="text" name="display_name" value="{{ $authUser->display_name }}"
            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Giới thiệu bản thân</label>
          <textarea name="bio" rows="3"
            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 resize-none">{{ $authUser->bio }}</textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">URL ảnh đại diện</label>
          <input type="url" name="avatar_url" value="{{ $authUser->avatar_url }}"
            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"
            placeholder="https://...">
        </div>
        <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 text-white font-medium py-2.5 rounded-xl text-sm transition">
          Lưu thay đổi
        </button>
      </form>
    </div>

    {{-- Change password --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <h3 class="font-semibold text-gray-900 mb-4">🔒 Đổi mật khẩu</h3>
      <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
        @csrf @method('PUT')
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu hiện tại</label>
          <input type="password" name="current_password" required
            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
          <input type="password" name="password" required
            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu mới</label>
          <input type="password" name="password_confirmation" required
            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
        </div>
        <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-medium py-2.5 rounded-xl text-sm transition">
          Đổi mật khẩu
        </button>
      </form>
    </div>
  </div>
</div>
@endsection
