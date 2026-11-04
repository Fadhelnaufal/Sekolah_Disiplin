<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Semester;
use App\Models\SemesterSnapshot;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\AuditService;
use App\Services\EarlyWarningEngineService;
use App\Services\StudentPointService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index(Request $request, StudentPointService $pointService, EarlyWarningEngineService $earlyWarningService)
    {
        $tab = $request->input('tab', 'active');
        $query = $tab === 'draft' ? Student::onlyTrashed()->with('classRoom') : Student::with('classRoom');

        $user = auth()->user();
        if ($user && $user->hasRole('Teacher') && ! $user->hasAnyRole(['Super Admin', 'Tata Tertib', 'BK', 'Principal'])) {
            $teacher = Teacher::where('user_id', $user->id)->first();
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
        $activeCount = Student::count();
        $draftCount = Student::onlyTrashed()->count();

        return Inertia::render('Students/Index', [
            'students' => $paginator,
            'classRooms' => $classRooms,
            'filters' => $request->only('search', 'class_room_id', 'tab'),
            'hasActiveSemester' => $activeSemester !== null,
            'active_count' => $activeCount,
            'draft_count' => $draftCount,
            'current_tab' => $tab,
        ]);
    }

    public function create()
    {
        $classRooms = ClassRoom::whereIn('status', ['Active', 'active'])->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Students/Form', [
            'classRooms' => $classRooms,
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
        AuditService::logCreated($student, 'Mendaftarkan siswa baru: '.$student->name);

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function show(Request $request, Student $student, StudentPointService $pointService, EarlyWarningEngineService $earlyWarningService)
    {
        $student->load('classRoom');

        $user = auth()->user();
        if ($user && $user->hasRole('Teacher') && ! $user->hasAnyRole(['Super Admin', 'Tata Tertib', 'BK', 'Principal'])) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if (! $teacher) {
                abort(403, 'Akses ditolak: Anda belum terdaftar sebagai Guru.');
            }
            $classIds = $teacher->homeroomClasses()->pluck('id');
            if (! $classIds->contains($student->class_room_id)) {
                abort(403, 'Akses ditolak: Siswa ini bukan berada di kelas perwalian Anda.');
            }
        }

        $semesters = Semester::orderBy('start_date', 'desc')->get(['id', 'name', 'status']);
        $activeSemester = Semester::whereIn('status', ['Active', 'aktif', 'Aktif'])->first();

        // If no semester filter is provided, default to active semester or latest semester
        $selectedSemesterId = $request->input('semester_id');
        if (! $selectedSemesterId) {
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

        $counselingRecords = null;
        if ($user && $user->hasAnyRole(['Super Admin', 'BK', 'Principal'])) {
            $counselingQuery = $student->counselingRecords()
                ->with(['counselor', 'semester'])
                ->orderBy('date_scheduled', 'desc')
                ->orderBy('created_at', 'desc');

            if ($user->hasRole('BK')) {
                $counselingQuery->where(function ($q) use ($user) {
                    $q->where('counselor_id', $user->id)
                        ->orWhere('confidentiality_level', '!=', 'strict');
                });
            } else {
                $counselingQuery->where('confidentiality_level', '!=', 'strict');
            }

            $counselingRecords = $counselingQuery->get()->map(function ($record) {
                return [
                    'id' => $record->id,
                    'date_scheduled' => $record->date_scheduled ? $record->date_scheduled->format('Y-m-d') : null,
                    'date_completed' => $record->date_completed ? $record->date_completed->format('Y-m-d') : null,
                    'category' => $record->category,
                    'status' => $record->status,
                    'confidentiality_level' => $record->confidentiality_level,
                    'reason' => $record->reason,
                    'summary' => $record->summary,
                    'follow_up_action' => $record->follow_up_action,
                    'counselor_name' => $record->counselor ? $record->counselor->name : ($record->original_recorder_name ?: 'Guru BK'),
                    'counselor_id' => $record->counselor_id,
                    'semester_name' => $record->semester ? $record->semester->name : null,
                ];
            });
        }

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
            'counselingRecords' => $counselingRecords,
            'filters' => $request->only('semester_id', 'event_type'),
        ]);
    }

    public function printCard(Request $request, Student $student, StudentPointService $pointService, EarlyWarningEngineService $earlyWarningService)
    {
        $student->load(['classRoom.academicYear', 'school']);

        $user = auth()->user();
        if ($user && $user->hasRole('Teacher') && ! $user->hasAnyRole(['Super Admin', 'Tata Tertib', 'BK', 'Principal'])) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if (! $teacher) {
                abort(403, 'Akses ditolak: Anda belum terdaftar sebagai Guru.');
            }
            $classIds = $teacher->homeroomClasses()->pluck('id');
            if (! $classIds->contains($student->class_room_id)) {
                abort(403, 'Akses ditolak: Siswa ini bukan berada di kelas perwalian Anda.');
            }
        }

        $activeSemester = Semester::whereIn('status', ['Active', 'aktif', 'Aktif'])->first();
        $selectedSemesterId = $request->input('semester_id');

        if (! $selectedSemesterId && $activeSemester) {
            $selectedSemesterId = $activeSemester->id;
        }

        $selectedSemester = Semester::find($selectedSemesterId);
        if (! $selectedSemester) {
            abort(404, 'Semester tidak ditemukan.');
        }

        $violationPoints = 0;
        $achievementPoints = 0;
        $netPoints = 0;
        $riskStatus = 'NORMAL';

        if ($selectedSemester->status === 'Closed') {
            $snapshot = SemesterSnapshot::where('student_id', $student->id)
                ->where('semester_id', $selectedSemester->id)
                ->first();

            if (! $snapshot) {
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

        $school = $student->school ?? School::first();
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
            'classRooms' => $classRooms,
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'nis' => 'required|string|max:50|unique:students,nis,'.$student->id,
            'name' => 'required|string|max:255',
            'class_room_id' => 'required|exists:class_rooms,id',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:50',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $student->update($validated);
        AuditService::logUpdated($student, 'Memperbarui data siswa: '.$student->name);

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        AuditService::logDeleted($student, 'Memindahkan data siswa ke Draft Hapus: '.$student->name);
        $student->delete();

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil dipindahkan ke Draft Hapus.');
    }

    public function restore($id)
    {
        $student = Student::onlyTrashed()->findOrFail($id);
        $student->restore();
        AuditService::log('updated', 'Memulihkan data siswa dari Draft Hapus: '.$student->name, $student);

        return redirect()->back()->with('success', 'Data siswa berhasil dipulihkan dari Draft Hapus.');
    }

    public function forceDelete($id)
    {
        $student = Student::onlyTrashed()->findOrFail($id);

        if (SemesterSnapshot::where('student_id', $id)->exists()) {
            return redirect()->back()->with('error', 'Siswa tidak dapat dihapus permanen karena memiliki arsip catatan snapshot semester yang telah ditutup.');
        }

        AuditService::logDeleted($student, 'Menghapus data siswa secara permanen: '.$student->name);
        $student->forceDelete();

        return redirect()->back()->with('success', 'Data siswa telah dihapus secara permanen.');
    }

    public function bulkDraft(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $students = Student::whereIn('id', $validated['student_ids'])->get();
        foreach ($students as $student) {
            AuditService::logDeleted($student, 'Memindahkan siswa ke Draft Hapus: '.$student->name);
            $student->delete();
        }

        return redirect()->back()->with('success', count($students).' siswa berhasil dipindahkan ke Draft Hapus.');
    }

    public function bulkRestore(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array',
        ]);

        $students = Student::onlyTrashed()->whereIn('id', $validated['student_ids'])->get();
        foreach ($students as $student) {
            AuditService::log('updated', 'Memulihkan siswa dari Draft Hapus: '.$student->name, $student);
            $student->restore();
        }

        return redirect()->back()->with('success', count($students).' siswa berhasil dipulihkan dari Draft Hapus.');
    }

    public function updateClassName(Request $request)
    {
        $validated = $request->validate([
            'class_room_id' => 'required|exists:class_rooms,id',
            'name' => 'required|string|max:100',
        ]);

        $classRoom = ClassRoom::findOrFail($validated['class_room_id']);
        $oldName = $classRoom->name;
        $classRoom->update(['name' => $validated['name']]);

        AuditService::logUpdated($classRoom, "Mengubah nama kelas dari {$oldName} menjadi {$classRoom->name}");

        return redirect()->back()->with('success', "Nama kelas berhasil diperbarui menjadi {$classRoom->name}.");
    }

    public function downloadTemplate()
    {
        $filename = 'template_import_siswa.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = ['NO', 'NIS', 'NAMA SISWA', 'KELAS', 'WALI KELAS', 'NAMA ORANG TUA / WALI', 'NO. HP ORANG TUA', 'STATUS', 'KETERANGAN'];
        $sampleRow = ['1', '1001', 'Contoh Siswa', '7A', 'Suryanto', 'Bapak Contoh', '081234567890', 'Aktif', 'Siswa Baru'];

        $callback = function () use ($columns, $sampleRow) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $columns);
            fputcsv($handle, $sampleRow);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
