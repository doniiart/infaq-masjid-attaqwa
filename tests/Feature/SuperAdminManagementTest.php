<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\MosqueSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuperAdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_admin_cannot_access_user_management_or_mosque_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get('/users')->assertStatus(403);
        $this->actingAs($admin)->get('/mosque-settings')->assertStatus(403);
    }

    public function test_super_admin_can_create_edit_and_toggle_users_with_self_protection(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        // Tambah akun Admin baru
        $this->actingAs($superAdmin)->post('/users', [
            'name' => 'Bendahara Pembangunan',
            'email' => 'bendahara@attaqwa.id',
            'role' => 'admin',
            'is_active' => true,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/users');

        $newAdmin = User::where('email', 'bendahara@attaqwa.id')->first();
        $this->assertNotNull($newAdmin);
        $this->assertTrue(ActivityLog::where('action', 'user.created')->exists());

        // Nonaktifkan Admin baru
        $this->actingAs($superAdmin)
            ->patch("/users/{$newAdmin->id}/toggle-status")
            ->assertRedirect('/users');

        $this->assertFalse((bool) $newAdmin->fresh()->is_active);

        // Super Admin tidak boleh menonaktifkan dirinya sendiri
        $this->actingAs($superAdmin)
            ->patch("/users/{$superAdmin->id}/toggle-status")
            ->assertSessionHasErrors('status');

        $this->assertTrue((bool) $superAdmin->fresh()->is_active);
    }

    public function test_super_admin_can_update_mosque_settings_and_logo(): void
    {
        Storage::fake('public');
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($superAdmin)->post('/mosque-settings', [
            'name' => 'Masjid Jami At-Taqwa Candi Rejo',
            'address' => 'Candi Rejo, Jelobo, Wonosari, Klaten',
            'email' => 'info@masjidattaqwa.id',
            'phone' => '081234567890',
            'description' => 'Renovasi dan perluasan ruang utama ibadah.',
            'logo' => UploadedFile::fake()->image('logo-baru.png'),
        ])->assertRedirect('/mosque-settings');

        $setting = MosqueSetting::first();
        $this->assertNotNull($setting);
        $this->assertEquals('Masjid Jami At-Taqwa Candi Rejo', $setting->name);
        $this->assertTrue(ActivityLog::where('action', 'mosque_setting.updated')->exists());
    }
}