<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng ký — ChatApp</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-sky-900 flex items-center justify-center p-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-6">
      <div class="inline-flex w-12 h-12 bg-sky-500 rounded-2xl items-center justify-center mb-2 shadow-lg">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
      </div>
      <h1 class="text-xl font-bold text-white">Tạo tài khoản</h1>
    </div>

    <div class="bg-gray-800 rounded-2xl p-6 shadow-2xl border border-gray-700">
      @if(session('error'))
        <div class="mb-4 bg-red-500/15 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 text-sm">{{ session('error') }}</div>
      @endif

      <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-300 mb-1">Tên đăng nhập *</label>
            <input type="text" name="username" value="{{ old('username') }}"
              class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
              placeholder="vd: alice123" required>
            @error('username')<p class="mt-0.5 text-xs text-red-400">{{ $message }}</p>@enderror
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-300 mb-1">Tên hiển thị *</label>
            <input type="text" name="display_name" value="{{ old('display_name') }}"
              class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
              placeholder="Alice Nguyễn" required>
            @error('display_name')<p class="mt-0.5 text-xs text-red-400">{{ $message }}</p>@enderror
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-300 mb-1">Email *</label>
          <input type="email" name="email" value="{{ old('email') }}"
            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
            placeholder="alice@email.com" required>
          @error('email')<p class="mt-0.5 text-xs text-red-400">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-300 mb-1">Mật khẩu *</label>
            <input type="password" name="password"
              class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
              placeholder="Tối thiểu 6 ký tự" required>
            @error('password')<p class="mt-0.5 text-xs text-red-400">{{ $message }}</p>@enderror
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-300 mb-1">Xác nhận mật khẩu *</label>
            <input type="password" name="password_confirmation"
              class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
              placeholder="Nhập lại" required>
          </div>
        </div>

        <button type="submit"
          class="w-full bg-sky-500 hover:bg-sky-600 text-white font-semibold py-2.5 rounded-xl transition text-sm mt-2">
          Tạo tài khoản
        </button>
      </form>

      <p class="text-center text-gray-400 text-sm mt-4">
        Đã có tài khoản?
        <a href="{{ route('login') }}" class="text-sky-400 hover:text-sky-300 font-medium">Đăng nhập</a>
      </p>
    </div>
  </div>
</body>
</html>
