@extends('layouts.app')
@section('title', 'Backup & Restore')

@section('content')
<div class="flex-1 overflow-y-auto p-6 bg-gray-50">
  <div class="max-w-3xl mx-auto space-y-5">

    {{-- Admin nav --}}
    <div class="flex gap-2">
      <a href="{{ route('admin.users') }}" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">👤 Người dùng</a>
      <a href="{{ route('admin.backup') }}" class="bg-sky-500 text-white text-sm font-medium px-4 py-2 rounded-lg">💾 Backup</a>
      <a href="{{ route('admin.logs') }}" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">📒 Logs</a>
      <a href="{{ route('admin.redis_info') }}" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">🔴 Redis Info</a>
    </div>

    {{-- Alerts --}}
    @foreach(['success','error','info'] as $type)
      @if(session($type))
        <div class="bg-{{ $type==='success'?'green':($type==='error'?'red':'sky') }}-50 border border-{{ $type==='success'?'green':($type==='error'?'red':'sky') }}-200 text-{{ $type==='success'?'green':($type==='error'?'red':'sky') }}-700 rounded-lg px-4 py-3 text-sm">
          {{ session($type) }}
        </div>
      @endif
    @endforeach

    {{-- Create Backup --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
      <h3 class="font-semibold text-gray-900 mb-1 flex items-center gap-2">
        <span>💾 Tạo Backup mới</span>
      </h3>
      <p class="text-xs text-gray-500 mb-4">
        Toàn bộ cơ sở dữ liệu Redis sẽ được trích xuất thành file <code class="bg-gray-100 text-sky-700 px-1.5 py-0.5 rounded font-mono">.json</code> lưu trong thư mục <code class="bg-gray-100 text-sky-700 px-1.5 py-0.5 rounded font-mono">storage/app/backups/</code> của project. Bạn có thể tải file về máy hoặc khôi phục dữ liệu bất cứ lúc nào.
      </p>
      <form method="POST" action="{{ route('admin.backup.create') }}" class="flex gap-2">
        @csrf
        <input type="text" name="note" placeholder="Nhập ghi chú cho bản backup (ví dụ: Bản sao lưu trước khi cập nhật...)"
          class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
        <button class="bg-sky-500 hover:bg-sky-600 text-white font-medium px-5 py-2 rounded-lg text-sm transition flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
          <span>Tạo Backup ngay</span>
        </button>
      </form>
    </div>

    {{-- Backup list --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-900">📋 Danh sách file Backup</h3>
        <span class="text-xs text-gray-400">{{ count($backups) }} bản sao lưu</span>
      </div>
      @forelse($backups as $backup)
      <div class="px-5 py-3.5 border-b border-gray-50 last:border-0 flex items-center gap-3 hover:bg-gray-50/60 transition">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0
          {{ $backup['status'] === 'done' ? 'bg-emerald-100 text-emerald-600' : ($backup['status'] === 'failed' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600') }}">
          @if($backup['status'] === 'done')
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          @else
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          @endif
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <p class="text-sm font-semibold text-gray-900 truncate font-mono">
              {{ $backup['file_name'] ?? ('backup_' . substr($backup['backup_id'], 0, 8) . '.json') }}
            </p>
            @if(!empty($backup['size_bytes']))
              <span class="text-[11px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 font-mono">
                {{ number_format($backup['size_bytes'] / 1024, 1) }} KB
              </span>
            @endif
          </div>
          <p class="text-xs text-gray-500 truncate mt-0.5">
            <span class="text-gray-700 font-medium">{{ $backup['note'] ?: 'Không có ghi chú' }}</span> ·
            {{ \Carbon\Carbon::createFromTimestampMs($backup['created_at'])->format('d/m/Y H:i:s') }}
          </p>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
          @if($backup['status'] === 'done')
            {{-- Download --}}
            <a href="{{ route('admin.backup.download', $backup['backup_id']) }}"
              class="inline-flex items-center gap-1 bg-sky-50 hover:bg-sky-100 text-sky-700 text-xs px-3 py-1.5 rounded-lg font-medium transition"
              title="Tải file về máy">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              <span>Tải về</span>
            </a>

            {{-- 1-Click Restore --}}
            <form method="POST" action="{{ route('admin.backup.restore') }}"
              onsubmit="return confirm('⚠️ CẢNH BÁO: Toàn bộ dữ liệu hiện tại trong Redis sẽ được khôi phục từ bản backup này. Bạn có chắc chắn muốn khôi phục không?')">
              @csrf
              <input type="hidden" name="backup_id" value="{{ $backup['backup_id'] }}">
              <button type="submit"
                class="inline-flex items-center gap-1 bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs px-3 py-1.5 rounded-lg font-medium transition"
                title="Khôi phục dữ liệu từ bản này">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Khôi phục</span>
              </button>
            </form>
          @endif

          {{-- Delete --}}
          <form method="POST" action="{{ route('admin.backup.destroy', $backup['backup_id']) }}"
            onsubmit="return confirm('Xóa file backup này khỏi hệ thống?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition" title="Xóa backup">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </form>
        </div>
      </div>
      @empty
      <div class="text-center text-gray-400 py-10 text-sm">
        <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
        <p>Chưa có file backup nào. Hãy nhấn nút "Tạo Backup ngay" ở trên.</p>
      </div>
      @endforelse
    </div>
  </div>
</div>
@endsection
