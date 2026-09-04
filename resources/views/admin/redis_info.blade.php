@extends('layouts.app')
@section('title', 'Redis Info')

@section('content')
<div class="flex-1 overflow-y-auto p-6 bg-gray-50">
  <div class="max-w-5xl mx-auto space-y-5">

    {{-- Admin nav --}}
    <div class="flex gap-2">
      <a href="{{ route('admin.users') }}" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">👤 Người dùng</a>
      <a href="{{ route('admin.backup') }}" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">💾 Backup</a>
      <a href="{{ route('admin.logs') }}" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">📒 Logs</a>
      <a href="{{ route('admin.redis_info') }}" class="bg-sky-500 text-white text-sm font-medium px-4 py-2 rounded-lg">🔴 Redis Info</a>
    </div>

    @if(!empty($info))
      {{-- Overview Cards --}}
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
          <p class="text-xs text-gray-500 font-medium">Redis Version</p>
          <p class="text-lg font-bold text-gray-900 mt-1 font-mono">
            {{ $info['Server']['redis_version'] ?? ($info['redis_version'] ?? 'N/A') }}
          </p>
          <p class="text-[11px] text-gray-400 mt-0.5">Mode: {{ $info['Server']['redis_mode'] ?? 'standalone' }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
          <p class="text-xs text-gray-500 font-medium">Bộ nhớ sử dụng</p>
          <p class="text-lg font-bold text-emerald-600 mt-1 font-mono">
            {{ $info['Memory']['used_memory_human'] ?? ($info['used_memory_human'] ?? 'N/A') }}
          </p>
          <p class="text-[11px] text-gray-400 mt-0.5">Peak: {{ $info['Memory']['used_memory_peak_human'] ?? 'N/A' }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
          <p class="text-xs text-gray-500 font-medium">Clients kết nối</p>
          <p class="text-lg font-bold text-sky-600 mt-1 font-mono">
            {{ $info['Clients']['connected_clients'] ?? ($info['connected_clients'] ?? 0) }}
          </p>
          <p class="text-[11px] text-gray-400 mt-0.5">Blocked: {{ $info['Clients']['blocked_clients'] ?? 0 }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
          <p class="text-xs text-gray-500 font-medium">Thời gian hoạt động</p>
          <p class="text-lg font-bold text-indigo-600 mt-1 font-mono">
            {{ isset($info['Server']['uptime_in_days']) ? $info['Server']['uptime_in_days'] . ' ngày' : 'N/A' }}
          </p>
          <p class="text-[11px] text-gray-400 mt-0.5">{{ $info['Server']['uptime_in_seconds'] ?? 0 }}s</p>
        </div>
      </div>

      {{-- Section Details --}}
      <div class="space-y-4">
        @foreach($info as $section => $values)
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 bg-gray-50/80 border-b border-gray-200 flex items-center justify-between">
              <h4 class="font-semibold text-gray-800 text-sm flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                {{ is_string($section) ? $section : 'Chi tiết' }}
              </h4>
              <span class="text-xs text-gray-400">
                {{ is_array($values) ? count($values) . ' thông số' : '' }}
              </span>
            </div>

            <div class="p-4">
              @if(is_array($values))
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2">
                  @foreach($values as $key => $val)
                    <div class="flex items-start justify-between py-1.5 border-b border-gray-100 text-xs font-mono">
                      <span class="text-gray-500 font-medium select-all">{{ $key }}</span>
                      <span class="text-gray-900 text-right select-all break-all max-w-[60%]">
                        @if(is_array($val))
                          <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-[11px]">
                            @foreach($val as $subKey => $subVal)
                              {{ $subKey }}={{ is_array($subVal) ? json_encode($subVal) : $subVal }}{{ !$loop->last ? ', ' : '' }}
                            @endforeach
                          </span>
                        @elseif(is_bool($val))
                          <span class="text-purple-600 font-semibold">{{ $val ? 'true' : 'false' }}</span>
                        @else
                          {{ (string)$val }}
                        @endif
                      </span>
                    </div>
                  @endforeach
                </div>
              @else
                <div class="flex items-center justify-between py-1.5 text-xs font-mono">
                  <span class="text-gray-500 font-medium">{{ $section }}</span>
                  <span class="text-gray-900 font-semibold">{{ (string)$values }}</span>
                </div>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <p class="text-sm font-medium text-gray-600">Không thể kết nối đến Redis server</p>
        <p class="text-xs text-gray-400 mt-1">Vui lòng kiểm tra lại dịch vụ Redis trong Laragon.</p>
      </div>
    @endif

  </div>
</div>
@endsection
