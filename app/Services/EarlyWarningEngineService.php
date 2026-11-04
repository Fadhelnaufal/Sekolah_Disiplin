<?php

namespace App\Services;

use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use Carbon\Carbon;

class EarlyWarningEngineService
{
    protected StudentPointService $pointService;

    // Default fallback values if school has no specific configuration
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
        $school = $student->school ?? School::first();

        $highestPointThreshold = $school?->highest_point_threshold ?? $this->highestPointThreshold;
        $rapidIncreaseThreshold = $school?->rapid_increase_threshold ?? $this->rapidIncreaseThreshold;
        $attentionThreshold = $school?->attention_threshold ?? $this->attentionThreshold;
        $earlyWarningDays = $school?->early_warning_days ?? $this->earlyWarningDays;

        $netPoints = $this->pointService->getNetPoints($student, $semester);

        if ($netPoints >= $highestPointThreshold) {
            return self::STATUS_HIGH_THRESHOLD;
        }

        $rapidPoints = $this->calculateRapidPoints($student, $semester, $earlyWarningDays);
        if ($rapidPoints >= $rapidIncreaseThreshold) {
            return self::STATUS_RAPID_INCREASE;
        }

        if ($netPoints >= $attentionThreshold) {
            return self::STATUS_ATTENTION;
        }

        return self::STATUS_NORMAL;
    }

    protected function calculateRapidPoints(Student $student, Semester $semester, ?int $days = null): int
    {
        $days = $days ?? $this->earlyWarningDays;
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days);

        return (int) $student->disciplineEvents()
            ->where('semester_id', $semester->id)
            ->where('event_type', 'violation')
            ->whereBetween('event_date', [$startDate, $endDate])
            ->sum('points_effect');
    }
}
