<?php $__env->startSection('title', $nickname ?: $otherUser->getName()); ?>

<?php $__env->startSection('content'); ?>
<div class="flex h-full flex-col">

  
  <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 flex-shrink-0">
    <a href="<?php echo e(route('chat.index')); ?>" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition mr-1" title="Quay lại danh sách chat">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <div class="relative">
      <?php if($otherUser->avatar_url): ?>
        <img src="<?php echo e($otherUser->avatar_url); ?>" class="w-10 h-10 rounded-full object-cover">
      <?php else: ?>
        <div class="w-10 h-10 rounded-full bg-sky-500 flex items-center justify-center text-white font-bold">
          <?php echo e(strtoupper(substr($otherUser->getName(), 0, 1))); ?>

        </div>
      <?php endif; ?>
      <?php if($otherUser->is_online): ?>
        <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 rounded-full border-2 border-white"></span>
      <?php endif; ?>
    </div>
    <div class="flex-1 min-w-0">
      <h3 class="font-semibold text-gray-900 text-sm truncate">
        <?php echo e($nickname ?: $otherUser->getName()); ?>

      </h3>
      <p class="text-xs <?php echo e($otherUser->is_online ? 'text-green-500 font-medium' : 'text-gray-400'); ?>">
        <?php echo e($otherUser->is_online ? 'Đang online' : 'Offline'); ?>

      </p>
    </div>

    
    <div class="flex items-center gap-1.5">
      
      <button onclick="toggleSearch()"
        class="p-2 text-gray-400 hover:text-sky-500 hover:bg-gray-100 rounded-lg transition" title="Tìm kiếm tin nhắn">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </button>

      <?php if(!$isBlocked): ?>
        
        <button onclick="document.getElementById('nickname-modal').classList.toggle('hidden')"
          class="p-2 text-gray-400 hover:text-sky-500 hover:bg-gray-100 rounded-lg transition" title="Đặt biệt danh">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
        </button>
      <?php endif; ?>

      
      <form method="POST" action="<?php echo e($isBlocked ? route('friends.unblock') : route('friends.block')); ?>"
        onsubmit="return confirm('<?php echo e($isBlocked ? 'Gỡ chặn cho người này?' : 'Chặn người dùng này? Hai người sẽ không thể nhắn tin cho nhau.'); ?>')">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="target_id" value="<?php echo e($otherUser->user_id); ?>">
        <button type="submit" class="p-2 <?php echo e($isBlocked ? 'text-red-500 bg-red-50 hover:bg-red-100' : 'text-gray-400 hover:text-red-500 hover:bg-gray-100'); ?> rounded-lg transition"
          title="<?php echo e($isBlocked ? 'Bấm để gỡ chặn' : 'Chặn người dùng'); ?>">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        </button>
      </form>
    </div>
  </div>

  
  <div id="search-bar" class="hidden bg-sky-50 border-b border-sky-200 px-4 py-2 flex items-center gap-2">
    <svg class="w-4 h-4 text-sky-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    <input type="text" id="search-input" placeholder="Tìm kiếm trong đoạn hội thoại..."
      class="flex-1 bg-transparent text-sm text-gray-700 focus:outline-none"
      oninput="searchMessages(this.value)">
    <span id="search-count" class="text-xs text-sky-600 font-medium hidden"></span>
    <button onclick="toggleSearch()" class="text-gray-400 hover:text-gray-600 font-bold text-sm">✕</button>
  </div>

  
  <div id="pinned-banner" class="hidden bg-amber-50 border-b border-amber-200 px-4 py-2 flex items-center gap-2 text-xs text-amber-800 cursor-pointer" onclick="scrollToPinned()">
    <span class="font-semibold bg-amber-200 text-amber-900 px-1.5 py-0.5 rounded text-[10px]">ĐÃ GHIM</span>
    <span id="pinned-content" class="truncate text-amber-800"></span>
    <button onclick="event.stopPropagation(); unpinMessage()" class="ml-auto text-amber-600 hover:text-amber-900 font-bold flex-shrink-0">✕</button>
  </div>

  
  <?php if($isBlocked): ?>
    <div class="bg-red-50 border-b border-red-200 px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-2 text-xs text-red-700">
        <span class="font-semibold bg-red-200 text-red-800 px-1.5 py-0.5 rounded text-[10px]">ĐÃ CHẶN</span>
        <span>Bạn đã chặn người dùng này. Không thể gửi hoặc nhận tin nhắn mới.</span>
      </div>
      <form method="POST" action="<?php echo e(route('friends.unblock')); ?>" onsubmit="return confirm('Gỡ chặn người này?')">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="target_id" value="<?php echo e($otherUser->user_id); ?>">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-xs font-medium transition shadow-xs">
          Gỡ chặn ngay
        </button>
      </form>
    </div>
  <?php endif; ?>

  
  <div id="nickname-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-5 w-80 shadow-xl">
      <h4 class="font-semibold text-gray-900 mb-3 text-sm">Đặt biệt danh cho <?php echo e($otherUser->getName()); ?></h4>
      <form method="POST" action="<?php echo e(route('friends.nickname')); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="friend_id" value="<?php echo e($otherUser->user_id); ?>">
        <input type="text" name="nickname" value="<?php echo e($nickname); ?>"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"
          placeholder="Nhập biệt danh (để trống để xóa)">
        <div class="flex gap-2 mt-3">
          <button type="submit" class="flex-1 bg-sky-500 text-white rounded-lg py-2 text-sm font-medium hover:bg-sky-600">Lưu</button>
          <button type="button" onclick="document.getElementById('nickname-modal').classList.add('hidden')"
            class="flex-1 bg-gray-100 text-gray-700 rounded-lg py-2 text-sm font-medium hover:bg-gray-200">Hủy</button>
        </div>
      </form>
    </div>
  </div>

  
  <div id="poll-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-sm shadow-xl p-5">
      <h4 class="font-semibold text-gray-900 mb-3 text-sm">Tạo cuộc bình chọn</h4>
      <input type="text" id="poll-question" placeholder="Nhập câu hỏi bình chọn..."
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 mb-3">
      <div id="poll-options-container" class="space-y-2 mb-3">
        <input type="text" placeholder="Lựa chọn 1" class="poll-option w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-sky-400">
        <input type="text" placeholder="Lựa chọn 2" class="poll-option w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-sky-400">
      </div>
      <button type="button" onclick="addPollOption()" class="text-sky-600 text-xs hover:underline mb-3 block">+ Thêm lựa chọn</button>
      <div class="flex gap-2">
        <button onclick="submitPoll()" class="flex-1 bg-sky-500 hover:bg-sky-600 text-white rounded-lg py-2 text-sm font-medium">Tạo bình chọn</button>
        <button onclick="document.getElementById('poll-modal').classList.add('hidden')" class="flex-1 bg-gray-100 text-gray-700 rounded-lg py-2 text-sm font-medium">Hủy</button>
      </div>
    </div>
  </div>

  
  <div id="file-preview-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-4xl h-[85vh] shadow-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200">
      <div class="px-5 py-3.5 border-b border-gray-200 flex items-center justify-between bg-gray-50 flex-shrink-0">
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center font-bold text-xs flex-shrink-0" id="preview-modal-icon">
            DOC
          </div>
          <div class="min-w-0">
            <h4 class="font-bold text-gray-900 text-sm truncate" id="preview-modal-title">Xem trước tệp</h4>
            <p class="text-[11px] text-gray-400" id="preview-modal-size"></p>
          </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
          <a id="preview-modal-download" href="#" target="_blank" download
            class="bg-sky-500 hover:bg-sky-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Tải về</span>
          </a>
          <button type="button" onclick="closeFilePreviewModal()"
            class="text-gray-400 hover:text-gray-600 hover:bg-gray-200 p-1.5 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>

      <div class="flex-1 bg-gray-100 overflow-auto p-4 flex items-center justify-center relative" id="preview-modal-body">
        <div id="preview-modal-loading" class="flex flex-col items-center gap-2 text-gray-500 text-xs">
          <svg class="animate-spin h-6 w-6 text-sky-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>Đang tải nội dung xem trước...</span>
        </div>
        <div id="preview-modal-content" class="w-full h-full hidden overflow-auto"></div>
      </div>
    </div>
  </div>

  
  <div id="messages-container" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-gray-50">
    <div id="empty-state" class="<?php echo e(count($messages) > 0 ? 'hidden' : ''); ?> text-center text-gray-400 text-sm py-16">
      <p>Chưa có tin nhắn nào. Bắt đầu cuộc trò chuyện bằng tin nhắn đầu tiên.</p>
    </div>

    <?php
      $pinnedMsgId   = '';
      $pinnedContent = '';
      $otherAvatar   = $otherUser->avatar_url;
      $otherInitial  = strtoupper(substr($otherUser->getName(), 0, 1));
      $myAvatar      = $authUser->avatar_url;
      $myInitial     = strtoupper(substr($authUser->getName(), 0, 1));
      $otherDisplayName = $nickname ?: $otherUser->getName();

      // Build reply_to lookup map
      $msgMap = [];
      foreach($messages as $m) { $msgMap[$m->msg_id] = $m; }
    ?>

    <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php
        $isSystem = ($msg->type === 'system') || ($msg->sender_id === 'system');
      ?>

      <?php if($isSystem): ?>
        
        <div class="flex justify-center my-2.5 msg-row" id="msg-row-<?php echo e($msg->msg_id); ?>" data-msg-id="<?php echo e($msg->msg_id); ?>">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-50/90 border border-sky-100/80 text-sky-700 text-xs shadow-2xs max-w-lg text-center">
            <svg class="w-3.5 h-3.5 text-sky-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium leading-relaxed"><?php echo e($msg->content); ?></span>
            <span class="text-[10px] text-sky-400 ml-1 flex-shrink-0">
              <?php echo e($msg->created_at ? \Carbon\Carbon::createFromTimestampMs($msg->created_at)->format('H:i') : ''); ?>

            </span>
          </div>
        </div>
      <?php else: ?>
      <?php
        $isMine  = $msg->sender_id === $authUser->user_id;
        $senderDisplayName = $isMine ? 'Bạn' : $otherDisplayName;

        // Lookup reply message
        $replyMsg = null;
        if (!empty($msg->reply_to)) {
          $replyMsg = $msgMap[$msg->reply_to] ?? app(\App\Repositories\MessageRepository::class)->findById($msg->reply_to);
        }

        // Check if poll
        $isPoll = ($msg->type === 'poll') || (!empty($msg->content) && str_starts_with(trim($msg->content), '{"question":'));
        $pollData = null;
        if ($isPoll) {
          $pollData = json_decode($msg->content, true);
          if (!is_array($pollData) || !isset($pollData['options'])) {
            $isPoll = false;
          }
        }
      ?>

      <div class="flex <?php echo e($isMine ? 'justify-end' : 'justify-start'); ?> items-center gap-2 group msg-row py-1"
           id="msg-row-<?php echo e($msg->msg_id); ?>" data-msg-id="<?php echo e($msg->msg_id); ?>">

        
        <?php if($isMine && !$msg->isDeleted()): ?>
          <div class="actions-panel opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1 bg-white border border-gray-200 rounded-lg shadow-sm px-1.5 py-1 self-center flex-shrink-0">
            <button onclick="replyToMsg('<?php echo e($msg->msg_id); ?>')" class="px-2 py-0.5 text-xs text-gray-600 hover:text-sky-600 hover:bg-sky-50 rounded font-medium transition">Trả lời</button>
            <button onclick="pinMessage('<?php echo e($msg->msg_id); ?>')" class="px-2 py-0.5 text-xs text-gray-600 hover:text-amber-600 hover:bg-amber-50 rounded font-medium transition">Ghim</button>
            <button onclick="editMsg('<?php echo e($msg->msg_id); ?>')" class="px-2 py-0.5 text-xs text-gray-600 hover:text-sky-600 hover:bg-sky-50 rounded font-medium transition">Sửa</button>
            <button onclick="deleteMsg('<?php echo e($msg->msg_id); ?>')" class="px-2 py-0.5 text-xs text-gray-600 hover:text-red-600 hover:bg-red-50 rounded font-medium transition">Xóa</button>
          </div>
        <?php endif; ?>

        
        <?php if(!$isMine): ?>
          <div class="flex-shrink-0 self-end mb-1">
            <?php if($otherAvatar): ?>
              <img src="<?php echo e($otherAvatar); ?>" class="w-8 h-8 rounded-full object-cover">
            <?php else: ?>
              <div class="w-8 h-8 rounded-full bg-sky-500 flex items-center justify-center text-white font-bold text-xs">
                <?php echo e($otherInitial); ?>

              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="max-w-[70%]">
          
          <p class="text-xs font-semibold <?php echo e($isMine ? 'text-sky-600 text-right mr-1' : 'text-gray-700 ml-1'); ?> mb-1">
            <?php echo e($senderDisplayName); ?>

          </p>

          
          <?php if($replyMsg): ?>
            <?php
              $replySenderName = $replyMsg->sender_id === $authUser->user_id ? 'Bạn' : $otherDisplayName;
              $replyContentSnippet = '';
              if ($replyMsg->isDeleted()) {
                $replyContentSnippet = 'Tin nhắn đã bị xóa';
              } elseif ($replyMsg->type === 'poll') {
                $rpd = json_decode($replyMsg->content, true);
                $replyContentSnippet = '[Bình chọn] ' . ($rpd['question'] ?? '');
              } elseif ($replyMsg->type === 'file') {
                $replyContentSnippet = '[Tệp đính kèm]';
              } else {
                $replyContentSnippet = Str::limit($replyMsg->content, 70);
              }
            ?>
            <div class="reply-preview-block mb-1 border-l-2 <?php echo e($isMine ? 'border-sky-400 bg-sky-50/70' : 'border-gray-400 bg-gray-100'); ?> px-2 py-1 rounded cursor-pointer text-xs transition hover:opacity-90"
                 onclick="document.getElementById('msg-row-<?php echo e($replyMsg->msg_id); ?>')?.scrollIntoView({behavior:'smooth',block:'center'})">
              <span class="font-semibold text-gray-800">Trả lời <?php echo e($replySenderName); ?>:</span>
              <span class="text-gray-600 ml-1"><?php echo e($replyContentSnippet); ?></span>
            </div>
          <?php endif; ?>

          
          <div class="px-3.5 py-2 text-sm shadow-sm rounded-2xl bubble-content
            <?php echo e($isMine
              ? 'bg-sky-500 text-white rounded-br-xs'
              : 'bg-white text-gray-900 border border-gray-200 rounded-bl-xs'); ?>

            <?php echo e($msg->isDeleted() ? 'opacity-60 italic' : ''); ?>">

            <?php if($isPoll && !$msg->isDeleted()): ?>
              <div class="poll-container w-64 py-1" id="poll-<?php echo e($msg->msg_id); ?>">
                <p class="font-semibold text-xs mb-2 <?php echo e($isMine ? 'text-white' : 'text-gray-900'); ?>">
                  [Bình chọn] <?php echo e($pollData['question'] ?? 'Cuộc bình chọn'); ?>

                </p>
                <div class="space-y-1.5 poll-options-list">
                  <?php
                    $totalVotes = 0;
                    foreach ($pollData['options'] as $idx => $opt) {
                      $totalVotes += count($pollData['votes'][$idx] ?? []);
                    }
                  ?>
                  <?php $__currentLoopData = $pollData['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                      $optionVotes = count($pollData['votes'][$idx] ?? []);
                      $votedThis = in_array($authUser->user_id, $pollData['votes'][$idx] ?? []);
                      $pct = $totalVotes > 0 ? round(($optionVotes / $totalVotes) * 100) : 0;
                    ?>
                    <button type="button" onclick="castVote('<?php echo e($msg->msg_id); ?>', <?php echo e($idx); ?>)"
                      class="w-full text-left p-2 rounded-lg border transition relative overflow-hidden text-xs
                        <?php echo e($votedThis ? 'border-sky-300 bg-white/20 font-bold' : 'border-gray-200/40 hover:bg-black/5'); ?>

                        <?php echo e($isMine ? 'text-white' : 'text-gray-800 bg-gray-50'); ?>">
                      <div class="absolute inset-y-0 left-0 <?php echo e($votedThis ? 'bg-sky-400/40' : 'bg-gray-300/30'); ?>" style="width: <?php echo e($pct); ?>%"></div>
                      <div class="relative flex justify-between items-center z-10">
                        <span class="truncate"><?php echo e($opt); ?></span>
                        <span class="text-[11px] opacity-80 ml-2 flex-shrink-0"><?php echo e($pct); ?>% (<?php echo e($optionVotes); ?>)</span>
                      </div>
                    </button>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <p class="text-[10px] opacity-70 mt-2 text-right poll-total-votes"><?php echo e($totalVotes); ?> lượt bình chọn</p>
              </div>
            <?php elseif($msg->type === 'file' && !$msg->isDeleted()): ?>
              <?php
                $fileData = json_decode($msg->content, true) ?: [];
                $fName    = $fileData['name'] ?? 'Tệp đính kèm';
                $fSize    = $fileData['size'] ?? '';
                $fMime    = $fileData['mime'] ?? '';
                $ext      = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
                $isImg    = str_starts_with($fMime, 'image/') || in_array($ext, ['jpg','jpeg','png','gif','webp','svg','bmp']);

                // Chuẩn hóa URL sang relative path để tránh lỗi domain/tunnel cũ
                $fUrl = !empty($fileData['object_key'])
                    ? ('/files/serve?key=' . urlencode($fileData['object_key']))
                    : ($fileData['url'] ?? '#');
                if (!empty($fUrl) && str_contains($fUrl, 'files/serve')) {
                    $parsed = parse_url($fUrl);
                    if (!empty($parsed['query'])) {
                        $fUrl = '/files/serve?' . $parsed['query'];
                    }
                }
              ?>
              <?php if($isImg): ?>
                <div class="space-y-1.5">
                  <img src="<?php echo e($fUrl); ?>" class="max-w-[260px] max-h-[300px] object-cover rounded-xl cursor-pointer hover:opacity-95 shadow-xs transition"
                       onclick="previewFile('<?php echo e($fUrl); ?>', '<?php echo e(addslashes($fName)); ?>', '<?php echo e($fSize); ?>', 'image')">
                  <div class="flex items-center justify-between text-[11px] <?php echo e($isMine ? 'text-white/80' : 'text-gray-500'); ?> px-0.5">
                    <span class="truncate max-w-[170px]"><?php echo e($fName); ?></span>
                    <span><?php echo e($fSize); ?></span>
                  </div>
                </div>
              <?php else: ?>
                <div class="w-64 p-2.5 rounded-xl border <?php echo e($isMine ? 'bg-white/10 border-white/20 text-white' : 'bg-gray-50 border-gray-200 text-gray-800'); ?> shadow-xs">
                  <div class="flex items-start gap-2.5">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-xs
                      <?php echo e(in_array($ext, ['doc','docx']) ? 'bg-blue-600 text-white' : (in_array($ext, ['pdf']) ? 'bg-red-500 text-white' : (in_array($ext, ['xls','xlsx']) ? 'bg-emerald-600 text-white' : 'bg-gray-600 text-white'))); ?>">
                      <?php echo e(strtoupper($ext ?: 'FILE')); ?>

                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-xs font-semibold truncate leading-tight mb-0.5" title="<?php echo e($fName); ?>"><?php echo e($fName); ?></p>
                      <p class="text-[10px] <?php echo e($isMine ? 'text-white/70' : 'text-gray-400'); ?>"><?php echo e($fSize); ?> • Tệp đính kèm</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 mt-2.5 pt-2 border-t <?php echo e($isMine ? 'border-white/15' : 'border-gray-200'); ?>">
                    <button type="button" onclick="previewFile('<?php echo e($fUrl); ?>', '<?php echo e(addslashes($fName)); ?>', '<?php echo e($fSize); ?>', '<?php echo e($ext); ?>')"
                      class="flex-1 py-1 px-2 rounded-lg text-xs font-medium text-center transition flex items-center justify-center gap-1
                        <?php echo e($isMine ? 'bg-white/20 hover:bg-white/30 text-white' : 'bg-sky-50 hover:bg-sky-100 text-sky-700'); ?>">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                      <span>Xem trước</span>
                    </button>
                    <a href="<?php echo e($fUrl); ?>" target="_blank" download
                      class="py-1 px-2.5 rounded-lg text-xs font-medium transition flex items-center justify-center gap-1
                        <?php echo e($isMine ? 'hover:bg-white/20 text-white' : 'hover:bg-gray-200 text-gray-700'); ?>" title="Tải về">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                      <span>Tải về</span>
                    </a>
                  </div>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <span class="msg-text-span">
                <?php if($msg->isDeleted()): ?>
                  Tin nhắn đã bị xóa
                <?php else: ?>
                  <?php echo e($msg->content); ?>

                <?php endif; ?>
              </span>
            <?php endif; ?>

            <span class="edited-tag text-[10px] opacity-70 ml-1 <?php echo e($msg->isEdited() && !$msg->isDeleted() ? '' : 'hidden'); ?>">(đã sửa)</span>
          </div>

          <p class="text-[10px] text-gray-400 mt-0.5 <?php echo e($isMine ? 'text-right mr-1' : 'text-left ml-1'); ?>">
            <?php echo e(\Carbon\Carbon::createFromTimestampMs($msg->created_at)->format('H:i')); ?>

          </p>
        </div>

        
        <?php if(!$isMine && !$msg->isDeleted()): ?>
          <div class="actions-panel opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1 bg-white border border-gray-200 rounded-lg shadow-sm px-1.5 py-1 self-center flex-shrink-0">
            <button onclick="replyToMsg('<?php echo e($msg->msg_id); ?>')" class="px-2 py-0.5 text-xs text-gray-600 hover:text-sky-600 hover:bg-sky-50 rounded font-medium transition">Trả lời</button>
            <button onclick="pinMessage('<?php echo e($msg->msg_id); ?>')" class="px-2 py-0.5 text-xs text-gray-600 hover:text-amber-600 hover:bg-amber-50 rounded font-medium transition">Ghim</button>
          </div>
        <?php endif; ?>

        
        <?php if($isMine): ?>
          <div class="flex-shrink-0 self-end mb-1">
            <?php if($myAvatar): ?>
              <img src="<?php echo e($myAvatar); ?>" class="w-8 h-8 rounded-full object-cover">
            <?php else: ?>
              <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold text-xs">
                <?php echo e($myInitial); ?>

              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>

  
  <?php if(!$isBlocked): ?>
  <div class="bg-white border-t border-gray-200 px-4 py-3 flex-shrink-0">

    
    <div id="reply-bar" class="hidden mb-2 bg-sky-50 border border-sky-200 rounded-lg px-3 py-1.5 flex items-start gap-2 text-xs text-sky-800">
      <div class="flex-1 min-w-0">
        <p class="font-semibold text-sky-700 text-[11px]">Đang trả lời: <span id="reply-sender-label"></span></p>
        <p class="truncate text-sky-800 italic" id="reply-content-label"></p>
      </div>
      <button onclick="cancelReply()" class="text-gray-400 hover:text-gray-600 font-bold flex-shrink-0">✕ Hủy</button>
    </div>

    
    <div id="edit-bar" class="hidden mb-2 bg-sky-50 border border-sky-200 rounded-lg px-3 py-1.5 flex items-center gap-2 text-xs text-sky-700">
      <span>Đang sửa tin nhắn...</span>
      <button onclick="cancelEdit()" class="ml-auto text-gray-400 hover:text-gray-600 font-bold">✕ Hủy</button>
    </div>

    
    <div class="flex items-center gap-1 mb-2">
      
      <label class="cursor-pointer p-1.5 text-gray-400 hover:text-sky-500 hover:bg-gray-100 rounded-lg transition" title="Đính kèm tệp/ảnh">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
        <input type="file" id="file-input" class="hidden" accept="*/*" onchange="handleFileAttach(this)">
      </label>

      
      <button onclick="document.getElementById('poll-modal').classList.remove('hidden')"
        class="p-1.5 text-gray-400 hover:text-sky-500 hover:bg-gray-100 rounded-lg transition" title="Tạo bình chọn">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      </button>

      
      <div id="file-preview" class="hidden flex items-center gap-2 flex-1 bg-sky-50 border border-sky-200 rounded-lg px-2 py-1">
        <span id="file-preview-name" class="text-xs text-sky-700 truncate flex-1"></span>
        <button onclick="clearFileAttach()" class="text-gray-400 hover:text-gray-600 font-bold text-sm">✕</button>
      </div>
    </div>

    <form id="send-form" class="flex items-end gap-2" onsubmit="event.preventDefault(); sendOrEdit();">
      <?php echo csrf_field(); ?>
      <input type="hidden" id="editing-msg-id" value="">
      <input type="hidden" id="reply-to-id" value="">
      <textarea id="message-input" rows="1"
        class="flex-1 bg-gray-100 rounded-xl px-4 py-2.5 text-sm text-gray-900 resize-none focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition"
        placeholder="Nhập tin nhắn (nhấn Enter để gửi, Shift+Enter để xuống dòng)..." maxlength="5000"
        onkeydown="if(event.key==='Enter' && !event.shiftKey){ event.preventDefault(); sendOrEdit(); }"></textarea>

      <button type="button" id="btn-send" onclick="sendOrEdit()"
        class="bg-sky-500 hover:bg-sky-600 active:scale-95 text-white rounded-xl p-2.5 transition flex-shrink-0 flex items-center justify-center min-w-[42px] min-h-[42px]">
        <svg id="btn-send-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
        <svg id="btn-send-spinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </button>
    </form>
  </div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const CSRF       = document.querySelector('meta[name="csrf-token"]').content;
const SEND_URL   = '<?php echo e(route("chat.dm.send", $otherUser->user_id, false)); ?>';
const FILE_URL   = '<?php echo e(route("chat.dm.file", $otherUser->user_id, false)); ?>';
const POLL_URL   = '<?php echo e(route("chat.dm.new", $otherUser->user_id, false)); ?>';
const EDIT_BASE  = '/message/';
const DEL_BASE   = '/message/';
const AUTH_USER_ID      = '<?php echo e($authUser->user_id); ?>';
const OTHER_USER_NAME   = '<?php echo e(addslashes($nickname ?: $otherUser->getName())); ?>';
const MY_NAME           = '<?php echo e(addslashes($authUser->getName())); ?>';

const msgContainer = document.getElementById('messages-container');
const inputEl      = document.getElementById('message-input');
const btnSend      = document.getElementById('btn-send');
const btnIcon      = document.getElementById('btn-send-icon');
const btnSpinner   = document.getElementById('btn-send-spinner');
const emptyState   = document.getElementById('empty-state');

// Always scroll immediately to the bottom (latest message)
function scrollToBottomInstant() {
  if (!msgContainer) return;
  msgContainer.scrollTop = msgContainer.scrollHeight;
}
scrollToBottomInstant();
requestAnimationFrame(() => scrollToBottomInstant());
setTimeout(scrollToBottomInstant, 100);
setTimeout(scrollToBottomInstant, 300);
window.addEventListener('load', scrollToBottomInstant);

// Load pinned message from localStorage
<?php $convKeyForPin = 'pin_conv_' . ($conv->conv_id ?? ''); ?>
const PIN_STORE_KEY = '<?php echo e($convKeyForPin); ?>';
(function() {
  const saved = localStorage.getItem(PIN_STORE_KEY);
  if (saved) {
    try {
      const p = JSON.parse(saved);
      showPinnedBanner(p.msgId, p.content);
    } catch(e){}
  }
})();

// Keep track of latest message timestamp
<?php
  $lastTimestamp = 0;
  if (count($messages) > 0) {
    $lastMsg = end($messages);
    $lastTimestamp = $lastMsg->created_at;
  }
?>
let lastTimestamp = <?php echo e($lastTimestamp); ?>;

// ── Anti-Spam & Send / Edit Logic ──
let isSubmitting      = false;
let lastSentTimestamp = 0;
let attachedFile      = null;

function setSendingState(sending) {
  isSubmitting = sending;
  if (!btnSend) return;
  if (sending) {
    btnSend.disabled = true;
    btnSend.classList.add('opacity-70', 'cursor-not-allowed');
    btnIcon.classList.add('hidden');
    btnSpinner.classList.remove('hidden');
  } else {
    btnSend.disabled = false;
    btnSend.classList.remove('opacity-70', 'cursor-not-allowed');
    btnIcon.classList.remove('hidden');
    btnSpinner.classList.add('hidden');
  }
}

async function sendOrEdit() {
  const editId  = document.getElementById('editing-msg-id').value;
  const replyId = document.getElementById('reply-to-id').value;
  const content = inputEl.value.trim();

  // Cho phép gửi file đính kèm
  if (!content && !attachedFile) return;

  // Nếu đang gửi chỉnh sửa hoặc gửi file
  if (attachedFile && !editId) {
    setSendingState(true);
    try {
      await sendFileMessage(replyId);
    } finally {
      setSendingState(false);
    }
    return;
  }

  if (editId) {
    setSendingState(true);
    try {
      const res = await fetch(EDIT_BASE + editId, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ content }),
      });
      const data = await res.json();
      if (data.success) {
        const row = document.getElementById(`msg-row-${editId}`);
        if (row) {
          const span = row.querySelector('.msg-text-span');
          if (span) span.textContent = content;
          const tag = row.querySelector('.edited-tag');
          if (tag) tag.classList.remove('hidden');
        }
        cancelEdit();
      } else {
        alert(data.error || 'Có lỗi xảy ra khi sửa tin nhắn.');
      }
    } catch (e) {
      alert('Lỗi kết nối mạng khi sửa tin nhắn.');
    } finally {
      setSendingState(false);
    }
    return;
  }

  // Gửi tin nhắn mới: Xóa ngay ô nhập (Optimistic UI) để người dùng có thể gõ tin nhắn tiếp theo ngay lập tức không bị khựng
  inputEl.value = '';
  inputEl.focus();
  cancelReply();

  lastSentContent   = content;
  lastSentTimestamp = Date.now();

  try {
    const body = { content };
    if (replyId) body.reply_to = replyId;

    const res = await fetch(SEND_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
      body: JSON.stringify(body),
    });

    const data = await res.json();

    if (data.success) {
      if (data.message) {
        appendMessageRow(data.message, true);
        if (data.message.created_at > lastTimestamp) lastTimestamp = data.message.created_at;
      }
    } else {
      alert(data.error || 'Có lỗi xảy ra khi gửi tin nhắn.');
    }
  } catch (e) {
    console.error(e);
    alert('Lỗi kết nối mạng khi gửi tin nhắn.');
  }
}

