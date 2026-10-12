<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\ClassRoom;
use App\Models\DisciplineEvent;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Violation;
use App\Services\StudentPointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DisciplineEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed basic permissions/roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    private function createDependencies()
    {
        $school = School::create([
            'name' => 'SMK Test',
            'application_name' => 'Disiplin Pro',
            'address' => 'Jl. Test No 1',
            'city' => 'Jakarta'
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
        
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('Teacher');
        $teacherUser->assignRole('Tata Tertib'); // add role so it has access
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

        return [$semester, $student, $teacherUser];
    }

    public function test_achievement_points_deduction_generates_negative_points_effect()
    {
        list($semester, $student, $teacherUser) = $this->createDependencies();

        $achievement = Achievement::create([
            'code' => 'TEST-ACH',
            'name' => 'Achievement Test',
            'level' => 'Sekolah',
            'deduction_points' => 10,
            'is_active' => true
        ]);

        $response = $this->actingAs($teacherUser)->post(route('discipline-events.store'), [
            'student_id' => $student->id,
            'event_date' => now()->format('Y-m-d'),
            'event_type' => 'achievement',
            'achievement_id' => $achievement->id,
            'description' => 'Test deduction'
        ]);

        $response->assertRedirect(route('discipline-events.index'));
        
        $this->assertDatabaseHas('discipline_events', [
            'student_id' => $student->id,
            'event_type' => 'achievement',
            'points_effect' => -10
        ]);
    }

    public function test_violation_and_achievement_results_in_correct_net_points()
    {
        list($semester, $student, $teacherUser) = $this->createDependencies();

        $violation = Violation::create([
            'code' => 'TEST-VIO',
            'name' => 'Violation Test',
            'category' => 'Ringan',
            'points' => 5,
            'is_active' => true
        ]);

        $achievement = Achievement::create([
            'code' => 'TEST-ACH',
            'name' => 'Achievement Test',
            'level' => 'Sekolah',
            'deduction_points' => 10,
            'is_active' => true
        ]);

        // Post violation (5 points)
        $this->actingAs($teacherUser)->post(route('discipline-events.store'), [
            'student_id' => $student->id,
            'event_date' => now()->format('Y-m-d'),
            'event_type' => 'violation',
            'violation_id' => $violation->id
        ]);

        // Post achievement (-10 points)
        $this->actingAs($teacherUser)->post(route('discipline-events.store'), [
            'student_id' => $student->id,
            'event_date' => now()->format('Y-m-d'),
            'event_type' => 'achievement',
            'achievement_id' => $achievement->id
        ]);

        $pointService = new StudentPointService();
        $netPoints = $pointService->getNetPoints($student, $semester);

        // Net points should be max(0, violation - achievement) => max(0, 5 - 10) = 0
        $this->assertEquals(0, $netPoints);
        $this->assertEquals(5, $pointService->getViolationPoints($student, $semester));
        $this->assertEquals(10, $pointService->getAchievementPoints($student, $semester));
    }

    public function test_net_points_are_never_negative()
    {
        list($semester, $student, $teacherUser) = $this->createDependencies();

        $achievement = Achievement::create([
            'code' => 'TEST-ACH-MAX',
            'name' => 'Achievement Test',
            'level' => 'Nasional',
            'deduction_points' => 100,
            'is_active' => true
        ]);

        $this->actingAs($teacherUser)->post(route('discipline-events.store'), [
            'student_id' => $student->id,
            'event_date' => now()->format('Y-m-d'),
            'event_type' => 'achievement',
            'achievement_id' => $achievement->id
        ]);

        $pointService = new StudentPointService();
        $netPoints = $pointService->getNetPoints($student, $semester);

        $this->assertEquals(0, $netPoints); // Should floor at 0
    }

    public function test_altering_master_achievement_does_not_alter_historical_points_effect()
    {
        list($semester, $student, $teacherUser) = $this->createDependencies();

        $achievement = Achievement::create([
            'code' => 'TEST-ACH',
            'name' => 'Achievement Test',
            'level' => 'Sekolah',
            'deduction_points' => 10,
            'is_active' => true
        ]);

        $this->actingAs($teacherUser)->post(route('discipline-events.store'), [
            'student_id' => $student->id,
            'event_date' => now()->format('Y-m-d'),
            'event_type' => 'achievement',
            'achievement_id' => $achievement->id
        ]);

        // Mutate master data
        $achievement->update(['deduction_points' => 50]);

        $event = DisciplineEvent::where('student_id', $student->id)->first();
        
        // Historical points effect should remain -10
        $this->assertEquals(-10, $event->points_effect);
    }

    public function test_user_cannot_forge_points_effect_via_request()
    {
        list($semester, $student, $teacherUser) = $this->createDependencies();

        $violation = Violation::create([
            'code' => 'TEST-VIO',
            'name' => 'Violation Test',
            'category' => 'Ringan',
            'points' => 5,
            'is_active' => true
        ]);

        // Attempting to send points_effect maliciously
        $this->actingAs($teacherUser)->post(route('discipline-events.store'), [
            'student_id' => $student->id,
            'event_date' => now()->format('Y-m-d'),
            'event_type' => 'violation',
            'violation_id' => $violation->id,
            'points_effect' => 100 // malicious injection
        ]);

        $event = DisciplineEvent::where('student_id', $student->id)->first();
        
        // System should ignore input and strictly fetch 5 from DB
        $this->assertEquals(5, $event->points_effect);
    }

    public function test_role_without_permission_is_rejected()
    {
        list($semester, $student) = $this->createDependencies();
        
        $unauthorizedUser = User::factory()->create();
        // Missing Teacher, Super Admin, or Tata Tertib role

        $violation = Violation::create([
            'code' => 'TEST-VIO',
            'name' => 'Violation Test',
            'category' => 'Ringan',
            'points' => 5,
        ]);

        $response = $this->actingAs($unauthorizedUser)->post(route('discipline-events.store'), [
            'student_id' => $student->id,
            'event_date' => now()->format('Y-m-d'),
            'event_type' => 'violation',
            'violation_id' => $violation->id,
        ]);

        $response->assertStatus(403);
    }
}
