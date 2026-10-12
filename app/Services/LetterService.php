<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\Student;
use App\Models\Semester;
use App\Models\School;

class LetterService
{
    protected StudentPointService $pointService;
    protected SanctionEngineService $sanctionService;

    public function __construct(StudentPointService $pointService, SanctionEngineService $sanctionService)
    {
        $this->pointService = $pointService;
        $this->sanctionService = $sanctionService;
    }

    public function generateLetterNumber(School $school, string $type): string
    {
        $prefix = strtoupper(substr($type, 0, 2));
        $count = Letter::where('type', $type)->count() + 1;
        $year = date('Y');
        return sprintf('%03d/%s/TATIB/%s', $count, $prefix, $year);
    }

    public function buildLetterData(Student $student, Semester $semester, string $type)
    {
        $school = School::first();
        $netPoints = $this->pointService->getNetPoints($student, $semester);
        $sanction = $this->sanctionService->evaluate($student, $semester);

        $recentEvents = $student->disciplineEvents()
            ->where('semester_id', $semester->id)
            ->with(['violation', 'achievement'])
            ->latest('event_date')
            ->latest('id')
            ->take(5)
            ->get()
            ->map(function ($event) {
                return [
                    'date' => $event->event_date->format('d-m-Y'),
                    'description' => $event->event_type === 'violation' 
                        ? ($event->violation ? $event->violation->name : 'Pelanggaran')
                        : ($event->achievement ? $event->achievement->name : 'Prestasi'),
                    'points' => $event->points_effect,
                ];
            });

        $signatoryTitle = 'Kepala Sekolah';
        $signatoryName = $school->principal_name ?? '.......................';
        
        if ($type === 'call_1') {
            $signatoryTitle = 'Guru BK / Koordinator BK';
            $signatoryName = $school->counseling_officer_name ?? '.......................';
        } elseif ($type === 'call_2') {
            $signatoryTitle = 'Wakil Kepala Sekolah Bidang Kesiswaan';
            $signatoryName = $school->vice_principal_name ?? '.......................';
        } elseif ($type === 'call_3') {
            $signatoryTitle = 'Kepala Sekolah';
            $signatoryName = $school->principal_name ?? '.......................';
        }

        return [
            'school' => $school,
            'student' => $student->load('classRoom.homeroomTeacher'),
            'semester' => $semester,
            'net_points' => $netPoints,
            'sanction' => $sanction,
            'recent_events' => $recentEvents,
            'date' => now()->format('d F Y'),
            'signatory_title' => $signatoryTitle,
            'signatory_name' => $signatoryName,
            'type' => $type,
        ];
    }
}