async function sendFileMessage(replyId) {
  if (!attachedFile) return;
  try {
    const formData = new FormData();
    formData.append('file', attachedFile);
    formData.append('_token', CSRF);
    if (replyId) formData.append('reply_to', replyId);

    const res = await fetch(FILE_URL, {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: formData,
    });
    const data = await res.json();
    if (data.success) {
      cancelReply();
      clearFileAttach();
      if (data.message) {
        appendMessageRow(data.message, true);
        if (data.message.created_at > lastTimestamp) lastTimestamp = data.message.created_at;
      }
    } else {
      alert(data.error || 'Không thể gửi tệp đính kèm.');
    }
  } catch(e) {
    console.error('File send error:', e);
    alert('Lỗi kết nối khi gửi tệp.');
  } finally {
    setSendingState(false);
  }
}

// ── File Attachment ──
function handleFileAttach(input) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) {
    alert('Kích thước tệp vượt quá 5MB. Vui lòng chọn tệp nhỏ hơn 5MB.');
    input.value = '';
    return;
  }
  attachedFile = file;
  const preview = document.getElementById('file-preview');
  const name    = document.getElementById('file-preview-name');
  name.textContent = file.name + ' (' + formatBytes(file.size) + ')';
  preview.classList.remove('hidden');
}

