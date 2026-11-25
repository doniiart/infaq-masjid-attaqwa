<?php

namespace App\Services;

use App\Models\OpeningBalance;
use App\Models\Transaction;
use Carbon\Carbon;

class FinancialSummaryService
{
    public function getSummary(): array
    {
        $openingBalance = (float) OpeningBalance::sum('amount');

        $totalIncome = (float) Transaction::where('type', 'income')
            ->where('status', 'verified')
            ->sum('amount');

        $totalExpense = (float) Transaction::where('type', 'expense')
            ->where('status', 'verified')
            ->sum('amount');

        $availableBalance = round($openingBalance + $totalIncome - $totalExpense, 2);

        return [
            'opening_balance' => round($openingBalance, 2),
            'total_income' => round($totalIncome, 2),
            'total_expense' => round($totalExpense, 2),
            'available_balance' => $availableBalance,
        ];
    }

    public function getMonthlyChart(int $months = 6): array
    {
        $months = in_array($months, [6, 12], true) ? $months : 6;
        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $series = [];
        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);

        for ($i = 0; $i < $months; $i++) {
            $current = $start->copy()->addMonths($i);
            $monthStart = $current->copy()->startOfMonth()->toDateString();
            $monthEnd = $current->copy()->endOfMonth()->toDateString();

            $income = (float) Transaction::where('type', 'income')
                ->where('status', 'verified')
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $expense = (float) Transaction::where('type', 'expense')
                ->where('status', 'verified')
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $series[] = [
                'label' => $monthNames[$current->month] . ' ' . $current->year,
                'income' => round($income, 2),
                'expense' => round($expense, 2),
            ];
        }

        return [
            'months' => $months,
            'period_label' => $series[0]['label'] . ' - ' . end($series)['label'],
            'series' => $series,
        ];
    }
}