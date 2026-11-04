<?php

namespace App\Http\Controllers;

use App\Models\CounselingRecord;
use App\Models\Semester;
use App\Models\Student;
use App\Services\AuditService;
use App\Services\EarlyWarningEngineService;
use App\Services\StudentPointService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CounselingRecordController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', CounselingRecord::class);

        $user = auth()->user();
        $query = CounselingRecord::with(['student.classRoom', 'counselor', 'semester']);

        // Data Scoping
        if ($user->hasRole('BK')) {
            $query->where('counselor_id', $user->id);
        } elseif ($user->hasRole('Super Admin') || $user->hasRole('Principal')) {
            $query->where('confidentiality_level', '!=', 'strict');
        } else {
            abort(403);
        }

        // Filtering
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $records = $query->latest('date_scheduled')->paginate(10)->withQueryString();

        $students = Student::with('classRoom')
            ->whereIn('status', ['Active', 'aktif', 'Aktif'])
            ->orderBy('name')
            ->get(['id', 'name', 'nis', 'class_room_id']);

        return Inertia::render('CounselingRecords/Index', [
            'records' => $records,
            'students' => $students,
            'can_create' => $user->can('create', CounselingRecord::class),
            'filters' => $request->only(['category', 'status', 'search']),
            'categories' => [
                'Konseling', 'Bimbingan Pribadi', 'Bimbingan Sosial',
                'Akademik', 'Perilaku dan kedisiplinan', 'Sosial dan pertemanan',
                'Karier dan pendidikan lanjutan', 'Kesejahteraan pribadi',
                'Bullying atau kekerasan', 'Lainnya',
            ],
            'statuses' => ['scheduled', 'in_progress', 'resolved', 'referred', 'cancelled'],
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', CounselingRecord::class);

        $activeSemester = Semester::whereIn('status', ['Active', 'aktif', 'Aktif'])->first();
        if (! $activeSemester) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
        }

        $students = Student::with('classRoom')->whereIn('status', ['Active', 'aktif', 'Aktif'])->get();

        return Inertia::render('CounselingRecords/Create', [
            'students' => $students,
            'default_student_id' => $request->query('student_id') ? (int) $request->query('student_id') : null,
            'categories' => [
                'Konseling', 'Bimbingan Pribadi', 'Bimbingan Sosial',
                'Akademik', 'Perilaku dan kedisiplinan', 'Sosial dan pertemanan',
                'Karier dan pendidikan lanjutan', 'Kesejahteraan pribadi',
                'Bullying atau kekerasan', 'Lainnya',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', CounselingRecord::class);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'date_scheduled' => 'required|date',
            'category' => 'nullable|string|max:255',
            'reason' => 'nullable|string',
            'summary' => 'nullable|string',
            'original_recorder_name' => 'nullable|string|max:255',
            'status' => 'nullable|in:scheduled,in_progress,resolved,referred,cancelled',
            'confidentiality_level' => 'nullable|in:normal,strict',
            'follow_up_action' => 'nullable|string',
        ]);

        $activeSemester = Semester::whereIn('status', ['Active', 'aktif', 'Aktif'])->first();
        if (! $activeSemester) {
            return back()->with('error', 'Tidak ada semester aktif. Harap atur semester aktif terlebih dahulu.');
        }

        $user = Auth::user();

        $record = CounselingRecord::create([
            'semester_id' => $activeSemester->id,
            'student_id' => $validated['student_id'],
            'counselor_id' => $user->id,
            'original_recorder_name' => ! empty($validated['original_recorder_name']) ? $validated['original_recorder_name'] : ($user->name ?? 'Petugas BK'),
            'date_scheduled' => $validated['date_scheduled'],
            'date_completed' => ! empty($validated['summary']) ? now()->toDateString() : null,
            'category' => ! empty($validated['category']) ? $validated['category'] : 'Konseling',
            'reason' => ! empty($validated['reason']) ? $validated['reason'] : 'Sesi pembinaan kedisiplinan dan karakter siswa',
            'summary' => $validated['summary'] ?? null,
            'follow_up_action' => $validated['follow_up_action'] ?? null,
            'status' => $validated['status'] ?? (! empty($validated['summary']) ? 'resolved' : 'scheduled'),
            'confidentiality_level' => $validated['confidentiality_level'] ?? 'normal',
            'created_by' => $user->id,
        ]);

        AuditService::logCreated($record, 'Mencatat sesi pembinaan siswa baru');

        return redirect()->route('counseling-records.index')->with('success', 'Catatan pembinaan berhasil disimpan.');
    }

    public function show(CounselingRecord $counselingRecord, StudentPointService $pointService, EarlyWarningEngineService $earlyWarningService)
    {
        $this->authorize('view', $counselingRecord);
        $counselingRecord->load([
            'student.classRoom.homeroomTeacher',
            'counselor',
            'semester.academicYear',
        ]);

        $user = Auth::user();
        $student = $counselingRecord->student;
        $semester = $counselingRecord->semester ?: Semester::whereIn('status', ['Active', 'aktif', 'Aktif'])->first();

        $studentSummary = null;
        if ($student && $semester) {
            $studentSummary = [
                'violation_points' => $pointService->getViolationPoints($student, $semester),
                'achievement_points' => $pointService->getAchievementPoints($student, $semester),
                'net_points' => $pointService->getNetPoints($student, $semester),
                'risk_status' => $earlyWarningService->evaluateRiskStatus($student, $semester),
            ];
        }

        return Inertia::render('CounselingRecords/Show', [
            'record' => $counselingRecord,
            'studentSummary' => $studentSummary,
            'can' => [
                'update' => $user ? $user->can('update', $counselingRecord) : false,
                'delete' => $user ? $user->can('delete', $counselingRecord) : false,
            ],
        ]);
    }

    public function edit(CounselingRecord $counselingRecord)
    {
        $this->authorize('update', $counselingRecord);
        $counselingRecord->load(['student.classRoom', 'counselor', 'semester.academicYear']);

        return Inertia::render('CounselingRecords/Edit', [
            'record' => $counselingRecord,
            'categories' => [
                'Konseling', 'Bimbingan Pribadi', 'Bimbingan Sosial',
                'Akademik', 'Perilaku dan kedisiplinan', 'Sosial dan pertemanan',
                'Karier dan pendidikan lanjutan', 'Kesejahteraan pribadi',
                'Bullying atau kekerasan', 'Lainnya',
            ],
            'statuses' => ['scheduled', 'in_progress', 'resolved', 'referred', 'cancelled'],
        ]);
    }

    public function update(Request $request, CounselingRecord $counselingRecord)
    {
        $this->authorize('update', $counselingRecord);

        if ($counselingRecord->semester && $counselingRecord->semester->status === 'Closed') {
            return back()->with('error', 'Tidak dapat mengubah catatan pada semester yang sudah ditutup.');
        }

        $validated = $request->validate([
            'student_id' => 'sometimes|required|exists:students,id',
            'date_scheduled' => 'required|date',
            'date_completed' => 'nullable|date',
            'category' => 'nullable|string|max:255',
            'reason' => 'nullable|string',
            'summary' => 'nullable|string',
            'original_recorder_name' => 'nullable|string|max:255',
            'status' => 'nullable|in:scheduled,in_progress,resolved,referred,cancelled',
            'confidentiality_level' => 'nullable|in:normal,strict',
            'follow_up_action' => 'nullable|string',
        ]);

        $updateData = [
            'date_scheduled' => $validated['date_scheduled'],
            'category' => ! empty($validated['category']) ? $validated['category'] : ($counselingRecord->category ?: 'Konseling'),
            'reason' => $validated['reason'] ?? $counselingRecord->reason,
            'summary' => $validated['summary'] ?? $counselingRecord->summary,
            'follow_up_action' => $validated['follow_up_action'] ?? $counselingRecord->follow_up_action,
            'status' => $validated['status'] ?? $counselingRecord->status,
            'confidentiality_level' => $validated['confidentiality_level'] ?? $counselingRecord->confidentiality_level,
            'updated_by' => Auth::id(),
        ];

        if (isset($validated['student_id'])) {
            $updateData['student_id'] = $validated['student_id'];
        }

        if (isset($validated['original_recorder_name'])) {
            $updateData['original_recorder_name'] = $validated['original_recorder_name'];
        }

        if (! empty($validated['date_completed'])) {
            $updateData['date_completed'] = $validated['date_completed'];
        } elseif (! empty($validated['summary']) && empty($counselingRecord->date_completed)) {
            $updateData['date_completed'] = now()->toDateString();
        }

        $counselingRecord->update($updateData);
        AuditService::logUpdated($counselingRecord, 'Memperbarui catatan pembinaan siswa');

        return redirect()->route('counseling-records.index')->with('success', 'Catatan pembinaan berhasil diperbarui.');
    }

    public function destroy(CounselingRecord $counselingRecord)
    {
        $this->authorize('delete', $counselingRecord);

        if ($counselingRecord->semester->status === 'Closed') {
            return back()->with('error', 'Tidak dapat menghapus catatan pada semester yang sudah ditutup.');
        }

        AuditService::logDeleted($counselingRecord, 'Menghapus catatan konseling siswa');
        $counselingRecord->delete();

        return redirect()->route('counseling-records.index')->with('success', 'Catatan konseling berhasil dihapus.');
    }
}
