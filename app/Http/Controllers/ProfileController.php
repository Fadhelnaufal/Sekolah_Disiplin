<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user()->load(['roles', 'teacher.homeroomClasses']);

        return Inertia::render('Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
                'roles' => $user->roles->pluck('name'),
                'teacher' => $user->teacher ? [
                    'id' => $user->teacher->id,
                    'name' => $user->teacher->name,
                    'employee_number' => $user->teacher->employee_number,
                    'phone' => $user->teacher->phone,
                    'status' => $user->teacher->status,
                    'homeroom_classes' => $user->teacher->homeroomClasses->pluck('name'),
                ] : null,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:50',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->teacher) {
            $user->teacher->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? $user->teacher->phone,
            ]);
        }

        AuditService::log('updated', "Pengguna {$user->name} memperbarui informasi profil", $user);

        return back()->with('success', 'Informasi profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini yang Anda masukkan tidak sesuai.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
            'password.min' => 'Kata sandi baru minimal harus terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditService::log('updated', "Pengguna {$user->name} memperbarui kata sandi mandiri", $user);

        return back()->with('success', 'Kata sandi Anda berhasil diperbarui.');
    }
}
