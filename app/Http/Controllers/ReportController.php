<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\School;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
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
            'school' => School::first()
        ];
    }

    public function monthly(Request $request)
    {
        $semester = $this->getActiveSemester($request);
        if (!$semester) {
            return redirect()->route('dashboard')->with('error', 'Semester aktif tidak ditemukan.');
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $classId = $request->input('class_id');

        // RBAC: If Teacher (Homeroom), force their own classes
        $user = auth()->user();
        $homeroomTeacherId = null;
        if ($user->hasRole('Teacher') && !$user->hasAnyRole(['Super Admin', 'BK', 'Principal', 'Tata Tertib'])) {
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
            'current_semester' => $semester
        ]));
    }

    public function classes(Request $request)
    {
        $semester = $this->getActiveSemester($request);
        if (!$semester) {
            return redirect()->route('dashboard')->with('error', 'Semester aktif tidak ditemukan.');
        }

        $user = auth()->user();
        $homeroomTeacherId = null;
        if ($user->hasRole('Teacher') && !$user->hasAnyRole(['Super Admin', 'BK', 'Principal', 'Tata Tertib'])) {
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
            'current_semester' => $semester
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
        if (!$semester) {
            return redirect()->route('dashboard')->with('error', 'Semester aktif tidak ditemukan.');
        }

        $monthlyTrend = $this->reportService->getMonthlyTrend($semester);
        $categoryDist = $this->reportService->getCategoryDistribution($semester);

        return Inertia::render('Reports/Analytics', array_merge($this->getCommonData(), [
            'monthly_trend' => $monthlyTrend,
            'category_distribution' => $categoryDist,
            'filters' => $request->only(['semester_id']),
            'current_semester' => $semester
        ]));
    }

    // --- EXPORTS ---

    protected function exportMonthlyExcel($students, $semester)
    {
        $filename = 'Laporan_Bulanan_' . now()->format('Ymd_His') . '.xlsx';
        $writer = SimpleExcelWriter::streamDownload($filename);
        
        foreach ($students as $student) {
            $writer->addRow([
                'NIS' => "'" . $student->nis, // Prevent injection
                'Nama' => "'" . $student->name,
                'Kelas' => $student->classRoom ? $student->classRoom->name : '-',
                'Total Pelanggaran' => $student->violation_count,
                'Total Poin Pelanggaran' => $student->violation_points,
                'Total Prestasi' => $student->achievement_count,
                'Total Poin Prestasi' => $student->achievement_points,
                'Poin Bersih' => $student->net_points
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
        $filename = 'Laporan_Kelas_' . now()->format('Ymd_His') . '.xlsx';
        $writer = SimpleExcelWriter::streamDownload($filename);
        
        foreach ($classes as $class) {
            $writer->addRow([
                'Nama Kelas' => "'" . $class->name,
                'Total Siswa Aktif' => $class->students_count,
                'Jumlah Pelanggaran' => $class->total_violation_count,
                'Total Poin Pelanggaran' => $class->total_violation_points,
                'Jumlah Prestasi' => $class->total_achievement_count,
                'Rata-rata Poin Bersih' => $class->average_net_points
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