function clearFileAttach() {
  attachedFile = null;
  document.getElementById('file-input').value = '';
  document.getElementById('file-preview').classList.add('hidden');
}

function formatBytes(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
  return (bytes/1048576).toFixed(1) + ' MB';
}

// ── Append message directly to DOM ──
function appendMessageRow(msg, isMine) {
  if (emptyState) emptyState.classList.add('hidden');
  if (document.getElementById(`msg-row-${msg.msg_id}`)) return;

  // Tin nhắn hệ thống (bình chọn, thông báo)
  if (msg.type === 'system' || msg.sender_id === 'system') {
    const sysDiv = document.createElement('div');
    sysDiv.className = 'flex justify-center my-2.5 msg-row';
    sysDiv.id = `msg-row-${msg.msg_id}`;
    sysDiv.setAttribute('data-msg-id', msg.msg_id);
    const timeStr = msg.formatted_time || '';
    sysDiv.innerHTML = `
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-50/90 border border-sky-100/80 text-sky-700 text-xs shadow-2xs max-w-lg text-center">
        <svg class="w-3.5 h-3.5 text-sky-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="font-medium leading-relaxed">${escapeHtml(msg.content)}</span>
        ${timeStr ? `<span class="text-[10px] text-sky-400 ml-1 flex-shrink-0">${escapeHtml(timeStr)}</span>` : ''}
      </div>`;
    msgContainer.appendChild(sysDiv);
    msgContainer.scrollTop = msgContainer.scrollHeight;
    return;
  }

  const div = document.createElement('div');
  div.className = `flex ${isMine ? 'justify-end' : 'justify-start'} items-center gap-2 group msg-row py-1`;
  div.id = `msg-row-${msg.msg_id}`;
  div.setAttribute('data-msg-id', msg.msg_id);

  const bubbleClass = isMine
    ? 'bg-sky-500 text-white rounded-br-xs'
    : 'bg-white text-gray-900 border border-gray-200 rounded-bl-xs';
  const timeStr = msg.formatted_time || 'Vừa xong';
  const senderDisplayName = isMine ? 'Bạn' : (msg.sender_name || OTHER_USER_NAME);

  // Avatar HTML
  const otherAvatarHtml = `<div class="flex-shrink-0 self-end mb-1"><div class="w-8 h-8 rounded-full bg-sky-500 flex items-center justify-center text-white font-bold text-xs">${escapeHtml(OTHER_USER_NAME.charAt(0).toUpperCase())}</div></div>`;
  const myAvatarHtml    = `<div class="flex-shrink-0 self-end mb-1"><div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold text-xs">${escapeHtml(MY_NAME.charAt(0).toUpperCase())}</div></div>`;

  // Actions panel HTML (beside the bubble in empty space, NEVER covers message)
  const actionsHtml = `
    <div class="actions-panel opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1 bg-white border border-gray-200 rounded-lg shadow-sm px-1.5 py-1 self-center flex-shrink-0">
      <button onclick="replyToMsg('${msg.msg_id}')" class="px-2 py-0.5 text-xs text-gray-600 hover:text-sky-600 hover:bg-sky-50 rounded font-medium transition">Trả lời</button>
      <button onclick="pinMessage('${msg.msg_id}')" class="px-2 py-0.5 text-xs text-gray-600 hover:text-amber-600 hover:bg-amber-50 rounded font-medium transition">Ghim</button>
      ${isMine ? `<button onclick="editMsg('${msg.msg_id}')" class="px-2 py-0.5 text-xs text-gray-600 hover:text-sky-600 hover:bg-sky-50 rounded font-medium transition">Sửa</button>` : ''}
      ${isMine ? `<button onclick="deleteMsg('${msg.msg_id}')" class="px-2 py-0.5 text-xs text-gray-600 hover:text-red-600 hover:bg-red-50 rounded font-medium transition">Xóa</button>` : ''}
    </div>`;

  // Reply preview HTML
  let replyHtml = '';
  if (msg.reply_to && msg.reply_content) {
    replyHtml = `
      <div class="reply-preview-block mb-1 border-l-2 ${isMine ? 'border-sky-400 bg-sky-50/70' : 'border-gray-400 bg-gray-100'} px-2 py-1 rounded cursor-pointer text-xs transition hover:opacity-90"
           onclick="document.getElementById('msg-row-${msg.reply_to}')?.scrollIntoView({behavior:'smooth',block:'center'})">
        <span class="font-semibold text-gray-800">Trả lời ${escapeHtml(msg.reply_sender || 'Người dùng')}:</span>
        <span class="text-gray-600 ml-1">${escapeHtml((msg.reply_content || '').substring(0, 70))}</span>
      </div>`;
  }

  // Bubble content
  let isPoll = (msg.type === 'poll') || (msg.content && msg.content.trim().startsWith('{"question":'));
  let bubbleContent = '';

  if (isPoll && msg.status !== 'deleted') {
    try {
      const pollData = JSON.parse(msg.content);
      let totalVotes = 0;
      (pollData.options || []).forEach((opt, idx) => {
        totalVotes += (pollData.votes && pollData.votes[idx]) ? pollData.votes[idx].length : 0;
      });

      let optionsHtml = '';
      (pollData.options || []).forEach((opt, idx) => {
        const votesCount = (pollData.votes && pollData.votes[idx]) ? pollData.votes[idx].length : 0;
        const votedThis = (pollData.votes && pollData.votes[idx] && pollData.votes[idx].includes(AUTH_USER_ID));
        const pct = totalVotes > 0 ? Math.round((votesCount / totalVotes) * 100) : 0;

        optionsHtml += `
          <button type="button" onclick="castVote('${msg.msg_id}', ${idx})"
            class="w-full text-left p-2 rounded-lg border transition relative overflow-hidden text-xs
              ${votedThis ? 'border-sky-300 bg-white/20 font-bold' : 'border-gray-200/40 hover:bg-black/5'}
              ${isMine ? 'text-white' : 'text-gray-800 bg-gray-50'}">
            <div class="absolute inset-y-0 left-0 ${votedThis ? 'bg-sky-400/40' : 'bg-gray-300/30'}" style="width: ${pct}%"></div>
            <div class="relative flex justify-between items-center z-10">
              <span class="truncate">${escapeHtml(opt)}</span>
              <span class="text-[11px] opacity-80 ml-2 flex-shrink-0">${pct}% (${votesCount})</span>
            </div>
          </button>`;
      });

      bubbleContent = `
        <div class="poll-container w-64 py-1" id="poll-${msg.msg_id}">
          <p class="font-semibold text-xs mb-2 ${isMine ? 'text-white' : 'text-gray-900'}">[Bình chọn] ${escapeHtml(pollData.question)}</p>
          <div class="space-y-1.5 poll-options-list">${optionsHtml}</div>
          <p class="text-[10px] opacity-70 mt-2 text-right poll-total-votes">${totalVotes} lượt bình chọn</p>
        </div>`;
    } catch(e) {
      bubbleContent = `<span class="msg-text-span">${escapeHtml(msg.content || '')}</span>`;
    }
  } else if (msg.type === 'file' && msg.file_data && msg.status !== 'deleted') {
    const fd = msg.file_data;
    const fName = fd.name || 'Tệp đính kèm';
    const fSize = fd.formatted_size || fd.size || '';
    let fUrl = fd.url || '#';
    if (fd.object_key) {
      fUrl = '/files/serve?key=' + encodeURIComponent(fd.object_key);
    } else if (fUrl && fUrl.includes('files/serve')) {
      try {
        const u = new URL(fUrl, window.location.origin);
        fUrl = u.pathname + u.search;
      } catch(e) {}
    }
    const fExt = (fName.split('.').pop() || 'file').toLowerCase();
    const isImg = (fd.mime && fd.mime.startsWith('image/')) || ['jpg','jpeg','png','gif','webp','svg','bmp'].includes(fExt);

    if (isImg) {
      bubbleContent = `
        <div class="space-y-1.5">
          <img src="${fUrl}" class="max-w-[260px] max-h-[300px] object-cover rounded-xl cursor-pointer hover:opacity-95 shadow-xs transition"
               onclick="previewFile('${fUrl}', '${escapeHtml(fName)}', '${fSize}', 'image')">
          <div class="flex items-center justify-between text-[11px] ${isMine ? 'text-white/80' : 'text-gray-500'} px-0.5">
            <span class="truncate max-w-[170px]">${escapeHtml(fName)}</span>
            <span>${fSize}</span>
          </div>
        </div>`;
    } else {
      let badgeColor = 'bg-gray-600';
      if (['doc','docx'].includes(fExt)) badgeColor = 'bg-blue-600';
      else if (['pdf'].includes(fExt)) badgeColor = 'bg-red-500';
      else if (['xls','xlsx'].includes(fExt)) badgeColor = 'bg-emerald-600';

      bubbleContent = `
        <div class="w-64 p-2.5 rounded-xl border ${isMine ? 'bg-white/10 border-white/20 text-white' : 'bg-gray-50 border-gray-200 text-gray-800'} shadow-xs">
          <div class="flex items-start gap-2.5">
            <div class="w-10 h-10 rounded-lg ${badgeColor} text-white flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-xs">
              ${escapeHtml(fExt.toUpperCase())}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold truncate leading-tight mb-0.5" title="${escapeHtml(fName)}">${escapeHtml(fName)}</p>
              <p class="text-[10px] ${isMine ? 'text-white/70' : 'text-gray-400'}">${fSize} • Tệp đính kèm</p>
            </div>
          </div>
          <div class="flex items-center gap-2 mt-2.5 pt-2 border-t ${isMine ? 'border-white/15' : 'border-gray-200'}">
            <button type="button" onclick="previewFile('${fUrl}', '${escapeHtml(fName)}', '${fSize}', '${fExt}')"
              class="flex-1 py-1 px-2 rounded-lg text-xs font-medium text-center transition flex items-center justify-center gap-1
                ${isMine ? 'bg-white/20 hover:bg-white/30 text-white' : 'bg-sky-50 hover:bg-sky-100 text-sky-700'}">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <span>Xem trước</span>
            </button>
            <a href="${fUrl}" target="_blank" download
              class="py-1 px-2.5 rounded-lg text-xs font-medium transition flex items-center justify-center gap-1
                ${isMine ? 'hover:bg-white/20 text-white' : 'hover:bg-gray-200 text-gray-700'}" title="Tải về">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              <span>Tải về</span>
            </a>
          </div>
        </div>`;
    }
  } else {
    bubbleContent = `<span class="msg-text-span">${escapeHtml(msg.status === 'deleted' ? 'Tin nhắn đã bị xóa' : msg.content)}</span>`;
  }

  div.innerHTML = `
    ${isMine ? actionsHtml : ''}
    ${!isMine ? otherAvatarHtml : ''}
    <div class="max-w-[70%]">
      <p class="text-xs font-semibold ${isMine ? 'text-sky-600 text-right mr-1' : 'text-gray-700 ml-1'} mb-1">${escapeHtml(senderDisplayName)}</p>
      ${replyHtml}
      <div class="px-3.5 py-2 text-sm shadow-sm rounded-2xl bubble-content ${bubbleClass} ${msg.status === 'deleted' ? 'opacity-60 italic' : ''}">
        ${bubbleContent}
        <span class="edited-tag text-[10px] opacity-70 ml-1 ${msg.status === 'edited' ? '' : 'hidden'}">(đã sửa)</span>
      </div>
      <p class="text-[10px] text-gray-400 mt-0.5 ${isMine ? 'text-right mr-1' : 'text-left ml-1'}">${timeStr}</p>
    </div>
    ${!isMine ? actionsHtml : ''}
    ${isMine ? myAvatarHtml : ''}
  `;

  msgContainer.appendChild(div);
  msgContainer.scrollTop = msgContainer.scrollHeight;
}

