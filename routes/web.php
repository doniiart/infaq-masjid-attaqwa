<?php

use App\Http\Controllers\ExpenseTransactionController;
use App\Http\Controllers\IncomeTransactionController;
use App\Http\Controllers\OpeningBalanceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicDashboardController;
use App\Http\Controllers\TransactionAttachmentController;
use App\Services\FinancialSummaryService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [PublicDashboardController::class, 'index'])->name('home');
Route::get('/attachments/{attachment}', [TransactionAttachmentController::class, 'show'])->name('attachments.show');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', function (FinancialSummaryService $financialService) {
        return Inertia::render('Dashboard', [
            'summary' => $financialService->getSummary(),
        ]);
    })->name('dashboard');

    // Saldo Awal (Dilihat semua pengurus, diubah hanya Super Admin)
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

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';