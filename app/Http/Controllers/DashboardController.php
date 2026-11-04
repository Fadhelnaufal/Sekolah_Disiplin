<?php

namespace App\Http\Controllers;

use App\Models\CounselingRecord;
use App\Models\DisciplineEvent;
use App\Models\FollowUp;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\EarlyWarningEngineService;
use App\Services\ReportService;
use App\Services\StudentPointService;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(
        EarlyWarningEngineService $warningService,
        StudentPointService $pointService,
        ReportService $reportService
    ) {
        $user = auth()->user();

        // 1. Determine primary role and role-based scoping
        $primaryRole = 'admin';
        if ($user->hasRole('Super Admin')) {
            $primaryRole = 'admin';
        } elseif ($user->hasRole('BK')) {
            $primaryRole = 'bk';
        } elseif ($user->hasRole('Tata Tertib')) {
            $primaryRole = 'tatib';
        } elseif ($user->hasRole('Principal')) {
            $primaryRole = 'principal';
        } elseif ($user->hasRole('Teacher')) {
            $primaryRole = 'teacher';
        }

        $teacher = null;
        $homeroomClasses = collect();
        $homeroomClassIds = [];
        $homeroomClassName = '';

        if ($primaryRole === 'teacher') {
            $teacher = Teacher::with('homeroomClasses')->where('user_id', $user->id)->first();
            if ($teacher && $teacher->homeroomClasses->isNotEmpty()) {
                $homeroomClasses = $teacher->homeroomClasses;
                $homeroomClassIds = $homeroomClasses->pluck('id')->all();
                $homeroomClassName = $homeroomClasses->pluck('name')->join(', ');
            }
        }

        $isHomeroom = count($homeroomClassIds) > 0;

        // 2. Get current active semester & school
        $currentSemester = Semester::with('academicYear')->whereIn('status', ['Active', 'active'])->first();
        $school = School::first();
        $today = Carbon::today()->toDateString();

        // Base student query
        $studentQuery = Student::whereIn('status', ['Active', 'active']);
        if ($isHomeroom) {
            $studentQuery->whereIn('class_room_id', $homeroomClassIds);
        }
        $totalStudents = (clone $studentQuery)->count();

        // Violations & Achievements today
        $violationsTodayQuery = DisciplineEvent::whereDate('event_date', $today)
            ->where('event_type', 'violation');
        $achievementsTodayQuery = DisciplineEvent::whereDate('event_date', $today)
            ->where('event_type', 'achievement');
        $followUpsQuery = FollowUp::whereIn('status', ['pending', 'in_progress']);

        if ($isHomeroom) {
            $violationsTodayQuery->whereHas('student', fn ($q) => $q->whereIn('class_room_id', $homeroomClassIds));
            $achievementsTodayQuery->whereHas('student', fn ($q) => $q->whereIn('class_room_id', $homeroomClassIds));
            $followUpsQuery->whereHas('student', fn ($q) => $q->whereIn('class_room_id', $homeroomClassIds));
        }

        $todayViolations = $violationsTodayQuery->count();
        $todayAchievements = $achievementsTodayQuery->count();
        $activeFollowUps = $followUpsQuery->count();

        // Historical / Semester violations count
        $currentViolationsCount = 0;
        if ($currentSemester) {
            $semesterViolationsQuery = DisciplineEvent::where('semester_id', $currentSemester->id)
                ->where('event_type', 'violation');
            if ($isHomeroom) {
                $semesterViolationsQuery->whereHas('student', fn ($q) => $q->whereIn('class_room_id', $homeroomClassIds));
            }
            $currentViolationsCount = $semesterViolationsQuery->count();
        }

        // Counseling needed (pending or scheduled)
        $counselingNeeded = CounselingRecord::whereIn('status', ['Pending', 'PENDING', 'scheduled'])->count();

        // 3. Students needing attention
        $studentsToEvaluate = (clone $studentQuery)->with('classRoom')->get();
        $attentionStudents = [];
        $attentionCount = 0;

        if ($currentSemester) {
            foreach ($studentsToEvaluate as $student) {
                $status = $warningService->evaluateRiskStatus($student, $currentSemester);
                if ($status !== EarlyWarningEngineService::STATUS_NORMAL) {
                    $attentionCount++;

                    if (count($attentionStudents) < 20) {
                        $attentionStudents[] = [
                            'id' => $student->id,
                            'name' => $student->name,
                            'nis' => $student->nis,
                            'class_name' => $student->classRoom ? $student->classRoom->name : '-',
                            'net_points' => $pointService->getNetPoints($student, $currentSemester),
                            'status' => $status,
                        ];
                    }
                }
            }
        }

        usort($attentionStudents, function ($a, $b) {
            return $b['net_points'] <=> $a['net_points'];
        });

        // 4. Recent discipline activity (10 items)
        $recentActivitiesQuery = DisciplineEvent::with(['student.classRoom', 'violation', 'achievement', 'recordedBy'])
            ->latest('event_date')
            ->latest('id');

        if ($isHomeroom) {
            $recentActivitiesQuery->whereHas('student', fn ($q) => $q->whereIn('class_room_id', $homeroomClassIds));
        }

        $recentActivities = $recentActivitiesQuery
            ->take(10)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'date' => $event->event_date->format('Y-m-d'),
                    'student_name' => $event->student ? $event->student->name : 'Tidak Diketahui',
                    'class_name' => $event->student && $event->student->classRoom ? $event->student->classRoom->name : '-',
                    'type' => $event->event_type,
                    'category' => strtolower($event->event_type) === 'violation'
                        ? ($event->violation ? $event->violation->name : '-')
                        : ($event->achievement ? $event->achievement->name : '-'),
                    'points' => $event->points_effect,
                    'recorded_by' => $event->original_recorder_name ?: ($event->recordedBy ? $event->recordedBy->name : 'Sistem'),
                ];
            });

        // 5. Monthly trend
        $monthlyTrend = $currentSemester
            ? $reportService->getMonthlyTrend(
                $currentSemester,
                $isHomeroom && count($homeroomClassIds) === 1 ? $homeroomClassIds[0] : null,
                $isHomeroom && $teacher ? $teacher->id : null,
                null,
                null,
                true,
                6
            )
            : collect();

        // 6. Role Context Configuration
        $roleContext = match ($primaryRole) {
            'teacher' => [
                'role' => 'teacher',
                'role_label' => $isHomeroom ? "Wali Kelas {$homeroomClassName}" : 'Guru Mata Pelajaran',
                'class_name' => $homeroomClassName ?: null,
                'banner' => [
                    'badge' => $isHomeroom ? "WALI KELAS {$homeroomClassName}" : 'GURU MATA PELAJARAN',
                    'title' => $isHomeroom ? "Pantau Disiplin Siswa Kelas {$homeroomClassName}" : 'Pencatatan Kejadian Siswa & Kedisiplinan',
                    'description' => $isHomeroom
                        ? "Pantau perkembangan poin disiplin dan prestasi siswa kelas {$homeroomClassName} secara langsung dan terarah."
                        : 'Catat insiden pelanggaran atau prestasi siswa di kelas untuk menjaga ketertiban bersama.',
                    'action_label' => 'Catat Kejadian Baru',
                    'action_url' => '/discipline-events/create',
                    'action_icon' => 'pi pi-plus-circle',
                ],
                'kpi_cards' => [
                    [
                        'label' => $isHomeroom ? "Siswa Kelas {$homeroomClassName}" : 'Total Siswa Aktif',
                        'value' => $totalStudents,
                        'icon' => 'pi pi-users',
                        'theme' => 'indigo',
                    ],
                    [
                        'label' => 'Pelanggaran Hari Ini',
                        'value' => $todayViolations,
                        'icon' => 'pi pi-exclamation-triangle',
                        'theme' => 'rose',
                    ],
                    [
                        'label' => 'Prestasi Hari Ini',
                        'value' => $todayAchievements,
                        'icon' => 'pi pi-star',
                        'theme' => 'emerald',
                    ],
                    [
                        'label' => 'Tindak Lanjut Aktif',
                        'value' => $activeFollowUps,
                        'icon' => 'pi pi-sliders-h',
                        'theme' => 'indigo',
                    ],
                ],
                'quick_links' => [
                    [
                        'label' => 'Catat Kejadian Baru',
                        'desc' => 'Input pelanggaran atau prestasi siswa',
                        'href' => '/discipline-events/create',
                        'icon' => 'pi pi-plus-circle',
                        'color' => 'indigo',
                    ],
                    [
                        'label' => 'Direktori Siswa & Kontak Ortu',
                        'desc' => 'Lihat biodata siswa dan kontak WhatsApp wali murid',
                        'href' => '/students',
                        'icon' => 'pi pi-users',
                        'color' => 'emerald',
                    ],
                    [
                        'label' => 'Rekapitulasi Wali Kelas',
                        'desc' => 'Rekap poin dan riwayat per kelas',
                        'href' => '/reports/homerooms',
                        'icon' => 'pi pi-table',
                        'color' => 'amber',
                    ],
                    [
                        'label' => 'Tindak Lanjut & Sanksi',
                        'desc' => 'Monitor pelaksanaan tindak lanjut siswa',
                        'href' => '/follow-ups',
                        'icon' => 'pi pi-sliders-h',
                        'color' => 'rose',
                    ],
                ],
            ],
            'bk' => [
                'role' => 'bk',
                'role_label' => 'Guru Bimbingan & Konseling',
                'class_name' => null,
                'banner' => [
                    'badge' => 'BIMBINGAN & KONSELING',
                    'title' => 'Pendampingan Karakter & Pembinaan Siswa',
                    'description' => 'Kelola jadwal konseling terpadu, pantau siswa dalam radar perhatian, dan terbitkan surat panggilan resmi.',
                    'action_label' => 'Jadwalkan Pembinaan',
                    'action_url' => '/counseling-records/create',
                    'action_icon' => 'pi pi-plus-circle',
                ],
                'kpi_cards' => [
                    [
                        'label' => 'Siswa Butuh Perhatian',
                        'value' => $attentionCount,
                        'icon' => 'pi pi-exclamation-circle',
                        'theme' => 'rose',
                    ],
                    [
                        'label' => 'Antrean Pembinaan BK',
                        'value' => $counselingNeeded,
                        'icon' => 'pi pi-clock',
                        'theme' => 'amber',
                    ],
                    [
                        'label' => 'Tindak Lanjut Kasus',
                        'value' => $activeFollowUps,
                        'icon' => 'pi pi-sliders-h',
                        'theme' => 'indigo',
                    ],
                    [
                        'label' => 'Pelanggaran Semester Ini',
                        'value' => $currentViolationsCount,
                        'icon' => 'pi pi-history',
                        'theme' => 'violet',
                    ],
                ],
                'quick_links' => [
                    [
                        'label' => 'Jadwalkan Pembinaan Baru',
                        'desc' => 'Buat sesi konseling dan arahan karakter siswa',
                        'href' => '/counseling-records/create',
                        'icon' => 'pi pi-plus-circle',
                        'color' => 'indigo',
                    ],
                    [
                        'label' => 'Daftar Kasus Pembinaan',
                        'desc' => 'Monitoring status dan catatan konseling BK',
                        'href' => '/counseling-records',
                        'icon' => 'pi pi-check-square',
                        'color' => 'amber',
                    ],
                    [
                        'label' => 'Surat Pemanggilan Ortu',
                        'desc' => 'Cetak dan kirim surat panggilan resmi',
                        'href' => '/reports',
                        'icon' => 'pi pi-envelope',
                        'color' => 'rose',
                    ],
                    [
                        'label' => 'Direktori Siswa & Profil',
                        'desc' => 'Cek catatan poin dan kontak orang tua siswa',
                        'href' => '/students',
                        'icon' => 'pi pi-users',
                        'color' => 'emerald',
                    ],
                ],
            ],
            'tatib' => [
                'role' => 'tatib',
                'role_label' => 'Tim Tata Tertib Sekolah',
                'class_name' => null,
                'banner' => [
                    'badge' => 'TIM TATA TERTIB SEKOLAH',
                    'title' => 'Penegakan Tata Tertib & Rekapitulasi Disiplin',
                    'description' => 'Rekam pelanggaran harian, perbarui master data sanksi, dan pantau penyelesaian tindak lanjut.',
                    'action_label' => 'Catat Kejadian Baru',
                    'action_url' => '/discipline-events/create',
                    'action_icon' => 'pi pi-plus-circle',
                ],
                'kpi_cards' => [
                    [
                        'label' => 'Total Siswa Aktif',
                        'value' => $totalStudents,
                        'icon' => 'pi pi-users',
                        'theme' => 'indigo',
                    ],
                    [
                        'label' => 'Pelanggaran Hari Ini',
                        'value' => $todayViolations,
                        'icon' => 'pi pi-exclamation-triangle',
                        'theme' => 'rose',
                    ],
                    [
                        'label' => 'Prestasi Hari Ini',
                        'value' => $todayAchievements,
                        'icon' => 'pi pi-star',
                        'theme' => 'emerald',
                    ],
                    [
                        'label' => 'Tindak Lanjut Aktif',
                        'value' => $activeFollowUps,
                        'icon' => 'pi pi-sliders-h',
                        'theme' => 'amber',
                    ],
                ],
                'quick_links' => [
                    [
                        'label' => 'Catat Kejadian Siswa',
                        'desc' => 'Input pelanggaran atau prestasi siswa',
                        'href' => '/discipline-events/create',
                        'icon' => 'pi pi-plus-circle',
                        'color' => 'indigo',
                    ],
                    [
                        'label' => 'Master Tata Tertib',
                        'desc' => 'Kelola jenis pelanggaran, sanksi, dan poin',
                        'href' => '/master-data',
                        'icon' => 'pi pi-database',
                        'color' => 'rose',
                    ],
                    [
                        'label' => 'Import Siswa Excel',
                        'desc' => 'Unggah berkas data siswa format Excel',
                        'href' => '/imports',
                        'icon' => 'pi pi-upload',
                        'color' => 'emerald',
                    ],
                    [
                        'label' => 'Rekapitulasi Disiplin',
                        'desc' => 'Tinjau rekap insiden dan poin siswa',
                        'href' => '/discipline-recap',
                        'icon' => 'pi pi-calendar',
                        'color' => 'amber',
                    ],
                ],
            ],
            'principal' => [
                'role' => 'principal',
                'role_label' => 'Kepala Sekolah',
                'class_name' => null,
                'banner' => [
                    'badge' => 'EXECUTIVE SUMMARY: KEPALA SEKOLAH',
                    'title' => 'Monitoring Iklim Disiplin & Capaian Prestasi',
                    'description' => 'Pantau dinamika kedisiplinan sekolah secara makro, efektivitas konseling, dan perbandingan antar kelas.',
                    'action_label' => 'Buka Analitik Tren',
                    'action_url' => '/reports/analytics',
                    'action_icon' => 'pi pi-chart-line',
                ],
                'kpi_cards' => [
                    [
                        'label' => 'Total Siswa Aktif',
                        'value' => $totalStudents,
                        'icon' => 'pi pi-users',
                        'theme' => 'indigo',
                    ],
                    [
                        'label' => 'Pelanggaran Semester Ini',
                        'value' => $currentViolationsCount,
                        'icon' => 'pi pi-exclamation-triangle',
                        'theme' => 'rose',
                    ],
                    [
                        'label' => 'Siswa Butuh Perhatian',
                        'value' => $attentionCount,
                        'icon' => 'pi pi-shield',
                        'theme' => 'amber',
                    ],
                    [
                        'label' => 'Tindak Lanjut Aktif',
                        'value' => $activeFollowUps,
                        'icon' => 'pi pi-sliders-h',
                        'theme' => 'emerald',
                    ],
                ],
                'quick_links' => [
                    [
                        'label' => 'Analitik & Tren Disiplin',
                        'desc' => 'Grafik dinamika poin dan tren pelanggaran',
                        'href' => '/reports/analytics',
                        'icon' => 'pi pi-chart-line',
                        'color' => 'indigo',
                    ],
                    [
                        'label' => 'Rekapitulasi Per Kelas',
                        'desc' => 'Evaluasi tingkat ketertiban tiap rombel',
                        'href' => '/reports/classes',
                        'icon' => 'pi pi-table',
                        'color' => 'emerald',
                    ],
                    [
                        'label' => 'Arsip Pembinaan Siswa',
                        'desc' => 'Tinjau catatan konseling dan penanganan BK',
                        'href' => '/counseling-records',
                        'icon' => 'pi pi-check-square',
                        'color' => 'amber',
                    ],
                    [
                        'label' => 'Rekapitulasi Disiplin',
                        'desc' => 'Laporan menyeluruh status poin siswa',
                        'href' => '/discipline-recap',
                        'icon' => 'pi pi-file',
                        'color' => 'rose',
                    ],
                ],
            ],
            default => [
                'role' => 'admin',
                'role_label' => 'Administrator Sistem',
                'class_name' => null,
                'banner' => [
                    'badge' => 'SISTEM ADMINISTRATOR',
                    'title' => 'Pusat Kendali Konfigurasi & Integritas Data',
                    'description' => 'Kelola konfigurasi sekolah, threshold early warning, master aturan, dan pantau log aktivitas sistem.',
                    'action_label' => 'Pengaturan Sistem',
                    'action_url' => '/settings',
                    'action_icon' => 'pi pi-cog',
                ],
                'kpi_cards' => [
                    [
                        'label' => 'Total Siswa Aktif',
                        'value' => $totalStudents,
                        'icon' => 'pi pi-users',
                        'theme' => 'indigo',
                    ],
                    [
                        'label' => 'Pelanggaran Hari Ini',
                        'value' => $todayViolations,
                        'icon' => 'pi pi-exclamation-triangle',
                        'theme' => 'rose',
                    ],
                    [
                        'label' => 'Prestasi Hari Ini',
                        'value' => $todayAchievements,
                        'icon' => 'pi pi-star',
                        'theme' => 'emerald',
                    ],
                    [
                        'label' => 'Tindak Lanjut Aktif',
                        'value' => $activeFollowUps,
                        'icon' => 'pi pi-sliders-h',
                        'theme' => 'indigo',
                    ],
                ],
                'quick_links' => [
                    [
                        'label' => 'Pengaturan Sekolah & Threshold',
                        'desc' => 'Konfigurasi identitas sekolah & early warning',
                        'href' => '/settings',
                        'icon' => 'pi pi-cog',
                        'color' => 'indigo',
                    ],
                    [
                        'label' => 'Master Pelanggaran & Poin',
                        'desc' => 'Atur kategori, jenis aturan & sanksi',
                        'href' => '/master-data',
                        'icon' => 'pi pi-database',
                        'color' => 'rose',
                    ],
                    [
                        'label' => 'Import & Export Excel',
                        'desc' => 'Impor siswa atau export data sekolah',
                        'href' => '/imports',
                        'icon' => 'pi pi-upload',
                        'color' => 'emerald',
                    ],
                    [
                        'label' => 'Audit Log Sistem',
                        'desc' => 'Riwayat aktivitas dan perubahan data',
                        'href' => '/audit-logs',
                        'icon' => 'pi pi-shield',
                        'color' => 'slate',
                    ],
                ],
            ],
        };

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_students' => $totalStudents,
                'today_violations' => $todayViolations,
                'today_achievements' => $todayAchievements,
                'active_follow_ups' => $activeFollowUps,
                'current_violations' => $currentViolationsCount,
                'attention_needed' => $attentionCount,
                'counseling_pending' => $counselingNeeded,
            ],
            'school_name' => $school ? $school->name : 'SMP Malang',
            'academic_year' => $currentSemester && $currentSemester->academicYear ? $currentSemester->academicYear->name : '2026/2027',
            'priority_students' => collect($attentionStudents)->take(6)->values()->all(),
            'recent_activities' => $recentActivities,
            'monthly_trend' => $monthlyTrend,
            'current_semester' => $currentSemester ? $currentSemester->name.' ('.$currentSemester->academicYear->name.')' : 'Belum diatur',
            'role_context' => $roleContext,
        ]);
    }
}
