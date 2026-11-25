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

Route::get('/', [PublicDashboardController::class, 'index'])->name('home');
Route::get('/attachments/{attachment}', [TransactionAttachmentController::class, 'show'])->name('attachments.show');
Route::get('/reports/pdf', [FinancialReportController::class, 'downloadPdf'])->name('reports.pdf');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', function (FinancialSummaryService $financialService) {
        return Inertia::render('Dashboard', [
            'summary' => $financialService->getSummary(),
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
});

require __DIR__.'/auth.php';