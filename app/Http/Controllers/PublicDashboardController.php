<?php

namespace App\Http\Controllers;

use App\Models\ConstructionDocument;
use App\Models\ConstructionStage;
use App\Models\Transaction;
use App\Services\FinancialSummaryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicDashboardController extends Controller
{
    public function index(Request $request, FinancialSummaryService $financialService): Response
    {
        $chartMonths = (int) $request->input('chart_months', 6);

        $query = Transaction::with(['attachments' => function ($q) {
            $q->where('is_public', true);
        }])
            ->where('status', 'verified')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        if ($request->filled('type') && in_array($request->input('type'), ['income', 'expense'], true)) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('date')) {
            $query->whereDate('transaction_date', $request->input('date'));
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
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere(function ($donorQuery) use ($search) {
                        $donorQuery->where('type', 'income')
                            ->where('is_anonymous', false)
                            ->where('donor_name', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->paginate(10)
            ->withQueryString()
            ->through(function (Transaction $tx) {
                return [
                    'id' => $tx->id,
                    'transaction_code' => $tx->transaction_code,
                    'type' => $tx->type,
                    'transaction_date' => $tx->transaction_date->format('Y-m-d'),
                    'amount' => (float) $tx->amount,
                    'source_or_purpose' => $tx->source_or_purpose,
                    'donor_display' => $tx->public_donor_name,
                    'description' => $tx->description,
                    'status' => $tx->status,
                    'attachments' => $tx->attachments->map(fn ($att) => [
                        'id' => $att->id,
                        'file_name' => $att->file_name,
                        'url' => asset('storage/' . ltrim($att->file_path, '/')),
                    ]),
                ];
            });

        $stages = ConstructionStage::orderBy('stage_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ConstructionStage $stage) => [
                'id' => $stage->id,
                'title' => $stage->title,
                'description' => $stage->description,
                'stage_order' => $stage->stage_order,
                'status' => $stage->status,
                'progress_percentage' => $stage->progress_percentage,
                'start_date' => $stage->start_date?->format('Y-m-d'),
                'end_date' => $stage->end_date?->format('Y-m-d'),
            ]);

        $documents = ConstructionDocument::with([
            'stage:id,title',
            'photos' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
        ])
            ->where('is_published', true)
            ->orderByDesc('documented_at')
            ->orderByDesc('id')
            ->take(12)
            ->get()
            ->map(fn (ConstructionDocument $doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'description' => $doc->description,
                'documented_at' => $doc->documented_at->format('Y-m-d'),
                'stage_title' => $doc->stage?->title,
                'photos' => $doc->photos->map(fn ($photo) => [
                    'id' => $photo->id,
                    'url' => asset('storage/' . ltrim($photo->file_path, '/')),
                    'caption' => $photo->caption,
                ]),
            ]);

        return Inertia::render('Public/Home', [
            'summary' => $financialService->getSummary(),
            'chart' => $financialService->getMonthlyChart($chartMonths),
            'transactions' => $transactions,
            'filters' => [
                'search' => $request->input('search', ''),
                'type' => $request->input('type', ''),
                'date' => $request->input('date', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
                'chart_months' => $chartMonths,
            ],
            'stages' => $stages,
            'documents' => $documents,
        ]);
    }
}