<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\AuditLogger;
use App\Services\FinancialSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class IncomeTransactionController extends Controller
{
    public function index(FinancialSummaryService $financialService): Response
    {
        $incomes = Transaction::with('creator:id,name')
            ->where('type', 'income')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->through(fn (Transaction $tx) => [
                'id' => $tx->id,
                'transaction_code' => $tx->transaction_code,
                'transaction_date' => $tx->transaction_date->format('Y-m-d'),
                'amount' => (float) $tx->amount,
                'source_or_purpose' => $tx->source_or_purpose,
                'donor_name' => $tx->donor_name,
                'donor_public' => $tx->public_donor_name,
                'is_anonymous' => $tx->is_anonymous,
                'description' => $tx->description,
                'status' => $tx->status,
                'creator_name' => $tx->creator?->name ?? '-',
            ]);

        return Inertia::render('Finance/Incomes', [
            'incomes' => $incomes,
            'summary' => $financialService->getSummary(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1000', 'max:999999999999.99'],
            'source_or_purpose' => ['required', 'string', 'max:150'],
            'donor_name' => ['nullable', 'string', 'max:150'],
            'is_anonymous' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'transaction_date.required' => 'Tanggal pemasukan wajib diisi.',
            'amount.required' => 'Nominal pemasukan wajib diisi.',
            'amount.min' => 'Nominal pemasukan minimal Rp 1.000.',
            'source_or_purpose.required' => 'Sumber atau metode pemasukan wajib diisi.',
        ]);

        // Cegah duplikasi pencatatan identik dalam 15 detik terakhir
        $isDuplicate = Transaction::where('type', 'income')
            ->where('status', 'verified')
            ->whereDate('transaction_date', $validated['transaction_date'])
            ->where('amount', $validated['amount'])
            ->where('source_or_purpose', $validated['source_or_purpose'])
            ->where('donor_name', $validated['donor_name'] ?? null)
            ->where('created_by', $request->user()->id)
            ->where('created_at', '>=', now()->subSeconds(15))
            ->exists();

        if ($isDuplicate) {
            throw ValidationException::withMessages([
                'amount' => 'Terdeteksi pencatatan pemasukan yang identik baru saja disimpan. Mohon periksa riwayat transaksi.',
            ]);
        }

        DB::transaction(function () use ($validated, $request) {
            $code = 'INQ-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

            $transaction = Transaction::create([
                'transaction_code' => $code,
                'type' => 'income',
                'transaction_date' => $validated['transaction_date'],
                'amount' => $validated['amount'],
                'source_or_purpose' => $validated['source_or_purpose'],
                'donor_name' => filled($validated['donor_name'] ?? null) ? trim($validated['donor_name']) : null,
                'is_anonymous' => (bool) $validated['is_anonymous'],
                'description' => $validated['description'] ?? null,
                'status' => 'verified',
                'created_by' => $request->user()->id,
            ]);

            AuditLogger::log(
                'transaction.income_created',
                "Mencatat pemasukan {$transaction->transaction_code} sebesar Rp " . number_format((float) $transaction->amount, 0, ',', '.'),
                $transaction,
                null,
                $transaction->toArray()
            );
        });

        return redirect()->route('incomes.index')
            ->with('success', 'Pemasukan infaq berhasil dicatat dan langsung masuk ke saldo aktif.');
    }
}