<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * AdminRepository — nhật ký, backup, thống kê hệ thống
 *
 * Key patterns:
 *   admin:log              — ZSet  — nhật ký hành động (score = timestamp)
 *   admin:backup:{id}      — Hash  — metadata backup
 *   admin:backups          — ZSet  — danh sách backup
 */
class AdminRepository
{
    /* ─── Log ─── */

    public function log(string $adminId, string $action, string $target, string $detail = ''): void
    {
        $now     = (int)(microtime(true) * 1000);
        $payload = json_encode([
            'admin_id' => $adminId,
            'action'   => $action,
            'target'   => $target,
            'detail'   => $detail,
            'at'       => $now,
        ]);
        Redis::zAdd('admin:log', $now, $payload);
        // Chỉ giữ 1000 log gần nhất
        Redis::zRemRangeByRank('admin:log', 0, -1001);
    }

    /** @return array[] */
    public function getLogs(int $limit = 50): array
    {
        $raw  = Redis::zRevRange('admin:log', 0, $limit - 1, ['WITHSCORES' => true]);
        $logs = [];
        if (!$raw) return [];

        // Predis trả về [member => score, ...]
        foreach ($raw as $payload => $score) {
            $data = json_decode($payload, true);
            if ($data) {
                $data['timestamp'] = (int)$score;
                $logs[] = $data;
            }
        }
        return $logs;
    }

    /* ─── Backup ─── */

