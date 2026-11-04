<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\DisciplineEvent;
use App\Models\FollowUp;
use App\Models\Semester;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            ->when($classId, fn ($q) => $q->where('class_room_id', $classId))
            ->when($homeroomTeacherId, function ($q) use ($homeroomTeacherId) {
                $q->whereHas('classRoom', function ($classQuery) use ($homeroomTeacherId) {
                    $classQuery->where('homeroom_teacher_id', $homeroomTeacherId);
                });
            });

        // Subqueries for performance
        $eventsQuery = DB::table('discipline_events')
            ->where('semester_id', $semester->id)
            ->whereNull('deleted_at')
            ->when($startDate, fn ($q) => $q->whereDate('event_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('event_date', '<=', $endDate));

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
            ->withCount(['students' => function ($q) {
                $q->whereIn('status', ['Active', 'active']);
            }])
            ->where('academic_year_id', $semester->academic_year_id)
            ->whereIn('status', ['Active', 'active'])
            ->when($homeroomTeacherId, fn ($q) => $q->where('homeroom_teacher_id', $homeroomTeacherId));

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

    /**
     * Get monthly trend of violations and achievements.
     * Supports filtering by class, date range, homeroom teacher, and timezone Asia/Jakarta.
     *
     * @return Collection<int, object>
     */
    public function getMonthlyTrend(
        Semester $semester,
        ?int $classId = null,
        ?int $homeroomTeacherId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        bool $fillEmptyMonths = false,
        ?int $maxMonths = null
    ) {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $monthSelect = "TO_CHAR(discipline_events.event_date, 'YYYY-MM')";
        } elseif ($driver === 'sqlite') {
            $monthSelect = "strftime('%Y-%m', discipline_events.event_date)";
        } else {
            $monthSelect = "DATE_FORMAT(discipline_events.event_date, '%Y-%m')";
        }

        $query = DisciplineEvent::query()
            ->where('discipline_events.semester_id', $semester->id)
            ->whereNull('discipline_events.deleted_at')
            ->when($startDate, fn ($q) => $q->whereDate('discipline_events.event_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('discipline_events.event_date', '<=', $endDate));

        if ($classId || $homeroomTeacherId) {
            $query->join('students', 'discipline_events.student_id', '=', 'students.id')
                ->when($classId, fn ($q) => $q->where('students.class_room_id', $classId))
                ->when($homeroomTeacherId, function ($q) use ($homeroomTeacherId) {
                    $q->join('class_rooms', 'students.class_room_id', '=', 'class_rooms.id')
                        ->where('class_rooms.homeroom_teacher_id', $homeroomTeacherId);
                });
        }

        $rows = $query->select(
            DB::raw("$monthSelect as month"),
            DB::raw("SUM(CASE WHEN discipline_events.event_type = 'violation' THEN 1 ELSE 0 END) as violation_count"),
            DB::raw("SUM(CASE WHEN discipline_events.event_type = 'achievement' THEN 1 ELSE 0 END) as achievement_count"),
            DB::raw("SUM(CASE WHEN discipline_events.event_type = 'violation' THEN discipline_events.points_effect ELSE 0 END) as violation_points"),
            DB::raw("SUM(CASE WHEN discipline_events.event_type = 'achievement' THEN ABS(discipline_events.points_effect) ELSE 0 END) as achievement_points")
        )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $shortMonths = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $mapped = $rows->keyBy('month')->map(function ($row) use ($shortMonths) {
            $vCount = (int) $row->violation_count;
            $aCount = (int) $row->achievement_count;
            $vPoints = (int) $row->violation_points;
            $aPoints = (int) $row->achievement_points;
            $net = max(0, $vPoints - $aPoints);

            $carbon = Carbon::createFromFormat('Y-m', $row->month, 'Asia/Jakarta');
            $label = ($shortMonths[$carbon->month] ?? $carbon->format('M')).' '.$carbon->year;

            return (object) [
                'month' => $row->month,
                'label' => $label,
                'count' => $vCount, // Backward compatibility with existing tests
                'violation_count' => $vCount,
                'achievement_count' => $aCount,
                'violation_points' => $vPoints,
                'achievement_points' => $aPoints,
                'net_points' => $net,
            ];
        });

        if (! $fillEmptyMonths) {
            return $mapped->values();
        }

        $now = Carbon::now('Asia/Jakarta');

        // Adaptive start: start from explicit filter or first recorded event
        if ($startDate) {
            $start = Carbon::parse($startDate, 'Asia/Jakarta')->startOfMonth();
        } else {
            $firstEvent = DisciplineEvent::where('semester_id', $semester->id)
                ->whereNull('deleted_at')
                ->min('event_date');

            if ($firstEvent) {
                $start = Carbon::parse($firstEvent, 'Asia/Jakarta')->startOfMonth();
            } else {
                $start = Carbon::parse($semester->start_date ?? '2026-01-01', 'Asia/Jakarta')->startOfMonth();
            }
        }

        // Adaptive end: cap at current month for active semester, or end of semester
        if ($endDate) {
            $end = Carbon::parse($endDate, 'Asia/Jakarta')->endOfMonth();
        } else {
            $semEnd = Carbon::parse($semester->end_date, 'Asia/Jakarta')->endOfMonth();
            $isActive = in_array(strtolower((string) $semester->status), ['active', 'aktif']);

            // Cap at current month if ongoing in the future
            $end = ($semEnd->gt($now) && $isActive)
                ? $now->copy()->endOfMonth()
                : $semEnd;
        }

        // Ensure valid range
        if ($start->gt($end)) {
            $start = $end->copy()->startOfMonth();
        }

        $result = collect();
        $curr = $start->copy();

        while ($curr->lte($end)) {
            $key = $curr->format('Y-m');
            if ($mapped->has($key)) {
                $result->push($mapped->get($key));
            } else {
                $label = ($shortMonths[$curr->month] ?? $curr->format('M')).' '.$curr->year;
                $result->push((object) [
                    'month' => $key,
                    'label' => $label,
                    'count' => 0,
                    'violation_count' => 0,
                    'achievement_count' => 0,
                    'violation_points' => 0,
                    'achievement_points' => 0,
                    'net_points' => 0,
                ]);
            }
            $curr->addMonth();
        }

        if ($maxMonths && $result->count() > $maxMonths) {
            $result = $result->slice(-$maxMonths)->values();
        }

        return $result;
    }

    public function getCategoryDistribution(
        Semester $semester,
        ?int $classId = null,
        ?int $homeroomTeacherId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = DisciplineEvent::query()
            ->where('discipline_events.semester_id', $semester->id)
            ->where('discipline_events.event_type', 'violation')
            ->whereNull('discipline_events.deleted_at')
            ->join('violations', 'discipline_events.violation_id', '=', 'violations.id')
            ->when($startDate, fn ($q) => $q->whereDate('discipline_events.event_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('discipline_events.event_date', '<=', $endDate));

        if ($classId || $homeroomTeacherId) {
            $query->join('students', 'discipline_events.student_id', '=', 'students.id')
                ->when($classId, fn ($q) => $q->where('students.class_room_id', $classId))
                ->when($homeroomTeacherId, function ($q) use ($homeroomTeacherId) {
                    $q->join('class_rooms', 'students.class_room_id', '=', 'class_rooms.id')
                        ->where('class_rooms.homeroom_teacher_id', $homeroomTeacherId);
                });
        }

        return $query->select(
            DB::raw("COALESCE(NULLIF(violations.category, ''), 'Lainnya') as category"),
            'violations.name',
            DB::raw('COUNT(discipline_events.id) as count'),
            DB::raw('SUM(discipline_events.points_effect) as total_points')
        )
            ->groupBy('category', 'violations.name')
            ->orderByDesc('count')
            ->get();
    }

    public function getCategorySummary(
        Semester $semester,
        ?int $classId = null,
        ?int $homeroomTeacherId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = DisciplineEvent::query()
            ->where('discipline_events.semester_id', $semester->id)
            ->where('discipline_events.event_type', 'violation')
            ->whereNull('discipline_events.deleted_at')
            ->join('violations', 'discipline_events.violation_id', '=', 'violations.id')
            ->when($startDate, fn ($q) => $q->whereDate('discipline_events.event_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('discipline_events.event_date', '<=', $endDate));

        if ($classId || $homeroomTeacherId) {
            $query->join('students', 'discipline_events.student_id', '=', 'students.id')
                ->when($classId, fn ($q) => $q->where('students.class_room_id', $classId))
                ->when($homeroomTeacherId, function ($q) use ($homeroomTeacherId) {
                    $q->join('class_rooms', 'students.class_room_id', '=', 'class_rooms.id')
                        ->where('class_rooms.homeroom_teacher_id', $homeroomTeacherId);
                });
        }

        return $query->select(
            DB::raw("COALESCE(NULLIF(violations.category, ''), 'Lainnya') as category"),
            DB::raw('COUNT(discipline_events.id) as count'),
            DB::raw('SUM(discipline_events.points_effect) as total_points')
        )
            ->groupBy('category')
            ->orderByDesc('count')
            ->get();
    }

    public function getFollowUpStatusDistribution(
        Semester $semester,
        ?int $classId = null,
        ?int $homeroomTeacherId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = FollowUp::query()
            ->whereNull('follow_ups.deleted_at')
            ->when($startDate, fn ($q) => $q->whereDate('follow_ups.created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('follow_ups.created_at', '<=', $endDate))
            ->when(! $startDate && ! $endDate, function ($q) use ($semester) {
                if ($semester->start_date && $semester->end_date) {
                    $q->whereBetween('follow_ups.created_at', [
                        Carbon::parse($semester->start_date)->startOfDay(),
                        Carbon::parse($semester->end_date)->endOfDay(),
                    ]);
                }
            });

        if ($classId || $homeroomTeacherId) {
            $query->join('students', 'follow_ups.student_id', '=', 'students.id')
                ->when($classId, fn ($q) => $q->where('students.class_room_id', $classId))
                ->when($homeroomTeacherId, function ($q) use ($homeroomTeacherId) {
                    $q->join('class_rooms', 'students.class_room_id', '=', 'class_rooms.id')
                        ->where('class_rooms.homeroom_teacher_id', $homeroomTeacherId);
                });
        }

        $rawStats = $query->select('follow_ups.status', DB::raw('COUNT(follow_ups.id) as count'))
            ->groupBy('follow_ups.status')
            ->pluck('count', 'status');

        $statusLabels = [
            'pending' => 'Menunggu Tindakan',
            'in_progress' => 'Sedang Diproses',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];

        return collect($statusLabels)->map(function ($label, $key) use ($rawStats) {
            return (object) [
                'status' => $key,
                'label' => $label,
                'count' => (int) ($rawStats[$key] ?? 0),
            ];
        })->values();
    }
}