function escapeHtml(text) {
  if (!text) return '';
  const d = document.createElement('div');
  d.textContent = String(text);
  return d.innerHTML;
}

// ── Poll Voting ──
async function castVote(msgId, optionIndex) {
  try {
    const res = await fetch(`/message/${msgId}/vote`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ option_index: optionIndex }),
    });

    const data = await res.json();
    if (data.success && data.poll_data) {
      updatePollUI(msgId, data.poll_data);
    } else {
      alert(data.error || 'Không thể bình chọn.');
    }
  } catch(e) {
    alert('Lỗi kết nối khi gửi bình chọn.');
  }
}

function updatePollUI(msgId, pollData) {
  const pollEl = document.getElementById(`poll-${msgId}`);
  if (!pollEl) return;

  const row = pollEl.closest('.msg-row');
  const isMine = row ? row.classList.contains('justify-end') : false;

  let totalVotes = 0;
  (pollData.options || []).forEach((opt, idx) => {
    totalVotes += (pollData.votes && pollData.votes[idx]) ? pollData.votes[idx].length : 0;
  });

  const listEl = pollEl.querySelector('.poll-options-list');
  if (listEl) {
    listEl.innerHTML = (pollData.options || []).map((opt, idx) => {
      const votesCount = (pollData.votes && pollData.votes[idx]) ? pollData.votes[idx].length : 0;
      const votedThis = (pollData.votes && pollData.votes[idx] && pollData.votes[idx].includes(AUTH_USER_ID));
      const pct = totalVotes > 0 ? Math.round((votesCount / totalVotes) * 100) : 0;

      return `
        <button type="button" onclick="castVote('${msgId}', ${idx})"
          class="w-full text-left p-2 rounded-lg border transition relative overflow-hidden text-xs
            ${votedThis ? 'border-sky-300 bg-white/20 font-bold' : 'border-gray-200/40 hover:bg-black/5'}
            ${isMine ? 'text-white' : 'text-gray-800 bg-gray-50'}">
          <div class="absolute inset-y-0 left-0 ${votedThis ? 'bg-sky-400/40' : 'bg-gray-300/30'}" style="width: ${pct}%"></div>
          <div class="relative flex justify-between items-center z-10">
            <span class="truncate">${escapeHtml(opt)}</span>
            <span class="text-[11px] opacity-80 ml-2 flex-shrink-0">${pct}% (${votesCount})</span>
          </div>
        </button>`;
    }).join('');
  }

  const totalEl = pollEl.querySelector('.poll-total-votes');
  if (totalEl) totalEl.textContent = `${totalVotes} lượt bình chọn`;
}

