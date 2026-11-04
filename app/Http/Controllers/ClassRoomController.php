<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassRoomController extends Controller
{
    public function index(Request $request)
    {
        $query = ClassRoom::with(['homeroomTeacher', 'academicYear']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'ilike', "%{$search}%");
        }

        $classRooms = $query->orderBy('grade_level')->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('ClassRooms/Index', [
            'classRooms' => $classRooms,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        $teachers = Teacher::whereIn('status', ['Active', 'aktif', 'Aktif'])->orderBy('name')->get(['id', 'name']);
        $academicYears = AcademicYear::orderBy('name', 'desc')->get(['id', 'name', 'status']);

        return Inertia::render('ClassRooms/Form', [
            'teachers' => $teachers,
            'academicYears' => $academicYears,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'grade_level' => 'required|integer',
            'homeroom_teacher_id' => 'nullable|exists:teachers,id',
            'status' => 'required|string|max:50',
        ]);

        $defaultSchool = School::first();
        if ($defaultSchool) {
            $validated['school_id'] = $defaultSchool->id;
        }

        ClassRoom::create($validated);

        return redirect()->route('class-rooms.index')->with('success', 'Data kelas berhasil ditambahkan.');
    }

    public function edit(ClassRoom $classRoom)
    {
        $teachers = Teacher::whereIn('status', ['Active', 'aktif', 'Aktif'])->orderBy('name')->get(['id', 'name']);
        $academicYears = AcademicYear::orderBy('name', 'desc')->get(['id', 'name', 'status']);

        return Inertia::render('ClassRooms/Form', [
            'classRoom' => $classRoom,
            'teachers' => $teachers,
            'academicYears' => $academicYears,
        ]);
    }

    public function update(Request $request, ClassRoom $classRoom)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'grade_level' => 'required|integer',
            'homeroom_teacher_id' => 'nullable|exists:teachers,id',
            'status' => 'required|string|max:50',
        ]);

        $classRoom->update($validated);

        return redirect()->route('class-rooms.index')->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(ClassRoom $classRoom)
    {
        if ($classRoom->students()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus kelas karena masih ada siswa yang terdaftar di kelas ini.');
        }

        $classRoom->delete();

        return redirect()->route('class-rooms.index')->with('success', 'Data kelas berhasil dihapus.');
    }
}
