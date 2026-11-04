<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\ClassRoom;
use App\Models\CounselingRecord;
use App\Models\DisciplineEvent;
use App\Models\SanctionRule;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\SimpleExcel\SimpleExcelReader;

class ExcelImportService
{
    /**
     * Parses the excel file and validates each row based on the target sheet type.
     * Returns statistics and preview rows.
     */
    public function parseAndPreview(string $filePath, string $sheet, array $options = []): array
    {
        $reader = SimpleExcelReader::create($filePath);
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'csv') {
            $reader->fromSheetName($sheet);
        }
        $reader->noHeaderRow();

        $headerMap = [];
        $isDataRow = false;

        $validRows = 0;
        $invalidRows = 0;
        $duplicateCandidates = 0;
        $skippedRows = 0;

        $previewData = [];
        $errors = [];

        $academicYearId = $options['academic_year_id'] ?? null;
        $semesterId = $options['semester_id'] ?? null;
        $currentUser = auth()->user();

        // Caches for DB lookups
        $classRooms = [];
        if ($academicYearId) {
            $classRooms = ClassRoom::where('academic_year_id', $academicYearId)->get()->keyBy('name');
        }

        $semesters = Semester::orderBy('start_date')->get();
        $violations = Violation::all()->keyBy('code');
        $achievements = Achievement::all()->keyBy('code');
        $users = User::all();

        $rows = $reader->getRows();

        foreach ($rows as $index => $row) {
            if (! $isDataRow) {
                // Find header row based on expected columns
                if ($this->isHeaderRow($sheet, $row)) {
                    $headerMap = $this->buildHeaderMap($row);
                    $isDataRow = true;
                }

                continue;
            }

            // Re-map row to associative array using headers
            $mappedRow = $this->mapRow($headerMap, $row);

            // Skip empty rows (if the required key is empty)
            if ($this->isEmptyRow($sheet, $mappedRow)) {
                continue;
            }

            $validation = $this->validateRow($sheet, $mappedRow, [
                'classRooms' => $classRooms,
                'semesters' => $semesters,
                'violations' => $violations,
                'achievements' => $achievements,
                'users' => $users,
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
                'currentUser' => $currentUser,
            ]);

            $status = $validation['status'];
            if ($status === 'valid' || $status === 'update') {
                $validRows++;
            } elseif ($status === 'invalid') {
                $invalidRows++;
                if (count($errors) < 50) {
                    $errors[] = 'Baris '.($index + 1).': '.implode(', ', $validation['errors']);
                }
            } elseif ($status === 'duplicate') {
                $duplicateCandidates++;
            } elseif ($status === 'skip') {
                $skippedRows++;
            }

            if (count($previewData) < 100) {
                $previewData[] = [
                    'original' => $mappedRow,
                    'status' => $status,
                    'errors' => $validation['errors'] ?? [],
                    'data' => $validation['data'] ?? null,
                ];
            }
        }