// ── Reply to Message ──
let replyingToId = null;

function replyToMsg(msgId) {
  const row = document.getElementById(`msg-row-${msgId}`);
  if (!row) return;
  replyingToId = msgId;
  document.getElementById('reply-to-id').value = msgId;

  const span = row.querySelector('.msg-text-span');
  const pollQ = row.querySelector('.poll-container p');
  const isMsgMine = row.classList.contains('justify-end');

  let content = '';
  if (span) {
    content = span.textContent.trim();
  } else if (pollQ) {
    content = pollQ.textContent.trim();
  }
  const sender = isMsgMine ? 'Bạn' : OTHER_USER_NAME;

  document.getElementById('reply-sender-label').textContent = sender;
  document.getElementById('reply-content-label').textContent = content.substring(0, 80);
  document.getElementById('reply-bar').classList.remove('hidden');
  document.getElementById('edit-bar').classList.add('hidden');
  inputEl.focus();
}

function cancelReply() {
  replyingToId = null;
  document.getElementById('reply-to-id').value = '';
  document.getElementById('reply-bar').classList.add('hidden');
}

// ── Edit / Delete Message ──
function editMsg(msgId) {
  const row = document.getElementById(`msg-row-${msgId}`);
  if (!row) return;
  const textSpan = row.querySelector('.msg-text-span');
  if (!textSpan) return;

  document.getElementById('editing-msg-id').value = msgId;
  inputEl.value = textSpan.textContent.trim();
  document.getElementById('edit-bar').classList.remove('hidden');
  document.getElementById('reply-bar').classList.add('hidden');
  inputEl.focus();
}

