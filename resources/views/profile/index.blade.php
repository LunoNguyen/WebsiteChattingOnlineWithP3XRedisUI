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
        @if($authUser->avatar_url)
          <img src="{{ $authUser->avatar_url }}" class="w-16 h-16 rounded-full object-cover shadow-sm border border-gray-100 flex-shrink-0">
        @else
          <div class="w-16 h-16 rounded-full bg-sky-500 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0">
            {{ strtoupper(substr($authUser->getName(), 0, 1)) }}
          </div>
        @endif
        <div>
          <h2 class="font-bold text-gray-900 text-lg">{{ $authUser->getName() }}</h2>
          <span class="inline-flex mt-1 items-center px-2 py-0.5 rounded-full text-xs font-medium
            {{ $authUser->role === 'admin' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700' }}">
            {{ $authUser->role === 'admin' ? '🛡️ Admin' : 'User' }}
          </span>
        </div>
      </div>

      <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf @method('PUT')

        {{-- Upload avatar file to MinIO --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Ảnh đại diện</label>
          <div class="flex items-center gap-4 p-3 bg-gray-50 border border-gray-200 rounded-xl">
            <div class="relative group cursor-pointer" onclick="document.getElementById('avatar-file-input').click()">
              <div id="avatar-preview-container" class="w-14 h-14 rounded-full overflow-hidden bg-sky-500 flex items-center justify-center text-white font-bold text-lg shadow-xs border-2 border-white">
                @if($authUser->avatar_url)
                  <img id="avatar-preview-img" src="{{ $authUser->avatar_url }}" class="w-full h-full object-cover">
                @else
                  <span id="avatar-preview-text">{{ strtoupper(substr($authUser->getName(), 0, 1)) }}</span>
                @endif
              </div>
              <div class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <input type="file" id="avatar-file-input" name="avatar_file" accept="image/*" class="hidden" onchange="previewAvatar(this)">
              <button type="button" onclick="document.getElementById('avatar-file-input').click()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 shadow-xs transition">
                <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span>Tải ảnh từ máy tính</span>
              </button>
              <p id="avatar-file-name" class="text-[11px] text-gray-500 mt-1 truncate">Hỗ trợ JPG, PNG, GIF, WEBP tối đa 5MB</p>
            </div>
          </div>
        </div>

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
        {{-- Ẩn url ảnh đại diện --}}
        <input type="hidden" name="avatar_url" value="{{ $authUser->avatar_url }}">
        <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 text-white font-medium py-2.5 rounded-xl text-sm transition shadow-sm">
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

@push('scripts')
<script>
function previewAvatar(input) {
  const file = input.files[0];
  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    alert('Kích thước ảnh đại diện vượt quá 5MB. Vui lòng chọn ảnh nhỏ hơn.');
    input.value = '';
    return;
  }

  const reader = new FileReader();
  reader.onload = function(e) {
    const container = document.getElementById('avatar-preview-container');
    container.innerHTML = `<img id="avatar-preview-img" src="${e.target.result}" class="w-full h-full object-cover">`;
    const nameEl = document.getElementById('avatar-file-name');
    if (nameEl) nameEl.textContent = `${file.name} (${(file.size / 1024).toFixed(1)} KB) - Đã chọn`;
  };
  reader.readAsDataURL(file);
}
</script>
@endpush
