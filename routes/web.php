<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicDashboardController;
use App\Services\FinancialSummaryService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [PublicDashboardController::class, 'index'])->name('home');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', function (FinancialSummaryService $financialService) {
        return Inertia::render('Dashboard', [
            'summary' => $financialService->getSummary(),
        ]);
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';