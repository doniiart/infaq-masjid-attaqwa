<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ExpenseTransactionController;
use App\Http\Controllers\IncomeTransactionController;
use App\Http\Controllers\OpeningBalanceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicDashboardController;
use App\Http\Controllers\TransactionAttachmentController;
use App\Http\Controllers\TransactionHistoryController;
use App\Services\FinancialSummaryService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\ConstructionProgressController;
use App\Http\Controllers\MosqueSettingController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\DatabaseBackupController;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

Route::get('/', [PublicDashboardController::class, 'index'])->name('home');
Route::get('/attachments/{attachment}', [TransactionAttachmentController::class, 'show'])->name('attachments.show');
Route::get('/reports/pdf', [FinancialReportController::class, 'downloadPdf'])->name('reports.pdf');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', function (FinancialSummaryService $financialService) {
        $dateColumn = Schema::hasColumn('transactions', 'transaction_date')
            ? 'transaction_date'
            : (Schema::hasColumn('transactions', 'date') ? 'date' : 'created_at');

        $monthlyIncomeChart = collect(range(5, 0))->map(function (int $monthsAgo) use ($dateColumn) {
            $monthDate = Carbon::now()->subMonths($monthsAgo);
            $start = $monthDate->copy()->startOfMonth()->toDateString();
            $end = $monthDate->copy()->endOfMonth()->toDateString();

            $query = Transaction::where('type', 'income')
                ->where('status', 'verified')
                ->whereDate($dateColumn, '>=', $start)
                ->whereDate($dateColumn, '<=', $end);

            return [
                'month' => $monthDate->translatedFormat('M Y'),
                'short_month' => $monthDate->translatedFormat('M'),
                'total' => (float) $query->sum('amount'),
                'count' => (int) $query->count(),
            ];
        })->values();

        return Inertia::render('Dashboard', [
            'summary' => $financialService->getSummary(),
            'monthlyIncomeChart' => $monthlyIncomeChart,
        ]);
    })->name('dashboard');

    // Saldo Awal
    Route::get('/opening-balances', [OpeningBalanceController::class, 'index'])->name('opening-balances.index');
    Route::post('/opening-balances', [OpeningBalanceController::class, 'store'])
        ->middleware('role:super_admin')
        ->name('opening-balances.store');
    Route::put('/opening-balances/{openingBalance}', [OpeningBalanceController::class, 'update'])
        ->middleware('role:super_admin')
        ->name('opening-balances.update');

    // Pemasukan Infaq
    Route::get('/incomes', [IncomeTransactionController::class, 'index'])->name('incomes.index');
    Route::post('/incomes', [IncomeTransactionController::class, 'store'])->name('incomes.store');

    // Pengeluaran Pembangunan
    Route::get('/expenses', [ExpenseTransactionController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseTransactionController::class, 'store'])->name('expenses.store');

    // Riwayat Transaksi & Pembatalan
    Route::get('/transactions', [TransactionHistoryController::class, 'index'])->name('transactions.index');
    Route::post('/transactions/{transaction}/cancel', [TransactionHistoryController::class, 'cancel'])->name('transactions.cancel');

    // Log Aktivitas Audit (Khusus Super Admin)
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('role:super_admin')
        ->name('activity-logs.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Laporan Keuangan PDF
    Route::get('/reports', [FinancialReportController::class, 'index'])->name('reports.index');

    // Dokumentasi & Progres Pembangunan
    Route::get('/construction', [ConstructionProgressController::class, 'index'])->name('construction.index');
    Route::post('/construction/stages', [ConstructionProgressController::class, 'storeStage'])->name('construction.stages.store');
    Route::put('/construction/stages/{stage}', [ConstructionProgressController::class, 'updateStage'])->name('construction.stages.update');
    Route::delete('/construction/stages/{stage}', [ConstructionProgressController::class, 'destroyStage'])->name('construction.stages.destroy');

    Route::post('/construction/documents', [ConstructionProgressController::class, 'storeDocument'])->name('construction.documents.store');
    Route::post('/construction/documents/{document}/update', [ConstructionProgressController::class, 'updateDocument'])->name('construction.documents.update');
    Route::delete('/construction/photos/{photo}', [ConstructionProgressController::class, 'destroyPhoto'])->name('construction.photos.destroy');
    Route::delete('/construction/documents/{document}', [ConstructionProgressController::class, 'destroyDocument'])->name('construction.documents.destroy');
    
    // Manajemen Pengguna & Profil Masjid (Khusus Super Admin)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');

        Route::get('/mosque-settings', [MosqueSettingController::class, 'edit'])->name('mosque-settings.edit');
        Route::post('/mosque-settings', [MosqueSettingController::class, 'update'])->name('mosque-settings.update');

        // Backup & Restore Database
        Route::get('/backups', [DatabaseBackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [DatabaseBackupController::class, 'store'])->name('backups.store');
        Route::post('/backups/{backupLog}/verify', [DatabaseBackupController::class, 'verify'])->name('backups.verify');
        Route::get('/backups/{backupLog}/download', [DatabaseBackupController::class, 'download'])->name('backups.download');
        Route::post('/backups/{backupLog}/restore', [DatabaseBackupController::class, 'restoreFromLog'])->name('backups.restore-log');
        Route::post('/backups/restore-upload', [DatabaseBackupController::class, 'restoreFromUpload'])->name('backups.restore-upload');
        Route::delete('/backups/{backupLog}', [DatabaseBackupController::class, 'destroy'])->name('backups.destroy');
    });
});

require __DIR__.'/auth.php';