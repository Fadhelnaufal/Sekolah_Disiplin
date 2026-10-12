<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Models\AcademicYear;
use App\Services\SemesterService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SemesterController extends Controller
{
    public function index(Request $request)
    {
        $query = Semester::with('academicYear');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'ilike', "%{$search}%");
        }

        if ($request->has('academic_year_id') && $request->input('academic_year_id') !== '') {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        $semesters = $query->orderBy('start_date', 'desc')->paginate(15)->withQueryString();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get(['id', 'name']);

        return Inertia::render('Semesters/Index', [
            'semesters' => $semesters,
            'academicYears' => $academicYears,
            'filters' => $request->only('search', 'academic_year_id'),
        ]);
    }

    public function create()
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get(['id', 'name', 'status']);
        return Inertia::render('Semesters/Form', [
            'academicYears' => $academicYears
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|string|max:50',
        ]);

        if ($validated['status'] === 'Active') {
            Semester::where('status', 'Active')->update(['status' => 'Inactive']);
        }

        Semester::create($validated);

        return redirect()->route('semesters.index')->with('success', 'Data semester berhasil ditambahkan.');
    }

    public function edit(Semester $semester)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get(['id', 'name', 'status']);
        return Inertia::render('Semesters/Form', [
            'semester' => $semester,
            'academicYears' => $academicYears
        ]);
    }

    public function update(Request $request, Semester $semester)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|string|max:50',
        ]);

        if ($validated['status'] === 'Active') {
            Semester::where('id', '!=', $semester->id)->where('status', 'Active')->update(['status' => 'Inactive']);
        }

        $semester->update($validated);

        return redirect()->route('semesters.index')->with('success', 'Data semester berhasil diperbarui.');
    }

    public function destroy(Semester $semester)
    {
        // Add protection if semester is used in discipline events
        if ($semester->status === 'Active') {
            return back()->with('error', 'Tidak dapat menghapus semester yang sedang aktif.');
        }

        $semester->delete();

        return redirect()->route('semesters.index')->with('success', 'Data semester berhasil dihapus.');
    }

    public function close(Semester $semester, SemesterService $semesterService)
    {
        try {
            $semesterService->closeSemester($semester, auth()->id());
            \App\Services\AuditService::log('closed', "Menutup semester {$semester->name}", $semester, [], ['status' => 'Closed']);
            return back()->with('success', 'Semester berhasil ditutup dan snapshot data siswa telah dibuat.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
