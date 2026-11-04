<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Services\AuditService;
use App\Services\Document\DocumentDefinitionRegistry;
use App\Services\Document\DocumentType;
use App\Services\LetterService;
use App\Services\SanctionEngineService;
use App\Services\StudentPointService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LetterController extends Controller
{
    use AuthorizesRequests;

    protected LetterService $letterService;

    protected StudentPointService $pointService;

    protected SanctionEngineService $sanctionService;

    public function __construct(
        LetterService $letterService,
        StudentPointService $pointService,
        SanctionEngineService $sanctionService
    ) {
        $this->letterService = $letterService;
        $this->pointService = $pointService;
        $this->sanctionService = $sanctionService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Letter::class);

        return app(ReportController::class)->index($request->merge(['tab' => 'template']));
    }

    public function updateTemplate(Request $request)
    {
        $this->authorize('updateTemplate', Letter::class);

        $school = School::firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'npsn' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'email' => 'nullable|string|max:100',
            'website' => 'nullable|string|max:255',
            'principal_name' => 'nullable|string|max:255',
            'principal_nip' => 'nullable|string|max:50',
            'signer_title' => 'nullable|string|max:100',
            'letter_city' => 'nullable|string|max:100',
            'classification_code' => 'nullable|string|max:50',
            'school_code' => 'nullable|string|max:100',
            'template_style' => 'nullable|string|in:formal_indonesia,formal_minimal',
            'show_npsn' => 'nullable|boolean',
            'show_phone' => 'nullable|boolean',
            'show_email' => 'nullable|boolean',
            'show_website' => 'nullable|boolean',
            'show_signature_image' => 'nullable|boolean',
            'show_stamp' => 'nullable|boolean',
            'logo' => 'nullable|image|max:2048',
            'signature' => 'nullable|image|max:2048',
            'stamp' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('schools', 'public');
            $validated['logo_path'] = Storage::url($logoPath);
        }

        if ($request->hasFile('signature')) {
            $sigPath = $request->file('signature')->store('schools', 'public');
            $validated['signature_path'] = Storage::url($sigPath);
        }

        if ($request->hasFile('stamp')) {
            $stampPath = $request->file('stamp')->store('schools', 'public');
            $validated['stamp_path'] = Storage::url($stampPath);
        }

        unset($validated['logo'], $validated['signature'], $validated['stamp']);

        $school->update($validated);
        AuditService::logUpdated($school, 'Memperbarui template laporan dan surat');

        return redirect()->back()->with('success', 'Template laporan berhasil disimpan.');
    }

    public function create(Request $request)
    {
        $this->authorize('create', Letter::class);

        $studentId = $request->get('student_id');
        $initialType = $request->get('type', DocumentType::PARENT_NOTIFICATION);

        $selectedStudent = null;
        $studentDisciplineSummary = null;

        $semester = Semester::where('status', 'Active')->first() ?? Semester::latest()->first();

        if ($studentId) {
            $selectedStudent = Student::with(['classRoom.homeroomTeacher', 'school'])->find($studentId);
            if ($selectedStudent && $semester) {
                $studentDisciplineSummary = $this->buildStudentSummary($selectedStudent, $semester);
            }
        }

        $students = Student::with('classRoom')
            ->whereIn('status', ['Active', 'active'])
            ->orderBy('name')
            ->get();

        $school = School::first() ?? new School([
            'name' => 'SMA Negeri 1 Sambit',
            'city' => 'Ponorogo',
            'signer_title' => 'Kepala Sekolah',
        ]);

        return Inertia::render('Letters/Create', [
            'students' => $students,
            'selectedStudent' => $selectedStudent,
            'studentDisciplineSummary' => $studentDisciplineSummary,
            'documentDefinitions' => DocumentDefinitionRegistry::getDefinitions(),
            'initialType' => $initialType,
            'school' => $school,
            'semester' => $semester,
        ]);
    }

    public function getStudentData(Student $student)
    {
        $this->authorize('create', Letter::class);
        $semester = Semester::where('status', 'Active')->first() ?? Semester::latest()->first();

        if (! $semester) {
            return response()->json(['error' => 'Semester aktif tidak ditemukan'], 404);
        }

        $summary = $this->buildStudentSummary($student, $semester);

        return response()->json($summary);
    }

    protected function buildStudentSummary(Student $student, Semester $semester): array
    {
        $violationPoints = $this->pointService->getViolationPoints($student, $semester);
        $achievementPoints = $this->pointService->getAchievementPoints($student, $semester);
        $netPoints = $this->pointService->getNetPoints($student, $semester);
        $sanction = $this->sanctionService->evaluate($student, $semester);

        $recentEvents = $student->disciplineEvents()
            ->where('semester_id', $semester->id)
            ->with(['violation', 'achievement'])
            ->latest('event_date')
            ->take(5)
            ->get()
            ->map(function ($event) {
                return [
                    'date' => $event->event_date->format('d/m/Y'),
                    'description' => $event->event_type === 'violation'
                        ? ($event->violation ? $event->violation->name : 'Pelanggaran')
                        : ($event->achievement ? $event->achievement->name : 'Prestasi'),
                    'points' => $event->points_effect,
                ];
            });

        return [
            'id' => $student->id,
            'nis' => $student->nis,
            'name' => $student->name,
            'parent_name' => $student->parent_name,
            'parent_phone' => $student->parent_phone,
            'class_name' => $student->classRoom?->name ?? 'Tanpa Kelas',
            'homeroom_teacher' => $student->classRoom?->homeroomTeacher?->name ?? '-',
            'violation_points' => $violationPoints,
            'achievement_points' => $achievementPoints,
            'net_points' => $netPoints,
            'sanction_level' => $sanction?->level,
            'sanction_action' => $sanction?->action,
            'recent_events' => $recentEvents,
        ];
    }

    public function store(Request $request)
    {
        $this->authorize('create', Letter::class);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|string',
            'subject' => 'nullable|string|max:255',
            // Type-specific optional/configurable fields
            'incident_summary' => 'nullable|string|max:1000',
            'school_action' => 'nullable|string|max:1000',
            'expected_follow_up' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:500',
            'problem_summary' => 'nullable|string|max:1000',
            'student_commitments' => 'nullable|string|max:1500',
            'guidance_plan' => 'nullable|string|max:1000',
            'evaluation_period' => 'nullable|string|max:100',
            'sanction_consequence' => 'nullable|string|max:1000',
            'call_level' => 'nullable|string|in:call_1,call_2,call_3,call_general,parent_call',
            'reason' => 'nullable|string|max:1000',
            'meeting_day_date' => 'nullable|string|max:100',
            'meeting_time' => 'nullable|string|max:100',
            'meeting_venue' => 'nullable|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'include_achievements' => 'nullable',
            'evaluation_notes' => 'nullable|string|max:1000',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $semester = Semester::where('status', 'Active')->firstOrFail();
        $school = School::firstOrFail();

        $callLevel = $validated['call_level'] ?? $validated['type'];

        // Enforce required signatory configuration
        if ($callLevel === 'call_1' && empty($school->counseling_officer_name)) {
            return back()->withErrors(['type' => 'Nama Guru BK / Koordinator BK belum diatur pada Master Data Sekolah.']);
        }
        if ($callLevel === 'call_2' && empty($school->vice_principal_name)) {
            return back()->withErrors(['type' => 'Nama Wakil Kepala Sekolah Bidang Kesiswaan belum diatur pada Master Data Sekolah.']);
        }
        if (in_array($callLevel, ['call_3', 'parent_call']) && empty($school->principal_name)) {
            return back()->withErrors(['type' => 'Nama Kepala Sekolah belum diatur pada Master Data Sekolah.']);
        }

        $letter = $this->letterService->generateAndStore(
            $student,
            $semester,
            $school,
            $validated['type'],
            $validated,
            Auth::user()
        );

        AuditService::logCreated($letter, 'Menerbitkan dokumen kedisiplinan: '.$letter->letter_number);

        return redirect()->route('letters.index')->with('success', 'Dokumen resmi berhasil diterbitkan.');
    }

    public function show(Letter $letter)
    {
        $this->authorize('view', $letter);
        $path = storage_path('app/'.$letter->pdf_path);

        // Fallback for legacy files
        if (! file_exists($path)) {
            $path = storage_path('app/public/'.$letter->pdf_path);
        }

        if (! file_exists($path)) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    public function download(Letter $letter)
    {
        $this->authorize('view', $letter);
        $path = storage_path('app/'.$letter->pdf_path);

        if (! file_exists($path)) {
            $path = storage_path('app/public/'.$letter->pdf_path);
        }

        if (! file_exists($path)) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        $downloadName = str_replace(['/', '\\'], '_', $letter->letter_number).'.pdf';

        return response()->download($path, $downloadName);
    }

    public function exportWord(Letter $letter)
    {
        $this->authorize('view', $letter);
        $student = $letter->student;
        $semester = $letter->semester;
        $customPayload = $letter->metadata['custom_payload'] ?? [];

        $letterData = $this->letterService->buildLetterData($student, $semester, $letter->type, $customPayload);
        $letterData['letter_number'] = $letter->letter_number;

        $view = view('word.letter', $letterData)->render();
        $fileName = 'surat_'.str_replace(['/', '\\'], '_', $letter->letter_number).'.doc';

        return response($view)
            ->header('Content-Type', 'application/msword; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
    }
}
