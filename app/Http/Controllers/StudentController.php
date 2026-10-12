<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Semester;
use App\Services\StudentPointService;
use App\Services\EarlyWarningEngineService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index(Request $request, StudentPointService $pointService, EarlyWarningEngineService $earlyWarningService)
    {
        $query = Student::with('classRoom');

        $user = auth()->user();
        if ($user && $user->hasRole('Teacher') && !$user->hasRole('Super Admin') && !$user->hasRole('Tata Tertib') && !$user->hasRole('BK')) {
            $teacher = \App\Models\Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $classIds = $teacher->homeroomClasses()->pluck('id');
                $query->whereIn('class_room_id', $classIds);
            } else {
                // If not a homeroom teacher, deny all for now (subject teacher relation missing)
                $query->where('id', 0);
            }
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'ilike', "%{$search}%")
                  ->orWhere('nis', 'ilike', "%{$search}%");
        }

        if ($request->has('class_room_id') && $request->input('class_room_id') !== '') {
            $query->where('class_room_id', $request->input('class_room_id'));
        }

        $paginator = $query->orderBy('name')->paginate(15)->withQueryString();
        
        // Append monitoring data (Points & Risk Status)
        $activeSemester = Semester::whereIn('status', ['Active', 'aktif', 'Aktif'])->first();
        
        $paginator->getCollection()->transform(function ($student) use ($activeSemester, $pointService, $earlyWarningService) {
            $points = 0;
            $status = 'NORMAL';
            
            if ($activeSemester) {
                $points = $pointService->getNetPoints($student, $activeSemester);
                $status = $earlyWarningService->evaluateRiskStatus($student, $activeSemester);
            }
            
            $student->net_points = $points;
            $student->risk_status = $status;
            
            return $student;
        });

        $classRooms = ClassRoom::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Students/Index', [
            'students' => $paginator,
            'classRooms' => $classRooms,
            'filters' => $request->only('search', 'class_room_id'),
            'hasActiveSemester' => $activeSemester !== null
        ]);
    }

    public function create()
    {
        $classRooms = ClassRoom::whereIn('status', ['Active', 'active'])->orderBy('name')->get(['id', 'name']);
        return Inertia::render('Students/Form', [
            'classRooms' => $classRooms
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nis' => 'required|string|max:50|unique:students',
            'name' => 'required|string|max:255',
            'class_room_id' => 'required|exists:class_rooms,id',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:50',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $defaultSchool = School::first();
        if ($defaultSchool) {
            $validated['school_id'] = $defaultSchool->id;
        }

        $student = Student::create($validated);
        \App\Services\AuditService::logCreated($student, 'Mendaftarkan siswa baru: ' . $student->name);

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function show(Request $request, Student $student, StudentPointService $pointService, EarlyWarningEngineService $earlyWarningService)
    {
        $student->load('classRoom');
        
        $user = auth()->user();
        if ($user && $user->hasRole('Teacher') && !$user->hasRole('Super Admin') && !$user->hasRole('Tata Tertib') && !$user->hasRole('BK')) {
            $teacher = \App\Models\Teacher::where('user_id', $user->id)->first();
            if (!$teacher) {
                abort(403, 'Akses ditolak: Anda belum terdaftar sebagai Guru.');
            }
            $classIds = $teacher->homeroomClasses()->pluck('id');
            if (!$classIds->contains($student->class_room_id)) {
                abort(403, 'Akses ditolak: Siswa ini bukan berada di kelas perwalian Anda.');
            }
        }
        
        $semesters = Semester::orderBy('start_date', 'desc')->get(['id', 'name', 'status']);
        $activeSemester = Semester::whereIn('status', ['Active', 'aktif', 'Aktif'])->first();
        
        // If no semester filter is provided, default to active semester or latest semester
        $selectedSemesterId = $request->input('semester_id');
        if (!$selectedSemesterId) {
            $selectedSemesterId = $activeSemester ? $activeSemester->id : ($semesters->first() ? $semesters->first()->id : null);
        }
        
        $selectedSemester = null;
        $violationPoints = 0;
        $achievementPoints = 0;
        $netPoints = 0;
        $riskStatus = 'NORMAL';

        if ($selectedSemesterId) {
            $selectedSemester = Semester::find($selectedSemesterId);
            if ($selectedSemester) {
                $violationPoints = $pointService->getViolationPoints($student, $selectedSemester);
                $achievementPoints = $pointService->getAchievementPoints($student, $selectedSemester);
                $netPoints = $pointService->getNetPoints($student, $selectedSemester);
                $riskStatus = $earlyWarningService->evaluateRiskStatus($student, $selectedSemester);
            }
        }

        $eventsQuery = $student->disciplineEvents()
            ->with(['violation', 'achievement', 'recordedBy', 'semester'])
            ->when($selectedSemesterId, function ($query, $semesterId) {
                return $query->where('semester_id', $semesterId);
            })
            ->when($request->input('event_type'), function ($query, $type) {
                return $query->where('event_type', $type);
            })
            ->orderBy('event_date', 'desc')
            ->orderBy('created_at', 'desc');

        $events = $eventsQuery->paginate(15)->withQueryString();

        return Inertia::render('Students/Show', [
            'student' => $student,
            'semesters' => $semesters,
            'selectedSemesterId' => (int) $selectedSemesterId,
            'summary' => [
                'violation_points' => $violationPoints,
                'achievement_points' => $achievementPoints,
                'net_points' => $netPoints,
                'risk_status' => $riskStatus,
            ],
            'events' => $events,
            'filters' => $request->only('semester_id', 'event_type')
        ]);
    }

    public function printCard(Request $request, Student $student, StudentPointService $pointService, EarlyWarningEngineService $earlyWarningService)
    {
        $student->load(['classRoom.academicYear', 'school']);
        
        $user = auth()->user();
        if ($user && $user->hasRole('Teacher') && !$user->hasRole('Super Admin') && !$user->hasRole('Tata Tertib') && !$user->hasRole('BK')) {
            $teacher = \App\Models\Teacher::where('user_id', $user->id)->first();
            if (!$teacher) {
                abort(403, 'Akses ditolak: Anda belum terdaftar sebagai Guru.');
            }
            $classIds = $teacher->homeroomClasses()->pluck('id');
            if (!$classIds->contains($student->class_room_id)) {
                abort(403, 'Akses ditolak: Siswa ini bukan berada di kelas perwalian Anda.');
            }
        }
        
        $activeSemester = Semester::whereIn('status', ['Active', 'aktif', 'Aktif'])->first();
        $selectedSemesterId = $request->input('semester_id');
        
        if (!$selectedSemesterId && $activeSemester) {
            $selectedSemesterId = $activeSemester->id;
        }

        $selectedSemester = Semester::find($selectedSemesterId);
        if (!$selectedSemester) {
            abort(404, 'Semester tidak ditemukan.');
        }

        $violationPoints = 0;
        $achievementPoints = 0;
        $netPoints = 0;
        $riskStatus = 'NORMAL';
        
        if ($selectedSemester->status === 'Closed') {
            $snapshot = \App\Models\SemesterSnapshot::where('student_id', $student->id)
                            ->where('semester_id', $selectedSemester->id)
                            ->first();
                            
            if (!$snapshot) {
                abort(404, 'Data riwayat snapshot tidak ditemukan untuk semester yang sudah ditutup ini. Hubungi administrator.');
            }
            
            $violationPoints = $snapshot->violation_points;
            $achievementPoints = $snapshot->achievement_points;
            $netPoints = $snapshot->net_points;
            $riskStatus = 'Selesai (Diarsipkan)';
        } else {
            $violationPoints = $pointService->getViolationPoints($student, $selectedSemester);
            $achievementPoints = $pointService->getAchievementPoints($student, $selectedSemester);
            $netPoints = $pointService->getNetPoints($student, $selectedSemester);
            $riskStatus = $earlyWarningService->evaluateRiskStatus($student, $selectedSemester);
        }

        $events = $student->disciplineEvents()
            ->with(['violation', 'achievement'])
            ->where('semester_id', $selectedSemester->id)
            ->orderBy('event_date', 'asc')
            ->get();
            
        $school = $student->school ?? \App\Models\School::first();
        $headmaster = $school->principal_name ?? '.......................';

        return Inertia::render('Students/PrintCard', [
            'student' => $student,
            'semester' => $selectedSemester,
            'school' => $school,
            'headmaster' => $headmaster,
            'summary' => [
                'violation_points' => $violationPoints,
                'achievement_points' => $achievementPoints,
                'net_points' => $netPoints,
                'risk_status' => $riskStatus,
            ],
            'events' => $events,
            'printDate' => now()->translatedFormat('d F Y'),
        ]);
    }

    public function edit(Student $student)
    {
        $classRooms = ClassRoom::whereIn('status', ['Active', 'active'])->orderBy('name')->get(['id', 'name']);
        return Inertia::render('Students/Form', [
            'student' => $student,
            'classRooms' => $classRooms
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'nis' => 'required|string|max:50|unique:students,nis,' . $student->id,
            'name' => 'required|string|max:255',
            'class_room_id' => 'required|exists:class_rooms,id',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:50',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $student->update($validated);
        \App\Services\AuditService::logUpdated($student, 'Memperbarui data siswa: ' . $student->name);

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        \App\Services\AuditService::logDeleted($student, 'Menghapus data siswa: ' . $student->name);
        $student->delete();

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil dihapus.');
    }
}
