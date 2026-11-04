<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FollowUpController extends Controller
{
    public function index(Request $request)
    {
        $query = FollowUp::with(['student.classRoom', 'assignedTo', 'disciplineEvent', 'counselingRecord']);

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('action_plan', 'ilike', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('name', 'ilike', "%{$search}%")
                            ->orWhere('nis', 'ilike', "%{$search}%");
                    });
            });
        }

        // Student filter
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        // Status tab filtering
        $statusFilter = $request->input('status', 'all');
        $today = Carbon::today()->toDateString();

        if ($statusFilter === 'overdue') {
            $query->whereNotIn('status', ['completed', 'cancelled'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $today);
        } elseif ($statusFilter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($statusFilter === 'in_progress') {
            $query->where('status', 'in_progress');
        } elseif ($statusFilter === 'completed') {
            $query->where('status', 'completed');
        }

        // Ordering: Overdue/Pending with closest due date first
        $followUps = $query->orderByRaw("
            CASE 
                WHEN status IN ('pending', 'in_progress') AND due_date < '{$today}' THEN 1
                WHEN status = 'in_progress' THEN 2
                WHEN status = 'pending' THEN 3
                ELSE 4
            END ASC
        ")->orderBy('due_date', 'asc')->latest()->paginate(15)->withQueryString();

        // Calculate counts
        $counts = [
            'all' => FollowUp::count(),
            'pending' => FollowUp::where('status', 'pending')->count(),
            'in_progress' => FollowUp::where('status', 'in_progress')->count(),
            'completed' => FollowUp::where('status', 'completed')->count(),
            'overdue' => FollowUp::whereNotIn('status', ['completed', 'cancelled'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $today)
                ->count(),
        ];

        // Active students for quick selection
        $students = Student::with('classRoom')
            ->whereIn('status', ['Active', 'aktif', 'Aktif'])
            ->orderBy('name')
            ->get(['id', 'nis', 'name', 'class_room_id']);

        // Users for assignment (Teachers, BK, Admins)
        $users = User::orderBy('name')->get(['id', 'name']);

        return Inertia::render('FollowUps/Index', [
            'followUps' => $followUps,
            'counts' => $counts,
            'filters' => $request->only(['search', 'status', 'student_id']),
            'students' => $students,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'title' => 'required|string|max:255',
            'action_plan' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'discipline_event_id' => 'nullable|exists:discipline_events,id',
            'counseling_record_id' => 'nullable|exists:counseling_records,id',
        ]);

        $validated['created_by'] = auth()->id();
        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        $followUp = FollowUp::create($validated);
        AuditService::logCreated($followUp, "Menambahkan tindak lanjut baru: {$followUp->title}");

        return redirect()->back()->with('success', 'Tindak lanjut berhasil ditambahkan.');
    }

    public function update(Request $request, FollowUp $followUp)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'action_plan' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'outcome' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validated['status'] === 'completed' && $followUp->status !== 'completed') {
            $validated['completed_at'] = now();
        } elseif ($validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }

        $followUp->update($validated);
        AuditService::logUpdated($followUp, "Memperbarui tindak lanjut: {$followUp->title}");

        return redirect()->back()->with('success', 'Tindak lanjut berhasil diperbarui.');
    }

    public function destroy(FollowUp $followUp)
    {
        AuditService::logDeleted($followUp, "Menghapus tindak lanjut: {$followUp->title}");
        $followUp->delete();

        return redirect()->back()->with('success', 'Tindak lanjut berhasil dihapus.');
    }
}
