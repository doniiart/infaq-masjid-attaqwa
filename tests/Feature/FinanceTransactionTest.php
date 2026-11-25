<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\OpeningBalance;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_create_or_update_opening_balance(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        // Admin boleh melihat halaman saldo awal, tetapi ditolak saat POST
        $this->actingAs($admin)->get('/opening-balances')->assertStatus(200);
        $this->actingAs($admin)->post('/opening-balances', [
            'amount' => 5000000,
            'recorded_date' => '2026-11-01',
        ])->assertStatus(403);

        // Super Admin berhasil menyimpan saldo awal dan tercatat di log aktivitas
        $this->actingAs($superAdmin)->post('/opening-balances', [
            'amount' => 15000000,
            'recorded_date' => '2026-11-01',
            'notes' => 'Saldo awal kas masjid',
        ])->assertRedirect('/opening-balances');

        $this->assertDatabaseHas('opening_balances', ['amount' => '15000000.00']);
        $this->assertTrue(ActivityLog::where('action', 'opening_balance.created')->exists());
    }

    public function test_income_transaction_stores_real_donor_name_and_respects_privacy(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->post('/incomes', [
            'transaction_date' => '2026-11-20',
            'amount' => 750000,
            'source_or_purpose' => 'Transfer Bank',
            'donor_name' => 'Haji Budi Santoso',
            'is_anonymous' => true,
            'description' => 'Infaq pembangunan kubah',
        ])->assertRedirect('/incomes');

        $tx = Transaction::first();
        $this->assertNotNull($tx);
        $this->assertEquals('Haji Budi Santoso', $tx->donor_name);
        $this->assertEquals('Hamba Allah', $tx->public_donor_name);
        $this->assertTrue(ActivityLog::where('action', 'transaction.income_created')->exists());
    }

    public function test_expense_cannot_exceed_available_balance_and_protects_private_attachments(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        OpeningBalance::create([
            'amount' => 2000000,
            'recorded_date' => '2026-11-01',
            'created_by' => $admin->id,
        ]);

        // Pengeluaran melebihi saldo (2.500.000 > 2.000.000) harus ditolak
        $this->actingAs($admin)->post('/expenses', [
            'transaction_date' => '2026-11-21',
            'amount' => 2500000,
            'source_or_purpose' => 'Beli keramik',
        ])->assertSessionHasErrors('amount');

        $this->assertEquals(0, Transaction::where('type', 'expense')->count());

        // Pengeluaran sah dengan lampiran privat
        $file = UploadedFile::fake()->image('nota-semen.jpg');
        $this->actingAs($admin)->post('/expenses', [
            'transaction_date' => '2026-11-21',
            'amount' => 1200000,
            'source_or_purpose' => 'Beli 20 zak semen',
            'attachment' => $file,
            'is_public_attachment' => false,
        ])->assertRedirect('/expenses');

        $attachment = TransactionAttachment::first();
        $this->assertNotNull($attachment);

        // Tamu publik ditolak akses ke lampiran privat
        auth()->logout();
        $this->get(route('attachments.show', $attachment->id))->assertStatus(403);

        // Admin aktif dapat mengakses lampiran privat
        $this->actingAs($admin)->get(route('attachments.show', $attachment->id))->assertStatus(200);
    }

    public function test_invalid_or_negative_amounts_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->post('/incomes', [
            'transaction_date' => '2026-11-21',
            'amount' => -50000,
            'source_or_purpose' => 'Tunai',
            'is_anonymous' => false,
        ])->assertSessionHasErrors('amount');
    }
}