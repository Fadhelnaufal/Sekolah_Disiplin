<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Semester;
use Carbon\CarbonPeriod;

class StudentPointService
{
    public function getViolationPoints(Student $student, Semester $semester): int
    {
        return (int) $student->disciplineEvents()
            ->where('semester_id', $semester->id)
            ->where('event_type', 'violation')
            ->sum('points_effect');
    }

    public function getAchievementPoints(Student $student, Semester $semester): int
    {
        return (int) abs($student->disciplineEvents()
            ->where('semester_id', $semester->id)
            ->where('event_type', 'achievement')
            ->sum('points_effect'));
    }

    public function getNetPoints(Student $student, Semester $semester): int
    {
        $net = $this->getViolationPoints($student, $semester) - $this->getAchievementPoints($student, $semester);
        return max(0, $net);
    }

    public function getEventCount(Student $student, Semester $semester): int
    {
        return $student->disciplineEvents()
            ->where('semester_id', $semester->id)
            ->count();
    }

    public function getPointsInPeriod(Student $student, CarbonPeriod $period): int
    {
        return (int) $student->disciplineEvents()
            ->where('event_type', 'violation')
            ->whereBetween('event_date', [$period->getStartDate(), $period->getEndDate()])
            ->sum('points_effect');
    }
}
