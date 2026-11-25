<?php

namespace App\Services;

use App\Models\OpeningBalance;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class FinancialReportService
{
    public function resolvePeriod(string $periodType, ?string $customStart = null, ?string $customEnd = null): array
    {
        $today = Carbon::today();

        switch ($periodType) {
            case 'weekly':
                $start = $today->copy()->startOfWeek(Carbon::MONDAY);
                $end = $today->copy()->endOfWeek(Carbon::SUNDAY);
                $label = 'Laporan Mingguan';
                break;

            case 'quarterly':
                $start = $today->copy()->firstOfQuarter();
                $end = $today->copy()->lastOfQuarter();
                $label = 'Laporan Tiga Bulanan (Triwulan)';
                break;

            case 'custom':
                if (! $customStart || ! $customEnd) {
                    throw ValidationException::withMessages([
                        'start_date' => 'Tanggal mulai dan tanggal akhir wajib diisi untuk rentang tanggal khusus.',
                    ]);
                }
                $start = Carbon::parse($customStart)->startOfDay();
                $end = Carbon::parse($customEnd)->startOfDay();
                if ($end->lt($start)) {
                    throw ValidationException::withMessages([
                        'end_date' => 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.',
                    ]);
                }
                $label = 'Laporan Periode Khusus';
                break;

            case 'monthly':
            default:
                $periodType = 'monthly';
                $start = $today->copy()->startOfMonth();
                $end = $today->copy()->endOfMonth();
                $label = 'Laporan Bulanan';
                break;
        }

        return [
            'period_type' => $periodType,
            'period_title' => $label,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'period_range_indo' => $this->formatDateIndo($start) . ' s.d. ' . $this->formatDateIndo($end),
        ];
    }

    public function buildReportData(string $periodType = 'monthly', ?string $customStart = null, ?string $customEnd = null): array
    {
        $period = $this->resolvePeriod($periodType, $customStart, $customEnd);
        $startDate = $period['start_date'];
        $endDate = $period['end_date'];

        // Saldo sebelum periode dimulai
        $openingBeforePeriod = (float) OpeningBalance::whereDate('recorded_date', '<=', $startDate)->sum('amount');
        $incomeBeforePeriod = (float) Transaction::where('type', 'income')
            ->where('status', 'verified')
            ->whereDate('transaction_date', '<', $startDate)
            ->sum('amount');
        $expenseBeforePeriod = (float) Transaction::where('type', 'expense')
            ->where('status', 'verified')
            ->whereDate('transaction_date', '<', $startDate)
            ->sum('amount');

        $periodOpeningBalance = round($openingBeforePeriod + $incomeBeforePeriod - $expenseBeforePeriod, 2);

        // Rincian pemasukan dalam periode
        $incomes = Transaction::where('type', 'income')
            ->where('status', 'verified')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->map(fn (Transaction $tx) => [
                'id' => $tx->id,
                'transaction_code' => $tx->transaction_code,
                'transaction_date' => $tx->transaction_date->format('Y-m-d'),
                'transaction_date_indo' => $this->formatDateIndo($tx->transaction_date),
                'source_or_purpose' => $tx->source_or_purpose,
                'donor_display' => $tx->public_donor_name,
                'description' => $tx->description,
                'amount' => (float) $tx->amount,
            ]);

        // Rincian pengeluaran dalam periode
        $expenses = Transaction::where('type', 'expense')
            ->where('status', 'verified')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->map(fn (Transaction $tx) => [
                'id' => $tx->id,
                'transaction_code' => $tx->transaction_code,
                'transaction_date' => $tx->transaction_date->format('Y-m-d'),
                'transaction_date_indo' => $this->formatDateIndo($tx->transaction_date),
                'source_or_purpose' => $tx->source_or_purpose,
                'description' => $tx->description,
                'amount' => (float) $tx->amount,
            ]);

        $totalIncome = round((float) $incomes->sum('amount'), 2);
        $totalExpense = round((float) $expenses->sum('amount'), 2);
        $periodClosingBalance = round($periodOpeningBalance + $totalIncome - $totalExpense, 2);

        $totalFlow = $totalIncome + $totalExpense;
        $incomePercent = $totalFlow > 0 ? round(($totalIncome / $totalFlow) * 100, 1) : 0;
        $expensePercent = $totalFlow > 0 ? round(($totalExpense / $totalFlow) * 100, 1) : 0;

        return [
            'period' => $period,
            'period_opening_balance' => $periodOpeningBalance,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'period_closing_balance' => $periodClosingBalance,
            'income_percent' => $incomePercent,
            'expense_percent' => $expensePercent,
            'incomes' => $incomes->values()->all(),
            'expenses' => $expenses->values()->all(),
            'generated_at_indo' => $this->formatDateTimeIndo(now()),
        ];
    }

    public function formatDateIndo(Carbon $date): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $date->day . ' ' . $months[$date->month] . ' ' . $date->year;
    }

    public function formatDateTimeIndo(Carbon $date): string
    {
        return $this->formatDateIndo($date) . ', Pukul ' . $date->format('H:i') . ' WIB';
    }
}