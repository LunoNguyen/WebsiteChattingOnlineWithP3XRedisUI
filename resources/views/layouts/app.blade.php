<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'ChatApp') — ChatApp</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: { 50:'#f0f9ff', 500:'#0ea5e9', 600:'#0284c7', 700:'#0369a1' }
          }
        }
      }
    }
  </script>
  <style>
    [x-cloak] { display: none !important; }
    .chat-bubble-mine { border-radius: 18px 18px 4px 18px; }
    .chat-bubble-other { border-radius: 18px 18px 18px 4px; }
    #messages-container { scroll-behavior: smooth; }
    .sidebar-link:hover { background: rgba(255,255,255,0.1); }
    .sidebar-link.active { background: rgba(255,255,255,0.15); }
  </style>
  @stack('styles')
</head>
<body class="bg-gray-100 h-screen flex overflow-hidden text-gray-900">

  {{-- ═══ SIDEBAR ══════════════════════════════════════════════════════ --}}
  <aside class="w-72 bg-gray-900 flex flex-col h-full flex-shrink-0">

    {{-- Logo --}}
    <div class="px-4 py-4 border-b border-gray-700 flex items-center gap-2">
      <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center text-white font-bold text-sm">C</div>
      <span class="text-white font-semibold text-base">ChatApp</span>
      @if($totalUnread ?? 0)
        <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 font-bold">{{ $totalUnread ?? 0 }}</span>
      @endif
    </div>

    {{-- Nav icons --}}
    <nav class="px-2 pt-2 space-y-0.5">
      <a href="{{ route('chat.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-gray-300 hover:text-white {{ request()->routeIs('chat.*') ? 'active text-white' : '' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        <span class="text-sm font-medium">Tin nhắn</span>
      </a>
      <a href="{{ route('friends.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-gray-300 hover:text-white {{ request()->routeIs('friends.*') ? 'active text-white' : '' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span class="text-sm font-medium">Bạn bè</span>
      </a>
      <a href="{{ route('groups.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-gray-300 hover:text-white {{ request()->routeIs('groups.*') ? 'active text-white' : '' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <span class="text-sm font-medium">Nhóm</span>
      </a>
      @if(($authUser ?? null)?->isAdmin())
      <a href="{{ route('admin.users') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-yellow-400 hover:text-yellow-200 {{ request()->routeIs('admin.*') ? 'active' : '' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span class="text-sm font-medium">Quản trị</span>
      </a>
      @endif
    </nav>

    {{-- Spacer --}}
    <div class="flex-1"></div>

    {{-- User info + logout --}}
    <div class="p-3 border-t border-gray-700">
      <div class="flex items-center gap-2">
        <a href="{{ route('profile') }}" class="flex items-center gap-2 flex-1 min-w-0 hover:bg-gray-800 rounded-lg p-1.5 transition">
          <div class="relative flex-shrink-0">
            @if(($authUser ?? null)?->avatar_url)
              <img src="{{ $authUser->avatar_url }}" class="w-8 h-8 rounded-full object-cover">
            @else
              <div class="w-8 h-8 rounded-full bg-sky-500 flex items-center justify-center text-white text-sm font-bold">
                {{ strtoupper(substr($authUser?->getName() ?? 'U', 0, 1)) }}
              </div>
            @endif
            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 rounded-full border-2 border-gray-900"></span>
          </div>
          <div class="min-w-0">
            <p class="text-white text-xs font-semibold truncate">{{ $authUser?->getName() }}</p>
            <p class="text-gray-400 text-[10px] truncate">{{ '@' . ($authUser?->username ?? '') }}</p>
          </div>
        </a>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="text-gray-400 hover:text-red-400 transition p-1.5 rounded" title="Đăng xuất">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          </button>
        </form>
      </div>
    </div>
  </aside>

  {{-- ═══ MAIN CONTENT ═════════════════════════════════════════════════ --}}
  <main class="flex-1 flex flex-col overflow-hidden">
    @yield('content')
  </main>

@stack('scripts')
</body>
</html>
