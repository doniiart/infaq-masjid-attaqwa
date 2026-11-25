<?php

namespace App\Http\Controllers;

use App\Models\ConstructionDocument;
use App\Models\ConstructionDocumentPhoto;
use App\Models\ConstructionStage;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ConstructionProgressController extends Controller
{
    public function index(): Response
    {
        $stages = ConstructionStage::withCount('documents')
            ->orderBy('stage_order')
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
                'documents_count' => $stage->documents_count,
            ]);

        $documents = ConstructionDocument::with([
            'stage:id,title',
            'creator:id,name',
            'photos' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
        ])
            ->orderByDesc('documented_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->through(fn (ConstructionDocument $doc) => [
                'id' => $doc->id,
                'construction_stage_id' => $doc->construction_stage_id,
                'stage_title' => $doc->stage?->title,
                'title' => $doc->title,
                'description' => $doc->description,
                'documented_at' => $doc->documented_at->format('Y-m-d'),
                'is_published' => $doc->is_published,
                'creator_name' => $doc->creator?->name ?? '-',
                'photos' => $doc->photos->map(fn (ConstructionDocumentPhoto $photo) => [
                    'id' => $photo->id,
                    'url' => asset('storage/' . ltrim($photo->file_path, '/')),
                    'caption' => $photo->caption,
                    'sort_order' => $photo->sort_order,
                ]),
            ]);

        return Inertia::render('Construction/Index', [
            'stages' => $stages,
            'documents' => $documents,
        ]);
    }

    public function storeStage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'stage_order' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', 'in:planned,in_progress,completed'],
            'progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ], [
            'title.required' => 'Nama tahapan pembangunan wajib diisi.',
            'progress_percentage.max' => 'Persentase progres maksimal 100%.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
        ]);

        if ($validated['status'] === 'completed') {
            $validated['progress_percentage'] = 100;
        }

        DB::transaction(function () use ($validated, $request) {
            $stage = ConstructionStage::create([
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);

            AuditLogger::log(
                'construction.stage_created',
                "Menambahkan tahapan pembangunan: {$stage->title} ({$stage->progress_percentage}%)",
                $stage,
                null,
                $stage->toArray()
            );
        });

        return redirect()->route('construction.index')
            ->with('success', 'Tahapan progres pembangunan berhasil ditambahkan.');
    }

    public function updateStage(Request $request, ConstructionStage $stage): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'stage_order' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', 'in:planned,in_progress,completed'],
            'progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        if ($validated['status'] === 'completed') {
            $validated['progress_percentage'] = 100;
        }

        DB::transaction(function () use ($validated, $request, $stage) {
            $oldValues = $stage->toArray();

            $stage->update([
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);

            AuditLogger::log(
                'construction.stage_updated',
                "Memperbarui tahapan pembangunan: {$stage->title} ({$stage->progress_percentage}%)",
                $stage,
                $oldValues,
                $stage->fresh()->toArray()
            );
        });

        return redirect()->route('construction.index')
            ->with('success', 'Tahapan pembangunan berhasil diperbarui.');
    }

    public function destroyStage(ConstructionStage $stage): RedirectResponse
    {
        DB::transaction(function () use ($stage) {
            $oldValues = $stage->toArray();
            $title = $stage->title;
            $stage->delete();

            AuditLogger::log(
                'construction.stage_deleted',
                "Menghapus tahapan pembangunan: {$title}",
                null,
                $oldValues,
                null
            );
        });

        return redirect()->route('construction.index')
            ->with('success', 'Tahapan pembangunan berhasil dihapus.');
    }

    public function storeDocument(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'construction_stage_id' => ['nullable', 'exists:construction_stages,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'documented_at' => ['required', 'date'],
            'is_published' => ['required', 'boolean'],
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'captions' => ['nullable', 'array'],
            'captions.*' => ['nullable', 'string', 'max:255'],
        ], [
            'title.required' => 'Judul dokumentasi wajib diisi.',
            'documented_at.required' => 'Tanggal dokumentasi wajib diisi.',
            'photos.required' => 'Minimal 1 foto dokumentasi wajib diunggah.',
            'photos.max' => 'Maksimal 10 foto dalam sekali unggah.',
            'photos.*.image' => 'Semua file yang diunggah harus berupa gambar.',
            'photos.*.mimes' => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'photos.*.max' => 'Ukuran masing-masing foto maksimal 5 MB.',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $document = ConstructionDocument::create([
                'construction_stage_id' => $validated['construction_stage_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'documented_at' => $validated['documented_at'],
                'is_published' => (bool) $validated['is_published'],
                'created_by' => $request->user()->id,
            ]);

            $captions = $validated['captions'] ?? [];
            foreach ($request->file('photos') as $index => $file) {
                $path = $file->store('construction-docs', 'public');

                ConstructionDocumentPhoto::create([
                    'construction_document_id' => $document->id,
                    'file_path' => $path,
                    'caption' => $captions[$index] ?? null,
                    'sort_order' => $index + 1,
                ]);
            }

            AuditLogger::log(
                'construction.document_created',
                "Mengunggah dokumentasi pembangunan: {$document->title} (" . count($request->file('photos')) . " foto)",
                $document,
                null,
                $document->load('photos')->toArray()
            );
        });

        return redirect()->route('construction.index')
            ->with('success', 'Dokumentasi pembangunan dan foto berhasil diunggah.');
    }

    public function updateDocument(Request $request, ConstructionDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'construction_stage_id' => ['nullable', 'exists:construction_stages,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'documented_at' => ['required', 'date'],
            'is_published' => ['required', 'boolean'],
            'new_photos' => ['nullable', 'array', 'max:10'],
            'new_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'new_photos.*.image' => 'Semua file tambahan harus berupa gambar.',
            'new_photos.*.mimes' => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'new_photos.*.max' => 'Ukuran masing-masing foto maksimal 5 MB.',
        ]);

        DB::transaction(function () use ($validated, $request, $document) {
            $oldValues = $document->toArray();

            $document->update([
                'construction_stage_id' => $validated['construction_stage_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'documented_at' => $validated['documented_at'],
                'is_published' => (bool) $validated['is_published'],
            ]);

            if ($request->hasFile('new_photos')) {
                $maxOrder = (int) $document->photos()->max('sort_order');
                foreach ($request->file('new_photos') as $idx => $file) {
                    $path = $file->store('construction-docs', 'public');
                    ConstructionDocumentPhoto::create([
                        'construction_document_id' => $document->id,
                        'file_path' => $path,
                        'sort_order' => $maxOrder + $idx + 1,
                    ]);
                }
            }

            AuditLogger::log(
                'construction.document_updated',
                "Memperbarui dokumentasi pembangunan: {$document->title}",
                $document,
                $oldValues,
                $document->fresh()->toArray()
            );
        });

        return redirect()->route('construction.index')
            ->with('success', 'Dokumentasi pembangunan berhasil diperbarui.');
    }

    public function destroyPhoto(ConstructionDocumentPhoto $photo): RedirectResponse
    {
        DB::transaction(function () use ($photo) {
            $docTitle = $photo->document?->title ?? '-';
            if (Storage::disk('public')->exists($photo->file_path)) {
                Storage::disk('public')->delete($photo->file_path);
            }
            $photo->delete();

            AuditLogger::log(
                'construction.photo_deleted',
                "Menghapus 1 foto dari dokumentasi: {$docTitle}"
            );
        });

        return redirect()->route('construction.index')
            ->with('success', 'Foto dokumentasi berhasil dihapus.');
    }

    public function destroyDocument(ConstructionDocument $document): RedirectResponse
    {
        DB::transaction(function () use ($document) {
            $oldValues = $document->load('photos')->toArray();
            $title = $document->title;

            foreach ($document->photos as $photo) {
                if (Storage::disk('public')->exists($photo->file_path)) {
                    Storage::disk('public')->delete($photo->file_path);
                }
            }

            $document->delete();

            AuditLogger::log(
                'construction.document_deleted',
                "Menghapus dokumentasi pembangunan: {$title}",
                null,
                $oldValues,
                null
            );
        });

        return redirect()->route('construction.index')
            ->with('success', 'Dokumentasi pembangunan berhasil dihapus.');
    }
}