function cancelEdit() {
  document.getElementById('editing-msg-id').value = '';
  inputEl.value = '';
  document.getElementById('edit-bar').classList.add('hidden');
}

async function deleteMsg(msgId) {
  if (!confirm('Bạn có chắc chắn muốn xóa tin nhắn này?')) return;
  try {
    const res = await fetch(DEL_BASE + msgId, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
    });
    const data = await res.json();
    if (data.success) {
      const row = document.getElementById(`msg-row-${msgId}`);
      if (row) {
        const bubble   = row.querySelector('.bubble-content');
        const textSpan = row.querySelector('.msg-text-span');
        const actions  = row.querySelector('.actions-panel');
        if (textSpan) textSpan.textContent = 'Tin nhắn đã bị xóa';
        if (bubble) bubble.classList.add('opacity-60', 'italic');
        if (actions) actions.remove();
      }
    } else {
      alert(data.error || 'Không thể xóa tin nhắn.');
    }
  } catch(e) {
    alert('Lỗi kết nối khi xóa tin nhắn.');
  }
}

// ── Search Messages ──
function toggleSearch() {
  const bar = document.getElementById('search-bar');
  const input = document.getElementById('search-input');
  const isHidden = bar.classList.contains('hidden');
  bar.classList.toggle('hidden');
  if (isHidden) {
    input.focus();
  } else {
    input.value = '';
    searchMessages('');
  }
}

