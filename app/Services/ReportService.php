<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Semester;
use App\Models\DisciplineEvent;
use App\Models\ClassRoom;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Get aggregate points for students with dynamic filtering.
     * Overcomes N+1 problem of StudentPointService by aggregating at DB level.
     */
    public function getStudentAggregates(
        Semester $semester,
        ?int $classId = null,
        ?int $homeroomTeacherId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = Student::query()
            ->with('classRoom')
            ->whereIn('status', ['Active', 'active'])
            ->when($classId, fn($q) => $q->where('class_room_id', $classId))
            ->when($homeroomTeacherId, function ($q) use ($homeroomTeacherId) {
                $q->whereHas('classRoom', function ($classQuery) use ($homeroomTeacherId) {
                    $classQuery->where('homeroom_teacher_id', $homeroomTeacherId);
                });
            });

        // Subqueries for performance
        $eventsQuery = DB::table('discipline_events')
            ->where('semester_id', $semester->id)
            ->whereNull('deleted_at')
            ->when($startDate, fn($q) => $q->whereDate('event_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('event_date', '<=', $endDate));

        $violationPointsSub = clone $eventsQuery;
        $violationPointsSub->select(DB::raw('COALESCE(SUM(points_effect), 0)'))
            ->where('event_type', 'violation')
            ->whereColumn('student_id', 'students.id');

        $achievementPointsSub = clone $eventsQuery;
        $achievementPointsSub->select(DB::raw('COALESCE(SUM(points_effect), 0)'))
            ->where('event_type', 'achievement')
            ->whereColumn('student_id', 'students.id');

        $violationCountSub = clone $eventsQuery;
        $violationCountSub->select(DB::raw('COUNT(id)'))
            ->where('event_type', 'violation')
            ->whereColumn('student_id', 'students.id');
        
        $achievementCountSub = clone $eventsQuery;
        $achievementCountSub->select(DB::raw('COUNT(id)'))
            ->where('event_type', 'achievement')
            ->whereColumn('student_id', 'students.id');

        $students = $query->select('students.*')
            ->addSelect([
                'violation_points' => $violationPointsSub,
                'achievement_points' => $achievementPointsSub,
                'violation_count' => $violationCountSub,
                'achievement_count' => $achievementCountSub,
            ])
            ->get();

        return $students->map(function ($student) {
            $rawNet = $student->violation_points - abs($student->achievement_points);
            $student->net_points = max(0, $rawNet);
            return $student;
        });
    }

    public function getClassAggregates(Semester $semester, ?int $homeroomTeacherId = null)
    {
        $query = ClassRoom::query()
            ->withCount(['students' => function($q) {
                $q->whereIn('status', ['Active', 'active']);
            }])
            ->where('academic_year_id', $semester->academic_year_id)
            ->whereIn('status', ['Active', 'active'])
            ->when($homeroomTeacherId, fn($q) => $q->where('homeroom_teacher_id', $homeroomTeacherId));

        $classes = $query->get();

        // Calculate aggregates per class.
        // For performance on small/medium schools, we map over classes and pull aggregates.
        $studentIds = Student::whereIn('class_room_id', $classes->pluck('id'))->pluck('id');
        
        $eventStats = DisciplineEvent::where('semester_id', $semester->id)
            ->whereIn('student_id', $studentIds)
            ->select('student_id', 'event_type', DB::raw('SUM(points_effect) as total_points'), DB::raw('COUNT(id) as total_count'))
            ->groupBy('student_id', 'event_type')
            ->get();

        // Build a lookup array
        $statsByStudent = [];
        foreach ($eventStats as $stat) {
            $statsByStudent[$stat->student_id][$stat->event_type] = [
                'points' => (int) $stat->total_points,
                'count' => (int) $stat->total_count,
            ];
        }

        // Map to classes
        $students = Student::whereIn('class_room_id', $classes->pluck('id'))
            ->whereIn('status', ['Active', 'active'])
            ->get();

        $studentsByClass = $students->groupBy('class_room_id');

        return $classes->map(function ($class) use ($studentsByClass, $statsByStudent) {
            $classStudents = $studentsByClass->get($class->id, collect());
            
            $violationPoints = 0;
            $achievementPoints = 0;
            $violationCount = 0;
            $achievementCount = 0;
            $totalNetPoints = 0;

            foreach ($classStudents as $student) {
                $vPts = $statsByStudent[$student->id]['violation']['points'] ?? 0;
                $vCnt = $statsByStudent[$student->id]['violation']['count'] ?? 0;
                $aPts = abs($statsByStudent[$student->id]['achievement']['points'] ?? 0);
                $aCnt = $statsByStudent[$student->id]['achievement']['count'] ?? 0;

                $violationPoints += $vPts;
                $violationCount += $vCnt;
                $achievementPoints += $aPts;
                $achievementCount += $aCnt;
                
                $totalNetPoints += max(0, $vPts - $aPts);
            }

            $class->total_violation_points = $violationPoints;
            $class->total_achievement_points = $achievementPoints;
            $class->total_violation_count = $violationCount;
            $class->total_achievement_count = $achievementCount;
            $class->total_net_points = $totalNetPoints;
            $class->average_net_points = $class->students_count > 0 ? round($totalNetPoints / $class->students_count, 1) : 0;

            return $class;
        });
    }
    
    public function getMonthlyTrend(Semester $semester)
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            $monthSelect = "TO_CHAR(event_date, 'YYYY-MM')";
        } elseif ($driver === 'sqlite') {
            $monthSelect = "strftime('%Y-%m', event_date)";
        } else {
            $monthSelect = "DATE_FORMAT(event_date, '%Y-%m')";
        }

        return DisciplineEvent::where('semester_id', $semester->id)
            ->where('event_type', 'violation')
            ->select(DB::raw("$monthSelect as month"), DB::raw('COUNT(id) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    public function getCategoryDistribution(Semester $semester)
    {
        return DisciplineEvent::where('semester_id', $semester->id)
            ->where('event_type', 'violation')
            ->join('violations', 'discipline_events.violation_id', '=', 'violations.id')
            ->select('violations.name', DB::raw('COUNT(discipline_events.id) as count'))
            ->groupBy('violations.name')
            ->orderByDesc('count')
            ->get();
    }
}
