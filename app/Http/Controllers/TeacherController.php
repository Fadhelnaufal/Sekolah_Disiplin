<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = Teacher::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'ilike', "%{$search}%")
                  ->orWhere('employee_number', 'ilike', "%{$search}%");
        }

        $teachers = $query->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Teachers/Index', [
            'teachers' => $teachers,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Teachers/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_number' => 'nullable|string|max:50|unique:teachers',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:teachers',
            'status' => 'required|string|max:50',
            'create_account' => 'nullable|boolean'
        ]);

        $teacherData = \Illuminate\Support\Arr::except($validated, ['create_account']);
        
        if ($request->create_account && !empty($validated['email'])) {
            $user = \App\Models\User::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'name' => $validated['name'],
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                ]
            );
            if (!$user->hasRole('Teacher')) {
                $user->assignRole('Teacher');
            }
            $teacherData['user_id'] = $user->id;
        }

        Teacher::create($teacherData);

        return redirect()->route('teachers.index')->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function edit(Teacher $teacher)
    {
        return Inertia::render('Teachers/Form', [
            'teacher' => $teacher
        ]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $validated = $request->validate([
            'employee_number' => 'nullable|string|max:50|unique:teachers,employee_number,' . $teacher->id,
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:teachers,email,' . $teacher->id,
            'status' => 'required|string|max:50',
            'create_account' => 'nullable|boolean'
        ]);

        $teacherData = \Illuminate\Support\Arr::except($validated, ['create_account']);

        if ($request->create_account && !empty($validated['email']) && !$teacher->user_id) {
            $user = \App\Models\User::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'name' => $validated['name'],
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                ]
            );
            if (!$user->hasRole('Teacher')) {
                $user->assignRole('Teacher');
            }
            $teacherData['user_id'] = $user->id;
        } elseif ($teacher->user_id) {
            $user = clone $teacher->user;
            $user->name = $validated['name'];
            if (!empty($validated['email'])) {
                $user->email = $validated['email'];
            }
            $user->save();
        }

        $teacher->update($teacherData);

        return redirect()->route('teachers.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(Teacher $teacher)
    {
        // Prevent deleting if assigned to class as homeroom
        if ($teacher->homeroomClasses()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus guru karena masih terdaftar sebagai wali kelas aktif.');
        }

        $teacher->delete();

        return redirect()->route('teachers.index')->with('success', 'Data guru berhasil dihapus.');
    }
}
