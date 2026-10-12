<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CounselingRecord;
use App\Models\Student;
use App\Models\Semester;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $records = $query->latest('date_scheduled')->paginate(10)->withQueryString();

        return Inertia::render('CounselingRecords/Index', [
            'records' => $records,
            'filters' => $request->only(['category', 'status']),
            'categories' => [
                'Akademik', 'Perilaku dan kedisiplinan', 'Sosial dan pertemanan', 
                'Karier dan pendidikan lanjutan', 'Kesejahteraan pribadi', 
                'Bullying atau kekerasan', 'Lainnya'
            ],
            'statuses' => ['scheduled', 'in_progress', 'resolved', 'referred', 'cancelled'],
        ]);
    }

    public function create()
    {
        $this->authorize('create', CounselingRecord::class);
        
        $activeSemester = Semester::whereIn('status', ['Active', 'aktif', 'Aktif'])->first();
        if (!$activeSemester) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
        }

        $students = Student::with('classRoom')->whereIn('status', ['Active', 'aktif', 'Aktif'])->get();

        return Inertia::render('CounselingRecords/Create', [
            'students' => $students,
            'categories' => [
                'Akademik', 'Perilaku dan kedisiplinan', 'Sosial dan pertemanan', 
                'Karier dan pendidikan lanjutan', 'Kesejahteraan pribadi', 
                'Bullying atau kekerasan', 'Lainnya'
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', CounselingRecord::class);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'date_scheduled' => 'required|date',
            'category' => 'required|string',
            'reason' => 'required|string',
            'confidentiality_level' => 'required|in:normal,strict',
        ]);

        $activeSemester = Semester::whereIn('status', ['Active', 'aktif', 'Aktif'])->firstOrFail();

        $record = CounselingRecord::create(array_merge($validated, [
            'semester_id' => $activeSemester->id,
            'counselor_id' => Auth::id(),
            'status' => 'scheduled',
            'created_by' => Auth::id(),
        ]));
        
        \App\Services\AuditService::logCreated($record, "Mencatat jadwal konseling baru");

        return redirect()->route('counseling-records.index')->with('success', 'Jadwal konseling berhasil dibuat.');
    }

    public function show(CounselingRecord $counselingRecord)
    {
        $this->authorize('view', $counselingRecord);
        $counselingRecord->load(['student.classRoom', 'counselor', 'semester']);

        return Inertia::render('CounselingRecords/Show', [
            'record' => $counselingRecord,
        ]);
    }

    public function edit(CounselingRecord $counselingRecord)
    {
        $this->authorize('update', $counselingRecord);
        $counselingRecord->load('student.classRoom');

        return Inertia::render('CounselingRecords/Edit', [
            'record' => $counselingRecord,
            'categories' => [
                'Akademik', 'Perilaku dan kedisiplinan', 'Sosial dan pertemanan', 
                'Karier dan pendidikan lanjutan', 'Kesejahteraan pribadi', 
                'Bullying atau kekerasan', 'Lainnya'
            ],
            'statuses' => ['scheduled', 'in_progress', 'resolved', 'referred', 'cancelled'],
        ]);
    }

    public function update(Request $request, CounselingRecord $counselingRecord)
    {
        $this->authorize('update', $counselingRecord);

        if ($counselingRecord->semester->status === 'Closed') {
            return back()->with('error', 'Tidak dapat mengubah catatan pada semester yang sudah ditutup.');
        }

        $validated = $request->validate([
            'date_scheduled' => 'required|date',
            'date_completed' => 'nullable|date',
            'category' => 'required|string',
            'reason' => 'required|string',
            'summary' => 'nullable|string',
            'follow_up_action' => 'nullable|string',
            'status' => 'required|in:scheduled,in_progress,resolved,referred,cancelled',
            'confidentiality_level' => 'required|in:normal,strict',
        ]);

        $validated['updated_by'] = Auth::id();

        $counselingRecord->update($validated);
        \App\Services\AuditService::logUpdated($counselingRecord, "Memperbarui catatan konseling siswa");

        return redirect()->route('counseling-records.index')->with('success', 'Catatan konseling berhasil diperbarui.');
    }

    public function destroy(CounselingRecord $counselingRecord)
    {
        $this->authorize('delete', $counselingRecord);
        
        if ($counselingRecord->semester->status === 'Closed') {
            return back()->with('error', 'Tidak dapat menghapus catatan pada semester yang sudah ditutup.');
        }

        \App\Services\AuditService::logDeleted($counselingRecord, "Menghapus catatan konseling siswa");
        $counselingRecord->delete();

        return redirect()->route('counseling-records.index')->with('success', 'Catatan konseling berhasil dihapus.');
    }
}
