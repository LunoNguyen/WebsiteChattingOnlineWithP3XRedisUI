@extends('layouts.app')
@section('title', 'Nhóm của tôi')

@section('content')
<div class="flex-1 overflow-y-auto p-6 bg-gray-50">
  <div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-5">
      <h2 class="font-semibold text-gray-900 text-lg">👥 Nhóm của tôi</h2>
      <a href="{{ route('groups.create') }}"
        class="bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tạo nhóm mới
      </a>
    </div>

    @if(session('success'))
      <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">✅ {{ session('success') }}</div>
    @endif

    @forelse($myGroups as $group)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-3 flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl bg-purple-500 flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
        {{ strtoupper(substr($group->name, 0, 1)) }}
      </div>
      <div class="flex-1 min-w-0">
        <h3 class="font-semibold text-gray-900">{{ $group->name }}</h3>
        <p class="text-xs text-gray-500 truncate">{{ $group->description ?: 'Không có mô tả' }}</p>
        <p class="text-xs text-gray-400 mt-0.5">
          Tạo: {{ \Carbon\Carbon::createFromTimestampMs($group->created_at)->format('d/m/Y') }}
        </p>
      </div>
      <a href="{{ route('chat.group', $group->group_id) }}"
        class="bg-sky-100 hover:bg-sky-200 text-sky-700 text-sm font-medium px-4 py-2 rounded-lg transition">
        Mở chat
      </a>
    </div>
    @empty
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center text-gray-400">
      <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <p class="font-medium">Bạn chưa tham gia nhóm nào</p>
      <p class="text-sm mt-1">Tạo nhóm mới hoặc chờ được mời</p>
    </div>
    @endforelse
  </div>
</div>
@endsection
