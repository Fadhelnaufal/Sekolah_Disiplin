<?php

namespace App\Services;

use App\Models\Semester;
use App\Models\SemesterSnapshot;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SemesterService
{
    protected $studentPointService;

    public function __construct(StudentPointService $studentPointService)
    {
        $this->studentPointService = $studentPointService;
    }

    public function closeSemester(Semester $semester, int $userId): bool
    {
        // Allowed statuses are 'Draft', 'Active', 'Inactive'. We will mark it as 'Closed'.
        if ($semester->status === 'Closed') {
            throw new \Exception('Semester is already closed.');
        }

        DB::beginTransaction();

        try {
            // Update semester status
            $semester->update([
                'status' => 'Closed',
                'closed_at' => now(),
                'closed_by' => $userId,
            ]);

            // Get all students that are active or have events in this semester
            $students = Student::where('status', 'Active')->orWhereHas('disciplineEvents', function ($q) use ($semester) {
                $q->where('semester_id', $semester->id);
            })->get();

            $snapshots = [];
            foreach ($students as $student) {
                $violationPoints = $this->studentPointService->getViolationPoints($student, $semester);
                $achievementPoints = $this->studentPointService->getAchievementPoints($student, $semester);
                $netPoints = $this->studentPointService->getNetPoints($student, $semester);

                $snapshots[] = [
                    'semester_id' => $semester->id,
                    'student_id' => $student->id,
                    'class_room_id' => $student->class_room_id,
                    'violation_points' => $violationPoints,
                    'achievement_points' => $achievementPoints,
                    'net_points' => $netPoints,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Chunk insert
            foreach (array_chunk($snapshots, 500) as $chunk) {
                SemesterSnapshot::insert($chunk);
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to close semester: '.$e->getMessage());
            throw $e;
        }
    }
}
