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
    .sidebar-link:hover { background: rgba(255,255,255,0.1); }
    .sidebar-link.active { background: rgba(255,255,255,0.15); }
    /* Collapsed sidebar classes */
    aside.collapsed { width: 4.75rem !important; }
    aside.collapsed .sidebar-text { display: none !important; }
    aside.collapsed .sidebar-header { justify-content: center; padding-left: 0.25rem; padding-right: 0.25rem; }
    aside.collapsed .sidebar-header-logo { justify-content: center; margin: 0 auto; }
    aside.collapsed .sidebar-link { justify-content: center; padding-left: 0.5rem; padding-right: 0.5rem; }
    aside.collapsed .sidebar-badge { position: absolute; top: 2px; right: 4px; padding: 0.125rem 0.25rem; font-size: 0.65rem; }
    aside.collapsed .user-info-box { justify-content: center; }
    aside.collapsed .logout-form { display: none !important; }

    /* Mobile drawer states */
    @media (max-width: 767px) {
      #main-sidebar {
        position: fixed !important;
        top: 0;
        bottom: 0;
        left: 0;
        z-index: 50;
        transform: translateX(-100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        width: 16rem !important;
      }
      #main-sidebar.mobile-open {
        transform: translateX(0) !important;
      }
    }
  </style>
  @stack('styles')
