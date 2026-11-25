<?php

namespace Tests\Feature;

use App\Models\ConstructionDocument;
use App\Models\ConstructionStage;
use App\Models\OpeningBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_dashboard_displays_accurate_balances_and_hides_anonymous_donor_names(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        OpeningBalance::create([
            'amount' => 10000000,
            'recorded_date' => '2026-11-01',
            'notes' => 'Saldo awal kas pembangunan',
            'created_by' => $admin->id,
        ]);

        Transaction::create([
            'transaction_code' => 'TRX-IN-001',
            'type' => 'income',
            'transaction_date' => '2026-11-10',
            'amount' => 2500000,
            'source_or_purpose' => 'Transfer Bank',
            'donor_name' => 'Bapak Ahmad Rahasia',
            'is_anonymous' => true,
            'description' => 'Infaq pembangunan tahap 1',
            'status' => 'verified',
            'created_by' => $admin->id,
        ]);

        Transaction::create([
            'transaction_code' => 'TRX-IN-002',
            'type' => 'income',
            'transaction_date' => '2026-11-12',
            'amount' => 1500000,
            'source_or_purpose' => 'Tunai',
            'donor_name' => 'Keluarga H. Sulaiman',
            'is_anonymous' => false,
            'description' => 'Infaq tunai Jumat',
            'status' => 'verified',
            'created_by' => $admin->id,
        ]);

        Transaction::create([
            'transaction_code' => 'TRX-OUT-001',
            'type' => 'expense',
            'transaction_date' => '2026-11-15',
            'amount' => 4000000,
            'source_or_purpose' => 'Pembelian semen dan besi beton',
            'description' => 'Material pondasi',
            'status' => 'verified',
            'created_by' => $admin->id,
        ]);

        // Transaksi dibatalkan tidak boleh dihitung dalam saldo
        Transaction::create([
            'transaction_code' => 'TRX-OUT-999',
            'type' => 'expense',
            'transaction_date' => '2026-11-16',
            'amount' => 9000000,
            'source_or_purpose' => 'Salah input',
            'status' => 'cancelled',
            'cancellation_reason' => 'Kesalahan nominal',
            'created_by' => $admin->id,
        ]);

        ConstructionStage::create([
            'title' => 'Pekerjaan Persiapan & Pondasi',
            'stage_order' => 1,
            'status' => 'in_progress',
            'progress_percentage' => 45,
        ]);

        ConstructionDocument::create([
            'title' => 'Pengecoran Sloof Utama',
            'documented_at' => '2026-11-18',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);

        // 10.000.000 + (2.500.000 + 1.500.000) - 4.000.000 = 10.000.000
        $this->get('/')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->where('summary.opening_balance', 10000000)
                ->where('summary.total_income', 4000000)
                ->where('summary.total_expense', 4000000)
                ->where('summary.available_balance', 10000000)
                ->has('transactions.data', 3)
                ->where('transactions.data.2.donor_display', 'Hamba Allah')
                ->where('transactions.data.1.donor_display', 'Keluarga H. Sulaiman')
                ->has('stages', 1)
                ->has('documents', 1)
            );

        // Pastikan pencarian nama asli donatur anonim tidak membocorkan transaksi
        $this->get('/?search=Ahmad+Rahasia')
            ->assertInertia(fn (Assert $page) => $page
                ->has('transactions.data', 0)
            );
    }

    public function test_public_transactions_can_be_filtered_by_type_and_date_range(): void
    {
        $admin = User::factory()->create();

        Transaction::create([
            'transaction_code' => 'TRX-1',
            'type' => 'income',
            'transaction_date' => '2026-11-05',
            'amount' => 500000,
            'source_or_purpose' => 'Kotak Infaq',
            'status' => 'verified',
            'created_by' => $admin->id,
        ]);

        Transaction::create([
            'transaction_code' => 'TRX-2',
            'type' => 'expense',
            'transaction_date' => '2026-11-20',
            'amount' => 200000,
            'source_or_purpose' => 'Pasir',
            'status' => 'verified',
            'created_by' => $admin->id,
        ]);

        $this->get('/?type=expense&date_from=2026-11-15&date_to=2026-11-25')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('transactions.data', 1)
                ->where('transactions.data.0.transaction_code', 'TRX-2')
            );
    }
}