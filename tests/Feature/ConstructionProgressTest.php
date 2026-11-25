<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ConstructionDocument;
use App\Models\ConstructionStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConstructionProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_construction_stages_and_progress(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->post('/construction/stages', [
            'title' => 'Pekerjaan Pondasi & Sloof',
            'description' => 'Galian tanah dan pengecoran sloof',
            'stage_order' => 1,
            'status' => 'in_progress',
            'progress_percentage' => 60,
        ])->assertRedirect('/construction');

        $stage = ConstructionStage::first();
        $this->assertNotNull($stage);
        $this->assertEquals(60, $stage->progress_percentage);

        // Update ke selesai otomatis set progres menjadi 100%
        $this->actingAs($admin)->put("/construction/stages/{$stage->id}", [
            'title' => 'Pekerjaan Pondasi & Sloof',
            'stage_order' => 1,
            'status' => 'completed',
            'progress_percentage' => 80,
        ])->assertRedirect('/construction');

        $this->assertEquals(100, $stage->fresh()->progress_percentage);
        $this->assertTrue(ActivityLog::where('action', 'construction.stage_updated')->exists());
    }

    public function test_admin_can_upload_multiple_photos_and_control_public_visibility(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $stage = ConstructionStage::create([
            'title' => 'Struktur Atap',
            'stage_order' => 2,
            'status' => 'in_progress',
            'progress_percentage' => 30,
        ]);

        // Unggah 2 foto dokumentasi sekaligus
        $this->actingAs($admin)->post('/construction/documents', [
            'construction_stage_id' => $stage->id,
            'title' => 'Pemasangan Rangka Kubah',
            'description' => 'Pemasangan rangka baja ringan area kubah utama',
            'documented_at' => '2026-11-25',
            'is_published' => true,
            'photos' => [
                UploadedFile::fake()->image('kubah-1.jpg'),
                UploadedFile::fake()->image('kubah-2.png'),
            ],
        ])->assertRedirect('/construction');

        $doc = ConstructionDocument::with('photos')->first();
        $this->assertNotNull($doc);
        $this->assertCount(2, $doc->photos);
        Storage::disk('public')->assertExists($doc->photos[0]->file_path);

        // Pastikan tampil di halaman beranda publik
        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents', 1)
                ->has('documents.0.photos', 2)
            );

        // Ubah menjadi tidak dipublikasikan
        $this->actingAs($admin)->post("/construction/documents/{$doc->id}/update", [
            'construction_stage_id' => $stage->id,
            'title' => 'Pemasangan Rangka Kubah',
            'documented_at' => '2026-11-25',
            'is_published' => false,
        ])->assertRedirect('/construction');

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents', 0)
            );
    }

    public function test_invalid_file_types_are_rejected_in_construction_document_upload(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->post('/construction/documents', [
            'title' => 'Dokumen Invalid',
            'documented_at' => '2026-11-25',
            'is_published' => true,
            'photos' => [
                UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload'),
            ],
        ])->assertSessionHasErrors('photos.0');
    }
}