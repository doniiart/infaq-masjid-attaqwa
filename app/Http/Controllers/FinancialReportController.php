<?php

namespace App\Http\Controllers;

use App\Models\MosqueSetting;
use App\Services\AuditLogger;
use App\Services\FinancialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class FinancialReportController extends Controller
{
    public function index(Request $request, FinancialReportService $reportService): Response
    {
        $periodType = (string) $request->input('period_type', 'monthly');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $report = $reportService->buildReportData($periodType, $startDate, $endDate);

        return Inertia::render('Finance/Reports', [
            'report' => $report,
            'filters' => [
                'period_type' => $report['period']['period_type'],
                'start_date' => $report['period']['start_date'],
                'end_date' => $report['period']['end_date'],
            ],
        ]);
    }

    public function downloadPdf(Request $request, FinancialReportService $reportService): HttpResponse
    {
        $validated = $request->validate([
            'period_type' => ['nullable', 'in:weekly,monthly,quarterly,custom'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $periodType = $validated['period_type'] ?? 'monthly';
        $report = $reportService->buildReportData(
            $periodType,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        $mosque = MosqueSetting::first();
        $generatorName = $request->user()?->name ?? 'Pengunjung Publik (Jamaah)';

        // Encode logo resmi ke base64 agar selalu tampil sempurna pada dokumen PDF
        $logoBase64 = null;
        $logoRelative = ltrim($mosque?->logo_path ?: 'images/logo-masjid-attaqwa.png', '/');
        $logoFullPath = public_path($logoRelative);
        if (file_exists($logoFullPath)) {
            $mime = mime_content_type($logoFullPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFullPath));
        }

        if ($request->user()) {
            AuditLogger::log(
                'report.pdf_downloaded',
                "Mengunduh laporan keuangan PDF ({$report['period']['period_title']}: {$report['period']['period_range_indo']})"
            );
        }

        $pdf = Pdf::loadView('reports.financial-pdf', [
            'report' => $report,
            'mosque' => $mosque,
            'logoBase64' => $logoBase64,
            'generatorName' => $generatorName,
        ])->setPaper('a4', 'portrait');

        $fileName = 'Laporan-Keuangan-Masjid-At-Taqwa-' . $report['period']['start_date'] . '-sd-' . $report['period']['end_date'] . '.pdf';

        return $pdf->download($fileName);
    }
}