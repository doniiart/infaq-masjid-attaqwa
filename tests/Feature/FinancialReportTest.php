<?php

namespace Tests\Feature;

use App\Models\OpeningBalance;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_calculates_period_opening_balance_from_prior_period_accurately(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        OpeningBalance::create([
            'amount' => 10000000,
            'recorded_date' => '2026-10-01',
            'created_by' => $admin->id,
        ]);

        // Transaksi bulan Oktober 2026
        Transaction::create([
            'transaction_code' => 'INQ-OCT-01',
            'type' => 'income',
            'transaction_date' => '2026-10-15',
            'amount' => 3000000,
            'source_or_purpose' => 'Transfer Bank',
            'donor_name' => 'Hamba Dermawan',
            'is_anonymous' => true,
            'status' => 'verified',
            'created_by' => $admin->id,
        ]);

        Transaction::create([
            'transaction_code' => 'OUT-OCT-01',
            'type' => 'expense',
            'transaction_date' => '2026-10-20',
            'amount' => 1000000,
            'source_or_purpose' => 'Semen',
            'status' => 'verified',
            'created_by' => $admin->id,
        ]);

        // Transaksi bulan November 2026
        Transaction::create([
            'transaction_code' => 'INQ-NOV-01',
            'type' => 'income',
            'transaction_date' => '2026-11-10',
            'amount' => 5000000,
            'source_or_purpose' => 'Tunai',
            'donor_name' => 'Keluarga Ahmad',
            'is_anonymous' => false,
            'status' => 'verified',
            'created_by' => $admin->id,
        ]);

        // Transaksi dibatalkan harus diabaikan
        Transaction::create([
            'transaction_code' => 'OUT-NOV-CANCEL',
            'type' => 'expense',
            'transaction_date' => '2026-11-12',
            'amount' => 2000000,
            'source_or_purpose' => 'Batal',
            'status' => 'cancelled',
            'cancellation_reason' => 'Kesalahan input',
            'created_by' => $admin->id,
        ]);

        $report = app(FinancialReportService::class)->buildReportData('custom', '2026-11-01', '2026-11-30');

        // Saldo awal November = 10.000.000 + 3.000.000 - 1.000.000 = 12.000.000
        $this->assertEquals(12000000, $report['period_opening_balance']);
        $this->assertEquals(5000000, $report['total_income']);
        $this->assertEquals(0, $report['total_expense']);
        $this->assertEquals(17000000, $report['period_closing_balance']);
        $this->assertEquals('Keluarga Ahmad', $report['incomes'][0]['donor_display']);
    }

    public function test_public_and_admin_can_download_pdf_reports_for_all_period_types(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        foreach (['weekly', 'monthly', 'quarterly'] as $period) {
            $response = $this->get(route('reports.pdf', ['period_type' => $period]));
            $response->assertStatus(200);
            $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        }

        $customResponse = $this->actingAs($admin)->get(route('reports.pdf', [
            'period_type' => 'custom',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-25',
        ]));
        $customResponse->assertStatus(200);
    }
}