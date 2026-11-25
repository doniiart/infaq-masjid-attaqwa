<?php

namespace App\Http\Controllers;

use App\Models\MosqueSetting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MosqueSettingController extends Controller
{
    public function edit(): Response
    {
        $setting = MosqueSetting::first();

        return Inertia::render('Admin/MosqueSettings', [
            'setting' => [
                'id' => $setting?->id,
                'name' => $setting?->name ?? 'Masjid At-Taqwa',
                'address' => $setting?->address ?? 'Candi Rejo, RT 23 RW 09, Jelobo, Wonosari, Klaten, Jawa Tengah 57473',
                'email' => $setting?->email ?? 'masjidattaqwa2309@gmail.com',
                'phone' => $setting?->phone ?? '',
                'description' => $setting?->description ?? '',
                'logo_url' => asset(ltrim($setting?->logo_path ?: 'images/logo-masjid-attaqwa.png', '/')),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'address' => ['required', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'Nama masjid wajib diisi.',
            'address.required' => 'Alamat masjid wajib diisi.',
            'logo.image' => 'File logo harus berupa gambar.',
            'logo.mimes' => 'Format logo harus JPG, JPEG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran file logo maksimal 2 MB.',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $setting = MosqueSetting::first() ?? new MosqueSetting();
            $oldValues = $setting->exists ? $setting->toArray() : null;

            $columns = Schema::getColumnListing('mosque_settings');
            $data = [
                'name' => trim($validated['name']),
                'address' => trim($validated['address']),
            ];

            if (in_array('email', $columns, true)) {
                $data['email'] = $validated['email'] ?? null;
            }
            if (in_array('phone', $columns, true)) {
                $data['phone'] = $validated['phone'] ?? null;
            }
            if (in_array('description', $columns, true)) {
                $data['description'] = $validated['description'] ?? null;
            }
            if (in_array('updated_by', $columns, true)) {
                $data['updated_by'] = $request->user()->id;
            }

            if ($request->hasFile('logo') && in_array('logo_path', $columns, true)) {
                if ($setting->logo_path && str_starts_with($setting->logo_path, 'storage/')) {
                    $oldStoragePath = substr($setting->logo_path, strlen('storage/'));
                    Storage::disk('public')->delete($oldStoragePath);
                }

                $storedPath = $request->file('logo')->store('mosque-branding', 'public');
                $data['logo_path'] = 'storage/' . $storedPath;
            }

            $setting->fill($data);
            $setting->save();

            AuditLogger::log(
                'mosque_setting.updated',
                "Memperbarui profil dan informasi resmi {$setting->name}",
                $setting,
                $oldValues,
                $setting->fresh()->toArray()
            );
        });

        return redirect()->route('mosque-settings.edit')
            ->with('success', 'Profil & informasi resmi Masjid At-Taqwa berhasil diperbarui.');
    }
}