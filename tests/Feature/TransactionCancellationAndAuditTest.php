<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinancialSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCancellationAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_cancellation_requires_reason_saves_snapshot_and_updates_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $tx = Transaction::create([
            'transaction_code' => 'INQ-TEST-001',
            'type' => 'income',
            'transaction_date' => '2026-11-22',
            'amount' => 3000000,
            'source_or_purpose' => 'Transfer Bank',
            'donor_name' => 'Hamba Allah',
            'status' => 'verified',
            'created_by' => $admin->id,
        ]);

        // Tanpa alasan wajib ditolak
        $this->actingAs($admin)
            ->post("/transactions/{$tx->id}/cancel", ['cancellation_reason' => ''])
            ->assertSessionHasErrors('cancellation_reason');

        // Pembatalan dengan alasan sah
        $this->actingAs($admin)
            ->post("/transactions/{$tx->id}/cancel", [
                'cancellation_reason' => 'Salah input nominal transfer, seharusnya 300.000',
            ])
            ->assertRedirect('/transactions');

        $tx->refresh();
        $this->assertEquals('cancelled', $tx->status);
        $this->assertEquals($admin->id, $tx->cancelled_by);
        $this->assertNotNull($tx->cancelled_at);
        $this->assertNotNull($tx->previous_snapshot);
        $this->assertEquals('verified', $tx->previous_snapshot['status']);

        // Saldo aktif harus kembali 0 karena transaksi sudah dibatalkan
        $summary = app(FinancialSummaryService::class)->getSummary();
        $this->assertEquals(0, $summary['available_balance']);

        // Audit log wajib tercatat
        $this->assertTrue(ActivityLog::where('action', 'transaction.cancelled')->exists());
    }

    public function test_income_cancellation_is_blocked_if_it_causes_negative_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $income = Transaction::create([
            'transaction_code' => 'INQ-TEST-002',
            'type' => 'income',
            'transaction_date' => '2026-11-22',
            'amount' => 2000000,
            'source_or_purpose' => 'Tunai',
            'status' => 'verified',
            'created_by' => $admin->id,
        ]);

        Transaction::create([
            'transaction_code' => 'OUT-TEST-001',
            'type' => 'expense',
            'transaction_date' => '2026-11-23',
            'amount' => 1500000,
            'source_or_purpose' => 'Beli batu bata',
            'status' => 'verified',
            'created_by' => $admin->id,
        ]);

        // Membatalkan pemasukan 2 jt saat sudah ada pengeluaran 1,5 jt akan membuat saldo -1,5 jt -> ditolak
        $this->actingAs($admin)
            ->post("/transactions/{$income->id}/cancel", [
                'cancellation_reason' => 'Mencoba membatalkan pemasukan yang sudah dipakai',
            ])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertEquals('verified', $income->fresh()->status);
    }

    public function test_only_super_admin_can_view_activity_logs(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get('/activity-logs')->assertStatus(403);
        $this->actingAs($superAdmin)->get('/activity-logs')->assertStatus(200);
    }
}