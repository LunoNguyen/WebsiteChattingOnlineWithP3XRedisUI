@extends('layouts.app')
@section('title', 'Nhật ký Admin')

@section('content')
<div class="flex-1 overflow-y-auto p-6 bg-gray-50">
  <div class="max-w-4xl mx-auto">
    <div class="flex gap-2 mb-5">
      <a href="{{ route('admin.users') }}" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">👤 Người dùng</a>
      <a href="{{ route('admin.backup') }}" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">💾 Backup</a>
      <a href="{{ route('admin.logs') }}" class="bg-sky-500 text-white text-sm font-medium px-4 py-2 rounded-lg">📒 Logs</a>
      <a href="{{ route('admin.redis_info') }}" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">🔴 Redis Info</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-5 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-900">📒 Nhật ký hoạt động Admin (50 gần nhất)</h3>
      </div>
      @forelse($logs as $log)
      <div class="px-5 py-3 border-b border-gray-50 last:border-0 flex items-start gap-3">
        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-sm flex-shrink-0">
          {{ ['ban_user'=>'🔒','unban_user'=>'🔓','delete_user'=>'🗑️','create_backup'=>'💾','restore_initiated'=>'♻️'][$log['action']] ?? '📝' }}
        </div>
        <div class="flex-1">
          <p class="text-sm text-gray-900">
            <span class="font-medium font-mono text-xs">{{ substr($log['admin_id'],0,8) }}</span>
            <span class="mx-1 text-gray-400">→</span>
            <span class="font-semibold">{{ $log['action'] }}</span>
            <span class="mx-1 text-gray-400">target:</span>
            <span class="font-mono text-xs">{{ substr($log['target'],0,12) }}</span>
          </p>
          @if($log['detail'])<p class="text-xs text-gray-500 mt-0.5">{{ $log['detail'] }}</p>@endif
        </div>
        <span class="text-xs text-gray-400 flex-shrink-0">
          {{ \Carbon\Carbon::createFromTimestampMs($log['timestamp'])->format('d/m H:i') }}
        </span>
      </div>
      @empty
        <div class="text-center text-gray-400 py-10 text-sm">Chưa có nhật ký nào.</div>
      @endforelse
    </div>
  </div>
</div>
@endsection
