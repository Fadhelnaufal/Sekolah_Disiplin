<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\ClassRoom;
use App\Models\DisciplineEvent;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Violation;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DisciplineEventController extends Controller
{
    public function index(Request $request)
    {
        $query = DisciplineEvent::with(['student.classRoom', 'violation', 'achievement', 'recordedBy', 'semester']);

        $user = auth()->user();
        if ($user && $user->hasRole('Teacher') && ! $user->hasAnyRole(['Super Admin', 'Tata Tertib', 'BK', 'Principal'])) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $classIds = $teacher->homeroomClasses()->pluck('id');
                $query->whereHas('student', function ($q) use ($classIds) {
                    $q->whereIn('class_room_id', $classIds);
                });
            } else {
                $query->where('id', 0);
            }
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->input('search');
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('nis', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('event_type') && $request->event_type != '') {
            $query->where('event_type', $request->event_type);
        }

        $events = $query->orderBy('event_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('DisciplineEvents/Index', [
            'events' => $events,
            'filters' => $request->only('search', 'event_type'),
        ]);
    }

    public function create(Request $request)
    {
        // Require active semester
        $activeSemester = Semester::whereIn('status', ['Active', 'aktif', 'Aktif'])->first();
        if (! $activeSemester) {
            return redirect()->route('discipline-events.index')->with('error', 'Tidak ada semester aktif. Harap atur semester aktif terlebih dahulu.');
        }

        $studentsQuery = Student::with('classRoom')->whereIn('status', ['Active', 'aktif', 'Aktif']);

        $students = $studentsQuery->orderBy('name')->get(['id', 'name', 'nis', 'class_room_id', 'parent_name', 'parent_phone']);
        $classes = ClassRoom::orderBy('grade_level')->orderBy('name')->get(['id', 'name', 'grade_level']);
        $violations = Violation::orderBy('code')->get(['id', 'code', 'name', 'points']);
        $achievements = Achievement::orderBy('code')->get(['id', 'code', 'name', 'deduction_points as points']);

        $recentEvents = DisciplineEvent::with(['student.classRoom', 'violation', 'achievement', 'recordedBy'])
            ->latest('event_date')
            ->latest('created_at')
            ->take(8)
            ->get();

        return Inertia::render('DisciplineEvents/Create', [
            'students' => $students,
            'classes' => $classes,
            'violations' => $violations,
            'achievements' => $achievements,
            'activeSemester' => $activeSemester,
            'defaultStudentId' => $request->student_id ?? '',
            'recentEvents' => $recentEvents,
        ]);
    }

    public function store(Request $request)
    {
        $activeSemester = Semester::whereIn('status', ['Active', 'aktif', 'Aktif'])->first();
        if (! $activeSemester) {
            return back()->with('error', 'Tidak ada semester aktif.');
        }

        $validated = $request->validate([
            'student_id' => 'required_without:student_ids|nullable|exists:students,id',
            'student_ids' => 'required_without:student_id|nullable|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'event_date' => 'required|date',
            'event_type' => 'required|in:violation,achievement',
            'violation_id' => 'required_if:event_type,violation|nullable|exists:violations,id',
            'achievement_id' => 'required_if:event_type,achievement|nullable|exists:achievements,id',
            'description' => 'nullable|string',
            'follow_up' => 'nullable|string',
            'original_recorder_name' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|image|max:5120',
        ]);

        $user = auth()->user();
        if ($user && $user->hasRole('Teacher') && ! $user->hasAnyRole(['Super Admin', 'Tata Tertib', 'BK', 'Principal'])) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if (! $teacher) {
                abort(403, 'Akses ditolak: Anda belum terdaftar sebagai Guru.');
            }
        }

        $points = 0;
        if ($validated['event_type'] === 'violation') {
            $violation = Violation::find($validated['violation_id']);
            $points = $violation->points;
            $validated['achievement_id'] = null;
        } else {
            $achievement = Achievement::find($validated['achievement_id']);
            $points = -abs($achievement->deduction_points);
            $validated['violation_id'] = null;
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('discipline-events', 'public');
        }

        $studentIds = ! empty($validated['student_ids'])
            ? array_unique($validated['student_ids'])
            : [$validated['student_id']];

        $recorderName = $validated['original_recorder_name'] ?? (Auth::user()?->name ?? 'Petugas');

        DB::transaction(function () use ($studentIds, $validated, $points, $activeSemester, $attachmentPath, $recorderName) {
            foreach ($studentIds as $sid) {
                $event = DisciplineEvent::create([
                    'student_id' => $sid,
                    'semester_id' => $activeSemester->id,
                    'event_date' => $validated['event_date'],
                    'event_type' => $validated['event_type'],
                    'violation_id' => $validated['violation_id'] ?? null,
                    'achievement_id' => $validated['achievement_id'] ?? null,
                    'points_effect' => $points,
                    'description' => $validated['description'] ?? null,
                    'follow_up' => $validated['follow_up'] ?? null,
                    'recorded_by' => Auth::id(),
                    'original_recorder_name' => $recorderName,
                    'attachment_path' => $attachmentPath,
                ]);

                $type = $event->event_type === 'violation' ? 'pelanggaran' : 'prestasi';
                AuditService::logCreated($event, "Mencatat {$type} siswa");
            }
        });

        $count = count($studentIds);
        $typeName = $validated['event_type'] === 'violation' ? 'pelanggaran' : 'prestasi';
        $message = $count > 1
            ? "Catatan {$typeName} untuk {$count} siswa berhasil disimpan serentak."
            : "Catatan {$typeName} berhasil disimpan.";

        return redirect()->route('discipline-events.index')->with('success', $message);
    }

    public function destroy(DisciplineEvent $disciplineEvent)
    {
        if ($disciplineEvent->semester->status === 'Closed') {
            return back()->with('error', 'Tidak dapat menghapus catatan pada semester yang sudah ditutup.');
        }

        $type = $disciplineEvent->event_type === 'violation' ? 'pelanggaran' : 'prestasi';
        AuditService::logDeleted($disciplineEvent, "Menghapus catatan {$type} siswa");

        $disciplineEvent->delete();

        return redirect()->route('discipline-events.index')->with('success', 'Catatan berhasil dihapus.');
    }
}
