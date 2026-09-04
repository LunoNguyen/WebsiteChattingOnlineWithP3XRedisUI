<?php $__env->startSection('title', 'Quản lý người dùng'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex-1 overflow-y-auto p-6 bg-gray-50">
  <div class="max-w-5xl mx-auto">

    
    <div class="grid grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <p class="text-xs text-gray-500">Tổng người dùng</p>
        <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo e($stats['total_users']); ?></p>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <p class="text-xs text-gray-500">Đang online</p>
        <p class="text-2xl font-bold text-green-500 mt-1"><?php echo e($stats['online_count']); ?></p>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <p class="text-xs text-gray-500">Redis keys</p>
        <p class="text-2xl font-bold text-sky-500 mt-1"><?php echo e(number_format($stats['db_size'])); ?></p>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <p class="text-xs text-gray-500">Memory Redis</p>
        <p class="text-2xl font-bold text-purple-500 mt-1"><?php echo e($stats['memory_used']); ?></p>
      </div>
    </div>

    
    <?php if(session('success')): ?>
      <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">✅ <?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
      <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">❌ <?php echo e(session('error')); ?></div>
    <?php endif; ?>

    
    <div class="flex gap-2 mb-4">
      <a href="<?php echo e(route('admin.users')); ?>" class="bg-sky-500 text-white text-sm font-medium px-4 py-2 rounded-lg">👤 Người dùng</a>
      <a href="<?php echo e(route('admin.backup')); ?>" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">💾 Backup</a>
      <a href="<?php echo e(route('admin.logs')); ?>" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">📒 Logs</a>
      <a href="<?php echo e(route('admin.redis_info')); ?>" class="bg-white border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">🔴 Redis Info</a>
    </div>

    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Người dùng</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Email</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Role</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Online</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Hành động</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php $__currentLoopData = $allUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php if($user->status !== 'deleted'): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-sky-400 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                  <?php echo e(strtoupper(substr($user->getName(), 0, 1))); ?>

                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900"><?php echo e($user->getName()); ?></p>
                  <p class="text-xs text-gray-400">{{ $user->username }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600"><?php echo e($user->email); ?></td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                <?php echo e($user->role === 'admin' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700'); ?>">
                <?php echo e($user->role === 'admin' ? '🛡️ Admin' : 'User'); ?>

              </span>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                <?php echo e($user->status === 'active' ? 'bg-green-100 text-green-700'
                  : ($user->status === 'banned' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500')); ?>">
                <?php echo e(['active'=>'Hoạt động','banned'=>'Đã khóa','deleted'=>'Đã xóa'][$user->status] ?? $user->status); ?>

              </span>
            </td>
            <td class="px-4 py-3">
              <?php if(in_array($user->user_id, $onlineIds)): ?>
                <span class="w-2 h-2 bg-green-400 rounded-full inline-block"></span>
              <?php else: ?>
                <span class="w-2 h-2 bg-gray-300 rounded-full inline-block"></span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-right">
              <?php if($user->user_id !== $authUser->user_id && !$user->isAdmin()): ?>
                <?php if($user->status === 'banned'): ?>
                  <form method="POST" action="<?php echo e(route('admin.users.unban')); ?>" class="inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="user_id" value="<?php echo e($user->user_id); ?>">
                    <button class="text-xs bg-green-100 hover:bg-green-200 text-green-700 px-2.5 py-1.5 rounded-lg font-medium">Mở khóa</button>
                  </form>
                <?php else: ?>
                  <form method="POST" action="<?php echo e(route('admin.users.ban')); ?>" class="inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="user_id" value="<?php echo e($user->user_id); ?>">
                    <button class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-2.5 py-1.5 rounded-lg font-medium">Khóa</button>
                  </form>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-xs text-gray-300">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endif; ?>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\chat-app\resources\views/admin/users.blade.php ENDPATH**/ ?>