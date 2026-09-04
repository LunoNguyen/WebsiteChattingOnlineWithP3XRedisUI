<?php $__env->startSection('title', $groupInfo['group']->name); ?>

<?php $__env->startSection('content'); ?>
<div class="flex h-full flex-col">

  
  <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 flex-shrink-0">
    <a href="<?php echo e(route('chat.index')); ?>" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition mr-1" title="Quay lại">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <div class="relative">
      <?php if($groupInfo['group']->avatar_url): ?>
        <img src="<?php echo e($groupInfo['group']->avatar_url); ?>" class="w-10 h-10 rounded-full object-cover">
      <?php else: ?>
        <div class="w-10 h-10 rounded-full bg-purple-500 flex items-center justify-center text-white font-bold">
          <?php echo e(strtoupper(substr($groupInfo['group']->name, 0, 1))); ?>

        </div>
      <?php endif; ?>
    </div>
    <div class="flex-1">
      <h3 class="font-semibold text-gray-900 text-sm flex items-center gap-2">
        <?php echo e($groupInfo['group']->name); ?>

        <span class="text-xs px-2 py-0.5 rounded-full font-normal
          <?php echo e($myRole === 'owner' ? 'bg-amber-100 text-amber-800' : ($myRole === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600')); ?>">
          <?php echo e($myRole === 'owner' ? 'Trưởng nhóm' : ($myRole === 'admin' ? 'Phó nhóm' : 'Thành viên')); ?>

        </span>
      </h3>
      <p class="text-xs text-gray-400">
        <?php echo e(count($groupInfo['members'])); ?> thành viên
        <?php if($groupInfo['group']->description): ?>
          • <span class="italic"><?php echo e(Str::limit($groupInfo['group']->description, 40)); ?></span>
        <?php endif; ?>
      </p>
    </div>

    
    <div class="flex items-center gap-2">
      <button onclick="document.getElementById('members-modal').classList.toggle('hidden')"
        class="p-2 text-gray-400 hover:text-purple-600 hover:bg-gray-100 rounded-lg transition" title="Danh sách thành viên">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
      </button>

      <button onclick="document.getElementById('nickname-modal').classList.toggle('hidden')"
        class="p-2 text-gray-400 hover:text-sky-500 hover:bg-gray-100 rounded-lg transition" title="Đặt biệt danh trong nhóm">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
      </button>
    </div>
  </div>

  
  <div id="members-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-5 w-96 shadow-xl max-h-[80vh] flex flex-col">
      <div class="flex items-center justify-between pb-3 border-b border-gray-100">
        <h4 class="font-semibold text-gray-900 text-sm">Thành viên nhóm (<?php echo e(count($groupInfo['members'])); ?>)</h4>
        <button onclick="document.getElementById('members-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>
      <div class="overflow-y-auto py-2 flex-1 space-y-2">
        <?php $__currentLoopData = $groupInfo['members']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="flex items-center justify-between py-1.5 px-2 rounded-lg hover:bg-gray-50">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center text-white text-xs font-bold">
                <?php echo e(strtoupper(substr($m['user']->getName(), 0, 1))); ?>

              </div>
              <div>
                <p class="text-xs font-medium text-gray-800">
                  <?php echo e($m['nickname'] ?: $m['user']->getName()); ?>

                  <?php if($m['nickname']): ?>
                    <span class="text-[10px] text-gray-400">(<?php echo e($m['user']->getName()); ?>)</span>
                  <?php endif; ?>
                </p>
                <span class="text-[10px] text-gray-400"><?php echo e('@' . $m['user']->username); ?></span>
              </div>
            </div>
            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium
              <?php echo e($m['role'] === 'owner' ? 'bg-amber-100 text-amber-800' : ($m['role'] === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600')); ?>">
              <?php echo e($m['role'] === 'owner' ? 'Trưởng nhóm' : ($m['role'] === 'admin' ? 'Phó nhóm' : 'Thành viên')); ?>

            </span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
  </div>

  
  <div id="nickname-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-5 w-80 shadow-xl">
      <h4 class="font-semibold text-gray-900 mb-3 text-sm">Biệt danh của bạn trong nhóm</h4>
      <form method="POST" action="<?php echo e(route('groups.nickname', $groupInfo['group']->group_id)); ?>">
        <?php echo csrf_field(); ?>
        <?php
          $myNickname = '';
          foreach($groupInfo['members'] as $m) {
            if ($m['user']->user_id === $authUser->user_id) {
              $myNickname = $m['nickname'];
              break;
            }
          }
        ?>
        <input type="text" name="nickname" value="<?php echo e($myNickname); ?>"
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

  
  <div id="messages-container" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-gray-50">
    <?php $__empty_1 = true; $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <?php
        $isMine = $msg->sender_id === $authUser->user_id;
        $sender = null;
        $senderNick = '';
        foreach ($groupInfo['members'] as $m) {
          if ($m['user']->user_id === $msg->sender_id) {
            $sender = $m['user'];
            $senderNick = $m['nickname'] ?: $m['user']->getName();
            break;
          }
        }
      ?>
      <div class="flex <?php echo e($isMine ? 'justify-end' : 'justify-start'); ?> group" data-msg-id="<?php echo e($msg->msg_id); ?>">
        <div class="max-w-[70%]">
          <?php if(!$isMine): ?>
            <p class="text-[11px] text-gray-500 mb-0.5 font-medium ml-1">
              <?php echo e($senderNick ?: 'Người dùng'); ?>

            </p>
          <?php endif; ?>

          
          <div class="relative px-4 py-2 text-sm shadow-sm
            <?php echo e($isMine
              ? 'bg-purple-600 text-white chat-bubble-mine'
              : 'bg-white text-gray-900 chat-bubble-other border border-gray-100'); ?>

            <?php echo e($msg->isDeleted() ? 'opacity-50' : ''); ?>">

            <?php if($msg->isDeleted()): ?>
              <em class="text-xs">Tin nhắn đã bị xóa</em>
            <?php else: ?>
              <?php echo e($msg->content); ?>

              <?php if($msg->isEdited()): ?>
                <span class="text-[10px] opacity-60 ml-1">(đã sửa)</span>
              <?php endif; ?>
            <?php endif; ?>

            
            <?php if(($isMine || in_array($myRole, ['owner', 'admin'])) && !$msg->isDeleted()): ?>
              <div class="absolute -top-7 right-0 hidden group-hover:flex items-center gap-1 bg-white border border-gray-100 rounded-lg shadow px-1 py-0.5">
                <?php if($isMine): ?>
                  <button onclick="editMsg('<?php echo e($msg->msg_id); ?>', <?php echo e(json_encode($msg->content)); ?>)"
                    class="p-1 text-gray-500 hover:text-purple-600 text-xs">✏️</button>
                <?php endif; ?>
                <button onclick="deleteMsg('<?php echo e($msg->msg_id); ?>')"
                  class="p-1 text-gray-500 hover:text-red-500 text-xs">🗑️</button>
              </div>
            <?php endif; ?>
          </div>
          <p class="text-[10px] text-gray-400 mt-0.5 <?php echo e($isMine ? 'text-right' : 'text-left'); ?>">
            <?php echo e(\Carbon\Carbon::createFromTimestampMs($msg->created_at)->format('H:i')); ?>

          </p>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div class="text-center text-gray-400 text-sm py-16">
        <p>Chưa có tin nhắn nào trong nhóm. Hãy gửi tin nhắn đầu tiên! 👋</p>
      </div>
    <?php endif; ?>
  </div>

  
  <div class="bg-white border-t border-gray-200 px-4 py-3 flex-shrink-0">
    <div id="edit-bar" class="hidden mb-2 bg-purple-50 border border-purple-200 rounded-lg px-3 py-1.5 flex items-center gap-2 text-xs text-purple-700">
      <span>✏️ Đang sửa tin nhắn</span>
      <button onclick="cancelEdit()" class="ml-auto text-gray-400 hover:text-gray-600">✕</button>
    </div>

    <form id="send-form" class="flex items-end gap-2">
      <?php echo csrf_field(); ?>
      <input type="hidden" id="editing-msg-id" value="">
      <textarea id="message-input" rows="1"
        class="flex-1 bg-gray-100 rounded-xl px-4 py-2.5 text-sm text-gray-900 resize-none focus:outline-none focus:ring-2 focus:ring-purple-500 focus:bg-white transition"
        placeholder="Nhắn tin với nhóm..." maxlength="5000"
        onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendOrEdit();}"></textarea>
      <button type="button" onclick="sendOrEdit()"
        class="bg-purple-600 hover:bg-purple-700 text-white rounded-xl p-2.5 transition flex-shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
      </button>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const SEND_URL  = '<?php echo e(route("chat.group.send", $groupInfo["group"]->group_id)); ?>';
const EDIT_BASE = '/message/';
const DEL_BASE  = '/message/';

// Auto-scroll to bottom
const msgContainer = document.getElementById('messages-container');
msgContainer.scrollTop = msgContainer.scrollHeight;

async function sendOrEdit() {
  const input   = document.getElementById('message-input');
  const editId  = document.getElementById('editing-msg-id').value;
  const content = input.value.trim();
  if (!content) return;

  try {
    let url, method = 'POST';
    if (editId) { url = EDIT_BASE + editId; method = 'PUT'; }
    else          { url = SEND_URL; }

    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ content }),
    });
    const data = await res.json();
    if (data.success) { location.reload(); }
    else { alert(data.error || 'Lỗi gửi tin nhắn'); }
  } catch(e) { console.error(e); }
}

function editMsg(msgId, currentContent) {
  document.getElementById('editing-msg-id').value = msgId;
  document.getElementById('message-input').value = currentContent;
  document.getElementById('edit-bar').classList.remove('hidden');
  document.getElementById('message-input').focus();
}

function cancelEdit() {
  document.getElementById('editing-msg-id').value = '';
  document.getElementById('message-input').value = '';
  document.getElementById('edit-bar').classList.add('hidden');
}

async function deleteMsg(msgId) {
  if (!confirm('Xóa tin nhắn này?')) return;
  const res = await fetch(DEL_BASE + msgId, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
  });
  const data = await res.json();
  if (data.success) location.reload();
  else alert(data.error);
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\chat-app\resources\views/chat/group.blade.php ENDPATH**/ ?>