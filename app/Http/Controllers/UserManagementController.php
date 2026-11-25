<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        $users = User::orderByRaw("CASE WHEN role = 'super_admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
                'created_at' => $user->created_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in(['super_admin', 'admin'])],
            'is_active' => ['required', 'boolean'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required' => 'Nama lengkap pengurus wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Email tersebut sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'role' => $validated['role'],
                'is_active' => (bool) $validated['is_active'],
                'password' => Hash::make($validated['password']),
            ]);

            AuditLogger::log(
                'user.created',
                "Menambahkan akun pengurus baru: {$user->name} ({$user->role})",
                $user,
                null,
                [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                ]
            );
        });

        return redirect()->route('users.index')
            ->with('success', 'Akun pengurus baru berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['super_admin', 'admin'])],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ], [
            'email.unique' => 'Email tersebut sudah digunakan pengguna lain.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        $currentUser = $request->user();

        if ($currentUser->id === $user->id && ! $validated['is_active']) {
            throw ValidationException::withMessages([
                'is_active' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.',
            ]);
        }

        if ($currentUser->id === $user->id && $validated['role'] !== 'super_admin') {
            throw ValidationException::withMessages([
                'role' => 'Anda tidak dapat menurunkan peran Super Admin milik Anda sendiri.',
            ]);
        }

        // Pastikan minimal masih ada 1 Super Admin aktif di sistem
        $willLeaveNoActiveSuperAdmin = $user->role === 'super_admin'
            && $user->is_active
            && ($validated['role'] !== 'super_admin' || ! $validated['is_active'])
            && User::where('role', 'super_admin')->where('is_active', true)->count() <= 1;

        if ($willLeaveNoActiveSuperAdmin) {
            throw ValidationException::withMessages([
                'role' => 'Sistem harus memiliki minimal satu Super Admin yang aktif.',
            ]);
        }

        DB::transaction(function () use ($validated, $user) {
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
            ];

            $updateData = [
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'role' => $validated['role'],
                'is_active' => (bool) $validated['is_active'],
            ];

            if (filled($validated['password'] ?? null)) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            AuditLogger::log(
                'user.updated',
                "Memperbarui akun pengurus: {$user->name} ({$user->role}, status: " . ($user->is_active ? 'Aktif' : 'Nonaktif') . ')',
                $user,
                $oldValues,
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => (bool) $user->is_active,
                    'password_changed' => filled($validated['password'] ?? null),
                ]
            );
        });

        return redirect()->route('users.index')
            ->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            throw ValidationException::withMessages([
                'status' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.',
            ]);
        }

        $nextStatus = ! (bool) $user->is_active;

        if (! $nextStatus && $user->role === 'super_admin') {
            $activeSuperAdmins = User::where('role', 'super_admin')->where('is_active', true)->count();
            if ($activeSuperAdmins <= 1) {
                throw ValidationException::withMessages([
                    'status' => 'Sistem harus memiliki minimal satu Super Admin aktif.',
                ]);
            }
        }

        DB::transaction(function () use ($user, $nextStatus) {
            $oldStatus = (bool) $user->is_active;
            $user->update(['is_active' => $nextStatus]);

            AuditLogger::log(
                'user.status_toggled',
                ($nextStatus ? 'Mengaktifkan' : 'Menonaktifkan') . " akun pengurus: {$user->name}",
                $user,
                ['is_active' => $oldStatus],
                ['is_active' => $nextStatus]
            );
        });

        return redirect()->route('users.index')
            ->with('success', 'Status keaktifan akun pengurus berhasil diubah.');
    }
}