</head>
<body class="bg-gray-100 h-screen flex overflow-hidden text-gray-900 relative">

  {{-- Mobile Backdrop --}}
  <div id="sidebar-backdrop" onclick="closeMobileSidebar()"
       class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity duration-300"></div>

  {{-- ═══ SIDEBAR ══════════════════════════════════════════════════════ --}}
  <aside id="main-sidebar" class="w-64 bg-gray-900 flex flex-col h-full flex-shrink-0 transition-all duration-300 ease-in-out relative border-r border-gray-800 select-none">

    {{-- Logo & Brand Header --}}
    <div class="sidebar-header px-4 py-4 border-b border-gray-800 flex items-center justify-between min-h-[65px]">
      <div class="sidebar-header-logo flex items-center gap-3 overflow-hidden cursor-pointer"
           onclick="if(document.getElementById('main-sidebar').classList.contains('collapsed')) toggleSidebar();"
           title="ChatApp">
        <div class="w-9 h-9 bg-gradient-to-br from-sky-400 to-sky-600 rounded-xl flex items-center justify-center text-white font-bold text-base shadow-sm flex-shrink-0">
          C
        </div>
        <span class="sidebar-text text-white font-bold text-base whitespace-nowrap tracking-wide">ChatApp</span>
      </div>
      <div class="flex items-center gap-1">
        {{-- Desktop collapse toggle button --}}
        <button type="button" onclick="toggleSidebar()"
          class="sidebar-text hidden md:inline-flex text-gray-400 hover:text-white p-1.5 rounded-lg hover:bg-gray-800 transition flex-shrink-0"
          title="Thu hẹp mục lục">
          <svg id="sidebar-toggle-icon-top" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
          </svg>
        </button>
        {{-- Mobile close drawer button --}}
        <button type="button" onclick="closeMobileSidebar()"
          class="md:hidden text-gray-400 hover:text-white p-1.5 rounded-lg hover:bg-gray-800 transition flex-shrink-0"
          title="Đóng thanh mục lục">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    {{-- Nav links --}}
    <nav class="px-2 pt-3 space-y-1">
      <a href="{{ route('chat.index') }}" title="Tin nhắn"
        class="sidebar-link relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:text-white transition {{ request()->routeIs('chat.*') ? 'active text-white' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        <span class="sidebar-text text-sm font-medium whitespace-nowrap">Tin nhắn</span>
        <span id="sidebar-chat-badge" class="sidebar-badge ml-auto bg-sky-500 text-white text-xs font-bold rounded-full px-2 py-0.5 {{ ($totalUnread ?? 0) > 0 ? '' : 'hidden' }}">
          {{ ($totalUnread ?? 0) > 99 ? '99+' : ($totalUnread ?? 0) }}
        </span>
      </a>

      <a href="{{ route('friends.index') }}" title="Bạn bè"
        class="sidebar-link relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:text-white transition {{ request()->routeIs('friends.*') ? 'active text-white' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span class="sidebar-text text-sm font-medium whitespace-nowrap">Bạn bè</span>
        <span id="sidebar-friend-badge" class="sidebar-badge ml-auto bg-amber-500 text-white text-xs font-bold rounded-full px-2 py-0.5 animate-pulse {{ ($pendingFriendRequestsCount ?? 0) > 0 ? '' : 'hidden' }}" title="{{ $pendingFriendRequestsCount ?? 0 }} lời mời kết bạn mới">
          {{ $pendingFriendRequestsCount ?? 0 }}
        </span>
      </a>

      <a href="{{ route('groups.index') }}" title="Nhóm"
        class="sidebar-link relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:text-white transition {{ request()->routeIs('groups.*') ? 'active text-white' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <span class="sidebar-text text-sm font-medium whitespace-nowrap">Nhóm</span>
      </a>

      @if(($authUser ?? null)?->isAdmin())
      <a href="{{ route('admin.users') }}" title="Quản trị"
        class="sidebar-link relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-yellow-400 hover:text-yellow-200 transition {{ request()->routeIs('admin.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span class="sidebar-text text-sm font-medium whitespace-nowrap">Quản trị</span>
      </a>
      @endif
    </nav>

    {{-- Spacer --}}
    <div class="flex-1"></div>

    {{-- User info + logout --}}
    <div class="p-2.5 border-t border-gray-800">
      <div class="user-info-box flex items-center gap-2">
        <a href="{{ route('profile') }}" class="flex items-center gap-2 flex-1 min-w-0 hover:bg-gray-800 rounded-lg p-1.5 transition" title="{{ $authUser?->getName() }}">
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
          <div class="sidebar-text min-w-0">
            <p class="text-white text-xs font-semibold truncate">{{ $authUser?->getName() }}</p>
          </div>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="logout-form flex-shrink-0">
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
    @if(($pendingFriendRequestsCount ?? 0) > 0 && !request()->routeIs('friends.*'))
      <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-white px-4 py-2.5 text-xs font-medium flex items-center justify-between shadow-sm flex-shrink-0">
        <div class="flex items-center gap-2">
          <span class="text-base animate-bounce">👋</span>
          <span>Bạn có <strong class="underline font-bold">{{ $pendingFriendRequestsCount }} lời mời kết bạn mới</strong> đang chờ phản hồi!</span>
        </div>
        <a href="{{ route('friends.index') }}" class="bg-white text-amber-800 px-3 py-1 rounded-lg text-xs font-bold hover:bg-amber-50 transition shadow-xs flex items-center gap-1">
          <span>Xem & Xác nhận ngay</span>
          <span>→</span>
        </a>
      </div>
    @endif
    @yield('content')
  </main>

  <script>
    function applySidebarState(isCollapsed) {
      const sidebar = document.getElementById('main-sidebar');
      const iconTop = document.getElementById('sidebar-toggle-icon-top');
      if (!sidebar) return;
      if (isCollapsed) {
        sidebar.classList.add('collapsed');
        if (iconTop) iconTop.classList.add('rotate-180');
      } else {
        sidebar.classList.remove('collapsed');
        if (iconTop) iconTop.classList.remove('rotate-180');
      }
    }

    function openMobileSidebar() {
      const sidebar = document.getElementById('main-sidebar');
      const backdrop = document.getElementById('sidebar-backdrop');
      if (sidebar) sidebar.classList.add('mobile-open');
      if (backdrop) backdrop.classList.remove('hidden');
    }

    function closeMobileSidebar() {
      const sidebar = document.getElementById('main-sidebar');
      const backdrop = document.getElementById('sidebar-backdrop');
      if (sidebar) sidebar.classList.remove('mobile-open');
      if (backdrop) backdrop.classList.add('hidden');
    }

    function toggleMobileSidebar() {
      const sidebar = document.getElementById('main-sidebar');
      if (sidebar && sidebar.classList.contains('mobile-open')) {
        closeMobileSidebar();
      } else {
        openMobileSidebar();
      }
    }

    function toggleSidebar() {
      const sidebar = document.getElementById('main-sidebar');
      if (!sidebar) return;
      const isNowCollapsed = !sidebar.classList.contains('collapsed');
      applySidebarState(isNowCollapsed);
      try {
        localStorage.setItem('chat_sidebar_collapsed', isNowCollapsed ? '1' : '0');
      } catch (e) {}
    }

    // Khởi tạo trạng thái sidebar từ localStorage
    (function() {
      try {
        const saved = localStorage.getItem('chat_sidebar_collapsed');
        if (saved === '1') {
          applySidebarState(true);
        }
      } catch(e) {}
    })();

    // ── Global Redis status polling mỗi 10s (chạy trên tất cả các trang chat, friends, group, profile) ──
    let globalLastUnread = {{ (int)($totalUnread ?? 0) }};
    let globalLastRequests = {{ (int)($pendingFriendRequestsCount ?? 0) }};
    let globalLastMsgAt = 0;

    async function pollGlobalStatus() {
      try {
        const res = await fetch('{{ route("chat.status.poll", [], false) }}', {
          headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        // Cập nhật badge tin nhắn chưa đọc
        const chatBadge = document.getElementById('sidebar-chat-badge');
        if (chatBadge) {
          if (data.total_unread > 0) {
            chatBadge.textContent = data.total_unread > 99 ? '99+' : data.total_unread;
            chatBadge.classList.remove('hidden');
          } else {
            chatBadge.classList.add('hidden');
          }
        }

        // Cập nhật badge yêu cầu kết bạn
        const friendBadge = document.getElementById('sidebar-friend-badge');
        if (friendBadge) {
          if (data.pending_friend_requests > 0) {
            friendBadge.textContent = data.pending_friend_requests;
            friendBadge.classList.remove('hidden');
          } else {
            friendBadge.classList.add('hidden');
          }
        }

        // Nếu đang ở trang danh sách chat (/chat) và có tin nhắn mới hoặc unread thay đổi, tự động cập nhật
        const curPath = window.location.pathname;
        if (curPath === '/chat' || curPath === '/chat/') {
          if (data.total_unread !== globalLastUnread || (data.last_msg_at && globalLastMsgAt > 0 && data.last_msg_at > globalLastMsgAt)) {
            window.location.reload();
          }
        } else if (curPath.startsWith('/friends') && data.pending_friend_requests !== globalLastRequests) {
          window.location.reload();
        }

        globalLastUnread = data.total_unread;
        globalLastRequests = data.pending_friend_requests;
        if (data.last_msg_at) globalLastMsgAt = data.last_msg_at;

      } catch(e) {}
    }

    // Polling định kỳ mỗi 5s một lần
    setInterval(pollGlobalStatus, 5000);
  </script>
@stack('scripts')
</body>
</html>
