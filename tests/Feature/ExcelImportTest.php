<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\DisciplineEvent;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed'); // Or create necessary dependencies

        // Use seeded data
        $this->school = School::first();
        $this->academicYear = AcademicYear::first();
        $this->semester = Semester::first();

        $this->classRoom = ClassRoom::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => '7A',
            'grade_level' => '7',
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Super Admin');
    }

    public function test_can_upload_and_preview_data_siswa()
    {
        Storage::fake('local');

        $csvContent = "NO,NIS,NAMA SISWA,KELAS,WALI KELAS,NAMA ORANG TUA / WALI,NO. HP ORANG TUA,STATUS,KETERANGAN\n".
                      '1,12345,Budi,7A,Suryanto,Pak Budi,0812,Aktif,Baru';

        $file = UploadedFile::fake()->createWithContent('data_siswa.csv', $csvContent);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('imports.upload'), [
                'file' => $file,
                'sheet' => 'DATA_SISWA',
                'academic_year_id' => $this->academicYear->id,
            ]);

        if ($response->status() !== 200) {
            echo "\nUpload error: ".$response->content()."\n";
        }
        $response->assertStatus(200);
        $response->assertJsonStructure(['preview_id', 'stats', 'preview_rows']);

        $this->assertEquals(1, $response->json('stats.valid'));

        // Now Commit
        $commitResponse = $this->postJson(route('imports.commit'), [
            'preview_id' => $response->json('preview_id'),
        ]);

        $commitResponse->assertStatus(200);

        $this->assertDatabaseHas('students', [
            'nis' => '12345',
            'name' => 'Budi',
            'class_room_id' => $this->classRoom->id,
        ]);
    }

    public function test_duplicate_events_are_skipped()
    {
        Storage::fake('local');

        $student = Student::create([
            'school_id' => $this->school->id,
            'class_room_id' => $this->classRoom->id,
            'nis' => '999',
            'name' => 'Test Student',
            'status' => 'Active',
        ]);

        $violation = Violation::create([
            'code' => 'V1',
            'name' => 'Viol',
            'category' => 'Cat',
            'points' => 10,
        ]);

        // Ensure event date falls within the seeded semester
        $eventDate = $this->semester->start_date->format('Y-m-d');
        $eventDate2 = $this->semester->start_date->addDay()->format('Y-m-d');

        // Existing event
        DisciplineEvent::create([
            'semester_id' => $this->semester->id,
            'student_id' => $student->id,
            'event_date' => $eventDate,
            'event_type' => 'violation',
            'violation_id' => $violation->id,
            'points_effect' => 10,
            'description' => 'Did something bad',
            'recorded_by' => $this->adminUser->id,
        ]);

        $csvContent = "NO,TANGGAL,NIS,NAMA SISWA,KELAS,KODE,JENIS KEJADIAN,KATEGORI,POIN,URAIAN / KRONOLOGI,PENCATAT\n".
                      "1,{$eventDate},999,Test Student,7A,V1,Viol,Cat,10,Did something bad,Guru A\n".
                      "2,{$eventDate2},999,Test Student,7A,V1,Viol,Cat,10,New event,Guru A";

        $file = UploadedFile::fake()->createWithContent('catatan.csv', $csvContent);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('imports.upload'), [
                'file' => $file,
                'sheet' => 'CATATAN',
            ]);

        $response->assertStatus(200);

        // Row 1 should be duplicate, Row 2 should be valid
        $this->assertEquals(1, $response->json('stats.valid'));
        $this->assertEquals(1, $response->json('stats.duplicate'));

        $commitResponse = $this->postJson(route('imports.commit'), [
            'preview_id' => $response->json('preview_id'),
        ]);

        $commitResponse->assertStatus(200);

        // Total events should be 2 now
        $this->assertEquals(2, DisciplineEvent::count());

        $newEvent = DisciplineEvent::whereDate('event_date', $eventDate2)->first();
        $this->assertEquals('Guru A', $newEvent->original_recorder_name);
        $this->assertEquals($this->adminUser->id, $newEvent->recorded_by); // fallback to importer
    }

    public function test_catatan_semester_resolution_rules()
    {
        Storage::fake('local');

        $student = Student::create([
            'school_id' => $this->school->id,
            'class_room_id' => $this->classRoom->id,
            'nis' => '888',
            'name' => 'Student 888',
            'status' => 'Active',
        ]);

        $violation = Violation::create([
            'code' => 'V2',
            'name' => 'Viol 2',
            'category' => 'Cat',
            'points' => 10,
        ]);

        $startDate = $this->semester->start_date->format('Y-m-d');
        $endDate = $this->semester->end_date->format('Y-m-d');
        $beforeDate = $this->semester->start_date->copy()->subDay()->format('Y-m-d');
        $afterDate = $this->semester->end_date->copy()->addDay()->format('Y-m-d');

        // Create an overlapping semester to test ambiguity
        $overlappingSemester = Semester::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Ganjil Overlap',
            'start_date' => $this->semester->start_date,
            'end_date' => $this->semester->end_date,
            'status' => 'planned',
        ]);

        $csvContent = "NO,TANGGAL,NIS,NAMA SISWA,KELAS,KODE,JENIS KEJADIAN,KATEGORI,POIN,URAIAN / KRONOLOGI,PENCATAT\n".
                      "1,{$startDate},888,Student 888,7A,V2,Viol,Cat,10,Start date,Guru A\n".
                      "2,{$endDate},888,Student 888,7A,V2,Viol,Cat,10,End date,Guru A\n".
                      "3,{$beforeDate},888,Student 888,7A,V2,Viol,Cat,10,Before date,Guru A\n".
                      "4,{$afterDate},888,Student 888,7A,V2,Viol,Cat,10,After date,Guru A\n".
                      "5,{$startDate},888,Student 888,7A,V2,Viol,Cat,10,Overlap,Guru A";

        $file = UploadedFile::fake()->createWithContent('catatan.csv', $csvContent);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('imports.upload'), [
                'file' => $file,
                'sheet' => 'CATATAN',
                'semester_id' => $overlappingSemester->id, // Attempt to manually override
            ]);

        $response->assertStatus(200);
        $preview = $response->json('preview_rows');

        // Row 1 & 2: Ambiguous (due to overlapping semester)
        $this->assertEquals('invalid', $preview[0]['status']);
        $this->assertStringContainsString('lebih dari satu Semester (ambigu)', $preview[0]['errors'][0]);

        $this->assertEquals('invalid', $preview[1]['status']);
        $this->assertStringContainsString('lebih dari satu Semester (ambigu)', $preview[1]['errors'][0]);

        // Row 3: Before date (no match)
        $this->assertEquals('invalid', $preview[2]['status']);
        $this->assertStringContainsString('tidak cocok dengan Semester manapun', $preview[2]['errors'][0]);

        // Row 4: After date (no match)
        $this->assertEquals('invalid', $preview[3]['status']);
        $this->assertStringContainsString('tidak cocok dengan Semester manapun', $preview[3]['errors'][0]);

        // Fix the overlap so we can test success
        $overlappingSemester->delete();

        $responseSuccess = $this->actingAs($this->adminUser)
            ->postJson(route('imports.upload'), [
                'file' => $file,
                'sheet' => 'CATATAN',
            ]);

        $responseSuccess->assertStatus(200);
        $previewSuccess = $responseSuccess->json('preview_rows');

        // Now Row 1 and 2 should be valid (exactly on start and end dates)
        $this->assertEquals('valid', $previewSuccess[0]['status']);
        $this->assertEquals($this->semester->id, $previewSuccess[0]['data']['semester_id']); // Resolved correctly!

        $this->assertEquals('valid', $previewSuccess[1]['status']);
        $this->assertEquals($this->semester->id, $previewSuccess[1]['data']['semester_id']); // Resolved correctly!
    }
}
