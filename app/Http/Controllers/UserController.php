<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['roles', 'teacher']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $role = $request->input('role');
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        $availableRoles = Role::pluck('name');

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only('search', 'role'),
            'available_roles' => $availableRoles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'role' => 'required|string|exists:roles,name',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        AuditService::log('created', "Menambahkan pengguna baru: {$user->name} ({$validated['role']})", $user);

        return redirect()->route('users.index')->with('success', "Akun pengguna {$user->name} berhasil dibuat.");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($user->hasRole('Super Admin') && $validated['role'] !== 'Super Admin') {
            $superAdminCount = User::role('Super Admin')->count();
            if ($superAdminCount <= 1) {
                return back()->with('error', 'Sistem harus memiliki setidaknya satu Super Admin aktif.');
            }
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $user->syncRoles([$validated['role']]);

        AuditService::log('updated', "Memperbarui data akun pengguna: {$user->name}", $user);

        return redirect()->route('users.index')->with('success', "Data akun {$user->name} berhasil diperbarui.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditService::log('updated', "Mereset kata sandi pengguna: {$user->name}", $user);

        return back()->with('success', "Kata sandi untuk akun {$user->name} berhasil diatur ulang.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->hasRole('Super Admin')) {
            $superAdminCount = User::role('Super Admin')->count();
            if ($superAdminCount <= 1) {
                return back()->with('error', 'Tidak dapat menghapus satu-satunya akun Super Admin di sistem.');
            }
        }

        if ($user->teacher) {
            $user->teacher->update(['user_id' => null]);
        }

        $userName = $user->name;
        AuditService::log('deleted', "Menghapus akun pengguna: {$userName}", $user);

        $user->delete();

        return redirect()->route('users.index')->with('success', "Akun pengguna {$userName} berhasil dihapus.");
    }
}
