<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DatabaseBackupService
{
    private array $tables = [
        'mosque_settings',
        'users',
        'opening_balances',
        'transactions',
        'transaction_attachments',
        'construction_stages',
        'construction_documents',
        'construction_document_photos',
        'activity_logs',
    ];

    public function createBackup(string $type = 'manual', ?int $triggeredBy = null, ?string $label = null): BackupLog
    {
        $timestamp = now()->format('Ymd_His');
        $fileTag = $label ?: $type;
        $fileName = "backup_masjid_attaqwa_{$fileTag}_{$timestamp}.json.gz";
        $relativePath = "backups/{$fileName}";
        $actorId = $triggeredBy ?? User::where('role', 'super_admin')->value('id') ?? User::value('id');

        try {
            $records = [];
            $rowCounts = [];

            foreach ($this->tables as $table) {
                if (Schema::hasTable($table)) {
                    $rows = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
                    $records[$table] = $rows;
                    $rowCounts[$table] = count($rows);
                }
            }

            $encodedData = json_encode($records, JSON_UNESCAPED_UNICODE);
            if ($encodedData === false) {
                throw new RuntimeException('Gagal mengodekan data database ke format JSON.');
            }

            $checksum = hash('sha256', $encodedData);

            $envelope = [
                'app' => 'Sistem Informasi Pengelolaan Infaq Pembangunan Masjid At-Taqwa',
                'version' => '1.0',
                'created_at' => now()->toIso8601String(),
                'driver' => DB::getDriverName(),
                'checksum_sha256' => $checksum,
                'row_counts' => $rowCounts,
                'tables' => $records,
            ];

            $payloadJson = json_encode($envelope, JSON_UNESCAPED_UNICODE);
            $compressed = gzencode($payloadJson, 6);
            if ($compressed === false) {
                throw new RuntimeException('Gagal mengompresi file backup.');
            }

            Storage::disk('local')->put($relativePath, $compressed);
            $fileSize = Storage::disk('local')->size($relativePath);

            $log = $this->recordBackupLog([
                'file_name' => $fileName,
                'file_path' => $relativePath,
                'file_size' => $fileSize,
                'type' => $type,
                'status' => 'success',
                'triggered_by' => $actorId,
                'verified_at' => now(),
                'checksum' => $checksum,
            ]);

            if (Auth::check()) {
                AuditLogger::log(
                    'backup.created',
                    "Membuat backup database ({$fileTag}): {$fileName}",
                    $log,
                    null,
                    [
                        'file_name' => $fileName,
                        'type' => $type,
                        'file_size' => $fileSize,
                        'row_counts' => $rowCounts,
                    ]
                );
            }

            return $log;
        } catch (\Throwable $e) {
            if ($actorId) {
                $this->recordBackupLog([
                    'file_name' => $fileName,
                    'file_path' => $relativePath,
                    'file_size' => 0,
                    'type' => $type,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'triggered_by' => $actorId,
                ]);
            }

            throw $e;
        }
    }

    public function verifyBackupFile(string $relativePath): array
    {
        if (! Storage::disk('local')->exists($relativePath)) {
            throw new RuntimeException('File backup tidak ditemukan di penyimpanan server.');
        }

        $rawContent = Storage::disk('local')->get($relativePath);
        return $this->parseAndValidateRawContent($rawContent);
    }

    public function parseAndValidateRawContent(string $rawContent): array
    {
        $jsonString = str_starts_with($rawContent, "\x1f\x8b")
            ? @gzdecode($rawContent)
            : $rawContent;

        if ($jsonString === false || trim($jsonString) === '') {
            throw new RuntimeException('File backup rusak atau gagal didekompresi.');
        }

        $decoded = json_decode($jsonString, true);
        if (! is_array($decoded) || ! isset($decoded['tables'], $decoded['checksum_sha256']) || ! is_array($decoded['tables'])) {
            throw new RuntimeException('Format struktur file backup tidak valid.');
        }

        $recalculatedHash = hash('sha256', json_encode($decoded['tables'], JSON_UNESCAPED_UNICODE));
        if (! hash_equals((string) $decoded['checksum_sha256'], $recalculatedHash)) {
            throw new RuntimeException('Verifikasi integritas gagal: checksum file backup tidak cocok (file korup atau telah dimodifikasi).');
        }

        return $decoded;
    }

    public function verifyLogEntry(BackupLog $backupLog): BackupLog
    {
        try {
            $this->verifyBackupFile($backupLog->file_path);

            $updateData = ['status' => 'success'];
            if (Schema::hasColumn('backup_logs', 'verified_at')) {
                $updateData['verified_at'] = now();
            }
            if (Schema::hasColumn('backup_logs', 'error_message')) {
                $updateData['error_message'] = null;
            }

            $backupLog->update($updateData);

            AuditLogger::log(
                'backup.verified',
                "Memverifikasi integritas file backup: {$backupLog->file_name}",
                $backupLog
            );

            return $backupLog->fresh();
        } catch (\Throwable $e) {
            $updateData = ['status' => 'failed'];
            if (Schema::hasColumn('backup_logs', 'error_message')) {
                $updateData['error_message'] = $e->getMessage();
            }
            $backupLog->update($updateData);

            throw $e;
        }
    }

    public function restoreFromRawContent(string $rawContent, string $sourceLabel, ?int $triggeredBy = null): void
    {
        $payload = $this->parseAndValidateRawContent($rawContent);
        $tablesData = $payload['tables'];

        // Buat safety backup otomatis sebelum menimpa data saat ini
        $this->createBackup('manual', $triggeredBy, 'pre_restore');

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            DB::transaction(function () use ($tablesData) {
                foreach (array_reverse($this->tables) as $table) {
                    if (Schema::hasTable($table) && array_key_exists($table, $tablesData)) {
                        DB::table($table)->delete();
                    }
                }

                foreach ($this->tables as $table) {
                    if (Schema::hasTable($table) && ! empty($tablesData[$table])) {
                        foreach (array_chunk($tablesData[$table], 100) as $chunk) {
                            DB::table($table)->insert($chunk);
                        }
                    }
                }
            });
        } finally {
            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            } elseif ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        if (Auth::check()) {
            AuditLogger::log(
                'backup.restored',
                "Melakukan restore database dari sumber: {$sourceLabel}",
                null,
                null,
                [
                    'source' => $sourceLabel,
                    'backup_created_at' => $payload['created_at'] ?? null,
                    'row_counts' => $payload['row_counts'] ?? [],
                ]
            );
        }
    }

    public function getScheduledType(): string
    {
        $driver = DB::getDriverName();
        $definition = '';

        if ($driver === 'sqlite') {
            $sql = (string) DB::table('sqlite_master')
                ->where('type', 'table')
                ->where('name', 'backup_logs')
                ->value('sql');

            if (preg_match('/check\s*\(\s*["`]?type["`]?\s+in\s*\(([^)]+)\)/i', $sql, $matches)) {
                $definition = $matches[1];
            }
        } elseif ($driver === 'mysql') {
            $column = DB::selectOne("SHOW COLUMNS FROM backup_logs WHERE Field = 'type'");
            $definition = (string) ($column->Type ?? '');
        }

        if ($definition !== '' && preg_match_all("/'([^']+)'/", $definition, $valMatches)) {
            foreach ($valMatches[1] as $allowedType) {
                if ($allowedType !== 'manual') {
                    return $allowedType;
                }
            }
        }

        return 'scheduled';
    }

    public function pruneOldScheduledBackups(int $keepLatest = 30): int
    {
        $oldLogs = BackupLog::where('type', $this->getScheduledType())
            ->orderByDesc('id')
            ->skip($keepLatest)
            ->take(100)
            ->get();

        $deletedCount = 0;
        foreach ($oldLogs as $log) {
            if ($log->file_path && Storage::disk('local')->exists($log->file_path)) {
                Storage::disk('local')->delete($log->file_path);
            }
            $log->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    private function recordBackupLog(array $attributes): BackupLog
    {
        $columns = Schema::getColumnListing('backup_logs');
        $filtered = [];

        foreach ($attributes as $key => $value) {
            if (in_array($key, $columns, true)) {
                $filtered[$key] = $value;
            }
        }

        if (in_array('created_at', $columns, true) && ! isset($filtered['created_at'])) {
            $filtered['created_at'] = now();
        }

        return BackupLog::create($filtered);
    }
}