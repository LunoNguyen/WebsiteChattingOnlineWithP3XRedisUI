@extends('layouts.app')
@section('title', 'Tạo nhóm')

@section('content')
<div class="flex-1 overflow-y-auto p-6 bg-gray-50">
  <div class="max-w-lg mx-auto">

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <h2 class="font-semibold text-gray-900 text-lg mb-5">👥 Tạo nhóm chat mới</h2>

      @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">{{ session('error') }}</div>
      @endif

      <form method="POST" action="{{ route('groups.store') }}" class="space-y-4">
        @csrf

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tên nhóm *</label>
          <input type="text" name="name" value="{{ old('name') }}" required
            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"
            placeholder="VD: Team NoSQL 🚀">
          @error('name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
          <textarea name="description" rows="2"
            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 resize-none"
            placeholder="Mô tả ngắn về nhóm...">{{ old('description') }}</textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Thêm thành viên từ bạn bè</label>
          <div class="space-y-1.5 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
            @forelse($friends as $item)
              <label class="flex items-center gap-2.5 cursor-pointer hover:bg-gray-50 rounded px-1 py-0.5">
                <input type="checkbox" name="member_ids[]" value="{{ $item['user']->user_id }}"
                  class="w-4 h-4 text-sky-500 rounded border-gray-300">
                @if($item['user']->avatar_url)
                  <img src="{{ $item['user']->avatar_url }}" class="w-7 h-7 rounded-full object-cover flex-shrink-0">
                @else
                  <div class="w-7 h-7 rounded-full bg-sky-400 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr($item['user']->getName(), 0, 1)) }}
                  </div>
                @endif
                <div>
                  <span class="text-sm text-gray-900">{{ $item['nickname'] ?: $item['user']->getName() }}</span>
                </div>
                @if($item['is_online'])
                  <span class="w-1.5 h-1.5 bg-green-400 rounded-full ml-auto"></span>
                @endif
              </label>
            @empty
              <p class="text-gray-400 text-sm text-center py-2">Chưa có bạn bè để thêm vào nhóm.</p>
            @endforelse
          </div>
        </div>

        <div class="flex gap-3 pt-1">
          <button type="submit"
            class="flex-1 bg-sky-500 hover:bg-sky-600 text-white font-semibold py-2.5 rounded-xl transition text-sm">
            Tạo nhóm
          </button>
          <a href="{{ route('groups.index') }}"
            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2.5 rounded-xl text-center text-sm transition">
            Hủy
          </a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