function searchMessages(query) {
  const rows  = document.querySelectorAll('.msg-row');
  const count = document.getElementById('search-count');
  query = query.trim().toLowerCase();

  if (!query) {
    rows.forEach(r => r.style.opacity = '1');
    count.classList.add('hidden');
    return;
  }

  let found = 0;
  rows.forEach(row => {
    const span = row.querySelector('.msg-text-span');
    const text = span ? span.textContent.toLowerCase() : '';
    if (text.includes(query)) {
      row.style.opacity = '1';
      found++;
    } else {
      row.style.opacity = '0.25';
    }
  });

  count.textContent = `${found} kết quả`;
  count.classList.remove('hidden');
}

// ── Pin Message ──
function pinMessage(msgId) {
  const row    = document.getElementById(`msg-row-${msgId}`);
  const span   = row ? row.querySelector('.msg-text-span') : null;
  const content = span ? span.textContent.trim().substring(0, 80) : '';

  localStorage.setItem(PIN_STORE_KEY, JSON.stringify({ msgId, content }));
  showPinnedBanner(msgId, content);
}

function showPinnedBanner(msgId, content) {
  const banner = document.getElementById('pinned-banner');
  const el     = document.getElementById('pinned-content');
  el.textContent = content;
  banner.classList.remove('hidden');
  banner.dataset.msgId = msgId;
}

