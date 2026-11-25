<?php

namespace App\Http\Controllers;

use App\Models\OpeningBalance;
use App\Models\Transaction;
use App\Services\AuditLogger;
use App\Services\FinancialSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionHistoryController extends Controller
{
    public function index(Request $request, FinancialSummaryService $financialService): Response
    {
        $query = Transaction::with(['creator:id,name', 'canceller:id,name', 'attachments'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        if ($request->filled('type') && in_array($request->input('type'), ['income', 'expense'], true)) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status') && in_array($request->input('status'), ['verified', 'cancelled'], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                    ->orWhere('source_or_purpose', 'like', "%{$search}%")
                    ->orWhere('donor_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(15)
            ->withQueryString()
            ->through(fn (Transaction $tx) => [
                'id' => $tx->id,
                'transaction_code' => $tx->transaction_code,
                'type' => $tx->type,
                'transaction_date' => $tx->transaction_date->format('Y-m-d'),
                'amount' => (float) $tx->amount,
                'source_or_purpose' => $tx->source_or_purpose,
                'donor_name' => $tx->donor_name,
                'donor_public' => $tx->public_donor_name,
                'is_anonymous' => $tx->is_anonymous,
                'description' => $tx->description,
                'status' => $tx->status,
                'creator_name' => $tx->creator?->name ?? '-',
                'canceller_name' => $tx->canceller?->name,
                'cancelled_at' => $tx->cancelled_at?->format('d/m/Y H:i'),
                'cancellation_reason' => $tx->cancellation_reason,
                'attachments' => $tx->attachments->map(fn ($att) => [
                    'id' => $att->id,
                    'file_name' => $att->file_name,
                    'is_public' => $att->is_public,
                    'url' => route('attachments.show', $att->id),
                ]),
            ]);

        return Inertia::render('Finance/Transactions', [
            'transactions' => $transactions,
            'summary' => $financialService->getSummary(),
            'filters' => [
                'search' => $request->input('search', ''),
                'type' => $request->input('type', ''),
                'status' => $request->input('status', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
            ],
        ]);
    }

    public function cancel(Request $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'cancellation_reason.required' => 'Alasan pembatalan transaksi wajib diisi.',
            'cancellation_reason.min' => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        DB::transaction(function () use ($validated, $request, $transaction) {
            $lockedTx = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if ($lockedTx->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'Transaksi ini sudah berstatus dibatalkan.',
                ]);
            }

            // Jika transaksi pemasukan dibatalkan, pastikan saldo akhir tidak menjadi negatif
            if ($lockedTx->type === 'income') {
                $opening = (float) OpeningBalance::lockForUpdate()->sum('amount');
                $income = (float) Transaction::where('type', 'income')->where('status', 'verified')->lockForUpdate()->sum('amount');
                $expense = (float) Transaction::where('type', 'expense')->where('status', 'verified')->lockForUpdate()->sum('amount');
                $balanceAfterCancel = round(($opening + $income - (float) $lockedTx->amount) - $expense, 2);

                if ($balanceAfterCancel < 0) {
                    throw ValidationException::withMessages([
                        'cancellation_reason' => 'Pembatalan pemasukan ditolak karena akan menyebabkan saldo aktif menjadi negatif (Rp ' . number_format($balanceAfterCancel, 0, ',', '.') . ').',
                    ]);
                }
            }

            $snapshot = $lockedTx->toArray();

            $lockedTx->update([
                'status' => 'cancelled',
                'cancelled_by' => $request->user()->id,
                'cancelled_at' => now(),
                'cancellation_reason' => trim($validated['cancellation_reason']),
                'previous_snapshot' => $snapshot,
            ]);

            AuditLogger::log(
                'transaction.cancelled',
                "Membatalkan transaksi {$lockedTx->transaction_code} dengan alasan: {$lockedTx->cancellation_reason}",
                $lockedTx,
                $snapshot,
                $lockedTx->fresh()->toArray()
            );
        });

        return redirect()->route('transactions.index')
            ->with('success', "Transaksi {$transaction->transaction_code} berhasil dibatalkan dan dicatat dalam audit log.");
    }
}