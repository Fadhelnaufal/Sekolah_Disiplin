<?php

namespace App\Services;

use App\Models\SanctionRule;
use App\Models\Semester;
use App\Models\Student;

class SanctionEngineService
{
    protected StudentPointService $pointService;

    public function __construct(StudentPointService $pointService)
    {
        $this->pointService = $pointService;
    }

    public function evaluate(Student $student, Semester $semester): ?SanctionRule
    {
        $netPoints = $this->pointService->getNetPoints($student, $semester);

        return SanctionRule::where('is_active', true)
            ->where('min_points', '<=', $netPoints)
            ->where(function ($query) use ($netPoints) {
                $query->whereNull('max_points')
                    ->orWhere('max_points', '>=', $netPoints);
            })
            ->orderByDesc('level')
            ->first();
    }
}