function scrollToPinned() {
  const msgId = document.getElementById('pinned-banner').dataset.msgId;
  if (msgId) {
    const row = document.getElementById(`msg-row-${msgId}`);
    if (row) row.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

function unpinMessage() {
  localStorage.removeItem(PIN_STORE_KEY);
  document.getElementById('pinned-banner').classList.add('hidden');
}

// ── Poll ──
function addPollOption() {
  const container = document.getElementById('poll-options-container');
  const count = container.querySelectorAll('.poll-option').length + 1;
  const input = document.createElement('input');
  input.type = 'text';
  input.placeholder = `Lựa chọn ${count}`;
  input.className = 'poll-option w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-sky-400';
  container.appendChild(input);
}

async function submitPoll() {
  const question = document.getElementById('poll-question').value.trim();
  const optEls   = document.querySelectorAll('.poll-option');
  const options  = Array.from(optEls).map(e => e.value.trim()).filter(Boolean);

  if (!question || options.length < 2) {
    alert('Vui lòng nhập câu hỏi và ít nhất 2 lựa chọn.');
    return;
  }

  const content = JSON.stringify({ question, options, votes: {} });

  try {
    const res = await fetch(SEND_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
      body: JSON.stringify({ content, type: 'poll' }),
    });
    const data = await res.json();
    if (data.success) {
      document.getElementById('poll-modal').classList.add('hidden');
      document.getElementById('poll-question').value = '';
      document.querySelectorAll('.poll-option').forEach((el, i) => { if(i >= 2) el.remove(); else el.value = ''; });
      if (data.message) {
        appendMessageRow(data.message, true);
        if (data.message.created_at > lastTimestamp) lastTimestamp = data.message.created_at;
      }
    } else {
      alert(data.error || 'Không thể tạo bình chọn.');
    }
  } catch(e) {
    alert('Lỗi kết nối khi tạo bình chọn.');
  }
}

// ── File Preview Modal Implementation ──
async function previewFile(url, fileName, fileSize, ext) {
  const modal = document.getElementById('file-preview-modal');
  const title = document.getElementById('preview-modal-title');
  const sizeEl = document.getElementById('preview-modal-size');
  const dlBtn = document.getElementById('preview-modal-download');
  const iconEl = document.getElementById('preview-modal-icon');
  const loading = document.getElementById('preview-modal-loading');
  const content = document.getElementById('preview-modal-content');

  if (!modal) return;

  title.textContent = fileName || 'Xem trước tệp';
  sizeEl.textContent = fileSize ? `${fileSize} • Tệp đính kèm` : 'Tệp đính kèm';
  dlBtn.href = url;
  dlBtn.setAttribute('download', fileName || 'file');

  const lowerExt = (ext || '').toLowerCase().replace('.', '');
  iconEl.textContent = (lowerExt || 'FILE').substring(0, 4).toUpperCase();

  modal.classList.remove('hidden');
  loading.classList.remove('hidden');
  content.classList.add('hidden');
  content.innerHTML = '';

  try {
    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'image'].includes(lowerExt)) {
      content.innerHTML = `<div class="flex items-center justify-center min-h-full p-4"><img src="${url}" class="max-w-full max-h-[70vh] rounded-xl shadow-md object-contain bg-white"></div>`;
      loading.classList.add('hidden');
      content.classList.remove('hidden');
    } else if (['mp4', 'webm', 'mov', 'm4v', 'video'].includes(lowerExt)) {
      content.innerHTML = `
        <div class="flex flex-col items-center justify-center p-2">
          <video controls autoplay playsinline class="max-w-full max-h-[70vh] rounded-xl shadow-lg bg-black">
            <source src="${url}" type="video/${lowerExt === 'mov' ? 'mp4' : lowerExt}">
            Trình duyệt của bạn không hỗ trợ phát video này trực tiếp.
          </video>
        </div>`;
      loading.classList.add('hidden');
      content.classList.remove('hidden');
    } else if (['mp3', 'wav', 'ogg', 'm4a', 'audio'].includes(lowerExt)) {
      content.innerHTML = `
        <div class="bg-white p-8 rounded-xl shadow-md border border-gray-200 text-center max-w-md mx-auto space-y-4">
          <div class="w-16 h-16 bg-sky-100 text-sky-600 rounded-full flex items-center justify-center mx-auto text-2xl">🎵</div>
          <h5 class="font-bold text-gray-900 text-sm truncate">${escapeHtml(fileName)}</h5>
          <audio controls autoplay class="w-full mt-2">
            <source src="${url}">
            Trình duyệt không hỗ trợ phát âm thanh.
          </audio>
        </div>`;
      loading.classList.add('hidden');
      content.classList.remove('hidden');
    } else if (lowerExt === 'pdf') {
      content.innerHTML = `<iframe src="${url}#toolbar=1" class="w-full h-[70vh] rounded-xl border border-gray-200 bg-white"></iframe>`;
      loading.classList.add('hidden');
      content.classList.remove('hidden');
    } else if (['docx', 'doc'].includes(lowerExt)) {
      if (!window.mammoth) {
        await new Promise((resolve, reject) => {
          const s = document.createElement('script');
          s.src = 'https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js';
          s.onload = resolve;
          s.onerror = reject;
          document.head.appendChild(s);
        }).catch(() => null);
      }

      if (window.mammoth) {
        try {
          const res = await fetch(url);
          const arrayBuffer = await res.arrayBuffer();
          const result = await window.mammoth.convertToHtml({ arrayBuffer: arrayBuffer });
          content.innerHTML = `
            <div class="bg-white p-8 rounded-xl shadow-md border border-gray-200 max-w-3xl mx-auto prose prose-sky text-gray-800 text-sm leading-relaxed max-h-[70vh] overflow-y-auto">
              ${result.value || '<p class="text-gray-400 italic">Tệp tài liệu không có nội dung văn bản hiển thị.</p>'}
            </div>
          `;
          loading.classList.add('hidden');
          content.classList.remove('hidden');
        } catch(errDoc) {
          throw new Error('Không thể phân tích nội dung DOCX');
        }
      } else {
        throw new Error('Thư viện xem trước DOCX chưa sẵn sàng');
      }
    } else if (['txt', 'log', 'json', 'js', 'html', 'css', 'csv', 'md'].includes(lowerExt)) {
      const res = await fetch(url);
      const text = await res.text();
      content.innerHTML = `
        <pre class="bg-white p-6 rounded-xl shadow-md border border-gray-200 text-xs font-mono text-gray-800 whitespace-pre-wrap break-words max-w-4xl mx-auto max-h-[70vh] overflow-auto"><code>${escapeHtml(text)}</code></pre>
      `;
      loading.classList.add('hidden');
      content.classList.remove('hidden');
    } else {
      content.innerHTML = `
        <div class="bg-white p-8 rounded-xl shadow-md border border-gray-200 text-center max-w-md mx-auto space-y-3">
          <div class="w-16 h-16 bg-sky-100 text-sky-600 rounded-2xl flex items-center justify-center font-bold text-lg mx-auto">
            ${escapeHtml((lowerExt || 'FILE').toUpperCase())}
          </div>
          <h5 class="font-bold text-gray-900 text-sm">${escapeHtml(fileName)}</h5>
          <p class="text-xs text-gray-500">Định dạng này không hỗ trợ hiển thị trực tiếp trong trình duyệt. Vui lòng tải về máy để xem.</p>
          <a href="${url}" download target="_blank" class="inline-flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
            Tải tệp về máy (${fileSize || 'Tải về'})
          </a>
        </div>
      `;
      loading.classList.add('hidden');
      content.classList.remove('hidden');
    }
  } catch(e) {
    console.error('Lỗi khi xem trước tệp:', e);
    content.innerHTML = `
      <div class="bg-white p-8 rounded-xl shadow-md border border-gray-200 text-center max-w-md mx-auto space-y-3">
        <p class="text-red-500 text-sm font-semibold">Không thể xem trước tệp này.</p>
        <p class="text-xs text-gray-400">Có thể tệp chứa định dạng đặc thù hoặc kết nối bị gián đoạn.</p>
        <a href="${url}" download target="_blank" class="inline-flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
          Tải tệp về máy
        </a>
      </div>
    `;
    loading.classList.add('hidden');
    content.classList.remove('hidden');
  }
}

function closeFilePreviewModal() {
  const modal = document.getElementById('file-preview-modal');
  if (modal) modal.classList.add('hidden');
}

// ── Real-time Polling ──
let pollingTimer = null;

async function pollNewMessages() {
  try {
    const res = await fetch(`${POLL_URL}?after=${lastTimestamp}`, { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    if (data.success && data.messages && data.messages.length > 0) {
      data.messages.forEach(msg => {
        if (msg.sender_id !== AUTH_USER_ID) appendMessageRow(msg, false);
        if (msg.created_at > lastTimestamp) lastTimestamp = msg.created_at;
      });
    }
  } catch(e) {}
}

pollingTimer = setInterval(pollNewMessages, 1800);
window.addEventListener('beforeunload', () => { if (pollingTimer) clearInterval(pollingTimer); });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\chat-app\resources\views/chat/show.blade.php ENDPATH**/ ?>