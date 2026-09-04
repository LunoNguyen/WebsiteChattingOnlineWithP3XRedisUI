@extends('layouts.app')
@section('title', 'Tin nhắn')

@section('content')
<div class="flex h-full">

  {{-- Chat list panel --}}
  <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
    {{-- Header --}}
    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
      <h2 class="font-semibold text-gray-900">Tin nhắn</h2>
      <a href="{{ route('groups.create') }}"
         class="text-sky-500 hover:text-sky-600 text-xs font-medium flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tạo nhóm
      </a>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
      <div class="mx-3 mt-2 bg-green-50 border border-green-200 text-green-700 rounded-lg px-3 py-2 text-xs">{{ session('success') }}</div>
    @endif

    {{-- Search (UI only) --}}
    <div class="px-3 py-2 border-b border-gray-100">
      <div class="relative">
        <svg class="absolute left-2.5 top-2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input class="w-full bg-gray-100 rounded-lg pl-8 pr-3 py-1.5 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition"
               placeholder="Tìm kiếm cuộc trò chuyện...">
      </div>
    </div>

    {{-- Chat list --}}
    <div class="flex-1 overflow-y-auto">
      @forelse($chatList as $item)
        @php
          $href = $item['type'] === 'group'
            ? route('chat.group', $item['id'])
            : route('chat.dm', $item['other_user_id'] ?? $item['id']);
          $isActive = request()->is("chat/dm/{$item['id']}")
            || (isset($item['other_user_id']) && request()->is("chat/dm/{$item['other_user_id']}"))
            || request()->is("chat/group/{$item['id']}");
        @endphp
        <a href="{{ $href }}"
           class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 border-b border-gray-50 transition {{ $isActive ? 'bg-sky-50' : '' }}">
          {{-- Avatar --}}
          <div class="relative flex-shrink-0">
            @if($item['avatar'])
              <img src="{{ $item['avatar'] }}" class="w-11 h-11 rounded-full object-cover">
            @else
              <div class="w-11 h-11 rounded-full flex items-center justify-center font-bold text-sm
                {{ $item['type'] === 'group' ? 'bg-purple-500 text-white' : 'bg-sky-500 text-white' }}">
                {{ strtoupper(substr($item['name'], 0, 1)) }}
              </div>
            @endif
            @if($item['type'] === 'dm' && $item['is_online'])
              <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></span>
            @endif
          </div>

          {{-- Info --}}
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <span class="font-medium text-sm text-gray-900 truncate">{{ $item['name'] }}</span>
              @if($item['type'] === 'group')
                <span class="text-[10px] text-purple-500 font-medium ml-1 flex-shrink-0">nhóm</span>
              @endif
            </div>
            <p class="text-xs text-gray-500 truncate mt-0.5">
              {{ $item['last_msg_at'] ? \Carbon\Carbon::createFromTimestampMs($item['last_msg_at'])->diffForHumans() : 'Chưa có tin nhắn' }}
            </p>
          </div>

          {{-- Unread badge --}}
          @if($item['unread'] > 0)
            <span class="bg-sky-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 flex-shrink-0">
              {{ $item['unread'] > 99 ? '99+' : $item['unread'] }}
            </span>
          @endif
        </a>
      @empty
        <div class="flex flex-col items-center justify-center h-48 text-gray-400">
          <svg class="w-12 h-12 mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
          <p class="text-sm">Chưa có cuộc trò chuyện nào</p>
          <a href="{{ route('friends.index') }}" class="mt-2 text-xs text-sky-500 hover:underline">Tìm bạn bè →</a>
        </div>
      @endforelse
    </div>
  </div>

  {{-- Welcome placeholder --}}
  <div class="flex-1 flex flex-col items-center justify-center text-gray-400 bg-gray-50">
    <div class="text-center">
      <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
      </div>
      <h3 class="font-semibold text-gray-600 text-lg">Chọn một cuộc trò chuyện</h3>
      <p class="text-sm mt-1">hoặc bắt đầu trò chuyện mới với bạn bè</p>
      <a href="{{ route('friends.index') }}" class="mt-4 inline-flex items-center gap-1.5 bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Tìm bạn bè
      </a>
    </div>
  </div>

</div>
@endsection
