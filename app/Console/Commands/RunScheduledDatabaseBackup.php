<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class RunScheduledDatabaseBackup extends Command
{
    protected $signature = 'backup:run-scheduled {--keep=30 : Jumlah maksimal backup otomatis yang disimpan}';

    protected $description = 'Menjalankan pencadangan (backup) database otomatis terjadwal beserta verifikasi integritas';

    public function handle(DatabaseBackupService $backupService): int
    {
        $this->info('Memulai proses backup otomatis database Masjid At-Taqwa...');

        try {
            $scheduledType = $backupService->getScheduledType();
            $log = $backupService->createBackup($scheduledType, null, 'scheduled');
            $pruned = $backupService->pruneOldScheduledBackups((int) $this->option('keep'));

            $this->info("Backup berhasil dibuat dan diverifikasi: {$log->file_name} ({$log->file_size} bytes).");
            if ($pruned > 0) {
                $this->info("Membersihkan {$pruned} file backup terjadwal lama.");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Backup otomatis gagal: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}