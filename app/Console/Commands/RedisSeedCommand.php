<?php

namespace App\Console\Commands;

use App\Repositories\UserRepository;
use App\Repositories\GroupRepository;
use App\Repositories\MessageRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

/**
 * RedisSeedCommand — tạo dữ liệu mẫu để test
 * Chạy: php artisan redis:seed
 */
class RedisSeedCommand extends Command
{
    protected $signature   = 'redis:seed {--fresh : Xóa toàn bộ dữ liệu trước khi seed}';
    protected $description = 'Tạo dữ liệu mẫu (users, friends, group, messages) vào Redis';

    public function __construct(
        private UserRepository    $users,
        private GroupRepository   $groups,
        private MessageRepository $messages,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->warn('⚠️  Đang xóa toàn bộ dữ liệu Redis...');
            Redis::flushDb();
            $this->info('✅ Đã xóa xong.');
        }

        $this->info('🌱 Bắt đầu seed dữ liệu...');

        /* ─── Users ─── */
        $admin = $this->users->create([
            'username'      => 'admin',
            'display_name'  => 'Quản Trị Viên',
            'password_hash' => bcrypt('admin123'),
            'email'         => 'admin@chatapp.local',
            'role'          => 'admin',
            'bio'           => 'Tài khoản quản trị hệ thống',
        ]);
        $this->line("  👤 Admin: username=admin / password=admin123");

        $alice = $this->users->create([
            'username'      => 'alice',
            'display_name'  => 'Alice Nguyễn',
            'password_hash' => bcrypt('password'),
            'email'         => 'alice@chatapp.local',
            'bio'           => 'Xin chào! Tôi là Alice.',
        ]);
        $this->line("  👤 Alice: username=alice / password=password");

        $bob = $this->users->create([
            'username'      => 'bob',
            'display_name'  => 'Bob Trần',
            'password_hash' => bcrypt('password'),
            'email'         => 'bob@chatapp.local',
            'bio'           => 'Chào mọi người!',
        ]);
        $this->line("  👤 Bob: username=bob / password=password");

        $charlie = $this->users->create([
            'username'      => 'charlie',
            'display_name'  => 'Charlie Lê',
            'password_hash' => bcrypt('password'),
            'email'         => 'charlie@chatapp.local',
            'bio'           => '',
        ]);
        $this->line("  👤 Charlie: username=charlie / password=password");

        /* ─── Friends (Alice ↔ Bob, Alice ↔ Charlie) ─── */
        Redis::sAdd("user:{$alice->user_id}:friends", $bob->user_id);
        Redis::sAdd("user:{$bob->user_id}:friends",   $alice->user_id);
        Redis::sAdd("user:{$alice->user_id}:friends", $charlie->user_id);
        Redis::sAdd("user:{$charlie->user_id}:friends", $alice->user_id);
        $this->info('  🤝 Đã tạo quan hệ bạn bè: Alice↔Bob, Alice↔Charlie');

        /* ─── Nicknames ─── */
        Redis::hSet("user:{$alice->user_id}:nicknames", $bob->user_id, 'Thỏ Trắng');
        $this->info('  🏷️  Alice đặt nickname Bob = "Thỏ Trắng"');

        /* ─── DM Conversation (Alice → Bob) ─── */
        $conv = $this->messages->findOrCreateConversation($alice->user_id, $bob->user_id);
        $msg1 = $this->messages->createDirectMessage([
            'conv_id'   => $conv->conv_id,
            'sender_id' => $alice->user_id,
            'type'      => 'text',
            'content'   => 'Chào Bob! Lâu không gặp nhỉ 😊',
            'reply_to'  => '',
        ]);
        sleep(0); // Đảm bảo thứ tự
        $msg2 = $this->messages->createDirectMessage([
            'conv_id'   => $conv->conv_id,
            'sender_id' => $bob->user_id,
            'type'      => 'text',
            'content'   => 'Chào Alice! Ừ, lâu lắm rồi. Dạo này bạn thế nào?',
            'reply_to'  => '',
        ]);
        $this->info("  💬 Đã tạo DM giữa Alice và Bob ({$conv->conv_id})");

        /* ─── Group chat ─── */
        $group = $this->groups->create($alice->user_id, [
            'name'        => 'Team NoSQL 🚀',
            'description' => 'Nhóm học NoSQL với Redis',
            'max_members' => 50,
        ]);
        $this->groups->addMember($group->group_id, $bob->user_id,     'admin');
        $this->groups->addMember($group->group_id, $charlie->user_id, 'member');
        $this->groups->addMember($group->group_id, $admin->user_id,   'admin');
        $this->groups->setNickname($group->group_id, $alice->user_id, 'Nhóm trưởng');

        // Gửi tin nhắn trong nhóm
        $this->groups->createMessage([
            'conv_id'   => $group->group_id,
            'sender_id' => $alice->user_id,
            'type'      => 'text',
            'content'   => 'Chào cả nhóm! 👋 Dự án NoSQL của chúng ta bắt đầu thôi nào!',
        ]);
        $this->groups->createMessage([
            'conv_id'   => $group->group_id,
            'sender_id' => $bob->user_id,
            'type'      => 'text',
            'content'   => 'Sẵn sàng! Redis thật tuyệt vời 🔥',
        ]);
        $this->info("  👥 Đã tạo group \"Team NoSQL 🚀\" ({$group->group_id})");

        /* ─── Tóm tắt ─── */
        $this->newLine();
        $this->info('✅ Seed hoàn tất!');
        $this->table(
            ['Tài khoản', 'Mật khẩu', 'Role'],
            [
                ['admin',   'admin123', 'admin'],
                ['alice',   'password', 'user'],
                ['bob',     'password', 'user'],
                ['charlie', 'password', 'user'],
            ]
        );
        $this->line("🌐 Truy cập: http://chat-app.test/login");

        return self::SUCCESS;
    }
}
