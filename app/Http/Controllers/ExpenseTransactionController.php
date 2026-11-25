<?php

namespace App\Http\Controllers;

use App\Models\OpeningBalance;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Services\AuditLogger;
use App\Services\FinancialSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseTransactionController extends Controller
{
    public function index(FinancialSummaryService $financialService): Response
    {
        $expenses = Transaction::with(['creator:id,name', 'attachments'])
            ->where('type', 'expense')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->through(fn (Transaction $tx) => [
                'id' => $tx->id,
                'transaction_code' => $tx->transaction_code,
                'transaction_date' => $tx->transaction_date->format('Y-m-d'),
                'amount' => (float) $tx->amount,
                'source_or_purpose' => $tx->source_or_purpose,
                'description' => $tx->description,
                'status' => $tx->status,
                'creator_name' => $tx->creator?->name ?? '-',
                'attachments' => $tx->attachments->map(fn ($att) => [
                    'id' => $att->id,
                    'file_name' => $att->file_name,
                    'is_public' => $att->is_public,
                    'url' => route('attachments.show', $att->id),
                ]),
            ]);

        return Inertia::render('Finance/Expenses', [
            'expenses' => $expenses,
            'summary' => $financialService->getSummary(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1000', 'max:999999999999.99'],
            'source_or_purpose' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'is_public_attachment' => ['nullable', 'boolean'],
        ], [
            'transaction_date.required' => 'Tanggal pengeluaran wajib diisi.',
            'amount.required' => 'Nominal pengeluaran wajib diisi.',
            'amount.min' => 'Nominal pengeluaran minimal Rp 1.000.',
            'source_or_purpose.required' => 'Keperluan pengeluaran wajib diisi.',
            'attachment.mimes' => 'Bukti transaksi harus berupa gambar (JPG, PNG, WEBP) atau PDF.',
            'attachment.max' => 'Ukuran file bukti maksimal 5 MB.',
        ]);

        // Cegah duplikasi pencatatan pengeluaran identik dalam 15 detik
        $isDuplicate = Transaction::where('type', 'expense')
            ->where('status', 'verified')
            ->whereDate('transaction_date', $validated['transaction_date'])
            ->where('amount', $validated['amount'])
            ->where('source_or_purpose', $validated['source_or_purpose'])
            ->where('created_by', $request->user()->id)
            ->where('created_at', '>=', now()->subSeconds(15))
            ->exists();

        if ($isDuplicate) {
            throw ValidationException::withMessages([
                'amount' => 'Terdeteksi pencatatan pengeluaran identik baru saja disimpan. Mohon periksa daftar pengeluaran.',
            ]);
        }

        DB::transaction(function () use ($validated, $request) {
            // Hitung ulang saldo tersedia di dalam transaksi database untuk mencegah saldo negatif
            $opening = (float) OpeningBalance::lockForUpdate()->sum('amount');
            $income = (float) Transaction::where('type', 'income')->where('status', 'verified')->lockForUpdate()->sum('amount');
            $expense = (float) Transaction::where('type', 'expense')->where('status', 'verified')->lockForUpdate()->sum('amount');
            $availableBalance = round($opening + $income - $expense, 2);

            if ((float) $validated['amount'] > $availableBalance) {
                throw ValidationException::withMessages([
                    'amount' => 'Saldo dana tersedia (Rp ' . number_format($availableBalance, 0, ',', '.') . ') tidak mencukupi untuk pengeluaran ini.',
                ]);
            }

            $code = 'OUT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

            $transaction = Transaction::create([
                'transaction_code' => $code,
                'type' => 'expense',
                'transaction_date' => $validated['transaction_date'],
                'amount' => $validated['amount'],
                'source_or_purpose' => $validated['source_or_purpose'],
                'description' => $validated['description'] ?? null,
                'status' => 'verified',
                'created_by' => $request->user()->id,
            ]);

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('transaction-attachments', 'local');

                TransactionAttachment::create([
                    'transaction_id' => $transaction->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType() ?? 'application/octet-stream',
                    'file_size' => $file->getSize(),
                    'is_public' => (bool) ($validated['is_public_attachment'] ?? false),
                    'uploaded_by' => $request->user()->id,
                ]);
            }

            AuditLogger::log(
                'transaction.expense_created',
                "Mencatat pengeluaran {$transaction->transaction_code} sebesar Rp " . number_format((float) $transaction->amount, 0, ',', '.'),
                $transaction,
                null,
                $transaction->toArray()
            );
        });

        return redirect()->route('expenses.index')
            ->with('success', 'Pengeluaran dana pembangunan berhasil dicatat.');
    }
}