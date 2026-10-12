<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcademicYearController extends Controller
{
    public function index(Request $request)
    {
        $query = AcademicYear::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'ilike', "%{$search}%");
        }

        $academicYears = $query->orderBy('name', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('AcademicYears/Index', [
            'academicYears' => $academicYears,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('AcademicYears/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:academic_years',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|string|max:50',
        ]);

        $defaultSchool = School::first();
        if ($defaultSchool) {
            $validated['school_id'] = $defaultSchool->id;
        }

        // If status is Active, we might want to deactivate others (optional, but good practice)
        if ($validated['status'] === 'Active') {
            AcademicYear::where('status', 'Active')->update(['status' => 'Inactive']);
        }

        AcademicYear::create($validated);

        return redirect()->route('academic-years.index')->with('success', 'Data tahun ajaran berhasil ditambahkan.');
    }

    public function edit(AcademicYear $academicYear)
    {
        return Inertia::render('AcademicYears/Form', [
            'academicYear' => $academicYear
        ]);
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:academic_years,name,' . $academicYear->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|string|max:50',
        ]);

        if ($validated['status'] === 'Active') {
            AcademicYear::where('id', '!=', $academicYear->id)->where('status', 'Active')->update(['status' => 'Inactive']);
        }

        $academicYear->update($validated);

        return redirect()->route('academic-years.index')->with('success', 'Data tahun ajaran berhasil diperbarui.');
    }

    public function destroy(AcademicYear $academicYear)
    {
        if ($academicYear->semesters()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus tahun ajaran karena memiliki semester terkait.');
        }

        if ($academicYear->classRooms()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus tahun ajaran karena memiliki kelas terkait.');
        }

        $academicYear->delete();

        return redirect()->route('academic-years.index')->with('success', 'Data tahun ajaran berhasil dihapus.');
    }
}
