<?php

namespace App\Http\Controllers;

use App\Models\DisciplineEvent;
use App\Models\Student;
use App\Models\Violation;
use App\Models\Achievement;
use App\Models\Semester;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DisciplineEventController extends Controller
{
    public function index(Request $request)
    {
        $query = DisciplineEvent::with(['student.classRoom', 'violation', 'achievement', 'recordedBy', 'semester']);

        $user = auth()->user();
        if ($user && $user->hasRole('Teacher') && !$user->hasRole('Super Admin') && !$user->hasRole('Tata Tertib') && !$user->hasRole('BK')) {
            $teacher = \App\Models\Teacher::where('user_id', $user->id)->first();
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
        if (!$activeSemester) {
            return redirect()->route('discipline-events.index')->with('error', 'Tidak ada semester aktif. Harap atur semester aktif terlebih dahulu.');
        }

        $studentsQuery = Student::with('classRoom')->whereIn('status', ['Active', 'aktif', 'Aktif']);
        
        // Removed teacher homeroom restriction for dropdown so all teachers can report any student
        
        $students = $studentsQuery->orderBy('name')->get(['id', 'name', 'nis', 'class_room_id']);
        $violations = Violation::orderBy('code')->get(['id', 'code', 'name', 'points']);
        $achievements = Achievement::orderBy('code')->get(['id', 'code', 'name', 'deduction_points as points']);

        return Inertia::render('DisciplineEvents/Create', [
            'students' => $students,
            'violations' => $violations,
            'achievements' => $achievements,
            'activeSemester' => $activeSemester,
            'defaultStudentId' => $request->student_id ?? ''
        ]);
    }

    public function store(Request $request)
    {
        $activeSemester = Semester::whereIn('status', ['Active', 'aktif', 'Aktif'])->first();
        if (!$activeSemester) {
            return back()->with('error', 'Tidak ada semester aktif.');
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'event_date' => 'required|date',
            'event_type' => 'required|in:violation,achievement',
            'violation_id' => 'required_if:event_type,violation|nullable|exists:violations,id',
            'achievement_id' => 'required_if:event_type,achievement|nullable|exists:achievements,id',
            'description' => 'nullable|string',
            'follow_up' => 'nullable|string',
        ]);

        $user = auth()->user();
        if ($user && $user->hasRole('Teacher') && !$user->hasRole('Super Admin') && !$user->hasRole('Tata Tertib')) {
            $teacher = \App\Models\Teacher::where('user_id', $user->id)->first();
            if (!$teacher) {
                abort(403, 'Akses ditolak: Anda belum terdaftar sebagai Guru.');
            }
            // Removed homeroom check. All teachers can record events.
        }

        $points = 0;
        if ($validated['event_type'] === 'violation') {
            $violation = Violation::find($validated['violation_id']);
            $points = $violation->points;
            $validated['achievement_id'] = null;
        } else {
            $achievement = Achievement::find($validated['achievement_id']);
            // Convert to negative value for achievement
            $points = -abs($achievement->deduction_points);
            $validated['violation_id'] = null;
        }

        $validated['semester_id'] = $activeSemester->id;
        $validated['points_effect'] = $points; // Never trust user input, overwrite from DB.
        $validated['recorded_by'] = Auth::id();

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            $event = DisciplineEvent::create($validated);
            $type = $event->event_type === 'violation' ? 'pelanggaran' : 'prestasi';
            \App\Services\AuditService::logCreated($event, "Mencatat {$type} siswa");
        });

        return redirect()->route('discipline-events.index')->with('success', 'Catatan ' . ($validated['event_type'] === 'violation' ? 'pelanggaran' : 'prestasi') . ' berhasil disimpan.');
    }

    public function destroy(DisciplineEvent $disciplineEvent)
    {
        if ($disciplineEvent->semester->status === 'Closed') {
            return back()->with('error', 'Tidak dapat menghapus catatan pada semester yang sudah ditutup.');
        }

        $type = $disciplineEvent->event_type === 'violation' ? 'pelanggaran' : 'prestasi';
        \App\Services\AuditService::logDeleted($disciplineEvent, "Menghapus catatan {$type} siswa");
        
        $disciplineEvent->delete();

        return redirect()->route('discipline-events.index')->with('success', 'Catatan berhasil dihapus.');
    }
}