        return [
            'stats' => [
                'valid' => $validRows,
                'invalid' => $invalidRows,
                'duplicate' => $duplicateCandidates,
                'skipped' => $skippedRows,
                'total' => $validRows + $invalidRows + $duplicateCandidates + $skippedRows,
            ],
            'errors' => $errors,
            'preview' => $previewData,
        ];
    }

    /**
     * Actually commits the file into DB.
     */
    public function commit(string $filePath, string $sheet, array $options = []): array
    {
        $reader = SimpleExcelReader::create($filePath);
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'csv') {
            $reader->fromSheetName($sheet);
        }
        $reader->noHeaderRow();

        $headerMap = [];
        $isDataRow = false;

        $academicYearId = $options['academic_year_id'] ?? null;
        $semesterId = $options['semester_id'] ?? null;
        $currentUser = auth()->user();

        $classRooms = [];
        if ($academicYearId) {
            $classRooms = ClassRoom::where('academic_year_id', $academicYearId)->get()->keyBy('name');
        }
        $semesters = Semester::orderBy('start_date')->get();
        $violations = Violation::all()->keyBy('code');
        $achievements = Achievement::all()->keyBy('code');
        $users = User::all();

        $chunkSize = 500;
        $currentChunk = [];

        $successCount = 0;
        $failCount = 0;

        $rows = $reader->getRows();

        foreach ($rows as $row) {
            if (! $isDataRow) {
                if ($this->isHeaderRow($sheet, $row)) {
                    $headerMap = $this->buildHeaderMap($row);
                    $isDataRow = true;
                }

                continue;
            }

            $mappedRow = $this->mapRow($headerMap, $row);
            if ($this->isEmptyRow($sheet, $mappedRow)) {
                continue;
            }

            $validation = $this->validateRow($sheet, $mappedRow, [
                'classRooms' => $classRooms,
                'semesters' => $semesters,
                'violations' => $violations,
                'achievements' => $achievements,
                'users' => $users,
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
                'currentUser' => $currentUser,
            ]);

            if ($validation['status'] === 'valid' || $validation['status'] === 'update') {
                $currentChunk[] = $validation['data'];
            }

            if (count($currentChunk) >= $chunkSize) {
                $successCount += $this->processChunk($sheet, $currentChunk);
                $currentChunk = [];
            }
        }

        if (count($currentChunk) > 0) {
            $successCount += $this->processChunk($sheet, $currentChunk);
        }

        return [
            'success' => $successCount,
            'failed' => $failCount,
        ];
    }

    private function processChunk(string $sheet, array $chunk): int
    {
        $processed = 0;
        DB::beginTransaction();
        try {
            foreach ($chunk as $data) {
                if ($sheet === 'DATA_SISWA') {
                    Student::updateOrCreate(
                        ['nis' => $data['nis']],
                        $data
                    );
                } elseif ($sheet === 'MASTER_PELANGGARAN') {
                    Violation::updateOrCreate(
                        ['code' => $data['code']],
                        $data
                    );
                } elseif ($sheet === 'MASTER_PRESTASI') {
                    Achievement::updateOrCreate(
                        ['code' => $data['code']],
                        $data
                    );
                } elseif ($sheet === 'MASTER_SANKSI') {
                    SanctionRule::updateOrCreate(
                        ['level' => $data['level']],
                        $data
                    );
                } elseif ($sheet === 'CATATAN') {
                    DisciplineEvent::create($data);
                } elseif ($sheet === 'PEMBINAAN') {
                    CounselingRecord::create($data);
                } elseif ($sheet === 'SETUP') {
                    $school = School::first();
                    if ($school) {
                        $school->update($data);
                    }
                }
                $processed++;
            }
            DB::commit();

            return $processed;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import chunk failed: '.$e->getMessage());

            return 0; // Return 0 to indicate chunk failed
        }
    }

    private function isHeaderRow(string $sheet, array $row): bool
    {
        $vals = array_map('trim', array_map('strval', array_values($row)));
        $valsStr = implode(',', $vals);

        if ($sheet === 'DATA_SISWA' && in_array('NIS', $vals)) {
            return true;
        }
        if (in_array($sheet, ['MASTER_PELANGGARAN', 'MASTER_PRESTASI', 'CATATAN']) && in_array('KODE', $vals)) {
            return true;
        }
        if ($sheet === 'MASTER_SANKSI' && in_array('TINGKAT', $vals)) {
            return true;
        }
        if ($sheet === 'PEMBINAAN' && in_array('MASALAH', $vals)) {
            return true;
        }
        if ($sheet === 'SETUP' && in_array('Nama Sekolah', $vals)) {
            return true;
        } // SETUP has weird headers, but we will handle it via logic

        return false;
    }

    private function buildHeaderMap(array $row): array
    {
        $map = [];
        foreach ($row as $index => $val) {
            if ($val !== null && trim((string) $val) !== '') {
                $map[trim((string) $val)] = $index;
            }
        }

        return $map;
    }

    private function mapRow(array $headerMap, array $row): array
    {
        $mapped = [];
        $rowValues = array_values($row);
        foreach ($headerMap as $header => $index) {
            $mapped[$header] = $rowValues[$index] ?? null;
        }

        return $mapped;
    }

    private function isEmptyRow(string $sheet, array $mappedRow): bool
    {
        if ($sheet === 'DATA_SISWA' && empty($mappedRow['NIS'])) {
            return true;
        }
        if (in_array($sheet, ['MASTER_PELANGGARAN', 'MASTER_PRESTASI']) && empty($mappedRow['KODE'])) {
            return true;
        }
        if ($sheet === 'MASTER_SANKSI' && empty($mappedRow['TINGKAT'])) {
            return true;
        }
        if ($sheet === 'CATATAN' && empty($mappedRow['KODE'])) {
            return true;
        }
        if ($sheet === 'PEMBINAAN' && empty($mappedRow['NIS'])) {
            return true;
        }
        if ($sheet === 'SETUP') {
            return false;
        }

        return false;
    }

    private function validateRow(string $sheet, array $row, array $context): array
    {
        if ($sheet === 'DATA_SISWA') {
            $nis = (string) $row['NIS'];
            $name = $row['NAMA SISWA'];
            $className = $row['KELAS'];

            if (! $nis || ! $name || ! $className) {
                return ['status' => 'invalid', 'errors' => ['NIS, Nama, atau Kelas kosong']];
            }

            $class = $context['classRooms']->get($className);
            if (! $class) {
                return ['status' => 'invalid', 'errors' => ["Kelas '$className' tidak ditemukan di Tahun Ajaran yang dipilih"]];
            }

            return [
                'status' => 'update', // Upsert logic
                'data' => [
                    'school_id' => 1,
                    'nis' => $nis,
                    'name' => $name,
                    'class_room_id' => $class->id,
                    'parent_name' => $row['NAMA ORANG TUA / WALI'] ?? null,
                    'parent_phone' => $row['NO. HP ORANG TUA'] ?? null,
                    'status' => $row['STATUS'] ?? 'Active',
                    'notes' => $row['KETERANGAN'] ?? null,
                ],
            ];
        }

        if ($sheet === 'CATATAN') {
            $date = $row['TANGGAL'];
            $nis = (string) $row['NIS'];
            $code = $row['KODE'];
            $points = (float) ($row['POIN'] ?? 0);
            $desc = $row['URAIAN / KRONOLOGI'] ?? '';
            $pencatat = $row['PENCATAT'] ?? null;

            if (! $date || ! $nis || ! $code) {
                return ['status' => 'invalid', 'errors' => ['Tanggal, NIS, atau Kode kosong']];
            }

            // Format date
            try {
                if ($date instanceof \DateTimeInterface) {
                    $eventDate = Carbon::instance($date)->format('Y-m-d');
                } else {
                    $eventDate = Carbon::parse($date)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                return ['status' => 'invalid', 'errors' => ['Format tanggal tidak valid']];
            }

            // Find student
            $student = Student::where('nis', $nis)->first();
            if (! $student) {
                return ['status' => 'invalid', 'errors' => ["Siswa dengan NIS '$nis' tidak ditemukan"]];
            }

            // Find Semester
            $targetSemester = null;
            $matchedSemesters = $context['semesters']->filter(function ($sem) use ($eventDate) {
                $start = $sem->start_date instanceof Carbon ? $sem->start_date->format('Y-m-d') : substr((string) $sem->start_date, 0, 10);
                $end = $sem->end_date instanceof Carbon ? $sem->end_date->format('Y-m-d') : substr((string) $sem->end_date, 0, 10);

                return $eventDate >= $start && $eventDate <= $end;
            });

            if ($matchedSemesters->count() === 1) {
                $targetSemester = $matchedSemesters->first();
            } elseif ($matchedSemesters->count() > 1) {
                return ['status' => 'invalid', 'errors' => ["Tanggal '$eventDate' cocok dengan lebih dari satu Semester (ambigu)"]];
            }

            if (! $targetSemester) {
                return ['status' => 'invalid', 'errors' => ["Tanggal '$eventDate' tidak cocok dengan Semester manapun"]];
            }

            if ($targetSemester->status === 'Closed') {
                return ['status' => 'invalid', 'errors' => ['Tidak dapat mengimpor ke Semester yang sudah ditutup']];
            }

            // Find Violation/Achievement
            $violation = $context['violations']->get($code);
            $achievement = $context['achievements']->get($code);

            if (! $violation && ! $achievement) {
                return ['status' => 'invalid', 'errors' => ["Kode '$code' tidak ditemukan di Master"]];
            }

            $eventType = $violation ? 'violation' : 'achievement';

            // Recorder Matching
            $recordedById = $context['currentUser']->id;
            $originalRecorder = $pencatat;

            if ($pencatat) {
                $normName = trim($pencatat);
                $matchedUsers = $context['users']->filter(function ($u) use ($normName) {
                    return strcasecmp(trim($u->name), $normName) === 0;
                });
                if ($matchedUsers->count() === 1) {
                    $recordedById = $matchedUsers->first()->id;
                }
            }

            // Duplicate Check
            $isDuplicate = DisciplineEvent::where('student_id', $student->id)
                ->whereDate('event_date', $eventDate)
                ->when($violation, fn ($q) => $q->where('violation_id', $violation->id))
                ->when($achievement, fn ($q) => $q->where('achievement_id', $achievement->id))
                ->where('description', $desc)
                ->exists();

            if ($isDuplicate) {
                return ['status' => 'duplicate', 'errors' => []];
            }

            return [
                'status' => 'valid',
                'data' => [
                    'semester_id' => $targetSemester->id,
                    'student_id' => $student->id,
                    'event_date' => $eventDate,
                    'event_type' => $eventType,
                    'violation_id' => $violation ? $violation->id : null,
                    'achievement_id' => $achievement ? $achievement->id : null,
                    'points_effect' => $points,
                    'description' => $desc,
                    'recorded_by' => $recordedById,
                    'original_recorder_name' => $originalRecorder,
                ],
            ];
        }

        if ($sheet === 'PEMBINAAN') {
            $date = $row['TANGGAL'];
            $nis = (string) $row['NIS'];
            $desc = $row['MASALAH'] ?? '';
            $action = $row['TINDAKAN'] ?? '';
            $status = $row['HASIL'] ?? 'Belum Berubah';
            $pencatat = $row['PENGURUS'] ?? null;

            if (! $date || ! $nis || ! $desc) {
                return ['status' => 'invalid', 'errors' => ['Tanggal, NIS, atau Masalah kosong']];
            }

            // Format date
            try {
                if ($date instanceof \DateTimeInterface) {
                    $eventDate = Carbon::instance($date)->format('Y-m-d');
                } else {
                    $eventDate = Carbon::parse($date)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                return ['status' => 'invalid', 'errors' => ['Format tanggal tidak valid']];
            }

            $student = Student::where('nis', $nis)->first();
            if (! $student) {
                return ['status' => 'invalid', 'errors' => ["Siswa dengan NIS '$nis' tidak ditemukan"]];
            }

            // Find Semester
            $targetSemester = null;
            $matchedSemesters = $context['semesters']->filter(function ($sem) use ($eventDate) {
                $start = $sem->start_date instanceof Carbon ? $sem->start_date->format('Y-m-d') : substr((string) $sem->start_date, 0, 10);
                $end = $sem->end_date instanceof Carbon ? $sem->end_date->format('Y-m-d') : substr((string) $sem->end_date, 0, 10);

                return $eventDate >= $start && $eventDate <= $end;
            });

            if ($matchedSemesters->count() === 1) {
                $targetSemester = $matchedSemesters->first();
            } elseif ($matchedSemesters->count() > 1) {
                return ['status' => 'invalid', 'errors' => ["Tanggal '$eventDate' cocok dengan lebih dari satu Semester (ambigu)"]];
            }

            if (! $targetSemester) {
                return ['status' => 'invalid', 'errors' => ["Tanggal '$eventDate' tidak cocok dengan Semester manapun"]];
            }

            if ($targetSemester->status === 'Closed') {
                return ['status' => 'invalid', 'errors' => ['Tidak dapat mengimpor ke Semester yang sudah ditutup']];
            }

            // Recorder Matching
            $recordedById = $context['currentUser']->id;
            $originalRecorder = $pencatat;

            if ($pencatat) {
                $normName = trim($pencatat);
                $matchedUsers = $context['users']->filter(function ($u) use ($normName) {
                    return strcasecmp(trim($u->name), $normName) === 0;
                });
                if ($matchedUsers->count() === 1) {
                    $recordedById = $matchedUsers->first()->id;
                }
            }

            // Duplicate Check
            $isDuplicate = CounselingRecord::where('student_id', $student->id)
                ->where('date_scheduled', $eventDate)
                ->where('reason', $desc)
                ->exists();

            if ($isDuplicate) {
                return ['status' => 'duplicate', 'errors' => []];
            }

            return [
                'status' => 'valid',
                'data' => [
                    'semester_id' => $targetSemester->id,
                    'student_id' => $student->id,
                    'counselor_id' => $recordedById,
                    'original_recorder_name' => $originalRecorder,
                    'date_scheduled' => $eventDate,
                    'date_completed' => clone Carbon::parse($eventDate), // Based on audit
                    'reason' => $desc,
                    'follow_up_action' => $action,
                    'status' => $status,
                ],
            ];
        }

        if ($sheet === 'MASTER_PELANGGARAN') {
            $code = $row['KODE'] ?? '';
            $name = $row['JENIS PELANGGARAN'] ?? '';
            $category = $row['KATEGORI'] ?? '';
            $points = (float) ($row['POIN'] ?? 0);
            $desc = $row['KETERANGAN'] ?? null;

            if (! $code || ! $name || ! $points) {
                return ['status' => 'invalid', 'errors' => ['Kode, Nama, atau Poin kosong']];
            }

            return [
                'status' => 'update',
                'data' => [
                    'school_id' => 1,
                    'code' => $code,
                    'name' => $name,
                    'category' => $category,
                    'points' => $points,
                    'description' => $desc,
                    'is_active' => true,
                ],
            ];
        }

        if ($sheet === 'MASTER_PRESTASI') {
            $code = $row['KODE'] ?? '';
            $name = $row['JENIS PRESTASI / PERBAIKAN'] ?? '';
            $level = $row['TINGKAT'] ?? '';
            $points = (float) ($row['POIN PENGURANG'] ?? 0);

            if (! $code || ! $name || ! $points) {
                return ['status' => 'invalid', 'errors' => ['Kode, Nama, atau Poin kosong']];
            }

            return [
                'status' => 'update',
                'data' => [
                    'school_id' => 1,
                    'code' => $code,
                    'name' => $name,
                    'level' => $level,
                    'deduction_points' => $points,
                    'is_active' => true,
                ],
            ];
        }

        if ($sheet === 'MASTER_SANKSI') {
            $level = (int) ($row['TINGKAT'] ?? 0);
            $min = (float) ($row['POIN MULAI'] ?? 0);
            $max = $row['POIN SAMPAI'] ?? null;
            $action = $row['TINDAKAN'] ?? '';

            if (! $level || $min === null || ! $action) {
                return ['status' => 'invalid', 'errors' => ['Tingkat, Poin Mulai, atau Tindakan kosong']];
            }

            return [
                'status' => 'update',
                'data' => [
                    'level' => $level,
                    'min_points' => $min,
                    'max_points' => $max,
                    'action' => $action,
                    'executor' => $row['PELAKSANA'] ?? 'Wali Kelas',
                    'description' => $row['KETERANGAN'] ?? null,
                    'is_active' => true,
                ],
            ];
        }

        // SETUP sheet needs manual parsing since it's a key-value structure without standard headers
        if ($sheet === 'SETUP') {
            // We just grab raw row values.
            $vals = array_values($row);
            if (count($vals) >= 3) {
                $key = $vals[1];
                $val = $vals[2];
                if ($key === 'Nama Sekolah') {
                    return [
                        'status' => 'update',
                        'data' => ['name' => $val],
                    ];
                }
                if ($key === 'Alamat Sekolah') {
                    return [
                        'status' => 'update',
                        'data' => ['address' => $val],
                    ];
                }
            }

            return ['status' => 'skip', 'errors' => []];
        }

        return ['status' => 'invalid', 'errors' => ['Sheet tidak dikenal']];
    }
}
