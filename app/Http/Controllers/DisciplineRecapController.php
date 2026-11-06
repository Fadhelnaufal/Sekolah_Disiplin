<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Semester;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\SimpleExcel\SimpleExcelWriter;

class DisciplineRecapController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $semester = null;
        if ($request->filled('semester_id')) {
            $semester = Semester::with('academicYear')->find($request->semester_id);
        }
        if (! $semester) {
            $semester = Semester::with('academicYear')->whereIn('status', ['Active', 'active'])->first();
        }
        if (! $semester) {
            $semester = Semester::with('academicYear')->latest('start_date')->first();
        }

        $classes = ClassRoom::whereIn('status', ['Active', 'active'])->orderBy('name')->get(['id', 'name']);
        $school = School::first();

        $students = collect();
        if ($semester) {
            $classId = $request->input('class_id') ? (int) $request->input('class_id') : null;
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $user = auth()->user();
            $homeroomTeacherId = null;
            if ($user && $user->hasRole('Teacher') && ! $user->hasAnyRole(['Super Admin', 'BK', 'Principal', 'Tata Tertib'])) {
                $homeroomTeacherId = $user->teacher ? $user->teacher->id : -1;
            }

            $rawStudents = $this->reportService->getStudentAggregates($semester, $classId, $homeroomTeacherId, $startDate, $endDate);

            // Compute discipline status for each student
            $rawStudents = $rawStudents->map(function ($student) {
                $net = (int) ($student->net_points ?? 0);
                if ($net >= 50) {
                    $student->discipline_status = 'CRITICAL';
                    $student->discipline_status_label = 'Kritis';
                } elseif ($net >= 25) {
                    $student->discipline_status = 'WARNING';
                    $student->discipline_status_label = 'Waspada';
                } elseif ($net > 0) {
                    $student->discipline_status = 'ATTENTION';
                    $student->discipline_status_label = 'Perhatian';
                } else {
                    $student->discipline_status = 'SAFE';
                    $student->discipline_status_label = 'Aman';
                }

                return $student;
            });

            // Filter search
            if ($request->filled('search')) {
                $search = strtolower($request->input('search'));
                $rawStudents = $rawStudents->filter(function ($s) use ($search) {
                    return str_contains(strtolower($s->name), $search) || str_contains(strtolower($s->nis), $search);
                });
            }

            // Filter min points
            if ($request->filled('min_points')) {
                $minPoints = (int) $request->input('min_points');
                $rawStudents = $rawStudents->filter(function ($s) use ($minPoints) {
                    return ($s->net_points ?? 0) >= $minPoints;
                });
            }

            // Filter status
            if ($request->filled('status')) {
                $statusFilter = strtoupper($request->input('status'));
                $rawStudents = $rawStudents->filter(function ($s) use ($statusFilter) {
                    return ($s->discipline_status ?? '') === $statusFilter;
                });
            }

            $students = $rawStudents->values();
        }

        // Summary Statistics calculated accurately from the active dataset
        $totalStudents = $students->count();
        $totalViolations = (int) $students->sum('violation_count');
        $totalViolationPoints = (int) $students->sum('violation_points');
        $totalAchievements = (int) $students->sum('achievement_count');
        $totalAchievementPoints = (int) abs($students->sum('achievement_points'));
        $totalNetPoints = (int) $students->sum('net_points');
        $needGuidanceCount = $students->filter(fn ($s) => ($s->net_points ?? 0) > 0)->count();
        $safeCount = $students->filter(fn ($s) => ($s->net_points ?? 0) === 0)->count();
        $warningCount = $students->filter(fn ($s) => ($s->net_points ?? 0) >= 25 && ($s->net_points ?? 0) < 50)->count();
        $criticalCount = $students->filter(fn ($s) => ($s->net_points ?? 0) >= 50)->count();

        $summary = [
            'total_students' => $totalStudents,
            'total_violations' => $totalViolations,
            'total_violation_points' => $totalViolationPoints,
            'total_achievements' => $totalAchievements,
            'total_achievement_points' => $totalAchievementPoints,
            'total_net_points' => $totalNetPoints,
            'need_guidance_count' => $needGuidanceCount,
            'safe_count' => $safeCount,
            'warning_count' => $warningCount,
            'critical_count' => $criticalCount,
        ];

        // Handle Exports
        if ($request->input('export') === 'excel' && $semester) {
            $filename = 'Rekap_Disiplin_'.now()->format('Ymd_His').'.xlsx';
            $writer = SimpleExcelWriter::streamDownload($filename);
            foreach ($students as $student) {
                $writer->addRow([
                    'NIS' => "'".$student->nis,
                    'Nama' => "'".$student->name,
                    'Kelas' => $student->classRoom ? $student->classRoom->name : '-',
                    'Total Pelanggaran' => $student->violation_count,
                    'Poin Pelanggaran' => $student->violation_points,
                    'Total Prestasi' => $student->achievement_count,
                    'Poin Prestasi' => $student->achievement_points,
                    'Poin Bersih' => $student->net_points,
                    'Status Disiplin' => $student->discipline_status_label ?? 'Aman',
                ]);
            }

            return $writer->toBrowser();
        }

        if ($request->input('export') === 'pdf' && $semester) {
            $pdf = Pdf::loadView('pdf.reports.monthly', compact('students', 'semester', 'school'));

            return $pdf->download('Rekap_Disiplin_'.($semester->name).'.pdf');
        }

        return Inertia::render('DisciplineRecap/Index', [
            'students' => $students,
            'summary' => $summary,
            'classes' => $classes,
            'current_semester' => $semester,
            'semesters' => Semester::with('academicYear')->orderBy('start_date', 'desc')->get(),
            'filters' => $request->only(['semester_id', 'class_id', 'start_date', 'end_date', 'search', 'min_points', 'status']),
            'school' => $school,
        ]);
    }
}
