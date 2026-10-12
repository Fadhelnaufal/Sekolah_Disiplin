<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\DisciplineEvent;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Violation;
use App\Models\Letter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    private function createDependencies()
    {
        $school = School::create([
            'name' => 'SMK Test',
            'application_name' => 'Disiplin Pro',
            'address' => 'Jl. Test',
            'city' => 'Jakarta',
            'principal_name' => 'Kepsek Test',
            'vice_principal_name' => 'Waka Kesiswaan Test',
            'counseling_officer_name' => 'Guru BK Test'
        ]);
        
        $academicYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'start_date' => now()->subMonths(3)->format('Y-m-d'),
            'end_date' => now()->addMonths(3)->format('Y-m-d'),
            'status' => 'Active'
        ]);
        
        $semester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Ganjil',
            'start_date' => now()->subMonths(3)->format('Y-m-d'),
            'end_date' => now()->addMonths(3)->format('Y-m-d'),
            'status' => 'Active'
        ]);
        
        $bkUser = User::factory()->create();
        $bkUser->assignRole('BK');
        
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('Teacher');
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '12345',
            'name' => 'Guru Test',
            'status' => 'Active'
        ]);
        
        $classRoom = ClassRoom::create([
            'school_id' => $school->id, 
            'academic_year_id' => $academicYear->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => '10 RPL 1',
            'grade_level' => '10'
        ]);
        
        $student = Student::create([
            'school_id' => $school->id,
            'class_room_id' => $classRoom->id,
            'nis' => '100100',
            'name' => 'Siswa Test',
            'status' => 'Active'
        ]);

        return [$semester, $student, $bkUser, $teacherUser];
    }

    public function test_authorized_role_can_view_letters_index()
    {
        list($semester, $student, $bkUser) = $this->createDependencies();

        $response = $this->actingAs($bkUser)->get(route('letters.index'));
        $response->assertStatus(200);
    }

    public function test_unauthorized_role_cannot_view_letters()
    {
        list($semester, $student, $bkUser, $teacherUser) = $this->createDependencies();

        // Teacher cannot view letters index
        $response = $this->actingAs($teacherUser)->get(route('letters.index'));
        $response->assertStatus(403);
    }

    public function test_authorized_role_can_create_letter_and_points_are_computed_from_backend()
    {
        list($semester, $student, $bkUser) = $this->createDependencies();

        $violation = Violation::create([
            'code' => 'TEST-VIO',
            'name' => 'Test Vio',
            'category' => 'Ringan',
            'points' => 15,
        ]);

        DisciplineEvent::create([
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'event_date' => now(),
            'event_type' => 'violation',
            'violation_id' => $violation->id,
            'points_effect' => 15,
            'recorded_by' => $bkUser->id
        ]);

        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'parent_call',
            'subject' => 'Panggilan Orang Tua',
            'points' => 999 // Malicious injection attempt
        ]);

        $response->assertRedirect(route('letters.index'));
        
        $this->assertDatabaseHas('letters', [
            'student_id' => $student->id,
            'type' => 'parent_call',
            'status' => 'Generated'
        ]);

        $letter = Letter::first();
        // Point stored in metadata must be 15, not 999
        $this->assertEquals(15, $letter->metadata['net_points']);
    }

    public function test_recent_events_are_limited_to_5()
    {
        list($semester, $student, $bkUser) = $this->createDependencies();

        $violation = Violation::create([
            'code' => 'TEST-VIO',
            'name' => 'Test Vio',
            'category' => 'Ringan',
            'points' => 5,
        ]);

        // Insert 10 events
        for ($i = 0; $i < 10; $i++) {
            DisciplineEvent::create([
                'student_id' => $student->id,
                'semester_id' => $semester->id,
                'event_date' => now()->subDays(10 - $i),
                'event_type' => 'violation',
                'violation_id' => $violation->id,
                'points_effect' => 5,
                'recorded_by' => $bkUser->id
            ]);
        }

        $service = new \App\Services\LetterService(
            new \App\Services\StudentPointService(),
            new \App\Services\SanctionEngineService(new \App\Services\StudentPointService())
        );

        $data = $service->buildLetterData($student, $semester, 'notification');

        // Verify recent events is exactly 5
        $this->assertCount(5, $data['recent_events']);
        
        // Ensure net points is 50
        $this->assertEquals(50, $data['net_points']);
    }

    public function test_letter_generation_with_empty_data()
    {
        list($semester, $student, $bkUser) = $this->createDependencies();

        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'notification'
        ]);

        $response->assertRedirect(route('letters.index'));
        
        $letter = Letter::first();
        $this->assertEquals(0, $letter->metadata['net_points']);
    }

    public function test_fails_when_signatory_is_missing()
    {
        list($semester, $student, $bkUser) = $this->createDependencies();
        
        $school = School::first();
        // Clear counseling officer name
        $school->update(['counseling_officer_name' => null]);

        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'call_1'
        ]);

        $response->assertSessionHasErrors(['type']);
        $this->assertDatabaseCount('letters', 0);
    }

    public function test_stores_metadata_signatory_and_saves_to_local_disk()
    {
        list($semester, $student, $bkUser) = $this->createDependencies();
        
        $school = School::first();
        $school->update([
            'vice_principal_name' => 'Waka Kesiswaan Test',
            'principal_name' => 'Kepsek Test'
        ]);

        \Illuminate\Support\Facades\Storage::fake('local');

        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'call_2'
        ]);

        $response->assertRedirect(route('letters.index'));
        
        $letter = Letter::first();
        
        // Metadata saved correctly
        $this->assertEquals('Wakil Kepala Sekolah Bidang Kesiswaan', $letter->metadata['signatory_title']);
        $this->assertEquals('Waka Kesiswaan Test', $letter->metadata['signatory_name']);

        // File exists on local disk
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($letter->pdf_path);
    }

    public function test_cleans_up_orphaned_file_on_db_failure()
    {
        list($semester, $student, $bkUser) = $this->createDependencies();
        
        $school = School::first();
        $school->update([
            'principal_name' => 'Kepsek Test'
        ]);

        \Illuminate\Support\Facades\Storage::fake('local');

        // Force DB creation to fail
        Letter::saving(function () {
            throw new \Exception('Simulated DB Failure');
        });
        
        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'call_3'
        ]);

        $response->assertStatus(500); // Because we threw an unhandled exception
        
        // Ensure no files are left in the local disk
        $files = \Illuminate\Support\Facades\Storage::disk('local')->files('letters');
        $this->assertEmpty($files, 'Orphaned PDF file was not cleaned up.');
    }
}
