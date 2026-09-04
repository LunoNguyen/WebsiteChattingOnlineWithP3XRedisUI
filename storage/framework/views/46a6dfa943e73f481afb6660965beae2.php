<?php $__env->startSection('title', 'Nhật ký Admin'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex-1 overflow-y-auto p-6 bg-gray-50">
  <div class="max-w-4xl mx-auto">
    <div class="flex gap-2 mb-5">
      <a href="<?php echo e(route('admin.users')); ?>" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">👤 Người dùng</a>
      <a href="<?php echo e(route('admin.backup')); ?>" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">💾 Backup</a>
      <a href="<?php echo e(route('admin.logs')); ?>" class="bg-sky-500 text-white text-sm font-medium px-4 py-2 rounded-lg">📒 Logs</a>
      <a href="<?php echo e(route('admin.redis_info')); ?>" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">🔴 Redis Info</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-5 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-900">📒 Nhật ký hoạt động Admin (50 gần nhất)</h3>
      </div>
      <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <div class="px-5 py-3 border-b border-gray-50 last:border-0 flex items-start gap-3">
        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-sm flex-shrink-0">
          <?php echo e(['ban_user'=>'🔒','unban_user'=>'🔓','delete_user'=>'🗑️','create_backup'=>'💾','restore_initiated'=>'♻️'][$log['action']] ?? '📝'); ?>

        </div>
        <div class="flex-1">
          <p class="text-sm text-gray-900">
            <span class="font-medium font-mono text-xs"><?php echo e(substr($log['admin_id'],0,8)); ?></span>
            <span class="mx-1 text-gray-400">→</span>
            <span class="font-semibold"><?php echo e($log['action']); ?></span>
            <span class="mx-1 text-gray-400">target:</span>
            <span class="font-mono text-xs"><?php echo e(substr($log['target'],0,12)); ?></span>
          </p>
          <?php if($log['detail']): ?><p class="text-xs text-gray-500 mt-0.5"><?php echo e($log['detail']); ?></p><?php endif; ?>
        </div>
        <span class="text-xs text-gray-400 flex-shrink-0">
          <?php echo e(\Carbon\Carbon::createFromTimestampMs($log['timestamp'])->format('d/m H:i')); ?>

        </span>
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="text-center text-gray-400 py-10 text-sm">Chưa có nhật ký nào.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\chat-app\resources\views/admin/logs.blade.php ENDPATH**/ ?>