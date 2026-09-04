<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\AdminRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class BackupController extends Controller
{
    public function __construct(private AdminRepository $admin) {}

    /** Trang quản lý backup */
    public function index(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $backups  = $this->admin->getBackups();
        $stats    = $this->admin->getSystemStats();
        return view('admin.backup', compact('authUser', 'backups', 'stats'));
    }

    /** POST — tạo backup mới */
    public function create(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['note' => 'nullable|string|max:200']);

        $meta = $this->admin->createBackup($authUser->user_id, $request->note ?? '');
        $this->admin->log($authUser->user_id, 'create_backup', $meta['backup_id'], $request->note ?? '');

        if ($meta['status'] === 'done') {
            $fileName = $meta['file_name'] ?? '';
            return back()->with('success', "Đã tạo backup thành công! File lưu tại: storage/app/backups/{$fileName}");
        }
        return back()->with('error', 'Backup thất bại. ' . ($meta['note'] ?? ''));
    }

    /** GET — tải về file backup */
    public function download(Request $request, string $backupId)
    {
        $backup   = Redis::hGetAll("admin:backup:{$backupId}");
        $filePath = $backup['file_path'] ?? '';
        $fileName = $backup['file_name'] ?? "backup_{$backupId}.json";

        if (!$filePath || !file_exists($filePath)) {
            $alt = storage_path('app/backups/' . $fileName);
            if (file_exists($alt)) {
                $filePath = $alt;
            } else {
                return back()->with('error', 'Không tìm thấy file backup trên máy chủ.');
            }
        }

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    /** POST — khôi phục dữ liệu từ bản backup */
    public function restore(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        $request->validate(['backup_id' => 'required|string']);

        try {
            $this->admin->restoreBackup($request->backup_id);
            $this->admin->log($authUser->user_id, 'restore_backup', $request->backup_id, 'Khôi phục dữ liệu từ bản backup');
            return back()->with('success', "Đã khôi phục toàn bộ dữ liệu thành công từ bản backup {$request->backup_id}!");
        } catch (\Exception $e) {
            return back()->with('error', 'Khôi phục thất bại: ' . $e->getMessage());
        }
    }

    /** DELETE — xóa bản backup */
    public function destroy(Request $request, string $backupId)
    {
        $authUser = $request->attributes->get('auth_user');
        $this->admin->deleteBackup($backupId);
        $this->admin->log($authUser->user_id, 'delete_backup', $backupId, 'Xóa bản backup');
        return back()->with('success', 'Đã xóa bản backup thành công.');
    }

    /** GET — thông tin Redis INFO */
    public function redisInfo(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        try {
            $info = Redis::info();
        } catch (\Exception $e) {
            $info = [];
        }
        return view('admin.redis_info', compact('authUser', 'info'));
    }
}
