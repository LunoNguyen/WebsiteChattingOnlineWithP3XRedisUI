@extends('layouts.app')
@section('title', 'Bạn bè')

@section('content')
<div class="flex-1 overflow-y-auto p-6 bg-gray-50">
  <div class="max-w-3xl mx-auto space-y-6">

    {{-- Alerts --}}
    @if(session('success'))
      <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">❌ {{ session('error') }}</div>
    @endif

    {{-- Find friend & Profile Preview --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
      <h3 class="font-semibold text-gray-900 mb-1 flex items-center gap-2">
        <span>🔍 Tìm bạn bè</span>
      </h3>
      <p class="text-xs text-gray-500 mb-4">Nhập tên đăng nhập để xem thông tin hồ sơ trước khi gửi lời mời kết bạn.</p>

      {{-- Search bar --}}
      <div class="flex gap-2">
        <div class="relative flex-1">
          <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input type="text" id="search-username" placeholder="Nhập tên đăng nhập (username)..."
            class="w-full border border-gray-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
            onkeydown="if(event.key==='Enter'){event.preventDefault();searchUserProfile();}">
        </div>
        <button type="button" onclick="searchUserProfile()" id="btn-search-user"
          class="bg-sky-500 hover:bg-sky-600 text-white px-5 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <span>Tìm kiếm</span>
        </button>
      </div>

      {{-- Loading State --}}
      <div id="search-loading" class="hidden py-6 text-center text-sm text-gray-400">
        <svg class="animate-spin h-5 w-5 mx-auto text-sky-500 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Đang tra cứu thông tin...
      </div>

      {{-- Error Message --}}
      <div id="search-error" class="hidden mt-3 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-xs"></div>

      {{-- Profile Preview Card --}}
      <div id="profile-card" class="hidden mt-4 border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm transition">
        {{-- Banner --}}
        <div class="h-16 bg-gradient-to-r from-sky-400 to-indigo-500"></div>

        <div class="px-5 pb-5 pt-0">
          <div class="flex items-end justify-between -mt-8 mb-3">
            {{-- Avatar --}}
            <div class="relative">
              <div id="card-avatar" class="w-16 h-16 rounded-full border-4 border-white bg-sky-500 flex items-center justify-center text-white font-bold text-xl shadow-sm overflow-hidden">
                U
              </div>
              <span id="card-online-dot" class="absolute bottom-1 right-1 w-3.5 h-3.5 bg-green-400 rounded-full border-2 border-white"></span>
            </div>

            {{-- Role Badge --}}
            <span id="card-role" class="text-xs px-2.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-700">
              Thành viên
            </span>
          </div>

          {{-- Name --}}
          <div class="mb-3">
            <h4 id="card-name" class="font-bold text-gray-900 text-base">Tên người dùng</h4>
          </div>

          {{-- Bio --}}
          <div class="bg-gray-50 rounded-lg p-3 mb-3 text-xs text-gray-600">
            <p class="font-medium text-[11px] text-gray-400 uppercase tracking-wider mb-0.5">Tiểu sử</p>
            <p id="card-bio" class="italic">Chưa cập nhật tiểu sử.</p>
          </div>

          {{-- Info Grid --}}
          <div class="flex items-center gap-4 text-xs text-gray-500 mb-4 pb-3 border-b border-gray-100">
            <div>
              <span class="text-gray-400">Trạng thái: </span>
              <span id="card-status-text" class="font-medium text-green-600">Đang online</span>
            </div>
            <div>
              <span class="text-gray-400">Tham gia: </span>
              <span id="card-created-at" class="font-medium text-gray-700">--</span>
            </div>
          </div>

          {{-- Actions Area --}}
          <div id="card-action-area">
            {{-- Will be populated dynamically based on relation --}}
          </div>
        </div>
      </div>
    </div>

    {{-- ── Gợi ý kết bạn ── --}}
    @if(count($suggestions) > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
      <h3 class="font-semibold text-gray-900 mb-1 flex items-center gap-2 text-sm">
        <span>✨ Gợi ý kết bạn</span>
        <span class="text-xs text-gray-400 font-normal">(Bạn của bạn bè)</span>
      </h3>
      <p class="text-xs text-gray-500 mb-4">Những người bạn có thể quen biết thông qua bạn bè chung.</p>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach($suggestions as $sug)
          @php $sugUser = $sug['user']; @endphp
          <div class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-gray-50 transition">
            {{-- Avatar --}}
            <button type="button" onclick="openUserProfile('{{ $sugUser->user_id }}')" class="flex-shrink-0 hover:opacity-80">
              @if($sugUser->avatar_url)
                <img src="{{ $sugUser->avatar_url }}" class="w-11 h-11 rounded-full object-cover">
              @else
                <div class="w-11 h-11 rounded-full bg-gradient-to-br from-sky-400 to-indigo-500 flex items-center justify-center text-white font-bold">
                  {{ strtoupper(substr($sugUser->getName(), 0, 1)) }}
                </div>
              @endif
            </button>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
              <button type="button" onclick="openUserProfile('{{ $sugUser->user_id }}')" class="text-left hover:opacity-80 w-full">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ $sugUser->getName() }}</p>
                <p class="text-xs text-gray-400">
                  {{ $sug['mutual'] }} bạn chung
                </p>
              </button>
            </div>

            {{-- Action --}}
            <div class="flex-shrink-0">
              @if($sug['has_sent'])
                <span class="text-[11px] text-amber-600 bg-amber-50 border border-amber-200 px-2.5 py-1.5 rounded-lg font-medium">⏳ Đã gửi</span>
              @elseif($sug['has_received'])
                <form method="POST" action="{{ route('friends.accept') }}" class="inline">
                  @csrf
                  <input type="hidden" name="from_user_id" value="{{ $sugUser->user_id }}">
                  <button class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded-lg font-medium transition">✓ Chấp nhận</button>
                </form>
              @else
                <button type="button"
                  onclick="quickAddFriend('{{ $sugUser->user_id }}', '{{ $sugUser->getName() }}', this)"
                  class="bg-sky-500 hover:bg-sky-600 text-white text-xs px-3 py-1.5 rounded-lg font-medium transition">
                  + Kết bạn
                </button>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    </div>
    @endif

    {{-- ── Navigation Tabs ── --}}
    <div class="flex border-b border-gray-200 gap-2 sm:gap-6 text-sm font-medium bg-white p-2 sm:px-4 rounded-xl shadow-xs">
      <button type="button" onclick="switchFriendTab('friends')" id="tab-btn-friends"
        class="pb-2.5 px-3 border-b-2 border-sky-500 text-sky-600 flex items-center gap-2 transition font-semibold">
        <span>👥 Bạn bè</span>
        <span class="bg-sky-100 text-sky-700 text-xs px-2 py-0.5 rounded-full font-bold">{{ count($friends) }}</span>
      </button>

      <button type="button" onclick="switchFriendTab('requests')" id="tab-btn-requests"
        class="pb-2.5 px-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 flex items-center gap-2 transition font-medium">
        <span>📨 Lời mời kết bạn</span>
        @if(count($requests) > 0)
          <span class="bg-amber-500 text-white text-xs px-2 py-0.5 rounded-full font-bold animate-pulse">{{ count($requests) }}</span>
        @else
          <span class="bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded-full">0</span>
        @endif
      </button>

      <button type="button" onclick="switchFriendTab('blocked')" id="tab-btn-blocked"
        class="pb-2.5 px-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 flex items-center gap-2 transition font-medium">
        <span>🚫 Quản lý chặn</span>
        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full font-semibold">{{ count($blockedUsers) }}</span>
      </button>
    </div>

    {{-- ═════════ TAB 1: FRIENDS LIST ═════════ --}}
    <div id="tab-content-friends" class="space-y-4">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-gray-900 text-sm">👥 Danh sách bạn bè ({{ count($friends) }})</h3>
          <span class="text-xs text-gray-400">Bấm vào tên để xem hồ sơ chi tiết</span>
        </div>

        @forelse($friends as $item)
          @php $friend = $item['user']; @endphp
          <div class="flex items-center gap-3 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50/70 px-2 rounded-lg transition">
            <button type="button" onclick="openUserProfile('{{ $friend->user_id }}')"
              class="flex items-center gap-3 flex-1 min-w-0 text-left hover:opacity-80 transition group" title="Bấm để xem hồ sơ">
              <div class="relative flex-shrink-0">
                @if($friend->avatar_url)
                  <img src="{{ $friend->avatar_url }}" class="w-10 h-10 rounded-full object-cover">
                @else
                  <div class="w-10 h-10 rounded-full bg-sky-400 flex items-center justify-center text-white font-bold text-sm">
                    {{ strtoupper(substr($friend->getName(), 0, 1)) }}
                  </div>
                @endif
                @if($item['is_online'])
                  <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 rounded-full border-2 border-white"></span>
                @endif
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-sm text-gray-900 group-hover:text-sky-600 transition truncate">
                  {{ $item['nickname'] ?: $friend->getName() }}
                </p>
                <p class="text-xs {{ $item['is_online'] ? 'text-green-500 font-medium' : 'text-gray-400' }}">
                  {{ $item['is_online'] ? 'Đang online' : 'Offline' }}
                </p>
              </div>
            </button>

            <div class="flex gap-1.5 flex-shrink-0 items-center">
              <a href="{{ route('chat.dm', $friend->user_id) }}"
                 class="bg-sky-500 hover:bg-sky-600 text-white text-xs px-3 py-1.5 rounded-lg font-medium transition shadow-xs">Nhắn tin</a>

              {{-- Nickname --}}
              <button onclick="this.nextElementSibling.classList.toggle('hidden')"
                class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs px-2.5 py-1.5 rounded-lg transition" title="Đặt biệt danh">🏷️</button>
              <form method="POST" action="{{ route('friends.nickname') }}" class="hidden flex gap-1 items-center">
                @csrf
                <input type="hidden" name="friend_id" value="{{ $friend->user_id }}">
                <input type="text" name="nickname" value="{{ $item['nickname'] }}" placeholder="Biệt danh"
                  class="border border-gray-200 rounded-lg px-2 py-1 text-xs w-28 focus:outline-none focus:ring-1 focus:ring-sky-400">
                <button class="bg-sky-500 text-white text-xs px-2.5 py-1 rounded-lg font-medium">Lưu</button>
              </form>

              {{-- Remove friend --}}
              <form method="POST" action="{{ route('friends.remove') }}"
                onsubmit="return confirm('Hủy kết bạn với {{ $friend->getName() }}?')">
                @csrf
                <input type="hidden" name="friend_id" value="{{ $friend->user_id }}">
                <button class="bg-red-50 hover:bg-red-100 text-red-600 text-xs px-2.5 py-1.5 rounded-lg font-medium transition">Xóa bạn</button>
              </form>

              {{-- Block --}}
              <form method="POST" action="{{ route('friends.block') }}"
                onsubmit="return confirm('Chặn {{ $friend->getName() }}? Người này sẽ không thể nhắn tin cho bạn.')">
                @csrf
                <input type="hidden" name="target_id" value="{{ $friend->user_id }}">
                <button class="bg-gray-100 hover:bg-red-100 hover:text-red-600 text-gray-500 text-xs px-2.5 py-1.5 rounded-lg transition" title="Chặn người dùng">🚫 Chặn</button>
              </form>
            </div>
          </div>
        @empty
          <div class="text-center py-10 text-gray-400">
            <div class="text-4xl mb-2">👥</div>
            <p class="text-sm font-medium">Chưa có bạn bè nào</p>
            <p class="text-xs text-gray-400 mt-1">Dùng thanh tìm kiếm ở trên để kết bạn với mọi người nhé!</p>
          </div>
        @endforelse
      </div>
    </div>

    {{-- ═════════ TAB 2: PENDING REQUESTS ═════════ --}}
    <div id="tab-content-requests" class="hidden space-y-4">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="font-semibold text-gray-900 text-sm">📨 Lời mời kết bạn đang chờ duyệt ({{ count($requests) }})</h3>
            <p class="text-xs text-gray-400 mt-0.5">Những người muốn kết nối và trò chuyện cùng bạn</p>
          </div>
        </div>

        <div class="space-y-3">
          @forelse($requests as $req)
          <div class="flex items-center gap-3 p-3 bg-gray-50/70 border border-gray-100 rounded-xl hover:bg-gray-50 transition">
            <button type="button" onclick="openUserProfile('{{ $req['from_user']->user_id }}')"
              class="flex items-center gap-3 flex-1 min-w-0 text-left hover:opacity-80 transition group" title="Bấm để xem hồ sơ">
              <div class="w-10 h-10 rounded-full bg-sky-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 group-hover:ring-2 group-hover:ring-sky-400">
                {{ strtoupper(substr($req['from_user']->getName(), 0, 1)) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm text-gray-900 group-hover:text-sky-600 transition">{{ $req['from_user']->getName() }}</p>
                @if($req['message'])
                  <p class="text-xs text-sky-700 bg-sky-50 rounded px-2 py-0.5 mt-1 inline-block italic">"{{ $req['message'] }}"</p>
                @endif
              </div>
            </button>
            <div class="flex gap-2 flex-shrink-0">
              <form method="POST" action="{{ route('friends.accept') }}">
                @csrf
                <input type="hidden" name="from_user_id" value="{{ $req['from_user_id'] }}">
                <button class="bg-green-600 hover:bg-green-700 text-white text-xs px-3.5 py-2 rounded-lg font-medium transition shadow-xs flex items-center gap-1">
                  <span>✓</span>
                  <span>Đồng ý</span>
                </button>
              </form>
              <form method="POST" action="{{ route('friends.reject') }}">
                @csrf
                <input type="hidden" name="from_user_id" value="{{ $req['from_user_id'] }}">
                <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs px-3.5 py-2 rounded-lg font-medium transition">Từ chối</button>
              </form>
            </div>
          </div>
          @empty
            <div class="text-center py-10 text-gray-400">
              <div class="text-4xl mb-2">✨</div>
              <p class="text-sm font-medium">Không có lời mời kết bạn nào</p>
              <p class="text-xs text-gray-400 mt-1">Khi có ai đó gửi lời mời, bạn sẽ thấy thông báo tại đây.</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>

    {{-- ═════════ TAB 3: BLOCKED USERS MANAGEMENT ═════════ --}}
    <div id="tab-content-blocked" class="hidden space-y-4">
      {{-- Block management header card --}}
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-2">
          <div>
            <h3 class="font-semibold text-gray-900 text-sm flex items-center gap-2">
              <span>🚫 Quản lý danh sách chặn</span>
              <span class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full font-bold">{{ count($blockedUsers) }}</span>
            </h3>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">
              Người dùng bị chặn sẽ không thể gửi tin nhắn, gửi lời mời kết bạn hoặc xem trạng thái trực tuyến của bạn. Bạn có thể gỡ bỏ chặn bất kỳ lúc nào.
            </p>
          </div>
        </div>

        {{-- Quick Block Form --}}
        <div class="mt-4 pt-4 border-t border-gray-100">
          <label class="block text-xs font-medium text-gray-700 mb-1.5">Chặn nhanh qua tên đăng nhập (username):</label>
          <form method="POST" action="{{ route('friends.block') }}" class="flex gap-2"
            onsubmit="return confirm('Xác nhận chặn người dùng này?')">
            @csrf
            <input type="text" name="username" placeholder="Nhập username cần chặn..."
              class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-red-400" required>
            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-xs font-medium transition flex items-center gap-1">
              <span>🚫 Chặn</span>
            </button>
          </form>
        </div>
      </div>

      {{-- Blocked Users List --}}
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Danh sách tài khoản đang bị chặn</h4>

        <div class="space-y-2">
          @forelse($blockedUsers as $blocked)
            <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-gray-50 transition">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gray-400 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                  {{ strtoupper(substr($blocked->getName(), 0, 1)) }}
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-900">{{ $blocked->getName() }}</p>
                  @if($blocked->bio)
                    <p class="text-[11px] text-gray-500 italic mt-0.5 line-clamp-1">{{ $blocked->bio }}</p>
                  @endif
                </div>
              </div>

              {{-- Unblock Button --}}
              <form method="POST" action="{{ route('friends.unblock') }}"
                onsubmit="return confirm('Bạn có chắc chắn muốn gỡ chặn cho {{ $blocked->getName() }}?')">
                @csrf
                <input type="hidden" name="target_id" value="{{ $blocked->user_id }}">
                <button type="submit"
                  class="bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 hover:border-sky-300 text-xs px-3.5 py-1.5 rounded-lg font-medium transition flex items-center gap-1">
                  <span>🔓</span>
                  <span>Gỡ chặn</span>
                </button>
              </form>
            </div>
          @empty
            <div class="text-center py-10 text-gray-400">
              <div class="text-3xl mb-2">🕊️</div>
              <p class="text-sm font-medium">Không có ai trong danh sách chặn</p>
              <p class="text-xs text-gray-400 mt-1">Khi bạn chặn một người dùng, họ sẽ hiển thị ở đây và bạn có thể gỡ chặn bất kỳ lúc nào.</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>

  </div>
</div>

{{-- ── Global User Profile Modal ── --}}
<div id="user-profile-modal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150">
    <div class="h-20 bg-gradient-to-r from-sky-400 to-indigo-500 relative">
      <button onclick="closeUserProfileModal()" class="absolute top-3 right-3 text-white/80 hover:text-white bg-black/20 hover:bg-black/30 w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold transition">✕</button>
    </div>
    <div class="px-5 pb-5 pt-0">
      <div class="flex items-end justify-between -mt-10 mb-3">
        <div class="relative">
          <div id="modal-avatar" class="w-20 h-20 rounded-full border-4 border-white bg-sky-500 flex items-center justify-center text-white font-bold text-2xl shadow-sm overflow-hidden">
            U
          </div>
          <span id="modal-online-dot" class="absolute bottom-1 right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-white"></span>
        </div>
        <span id="modal-role" class="text-xs px-2.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-700">
          Thành viên
        </span>
      </div>

      <div class="mb-3">
        <h4 id="modal-name" class="font-bold text-gray-900 text-lg leading-snug">Tên người dùng</h4>
      </div>

      <div class="bg-gray-50 rounded-xl p-3 mb-4 text-xs text-gray-600">
        <p class="font-medium text-[11px] text-gray-400 uppercase tracking-wider mb-1">Tiểu sử</p>
        <p id="modal-bio" class="italic">Chưa cập nhật tiểu sử.</p>
      </div>

      <div class="flex items-center justify-between text-xs text-gray-500 mb-4 pb-3 border-b border-gray-100">
        <div>
          <span class="text-gray-400">Trạng thái: </span>
          <span id="modal-status-text" class="font-medium text-green-600">Online</span>
        </div>
        <div>
          <span class="text-gray-400">Tham gia: </span>
          <span id="modal-created-at" class="font-medium text-gray-700">--</span>
        </div>
      </div>

      <div id="modal-action-area" class="flex gap-2">
        {{-- Populated dynamically --}}
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

// ── Search & Preview User Profile ──
async function searchUserProfile() {
  const input = document.getElementById('search-username');
  const username = input.value.trim();
  const loading = document.getElementById('search-loading');
  const errorBox = document.getElementById('search-error');
  const card = document.getElementById('profile-card');

  if (!username) {
    errorBox.textContent = 'Vui lòng nhập tên đăng nhập để tìm kiếm.';
    errorBox.classList.remove('hidden');
    card.classList.add('hidden');
    return;
  }

  errorBox.classList.add('hidden');
  card.classList.add('hidden');
  loading.classList.remove('hidden');

  try {
    const res = await fetch(`/friends/search?username=${encodeURIComponent(username)}`, {
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();
    loading.classList.add('hidden');

    if (!data.success) {
      errorBox.textContent = data.error || 'Không tìm thấy người dùng.';
      errorBox.classList.remove('hidden');
      return;
    }

    renderProfileCard(data.user, data.relation);
    card.classList.remove('hidden');
  } catch (err) {
    loading.classList.add('hidden');
    errorBox.textContent = 'Có lỗi xảy ra khi tìm kiếm. Vui lòng thử lại.';
    errorBox.classList.remove('hidden');
  }
}

function renderProfileCard(user, relation) {
  // Avatar
  const avatarEl = document.getElementById('card-avatar');
  if (user.avatar_url) {
    avatarEl.innerHTML = `<img src="${user.avatar_url}" class="w-full h-full object-cover">`;
  } else {
    avatarEl.innerHTML = (user.name || user.username).charAt(0).toUpperCase();
  }

  // Online dot
  const onlineDot = document.getElementById('card-online-dot');
  if (user.is_online) {
    onlineDot.className = 'absolute bottom-1 right-1 w-3.5 h-3.5 bg-green-400 rounded-full border-2 border-white';
    document.getElementById('card-status-text').className = 'font-medium text-green-600';
    document.getElementById('card-status-text').textContent = 'Đang online';
  } else {
    onlineDot.className = 'absolute bottom-1 right-1 w-3.5 h-3.5 bg-gray-300 rounded-full border-2 border-white';
    document.getElementById('card-status-text').className = 'font-medium text-gray-400';
    document.getElementById('card-status-text').textContent = 'Offline';
  }

  // Name, bio, role, created_at
  document.getElementById('card-name').textContent = user.name;
  document.getElementById('card-bio').textContent = user.bio || 'Chưa cập nhật tiểu sử.';
  document.getElementById('card-role').textContent = user.role === 'admin' ? 'Quản trị viên' : 'Thành viên';
  document.getElementById('card-role').className = user.role === 'admin'
    ? 'text-xs px-2.5 py-0.5 rounded-full font-medium bg-amber-100 text-amber-800'
    : 'text-xs px-2.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-700';
  document.getElementById('card-created-at').textContent = user.created_at || 'Mới tham gia';

  // Actions
  const actionArea = document.getElementById('card-action-area');
  if (relation.is_me) {
    actionArea.innerHTML = `
      <div class="bg-gray-100 text-gray-600 text-xs py-2 px-3 rounded-lg text-center font-medium">
        👤 Đây là tài khoản của bạn
      </div>
    `;
  } else if (relation.is_friend) {
    actionArea.innerHTML = `
      <div class="flex items-center justify-between bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs p-2.5 rounded-lg">
        <span class="font-medium">🤝 Hai người đã là bạn bè</span>
        <a href="/chat/dm/${user.user_id}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-3 py-1.5 rounded-lg transition">
          Nhắn tin ngay
        </a>
      </div>
    `;
  } else if (relation.has_pending_sent) {
    actionArea.innerHTML = `
      <div class="bg-amber-50 border border-amber-200 text-amber-700 text-xs py-2.5 px-3 rounded-lg text-center font-medium">
        ⏳ Đã gửi lời mời kết bạn (đang chờ xác nhận)
      </div>
    `;
  } else if (relation.has_pending_received) {
    actionArea.innerHTML = `
      <div class="flex items-center justify-between bg-sky-50 border border-sky-200 text-sky-700 text-xs p-2.5 rounded-lg">
        <span class="font-medium">📨 Người này đã gửi lời mời cho bạn</span>
        <form method="POST" action="/friends/accept">
          <input type="hidden" name="_token" value="${CSRF_TOKEN}">
          <input type="hidden" name="from_user_id" value="${user.user_id}">
          <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-3 py-1.5 rounded-lg transition">
            Chấp nhận
          </button>
        </form>
      </div>
    `;
  } else if (relation.is_blocked || relation.is_blocked_by) {
    actionArea.innerHTML = `
      <div class="bg-red-50 border border-red-200 text-red-600 text-xs py-2.5 px-3 rounded-lg text-center font-medium">
        🚫 Không thể kết bạn với người dùng này
      </div>
    `;
  } else {
    actionArea.innerHTML = `
      <div class="space-y-2">
        <input type="text" id="invite-msg-input" placeholder="Lời nhắn gửi kèm (tùy chọn)..."
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500">
        <button type="button" onclick="submitFriendRequest('${user.user_id}', '${user.name.replace(/'/g, "\\'")}')" id="btn-submit-invite"
          class="w-full bg-sky-500 hover:bg-sky-600 text-white font-medium py-2 rounded-lg text-xs transition flex items-center justify-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
          <span>Gửi lời mời kết bạn</span>
        </button>
      </div>
    `;
  }
}

// ── Submit Friend Request ──
async function submitFriendRequest(targetUserId, targetName) {
  const btn = document.getElementById('btn-submit-invite');
  const msgInput = document.getElementById('invite-msg-input');
  const message = msgInput ? msgInput.value.trim() : '';

  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `
      <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <span>Đang gửi...</span>
    `;
  }

  try {
    const res = await fetch('/friends/request', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ user_id: targetUserId, message })
    });
    const data = await res.json();

    if (data.success) {
      document.getElementById('card-action-area').innerHTML = `
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs py-2.5 px-3 rounded-lg text-center font-medium">
          ✅ ${data.message || 'Đã gửi lời mời kết bạn thành công!'}
        </div>
      `;
    } else {
      alert(data.error || 'Không thể gửi lời mời kết bạn.');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `<span>Gửi lời mời kết bạn</span>`;
      }
    }
  } catch (err) {
    alert('Đã xảy ra lỗi khi gửi lời mời.');
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = `<span>Gửi lời mời kết bạn</span>`;
    }
  }
}

