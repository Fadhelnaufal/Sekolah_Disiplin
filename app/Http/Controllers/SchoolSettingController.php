<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Semester;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SchoolSettingController extends Controller
{
    public function index()
    {
        $school = School::first() ?? School::create([
            'name' => 'SMP Malang',
            'address' => 'Jl. Pendidikan No. 1',
            'city' => 'Malang',
            'application_name' => 'Sekolah Disiplin',
        ]);

        $academicYears = AcademicYear::with('semesters')->orderBy('start_date', 'desc')->get();
        $activeSemester = Semester::with('academicYear')->whereIn('status', ['Active', 'active'])->first();

        return Inertia::render('Settings/Index', [
            'school' => $school,
            'academicYears' => $academicYears,
            'activeSemester' => $activeSemester,
        ]);
    }

    public function update(Request $request)
    {
        $school = School::firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'npsn' => 'nullable|string|max:50',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'phone' => 'nullable|string|max:50',
            'principal_name' => 'nullable|string|max:255',
            'vice_principal_name' => 'nullable|string|max:255',
            'counseling_officer_name' => 'nullable|string|max:255',
            'application_name' => 'required|string|max:100',
            'attention_threshold' => 'nullable|integer|min:1|max:999',
            'early_warning_days' => 'nullable|integer|min:1|max:365',
            'rapid_increase_threshold' => 'nullable|integer|min:1|max:999',
            'highest_point_threshold' => 'nullable|integer|min:1|max:9999',
            'logo' => 'nullable|image|max:2048',
            'remove_logo' => 'nullable|boolean',
        ]);

        if ($request->boolean('remove_logo')) {
            if ($school->logo_path) {
                $oldPath = str_replace('/storage/', '', $school->logo_path);
                Storage::disk('public')->delete($oldPath);
            }
            $school->logo_path = null;
        } elseif ($request->hasFile('logo')) {
            if ($school->logo_path) {
                $oldPath = str_replace('/storage/', '', $school->logo_path);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('logo')->store('schools', 'public');
            $school->logo_path = Storage::url($path);
        }

        $school->fill([
            'name' => $validated['name'],
            'npsn' => $validated['npsn'] ?? null,
            'address' => $validated['address'],
            'city' => $validated['city'],
            'phone' => $validated['phone'] ?? null,
            'principal_name' => $validated['principal_name'] ?? null,
            'vice_principal_name' => $validated['vice_principal_name'] ?? null,
            'counseling_officer_name' => $validated['counseling_officer_name'] ?? null,
            'application_name' => $validated['application_name'],
            'attention_threshold' => $validated['attention_threshold'] ?? 20,
            'early_warning_days' => $validated['early_warning_days'] ?? 30,
            'rapid_increase_threshold' => $validated['rapid_increase_threshold'] ?? 15,
            'highest_point_threshold' => $validated['highest_point_threshold'] ?? 150,
        ]);
        $school->save();

        AuditService::logUpdated($school, 'Memperbarui profil dan logo sekolah');

        return redirect()->back()->with('success', 'Pengaturan profil dan logo sekolah berhasil disimpan.');
    }
}
