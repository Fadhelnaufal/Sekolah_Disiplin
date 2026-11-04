<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\Document\DocumentDefinitionRegistry;
use App\Services\Document\DocumentType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        $year = date('Y');
        $classification = $school->classification_code ?: '421.3';
        $schoolCode = $school->school_code ?: 'SMA-NEGERI-1-SAMBIT';

        $count = Letter::whereYear('created_at', $year)->count() + 1;

        return sprintf('%s/%03d/%s/%s', $classification, $count, $schoolCode, $year);
    }

    public function buildLetterData(Student $student, Semester $semester, string $type, array $customPayload = []): array
    {
        $school = $student->school ?? (School::first() ?? new School([
            'name' => 'SMA Negeri 1 Sambit',
            'address' => 'Jl. Ponorogo - Trenggalek, Besuki, Kec. Sambit',
            'city' => 'Ponorogo',
            'application_name' => 'Sekolah Disiplin',
        ]));

        $violationPoints = $this->pointService->getViolationPoints($student, $semester);
        $achievementPoints = $this->pointService->getAchievementPoints($student, $semester);
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

        // Detailed chronological events for Discipline History reports
        $allEvents = $student->disciplineEvents()
            ->where('semester_id', $semester->id)
            ->with(['violation', 'achievement'])
            ->orderBy('event_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'date' => $event->event_date->format('d/m/Y'),
                    'type' => $event->event_type,
                    'name' => $event->event_type === 'violation'
                        ? ($event->violation ? $event->violation->name : 'Pelanggaran')
                        : ($event->achievement ? $event->achievement->name : 'Prestasi'),
                    'category' => $event->event_type === 'violation'
                        ? ($event->violation?->category ?? 'Pelanggaran')
                        : ($event->achievement?->category ?? 'Prestasi'),
                    'description' => $event->description,
                    'points' => $event->points_effect,
                ];
            });

        // Filter achievements if explicitly disabled in options
        if (isset($customPayload['include_achievements']) && $customPayload['include_achievements'] === '0') {
            $allEvents = $allEvents->where('type', 'violation')->values();
        }

        // Formal follow-up actions (administrative only, zero leakage of private counseling summaries)
        $followUps = $student->followUps()
            ->whereNull('deleted_at')
            ->latest('due_date')
            ->get()
            ->map(function ($fu) {
                return [
                    'title' => $fu->title,
                    'action_plan' => $fu->action_plan,
                    'due_date' => $fu->due_date ? $fu->due_date->format('d/m/Y') : '-',
                    'status' => $fu->status,
                ];
            });

        $signatoryTitle = $school->signer_title ?: 'Kepala Sekolah';
        $signatoryName = $school->principal_name ?? '.......................';

        $callLevel = $customPayload['call_level'] ?? $type;
        if ($callLevel === 'call_1') {
            $signatoryTitle = ! empty($school->counseling_officer_name) ? 'Guru BK / Koordinator BK' : ($school->signer_title ?: 'Kepala Sekolah');
            $signatoryName = $school->counseling_officer_name ?: ($school->principal_name ?? '.......................');
        } elseif ($callLevel === 'call_2') {
            $signatoryTitle = ! empty($school->vice_principal_name) ? 'Wakil Kepala Sekolah Bidang Kesiswaan' : ($school->signer_title ?: 'Kepala Sekolah');
            $signatoryName = $school->vice_principal_name ?: ($school->principal_name ?? '.......................');
        } elseif ($callLevel === 'call_3') {
            $signatoryTitle = 'Kepala Sekolah';
            $signatoryName = $school->principal_name ?? '.......................';
        }

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $formattedDate = date('j').' '.($months[(int) date('n')] ?? date('F')).' '.date('Y');

        $canonicalType = DocumentType::normalize($type);
        $definition = DocumentDefinitionRegistry::get($canonicalType);
        $viewPath = $definition['view_path'] ?? 'pdf.letter';

        // Snapshot of current school identity for immutable historical archiving
        $schoolSnapshot = [
            'name' => $school->name,
            'npsn' => $school->npsn,
            'phone' => $school->phone,
            'address' => $school->address,
            'city' => $school->city,
            'province' => $school->province,
            'postal_code' => $school->postal_code,
            'email' => $school->email,
            'website' => $school->website,
            'principal_name' => $school->principal_name,
            'principal_nip' => $school->principal_nip,
            'signer_title' => $school->signer_title,
            'letter_city' => $school->letter_city,
            'classification_code' => $school->classification_code,
            'school_code' => $school->school_code,
            'template_style' => $school->template_style,
            'show_npsn' => $school->show_npsn,
            'show_phone' => $school->show_phone,
            'show_email' => $school->show_email,
            'show_website' => $school->show_website,
            'show_signature_image' => $school->show_signature_image,
            'show_stamp' => $school->show_stamp,
            'logo_path' => $school->logo_path,
            'signature_path' => $school->signature_path,
            'stamp_path' => $school->stamp_path,
        ];

        return array_merge([
            'school' => $school,
            'student' => $student->load('classRoom.homeroomTeacher'),
            'semester' => $semester,
            'violation_points' => $violationPoints,
            'achievement_points' => $achievementPoints,
            'net_points' => $netPoints,
            'sanction' => $sanction,
            'recent_events' => $recentEvents,
            'all_events' => $allEvents,
            'follow_ups' => $followUps,
            'date' => $formattedDate,
            'signatory_title' => $signatoryTitle,
            'signatory_name' => $signatoryName,
            'type' => $type,
            'canonical_type' => $canonicalType,
            'view_path' => $viewPath,
            'school_snapshot' => $schoolSnapshot,
            'subject' => $customPayload['subject'] ?? ($definition['default_subject'] ?? 'Surat Pemanggilan'),
            'incident_summary' => $customPayload['incident_summary'] ?? null,
            'school_action' => $customPayload['school_action'] ?? null,
            'expected_follow_up' => $customPayload['expected_follow_up'] ?? null,
            'notes' => $customPayload['notes'] ?? null,
            'problem_summary' => $customPayload['problem_summary'] ?? null,
            'student_commitments' => $customPayload['student_commitments'] ?? null,
            'guidance_plan' => $customPayload['guidance_plan'] ?? null,
            'evaluation_period' => $customPayload['evaluation_period'] ?? '30 (tiga puluh) hari kalender',
            'sanction_consequence' => $customPayload['sanction_consequence'] ?? null,
            'reason' => $customPayload['reason'] ?? null,
            'meeting_day_date' => $customPayload['meeting_day_date'] ?? null,
            'meeting_time' => $customPayload['meeting_time'] ?? null,
            'meeting_venue' => $customPayload['meeting_venue'] ?? null,
            'contact_person' => $customPayload['contact_person'] ?? null,
            'evaluation_notes' => $customPayload['evaluation_notes'] ?? null,
        ], $customPayload);
    }

    public function generateAndStore(
        Student $student,
        Semester $semester,
        School $school,
        string $type,
        array $payload,
        User $user
    ): Letter {
        $canonicalType = DocumentType::normalize($type);
        $letterNumber = $this->generateLetterNumber($school, $type);
        $documentData = $this->buildLetterData($student, $semester, $type, $payload);
        $documentData['letter_number'] = $letterNumber;

        $view = $documentData['view_path'] ?? 'pdf.letter';
        $pdf = Pdf::loadView($view, $documentData);

        $fileName = 'surat_'.strtolower($canonicalType).'_'.$student->nis.'_'.time().'.pdf';
        $filePath = 'letters/'.$fileName;

        Storage::disk('local')->put($filePath, $pdf->output());

        try {
            return DB::transaction(function () use (
                $student,
                $semester,
                $type,
                $letterNumber,
                $filePath,
                $documentData,
                $payload,
                $user
            ) {
                return Letter::create([
                    'student_id' => $student->id,
                    'semester_id' => $semester->id,
                    'type' => $type,
                    'letter_number' => $letterNumber,
                    'subject' => $documentData['subject'] ?? 'Dokumen Resmi',
                    'generated_at' => now(),
                    'generated_by' => $user->id,
                    'status' => 'Generated',
                    'pdf_path' => $filePath,
                    'metadata' => [
                        'net_points' => $documentData['net_points'],
                        'violation_points' => $documentData['violation_points'],
                        'achievement_points' => $documentData['achievement_points'],
                        'sanction_level' => $documentData['sanction'] ? $documentData['sanction']->level : null,
                        'signatory_title' => $documentData['signatory_title'] ?? 'Kepala Sekolah',
                        'signatory_name' => $documentData['signatory_name'] ?? '.......................',
                        'canonical_type' => $documentData['canonical_type'],
                        'custom_payload' => $payload,
                        'school_snapshot' => $documentData['school_snapshot'],
                    ],
                ]);
            });
        } catch (\Exception $e) {
            if (Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }
            throw $e;
        }
    }
}
