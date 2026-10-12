<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Semester;
use Carbon\Carbon;

class EarlyWarningEngineService
{
    protected StudentPointService $pointService;

    // Ideally these would come from a Settings table/config, but we use defaults for now.
    protected int $attentionThreshold = 20;
    protected int $earlyWarningDays = 30;
    protected int $rapidIncreaseThreshold = 15;
    protected int $highestPointThreshold = 150;

    public const STATUS_NORMAL = 'NORMAL';
    public const STATUS_ATTENTION = 'ATTENTION';
    public const STATUS_RAPID_INCREASE = 'RAPID_INCREASE';
    public const STATUS_HIGH_THRESHOLD = 'HIGH_THRESHOLD';

    public function __construct(StudentPointService $pointService)
    {
        $this->pointService = $pointService;
    }

    public function evaluateRiskStatus(Student $student, Semester $semester): string
    {
        $netPoints = $this->pointService->getNetPoints($student, $semester);
        
        if ($netPoints >= $this->highestPointThreshold) {
            return self::STATUS_HIGH_THRESHOLD;
        }

        $rapidPoints = $this->calculateRapidPoints($student, $semester);
        if ($rapidPoints >= $this->rapidIncreaseThreshold) {
            return self::STATUS_RAPID_INCREASE;
        }

        if ($netPoints >= $this->attentionThreshold) {
            return self::STATUS_ATTENTION;
        }

        return self::STATUS_NORMAL;
    }

    protected function calculateRapidPoints(Student $student, Semester $semester): int
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($this->earlyWarningDays);

        return (int) $student->disciplineEvents()
            ->where('semester_id', $semester->id)
            ->where('event_type', 'violation')
            ->whereBetween('event_date', [$startDate, $endDate])
            ->sum('points_effect');
    }
}
