<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Student;
use App\Models\Semester;
use App\Models\DisciplineEvent;
use App\Models\CounselingRecord;
use App\Services\StudentPointService;
use App\Services\EarlyWarningEngineService;

class DashboardController extends Controller
{
    public function index(EarlyWarningEngineService $warningService, StudentPointService $pointService)
    {
        // 1. Get current active semester
        $currentSemester = Semester::with('academicYear')->whereIn('status', ['Active', 'active'])->first();

        // 2. Summary metrics
        $totalStudents = Student::whereIn('status', ['Active', 'active'])->count();
        
        $currentViolationsCount = 0;
        if ($currentSemester) {
            $currentViolationsCount = DisciplineEvent::where('semester_id', $currentSemester->id)
                ->where('event_type', 'violation')
                ->count();
        }

        $counselingNeeded = CounselingRecord::whereIn('status', ['Pending', 'PENDING'])->count();

        // 3. Students needing attention
        $students = Student::with('classRoom')->get();
        $attentionStudents = [];
        $attentionCount = 0;

        if ($currentSemester) {
            foreach ($students as $student) {
                $status = $warningService->evaluateRiskStatus($student, $currentSemester);
                if ($status !== EarlyWarningEngineService::STATUS_NORMAL) {
                    $attentionCount++;
                    
                    // Limit collected list to save memory
                    if (count($attentionStudents) < 20) {
                        $attentionStudents[] = [
                            'id' => $student->id,
                            'name' => $student->name,
                            'nis' => $student->nis,
                            'class_name' => $student->classRoom ? $student->classRoom->name : '-',
                            'net_points' => $pointService->getNetPoints($student, $currentSemester),
                            'status' => $status
                        ];
                    }
                }
            }
        }

        usort($attentionStudents, function($a, $b) {
            return $b['net_points'] <=> $a['net_points'];
        });

        // 4. Recent discipline activity
        $recentActivities = DisciplineEvent::with(['student.classRoom', 'violation', 'achievement', 'recordedBy'])
            ->when($currentSemester, function($query) use ($currentSemester) {
                return $query->where('semester_id', $currentSemester->id);
            })
            ->latest('event_date')
            ->latest('id')
            ->take(5)
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
                    'recorded_by' => $event->recordedBy ? $event->recordedBy->name : 'Sistem'
                ];
            });

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_students' => $totalStudents,
                'current_violations' => $currentViolationsCount,
                'attention_needed' => $attentionCount,
                'counseling_pending' => $counselingNeeded,
            ],
            'priority_students' => collect($attentionStudents)->take(5)->values()->all(),
            'recent_activities' => $recentActivities,
            'current_semester' => $currentSemester ? $currentSemester->name . ' (' . $currentSemester->academicYear->name . ')' : 'Belum diatur'
        ]);
    }
}
