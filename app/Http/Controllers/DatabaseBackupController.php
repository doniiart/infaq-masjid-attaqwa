<?php

namespace App\Http\Controllers;

use App\Models\BackupLog;
use App\Services\AuditLogger;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupController extends Controller
{
    public function index(): Response
    {
        $backups = BackupLog::with('triggeredBy:id,name')
            ->orderByDesc('id')
            ->paginate(15)
            ->through(fn (BackupLog $log) => [
                'id' => $log->id,
                'file_name' => $log->file_name,
                'file_size' => (int) $log->file_size,
                'file_size_human' => $this->formatBytes((int) $log->file_size),
                'type' => $log->type,
                'status' => $log->status,
                'error_message' => $log->error_message,
                'triggered_by_name' => $log->type !== 'manual'
                    ? 'Jadwal Otomatis Sistem'
                    : ($log->triggeredBy?->name ?? 'Jadwal Otomatis Sistem'),
                'verified_at' => $log->verified_at?->format('d/m/Y H:i:s'),
                'created_at' => $log->created_at?->format('d/m/Y H:i:s'),
                'file_exists' => $log->file_path ? Storage::disk('local')->exists($log->file_path) : false,
            ]);

        return Inertia::render('Admin/Backups', [
            'backups' => $backups,
        ]);
    }

    public function store(Request $request, DatabaseBackupService $backupService): RedirectResponse
    {
        try {
            $log = $backupService->createBackup('manual', $request->user()->id);

            return redirect()->route('backups.index')
                ->with('success', "Backup manual berhasil dibuat dan terverifikasi ({$log->file_name}).");
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'backup' => 'Gagal membuat backup: ' . $e->getMessage(),
            ]);
        }
    }

    public function verify(BackupLog $backupLog, DatabaseBackupService $backupService): RedirectResponse
    {
        try {
            $backupService->verifyLogEntry($backupLog);

            return redirect()->route('backups.index')
                ->with('success', "Integritas file backup {$backupLog->file_name} valid dan terverifikasi.");
        } catch (\Throwable $e) {
            return redirect()->route('backups.index')
                ->with('error', 'Verifikasi gagal: ' . $e->getMessage());
        }
    }

    public function download(Request $request, BackupLog $backupLog): StreamedResponse
    {
        if (! $backupLog->file_path || ! Storage::disk('local')->exists($backupLog->file_path)) {
            abort(404, 'File backup tidak ditemukan.');
        }

        AuditLogger::log(
            'backup.downloaded',
            "Mengunduh file backup database: {$backupLog->file_name}",
            $backupLog
        );

        return Storage::disk('local')->download($backupLog->file_path, $backupLog->file_name);
    }

    public function restoreFromLog(Request $request, BackupLog $backupLog, DatabaseBackupService $backupService): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:RESTORE'],
        ], [
            'confirmation.required' => 'Ketik RESTORE untuk mengonfirmasi pemulihan database.',
            'confirmation.in' => 'Teks konfirmasi harus persis RESTORE (huruf kapital).',
        ]);

        if (! $backupLog->file_path || ! Storage::disk('local')->exists($backupLog->file_path)) {
            throw ValidationException::withMessages([
                'confirmation' => 'File backup tidak ditemukan di server.',
            ]);
        }

        try {
            $rawContent = Storage::disk('local')->get($backupLog->file_path);
            $backupService->restoreFromRawContent($rawContent, $backupLog->file_name, $request->user()->id);

            return redirect()->route('backups.index')
                ->with('success', "Database berhasil dipulihkan dari {$backupLog->file_name}. Backup pengaman (pre_restore) otomatis dibuat.");
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'confirmation' => 'Proses restore dibatalkan: ' . $e->getMessage(),
            ]);
        }
    }

    public function restoreFromUpload(Request $request, DatabaseBackupService $backupService): RedirectResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'max:51200'],
            'confirmation' => ['required', 'in:RESTORE'],
        ], [
            'backup_file.required' => 'Pilih file backup (.json atau .json.gz) terlebih dahulu.',
            'backup_file.max' => 'Ukuran file backup maksimal 50 MB.',
            'confirmation.required' => 'Ketik RESTORE untuk mengonfirmasi pemulihan database.',
            'confirmation.in' => 'Teks konfirmasi harus persis RESTORE (huruf kapital).',
        ]);

        try {
            $file = $request->file('backup_file');
            $rawContent = file_get_contents($file->getRealPath());
            $backupService->restoreFromRawContent($rawContent, 'Upload: ' . $file->getClientOriginalName(), $request->user()->id);

            return redirect()->route('backups.index')
                ->with('success', 'Database berhasil dipulihkan dari file unggahan. Backup pengaman otomatis telah disimpan.');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'backup_file' => 'Proses restore gagal: ' . $e->getMessage(),
            ]);
        }
    }

    public function destroy(BackupLog $backupLog): RedirectResponse
    {
        $fileName = $backupLog->file_name;

        if ($backupLog->file_path && Storage::disk('local')->exists($backupLog->file_path)) {
            Storage::disk('local')->delete($backupLog->file_path);
        }

        $backupLog->delete();

        AuditLogger::log(
            'backup.deleted',
            "Menghapus arsip file backup: {$fileName}"
        );

        return redirect()->route('backups.index')
            ->with('success', "Arsip backup {$fileName} berhasil dihapus.");
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}