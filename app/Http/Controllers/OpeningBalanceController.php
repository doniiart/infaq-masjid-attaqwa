<?php

namespace App\Http\Controllers;

use App\Models\OpeningBalance;
use App\Services\AuditLogger;
use App\Services\FinancialSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OpeningBalanceController extends Controller
{
    public function index(FinancialSummaryService $financialService): Response
    {
        $openingBalances = OpeningBalance::with('creator:id,name')
            ->orderByDesc('recorded_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (OpeningBalance $item) => [
                'id' => $item->id,
                'amount' => (float) $item->amount,
                'recorded_date' => $item->recorded_date->format('Y-m-d'),
                'notes' => $item->notes,
                'creator_name' => $item->creator?->name ?? '-',
                'created_at' => $item->created_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Finance/OpeningBalance', [
            'openingBalances' => $openingBalances,
            'summary' => $financialService->getSummary(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat mengatur saldo awal.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'recorded_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'amount.required' => 'Nominal saldo awal wajib diisi.',
            'amount.min' => 'Nominal saldo awal tidak boleh negatif.',
            'recorded_date.required' => 'Tanggal pencatatan wajib diisi.',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $balance = OpeningBalance::create([
                'amount' => $validated['amount'],
                'recorded_date' => $validated['recorded_date'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            AuditLogger::log(
                'opening_balance.created',
                "Menambahkan saldo awal sebesar Rp " . number_format((float) $balance->amount, 0, ',', '.'),
                $balance,
                null,
                $balance->toArray()
            );
        });

        return redirect()->route('opening-balances.index')
            ->with('success', 'Saldo awal berhasil disimpan dan dicatat dalam log aktivitas.');
    }

    public function update(Request $request, OpeningBalance $openingBalance): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'recorded_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'amount.required' => 'Nominal saldo awal wajib diisi.',
            'amount.numeric' => 'Nominal saldo awal harus berupa angka.',
            'amount.min' => 'Nominal saldo awal tidak boleh negatif.',
            'recorded_date.required' => 'Tanggal pencatatan wajib diisi.',
        ]);

        DB::transaction(function () use ($validated, $openingBalance) {
            $oldValues = $openingBalance->toArray();

            $openingBalance->update([
                'amount' => $validated['amount'],
                'recorded_date' => $validated['recorded_date'],
                'description' => $validated['description'] ?? null,
            ]);

            AuditLogger::log(
                'opening_balance.updated',
                'Memperbarui catatan saldo awal menjadi Rp ' . number_format((float) $openingBalance->amount, 0, ',', '.'),
                $openingBalance,
                $oldValues,
                $openingBalance->fresh()->toArray()
            );
        });

        return redirect()->route('opening-balances.index')
            ->with('success', 'Catatan saldo awal berhasil diperbarui dan dicatat dalam log aktivitas.');
    }
}