// ── Open Global User Profile Modal ──
async function openUserProfile(userId) {
  const modal = document.getElementById('user-profile-modal');
  modal.classList.remove('hidden');

  try {
    const res = await fetch(`/friends/user/${userId}`, {
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();
    if (!data.success) {
      alert(data.error || 'Không thể tải hồ sơ người dùng.');
      modal.classList.add('hidden');
      return;
    }

    const u = data.user;
    const rel = data.relation;

    // Avatar
    const avatarEl = document.getElementById('modal-avatar');
    if (u.avatar_url) {
      avatarEl.innerHTML = `<img src="${u.avatar_url}" class="w-full h-full object-cover">`;
    } else {
      avatarEl.innerHTML = (u.name || u.username).charAt(0).toUpperCase();
    }

    // Online
    const dot = document.getElementById('modal-online-dot');
    const statusText = document.getElementById('modal-status-text');
    if (u.is_online) {
      dot.className = 'absolute bottom-1 right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-white';
      statusText.className = 'font-medium text-green-600';
      statusText.textContent = 'Đang online';
    } else {
      dot.className = 'absolute bottom-1 right-1 w-4 h-4 bg-gray-300 rounded-full border-2 border-white';
      statusText.className = 'font-medium text-gray-400';
      statusText.textContent = 'Offline';
    }

    document.getElementById('modal-name').textContent = u.nickname ? `${u.nickname} (${u.name})` : u.name;
    document.getElementById('modal-bio').textContent = u.bio || 'Chưa cập nhật tiểu sử.';
    document.getElementById('modal-role').textContent = u.role === 'admin' ? 'Quản trị viên' : 'Thành viên';
    document.getElementById('modal-created-at').textContent = u.created_at || 'Mới tham gia';

    const actionArea = document.getElementById('modal-action-area');
    if (rel.is_friend) {
      actionArea.innerHTML = `
        <a href="/chat/dm/${u.user_id}" class="flex-1 bg-sky-500 hover:bg-sky-600 text-white font-medium py-2 rounded-xl text-center text-xs transition">
          💬 Nhắn tin
        </a>
      `;
    } else if (rel.can_send_request) {
      actionArea.innerHTML = `
        <button type="button" onclick="closeUserProfileModal(); document.getElementById('search-username').value='${u.username}'; searchUserProfile();"
          class="flex-1 bg-sky-500 hover:bg-sky-600 text-white font-medium py-2 rounded-xl text-center text-xs transition">
          ➕ Kết bạn với người này
        </button>
      `;
    } else {
      actionArea.innerHTML = `
        <button type="button" onclick="closeUserProfileModal()"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 rounded-xl text-center text-xs transition">
          Đóng
        </button>
      `;
    }
  } catch (err) {
    alert('Không thể tải thông tin.');
    modal.classList.add('hidden');
  }
}

function closeUserProfileModal() {
  document.getElementById('user-profile-modal').classList.add('hidden');
}

// ── Tab Switching ──
function switchFriendTab(tab) {
  const tabs = ['friends', 'requests', 'blocked'];
  tabs.forEach(t => {
    const content = document.getElementById(`tab-content-${t}`);
    const btn = document.getElementById(`tab-btn-${t}`);
    if (!content || !btn) return;

    if (t === tab) {
      content.classList.remove('hidden');
      btn.className = 'pb-2.5 px-3 border-b-2 border-sky-500 text-sky-600 flex items-center gap-2 transition font-semibold';
    } else {
      content.classList.add('hidden');
      btn.className = 'pb-2.5 px-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 flex items-center gap-2 transition font-medium';
    }
  });
  try { history.replaceState(null, null, '#' + tab); } catch(e){}
}

// Auto open tab from hash on load
window.addEventListener('DOMContentLoaded', () => {
  const hash = window.location.hash.replace('#', '');
  if (['friends', 'requests', 'blocked'].includes(hash)) {
    switchFriendTab(hash);
  }
});

// ── Quick Add Friend from Suggestion Card ──
async function quickAddFriend(userId, userName, btn) {
  if (!btn || btn.disabled) return;
  const originalText = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Đang gửi...';
  btn.classList.add('opacity-70', 'cursor-not-allowed');

  try {
    const res = await fetch('/friends/request', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ user_id: userId })
    });
    const data = await res.json();
    if (data.success) {
      // Replace button with "Đã gửi" badge
      btn.outerHTML = `<span class="text-[11px] text-amber-600 bg-amber-50 border border-amber-200 px-2.5 py-1.5 rounded-lg font-medium">⏳ Đã gửi</span>`;
    } else {
      alert(data.error || 'Không thể gửi lời mời kết bạn.');
      btn.disabled = false;
      btn.textContent = originalText;
      btn.classList.remove('opacity-70', 'cursor-not-allowed');
    }
  } catch(e) {
    alert('Lỗi kết nối. Vui lòng thử lại.');
    btn.disabled = false;
    btn.textContent = originalText;
    btn.classList.remove('opacity-70', 'cursor-not-allowed');
  }
}
</script>
@endpush