    /**
     * Tạo bản backup thật — lưu file JSON toàn bộ dữ liệu Redis vào storage/app/backups/
     */
    public function createBackup(string $adminId, string $note = '', string $type = 'full'): array
    {
        $backupId = Str::uuid()->toString();
        $now      = (int)(microtime(true) * 1000);
        $dateStr  = date('Y-m-d_His');
        $dir      = storage_path('app/backups');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fileName = "backup_{$dateStr}_{$backupId}.json";
        $filePath = $dir . DIRECTORY_SEPARATOR . $fileName;

        try {
            // Trigger Redis BGSAVE (cho Redis nội bộ nếu có)
            try {
                Redis::bgSave();
            } catch (\Exception $e) {
                // Bỏ qua nếu Redis đang bgsave
            }

            // Export toàn bộ dữ liệu Redis ra file
            $allKeys = Redis::keys('*');
            $backupData = [
                'meta' => [
                    'backup_id'  => $backupId,
                    'created_at' => $now,
                    'created_by' => $adminId,
                    'note'       => $note,
                    'type'       => $type,
                    'total_keys' => count($allKeys),
                ],
                'data' => [],
            ];

            foreach ($allKeys as $rawKey) {
                $key = $rawKey;
                $prefix = config('database.redis.options.prefix', '');
                if ($prefix && str_starts_with($key, $prefix)) {
                    $key = substr($key, strlen($prefix));
                }

                // Không snapshot metadata của chính bản backup này
                if ($key === "admin:backup:{$backupId}") continue;

                $typeStr = (string) Redis::type($key);
                $val = null;

                // Phpredis trả về int (1: string, 2: set, 3: list, 4: zset, 5: hash), Predis trả về string
                if ($typeStr === 'string' || $typeStr === '1') {
                    $val = ['type' => 'string', 'value' => Redis::get($key)];
                } elseif ($typeStr === 'hash' || $typeStr === '5') {
                    $val = ['type' => 'hash', 'value' => Redis::hGetAll($key)];
                } elseif ($typeStr === 'set' || $typeStr === '2') {
                    $val = ['type' => 'set', 'value' => Redis::sMembers($key)];
                } elseif ($typeStr === 'zset' || $typeStr === '4') {
                    $val = ['type' => 'zset', 'value' => Redis::zRange($key, 0, -1, ['WITHSCORES' => true])];
                } elseif ($typeStr === 'list' || $typeStr === '3') {
                    $val = ['type' => 'list', 'value' => Redis::lRange($key, 0, -1)];
                }

                if ($val !== null) {
                    $backupData['data'][$key] = $val;
                }
            }

            file_put_contents($filePath, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $fileSize = file_exists($filePath) ? filesize($filePath) : 0;

            // Thử copy dump.rdb nếu tìm thấy
            try {
                $redisDir = Redis::config('GET', 'dir')['dir'] ?? '';
                $rdbName  = Redis::config('GET', 'dbfilename')['dbfilename'] ?? 'dump.rdb';
                $rdbPath  = $redisDir ? rtrim($redisDir, '/\\') . DIRECTORY_SEPARATOR . $rdbName : '';
                if ($rdbPath && file_exists($rdbPath)) {
                    @copy($rdbPath, $dir . DIRECTORY_SEPARATOR . "dump_{$dateStr}_{$backupId}.rdb");
                }
            } catch (\Exception $e) {
                // Không bắt buộc
            }

            $meta = [
                'backup_id'  => $backupId,
                'created_by' => $adminId,
                'type'       => $type,
                'status'     => 'done',
                'note'       => $note,
                'created_at' => $now,
                'file_name'  => $fileName,
                'file_path'  => $filePath,
                'size_bytes' => $fileSize,
            ];

            Redis::hMSet("admin:backup:{$backupId}", $meta);
            Redis::zAdd('admin:backups', $now, $backupId);

            return $meta;
        } catch (\Exception $e) {
            $meta = [
                'backup_id'  => $backupId,
                'created_by' => $adminId,
                'type'       => $type,
                'status'     => 'failed',
                'note'       => $note . ' (Error: ' . $e->getMessage() . ')',
                'created_at' => $now,
                'file_name'  => '',
                'file_path'  => '',
                'size_bytes' => 0,
            ];
            Redis::hMSet("admin:backup:{$backupId}", $meta);
            Redis::zAdd('admin:backups', $now, $backupId);
            return $meta;
        }
    }

    /** Khôi phục dữ liệu từ bản backup JSON */
    public function restoreBackup(string $backupId): bool
    {
        $backup = Redis::hGetAll("admin:backup:{$backupId}");
        $fileName = $backup['file_name'] ?? '';
        $filePath = $backup['file_path'] ?? '';

        if (!$filePath || !file_exists($filePath)) {
            $alt = storage_path('app/backups/' . $fileName);
            if ($fileName && file_exists($alt)) {
                $filePath = $alt;
            } else {
                throw new \RuntimeException("Không tìm thấy file backup trên máy chủ.");
            }
        }

        $json = file_get_contents($filePath);
        $payload = json_decode($json, true);
        if (!$payload || !isset($payload['data'])) {
            throw new \RuntimeException("File backup không đúng định dạng.");
        }

        foreach ($payload['data'] as $key => $item) {
            $type = $item['type'] ?? '';
            $val  = $item['value'] ?? null;
            if ($val === null) continue;

            Redis::del($key);

            if ($type === 'string') {
                Redis::set($key, $val);
            } elseif ($type === 'hash' && is_array($val) && !empty($val)) {
                Redis::hMSet($key, $val);
            } elseif ($type === 'set' && is_array($val) && !empty($val)) {
                foreach ($val as $m) {
                    Redis::sAdd($key, $m);
                }
            } elseif ($type === 'zset' && is_array($val) && !empty($val)) {
                foreach ($val as $m => $s) {
                    Redis::zAdd($key, is_numeric($s) ? (float)$s : 0, (string)$m);
                }
            } elseif ($type === 'list' && is_array($val) && !empty($val)) {
                foreach ($val as $elem) {
                    Redis::rPush($key, $elem);
                }
            }
        }

        return true;
    }

    /** Xóa bản backup */
    public function deleteBackup(string $backupId): bool
    {
        $backup = Redis::hGetAll("admin:backup:{$backupId}");
        if ($backup) {
            $filePath = $backup['file_path'] ?? '';
            if ($filePath && file_exists($filePath)) {
                @unlink($filePath);
            }
            Redis::del("admin:backup:{$backupId}");
            Redis::zRem('admin:backups', $backupId);
        }
        return true;
    }

    /** @return array[] */
    public function getBackups(): array
    {
        $ids    = Redis::zRevRange('admin:backups', 0, 19);
        $result = [];
        foreach ($ids as $id) {
            $data = Redis::hGetAll("admin:backup:{$id}");
            if ($data) $result[] = $data;
        }
        return $result;
    }

    /* ─── Stats ─── */

    public function getSystemStats(): array
    {
        $onlineCount = (int) Redis::sCard('user:online');
        $totalUsers  = (int) Redis::sCard('user:all');
        $dbSize      = (int) Redis::dbSize();

        // Lấy thông tin memory Redis
        $info   = Redis::info('memory') ?? [];
        $memUsed = $info['used_memory_human'] ?? 'N/A';

        return [
            'online_count' => $onlineCount,
            'total_users'  => $totalUsers,
            'db_size'      => $dbSize,
            'memory_used'  => $memUsed,
        ];
    }
}
