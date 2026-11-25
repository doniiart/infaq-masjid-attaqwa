<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BackupLog;
use App\Models\OpeningBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseBackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_access_and_create_database_backups(): void
    {
        Storage::fake('local');
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get('/backups')->assertStatus(403);
        $this->actingAs($admin)->post('/backups')->assertStatus(403);

        $this->actingAs($superAdmin)->post('/backups')->assertRedirect('/backups');

        $log = BackupLog::first();
        $this->assertNotNull($log);
        $this->assertEquals('success', $log->status);
        $this->assertNotNull($log->verified_at);
        Storage::disk('local')->assertExists($log->file_path);
        $this->assertTrue(ActivityLog::where('action', 'backup.created')->exists());
    }

    public function test_scheduled_backup_command_runs_and_creates_verified_backup(): void
    {
        Storage::fake('local');
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $exitCode = Artisan::call('backup:run-scheduled');
        $this->assertEquals(0, $exitCode, Artisan::output());

        $log = BackupLog::where('type', '!=', 'manual')->first();
        $this->assertNotNull($log);
        $this->assertEquals('success', $log->status);
        Storage::disk('local')->assertExists($log->file_path);
    }

    public function test_restore_restores_previous_state_and_rejects_corrupted_files(): void
    {
        Storage::fake('local');
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        OpeningBalance::create([
            'amount' => 10000000,
            'recorded_date' => '2026-11-01',
            'created_by' => $superAdmin->id,
        ]);

        // Buat titik backup
        $this->actingAs($superAdmin)->post('/backups')->assertRedirect('/backups');
        $backupLog = BackupLog::first();

        // Tambahkan transaksi baru setelah titik backup
        Transaction::create([
            'transaction_code' => 'INQ-AFTER-BACKUP',
            'type' => 'income',
            'transaction_date' => '2026-11-26',
            'amount' => 5000000,
            'source_or_purpose' => 'Tunai',
            'status' => 'verified',
            'created_by' => $superAdmin->id,
        ]);
        $this->assertEquals(1, Transaction::count());

        // Restore dari titik backup harus menghapus transaksi setelah backup & membuat pre_restore backup
        $this->actingAs($superAdmin)->post("/backups/{$backupLog->id}/restore", [
            'confirmation' => 'RESTORE',
        ])->assertRedirect('/backups');

        $this->assertEquals(0, Transaction::count());
        $this->assertEquals(1, OpeningBalance::count());
        $this->assertTrue(BackupLog::where('file_name', 'like', '%pre_restore%')->exists());

        // File korup / tidak valid wajib ditolak saat restore upload
        $corruptFile = UploadedFile::fake()->createWithContent('corrupt.json', '{"invalid": true}');
        $this->actingAs($superAdmin)->post('/backups/restore-upload', [
            'backup_file' => $corruptFile,
            'confirmation' => 'RESTORE',
        ])->assertSessionHasErrors('backup_file');
    }
}