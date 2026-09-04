<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập — ChatApp</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-sky-900 flex items-center justify-center p-4">

  <div class="w-full max-w-md">
    
    <div class="text-center mb-8">
      <div class="inline-flex w-14 h-14 bg-sky-500 rounded-2xl items-center justify-center mb-3 shadow-lg">
        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-white">ChatApp</h1>
      <p class="text-gray-400 text-sm mt-1">Đăng nhập để tiếp tục</p>
    </div>

    
    <div class="bg-gray-800 rounded-2xl p-8 shadow-2xl border border-gray-700">

      
      <?php if(session('error')): ?>
        <div class="mb-4 bg-red-500/15 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 text-sm flex items-center gap-2">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <?php echo e(session('error')); ?>

        </div>
      <?php endif; ?>
      <?php if(session('success')): ?>
        <div class="mb-4 bg-green-500/15 border border-green-500/30 text-green-400 rounded-lg px-4 py-3 text-sm">
          <?php echo e(session('success')); ?>

        </div>
      <?php endif; ?>

      <form method="POST" action="<?php echo e(route('login.post')); ?>" class="space-y-5">
        <?php echo csrf_field(); ?>

        <div>
          <label class="block text-sm font-medium text-gray-300 mb-1.5">Tên đăng nhập</label>
          <input type="text" name="username" value="<?php echo e(old('username')); ?>"
            class="w-full bg-gray-700 border border-gray-600 rounded-xl px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm transition"
            placeholder="username" autocomplete="username" required autofocus>
          <?php $__errorArgs = ['username'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="mt-1 text-xs text-red-400"><?php echo e($message); ?></p>
          <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
          <div class="flex justify-between items-center mb-1.5">
            <label class="text-sm font-medium text-gray-300">Mật khẩu</label>
          </div>
          <input type="password" name="password"
            class="w-full bg-gray-700 border border-gray-600 rounded-xl px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm transition"
            placeholder="••••••••" autocomplete="current-password" required>
          <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="mt-1 text-xs text-red-400"><?php echo e($message); ?></p>
          <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <button type="submit"
          class="w-full bg-sky-500 hover:bg-sky-600 text-white font-semibold py-2.5 rounded-xl transition text-sm shadow-lg shadow-sky-500/20">
          Đăng nhập
        </button>
      </form>

      <p class="text-center text-gray-400 text-sm mt-6">
        Chưa có tài khoản?
        <a href="<?php echo e(route('register')); ?>" class="text-sky-400 hover:text-sky-300 font-medium">Đăng ký ngay</a>
      </p>
    </div>
  </div>
</body>
</html>
<?php /**PATH C:\laragon\www\chat-app\resources\views/auth/login.blade.php ENDPATH**/ ?>