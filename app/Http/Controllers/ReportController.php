<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Letter;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Services\LetterService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ReportController extends Controller
{
    protected ReportService $reportService;

    protected LetterService $letterService;

    public function __construct(ReportService $reportService, LetterService $letterService)
    {
        $this->reportService = $reportService;
        $this->letterService = $letterService;
    }

    public function index(Request $request)
    {
        $activeSemester = $this->getActiveSemester($request);
        $school = School::first() ?? School::create([
            'name' => 'SMP Malang',
            'address' => 'Jl. Pendidikan No. 1',
            'city' => 'Malang',
            'application_name' => 'Sekolah Disiplin',
        ]);
        if (empty($school->principal_name)) {
            $school->principal_name = auth()->user()?->name ?? 'Kepala Sekolah';
        }
        if (empty($school->letter_city)) {
            $school->letter_city = $school->city ?? 'Malang';
        }
        if (empty($school->classification_code)) {
            $school->classification_code = '421.3';
        }
        if (empty($school->school_code)) {
            $school->school_code = 'SMP-MALANG';
        }
        if (empty($school->template_style)) {
            $school->template_style = 'formal_indonesia';
        }

        $letters = Letter::with(['student.classRoom', 'generatedBy'])->latest()->paginate(15);

        $students = Student::with('classRoom')
            ->whereIn('status', ['Active', 'aktif', 'Aktif'])
            ->orderBy('name')
            ->get();

        $studentsData = $students->map(function ($s) use ($activeSemester) {
            $violationPoints = $activeSemester
                ? (int) $s->disciplineEvents()
                    ->where('semester_id', $activeSemester->id)
                    ->where('event_type', 'violation')
                    ->sum('points_effect')
                : 0;

            $achievementPoints = $activeSemester
                ? (int) abs($s->disciplineEvents()
                    ->where('semester_id', $activeSemester->id)
                    ->where('event_type', 'achievement')
                    ->sum('points_effect'))
                : 0;

            $netPoints = max(0, $violationPoints - $achievementPoints);

            return [
                'id' => $s->id,
                'name' => $s->name,
                'nis' => $s->nis,
                'class_name' => $s->classRoom?->name ?? 'VII A',
                'parent_name' => $s->parent_name ?? 'Nama Orang Tua/Wali',
                'parent_phone' => $s->parent_phone,
                'address' => $s->address ?? ($s->parent_address ?? 'Tempat tinggal siswa'),
                'violation_points' => $violationPoints,
                'achievement_points' => $achievementPoints,
                'net_points' => $netPoints,
            ];
        });

        return Inertia::render('Reports/Index', [
            'students' => $studentsData,
            'school' => $school,
            'activeSemester' => $activeSemester,
            'defaultStudentId' => $request->student_id ? (int) $request->student_id : ($studentsData->first()['id'] ?? null),
            'letters' => $letters,
            'initialTab' => $request->query('tab', 'generate'),
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|string',
            'notes' => 'nullable|string',
            'format' => 'required|in:pdf,word',
            'meeting_day_date' => 'nullable|string',
            'meeting_time' => 'nullable|string',
            'meeting_venue' => 'nullable|string',
            'meeting_purpose' => 'nullable|string',
        ]);

        $student = Student::with('classRoom')->findOrFail($validated['student_id']);
        $semester = $this->getActiveSemester($request) ?? Semester::latest()->firstOrFail();
        $school = School::first() ?? new School([
            'name' => 'SMP Malang',
            'address' => 'Alamat sekolah',
            'city' => 'Malang',
            'principal_name' => auth()->user()?->name ?? 'Kepala Sekolah',
        ]);
        if (empty($school->principal_name)) {
            $school->principal_name = auth()->user()?->name ?? 'Kepala Sekolah';
        }

        $type = $validated['type'];
        $typeTitles = [
            'notification' => 'SURAT PEMBERITAHUAN ORANG TUA/WALI',
            'call_1' => 'SURAT PANGGILAN ORANG TUA/WALI I',
            'call_2' => 'SURAT PANGGILAN ORANG TUA/WALI II',
            'call_3' => 'SURAT PANGGILAN ORANG TUA/WALI III',
            'statement' => 'SURAT PERJANJIAN KEDISIPLINAN SISWA',
            'recap' => 'LAPORAN REKAPITULASI KEDISIPLINAN SISWA',
        ];
        $documentTitle = $typeTitles[$type] ?? 'SURAT PEMBERITAHUAN ORANG TUA/WALI';

        $payload = [
            'notes' => $validated['notes'] ?? null,
            'meeting_day_date' => $validated['meeting_day_date'] ?? null,
            'meeting_time' => $validated['meeting_time'] ?? null,
            'meeting_venue' => $validated['meeting_venue'] ?? null,
            'meeting_purpose' => $validated['meeting_purpose'] ?? ($validated['notes'] ?? null),
            'reason' => $validated['meeting_purpose'] ?? ($validated['notes'] ?? null),
            'call_level' => $type,
        ];

        $letterNumber = $this->letterService->generateLetterNumber($school, in_array($type, ['call_1', 'call_2', 'call_3']) ? $type : 'notification');
        $letterData = $this->letterService->buildLetterData($student, $semester, in_array($type, ['call_1', 'call_2', 'call_3']) ? $type : 'notification', $payload);

        $violationPoints = (int) $student->disciplineEvents()
            ->where('semester_id', $semester->id)
            ->where('event_type', 'violation')
            ->sum('points_effect');
        $achievementPoints = (int) abs($student->disciplineEvents()
            ->where('semester_id', $semester->id)
            ->where('event_type', 'achievement')
            ->sum('points_effect'));

        $letterData['type'] = $type;
        $letterData['notes'] = $validated['notes'] ?? null;
        $letterData['document_title'] = $documentTitle;
        $letterData['letter_number'] = $letterNumber;
        $letterData['violation_points'] = $violationPoints;
        $letterData['achievement_points'] = $achievementPoints;
        $letterData['net_points'] = max(0, $violationPoints - $achievementPoints);

        if ($validated['format'] === 'pdf') {
            $pdf = Pdf::loadView('pdf.letter', $letterData);
            $fileName = 'surat_'.strtolower($type).'_'.$student->nis.'_'.time().'.pdf';

            Letter::create([
                'student_id' => $student->id,
                'semester_id' => $semester->id,
                'type' => in_array($type, ['notification', 'parent_call', 'call_1', 'call_2', 'call_3', 'counseling', 'statement', 'other']) ? $type : 'notification',
                'letter_number' => $letterNumber,
                'subject' => $documentTitle,
                'generated_at' => now(),
                'generated_by' => auth()->id(),
                'status' => 'Generated',
                'metadata' => [
                    'student_name' => $student->name,
                    'nis' => $student->nis,
                    'notes' => $validated['notes'] ?? null,
                    'meeting_day_date' => $validated['meeting_day_date'] ?? null,
                    'meeting_time' => $validated['meeting_time'] ?? null,
                    'meeting_venue' => $validated['meeting_venue'] ?? null,
                ],
            ]);

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            ]);
        }

        // Word format (.doc)
        $fileName = 'surat_'.strtolower($type).'_'.$student->nis.'_'.time().'.doc';
        $wordContent = view('word.letter', $letterData)->render();

        Letter::create([
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'type' => in_array($type, ['notification', 'parent_call', 'call_1', 'call_2', 'call_3', 'counseling', 'statement', 'other']) ? $type : 'notification',
            'letter_number' => $letterNumber,
            'subject' => $documentTitle,
            'generated_at' => now(),
            'generated_by' => auth()->id(),
            'status' => 'Generated',
            'metadata' => [
                'student_name' => $student->name,
                'nis' => $student->nis,
                'notes' => $validated['notes'] ?? null,
                'meeting_day_date' => $validated['meeting_day_date'] ?? null,
                'meeting_time' => $validated['meeting_time'] ?? null,
                'meeting_venue' => $validated['meeting_venue'] ?? null,
                'format' => 'word',
            ],
        ]);

        return response($wordContent, 200, [
            'Content-Type' => 'application/msword; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    protected function getActiveSemester(Request $request)
    {
        if ($request->has('semester_id')) {
            return Semester::with('academicYear')->find($request->semester_id);
        }

        return Semester::with('academicYear')->whereIn('status', ['Active', 'active'])->first();
    }

    protected function getCommonData()
    {
        return [
            'academic_years' => AcademicYear::with('semesters')->orderByDesc('start_date')->get(),
            'classes' => ClassRoom::whereIn('status', ['Active', 'active'])->get(),
            'school' => School::first(),
        ];
    }

    public function monthly(Request $request)
    {
        $semester = $this->getActiveSemester($request);
        if (! $semester) {
            return redirect()->route('dashboard')->with('error', 'Semester aktif tidak ditemukan.');
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $classId = $request->input('class_id');

        // RBAC: If Teacher (Homeroom), force their own classes
        $user = auth()->user();
        $homeroomTeacherId = null;
        if ($user->hasRole('Teacher') && ! $user->hasAnyRole(['Super Admin', 'BK', 'Principal', 'Tata Tertib'])) {
            $homeroomTeacherId = $user->teacher->id ?? -1;
        }

        $students = $this->reportService->getStudentAggregates($semester, $classId, $homeroomTeacherId, $startDate, $endDate);

        if ($request->input('export') === 'excel') {
            return $this->exportMonthlyExcel($students, $semester);
        }

        if ($request->input('export') === 'pdf') {
            return $this->exportMonthlyPdf($students, $semester, $this->getCommonData()['school']);
        }

        return Inertia::render('Reports/Monthly', array_merge($this->getCommonData(), [
            'students' => $students,
            'filters' => $request->only(['semester_id', 'start_date', 'end_date', 'class_id']),
            'current_semester' => $semester,
        ]));
    }

    public function classes(Request $request)
    {
        $semester = $this->getActiveSemester($request);
        if (! $semester) {
            return redirect()->route('dashboard')->with('error', 'Semester aktif tidak ditemukan.');
        }

        $user = auth()->user();
        $homeroomTeacherId = null;
        if ($user->hasRole('Teacher') && ! $user->hasAnyRole(['Super Admin', 'BK', 'Principal', 'Tata Tertib'])) {
            $homeroomTeacherId = $user->teacher->id ?? -1;
        }

        $classes = $this->reportService->getClassAggregates($semester, $homeroomTeacherId);

        if ($request->input('export') === 'excel') {
            return $this->exportClassesExcel($classes, $semester);
        }

        if ($request->input('export') === 'pdf') {
            return $this->exportClassesPdf($classes, $semester, $this->getCommonData()['school']);
        }

        return Inertia::render('Reports/Classes', array_merge($this->getCommonData(), [
            'class_aggregates' => $classes,
            'filters' => $request->only(['semester_id']),
            'current_semester' => $semester,
        ]));
    }

    public function homerooms(Request $request)
    {
        // For homerooms, it's virtually the same logic as classes but grouped or viewed by the homeroom teacher.
        // We'll reuse the class aggregates but provide a view that centers around the teacher.
        return $this->classes($request);
    }

    public function analytics(Request $request)
    {
        $semester = $this->getActiveSemester($request);
        if (! $semester) {
            return redirect()->route('dashboard')->with('error', 'Semester aktif tidak ditemukan.');
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $classId = $request->input('class_id') ? (int) $request->input('class_id') : null;

        // RBAC: If Teacher (Homeroom), force their own classes
        $user = auth()->user();
        $homeroomTeacherId = null;
        if ($user && $user->hasRole('Teacher') && ! $user->hasAnyRole(['Super Admin', 'BK', 'Principal', 'Tata Tertib'])) {
            $homeroomTeacherId = $user->teacher->id ?? -1;
        }

        $monthlyTrend = $this->reportService->getMonthlyTrend(
            $semester,
            $classId,
            $homeroomTeacherId,
            $startDate,
            $endDate,
            true
        );

        $categoryDist = $this->reportService->getCategoryDistribution(
            $semester,
            $classId,
            $homeroomTeacherId,
            $startDate,
            $endDate
        );

        $categorySummary = $this->reportService->getCategorySummary(
            $semester,
            $classId,
            $homeroomTeacherId,
            $startDate,
            $endDate
        );

        $classRecap = $this->reportService->getClassAggregates(
            $semester,
            $homeroomTeacherId
        )->sortByDesc('total_violation_count')->values();

        $followUpDistribution = $this->reportService->getFollowUpStatusDistribution(
            $semester,
            $classId,
            $homeroomTeacherId,
            $startDate,
            $endDate
        );

        $kpiSummary = [
            'total_violations' => (int) $monthlyTrend->sum('violation_count'),
            'total_achievements' => (int) $monthlyTrend->sum('achievement_count'),
            'total_violation_points' => (int) $monthlyTrend->sum('violation_points'),
            'total_achievement_points' => (int) $monthlyTrend->sum('achievement_points'),
            'total_net_points' => max(0, (int) $monthlyTrend->sum('violation_points') - (int) $monthlyTrend->sum('achievement_points')),
            'total_follow_ups' => (int) $followUpDistribution->sum('count'),
        ];

        return Inertia::render('Reports/Analytics', array_merge($this->getCommonData(), [
            'monthly_trend' => $monthlyTrend,
            'category_distribution' => $categoryDist,
            'category_summary' => $categorySummary,
            'class_recap' => $classRecap,
            'follow_up_distribution' => $followUpDistribution,
            'kpi_summary' => $kpiSummary,
            'filters' => $request->only(['semester_id', 'class_id', 'start_date', 'end_date']),
            'current_semester' => $semester,
        ]));
    }

    // --- EXPORTS ---

    protected function exportMonthlyExcel($students, $semester)
    {
        $filename = 'Laporan_Bulanan_'.now()->format('Ymd_His').'.xlsx';
        $writer = SimpleExcelWriter::streamDownload($filename);

        foreach ($students as $student) {
            $writer->addRow([
                'NIS' => "'".$student->nis, // Prevent injection
                'Nama' => "'".$student->name,
                'Kelas' => $student->classRoom ? $student->classRoom->name : '-',
                'Total Pelanggaran' => $student->violation_count,
                'Total Poin Pelanggaran' => $student->violation_points,
                'Total Prestasi' => $student->achievement_count,
                'Total Poin Prestasi' => $student->achievement_points,
                'Poin Bersih' => $student->net_points,
            ]);
        }

        return $writer->toBrowser();
    }

    protected function exportMonthlyPdf($students, $semester, $school)
    {
        $pdf = Pdf::loadView('pdf.reports.monthly', compact('students', 'semester', 'school'));

        return $pdf->download('Laporan_Bulanan.pdf');
    }

    protected function exportClassesExcel($classes, $semester)
    {
        $filename = 'Laporan_Kelas_'.now()->format('Ymd_His').'.xlsx';
        $writer = SimpleExcelWriter::streamDownload($filename);

        foreach ($classes as $class) {
            $writer->addRow([
                'Nama Kelas' => "'".$class->name,
                'Total Siswa Aktif' => $class->students_count,
                'Jumlah Pelanggaran' => $class->total_violation_count,
                'Total Poin Pelanggaran' => $class->total_violation_points,
                'Jumlah Prestasi' => $class->total_achievement_count,
                'Rata-rata Poin Bersih' => $class->average_net_points,
            ]);
        }

        return $writer->toBrowser();
    }

    protected function exportClassesPdf($classes, $semester, $school)
    {
        $pdf = Pdf::loadView('pdf.reports.classes', compact('classes', 'semester', 'school'));

        return $pdf->download('Laporan_Kelas.pdf');
    }
}
