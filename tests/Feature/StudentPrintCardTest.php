<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\DisciplineEvent;
use App\Models\School;
use App\Models\Semester;
use App\Models\SemesterSnapshot;
use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPrintCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->school = School::first();
        $this->academicYear = AcademicYear::first();

        $this->activeSemester = Semester::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Active Semester',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'status' => 'Active',
        ]);

        $this->closedSemester = Semester::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Closed Semester',
            'start_date' => '2025-07-01',
            'end_date' => '2025-12-31',
            'status' => 'Closed',
        ]);

        $this->classRoom = ClassRoom::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => '7A',
            'grade_level' => '7',
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'class_room_id' => $this->classRoom->id,
            'nis' => '111',
            'name' => 'Test Student',
            'status' => 'Active',
        ]);

        $this->violation = Violation::create([
            'code' => 'V1',
            'name' => 'Viol',
            'category' => 'Cat',
            'points' => 15,
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Super Admin');
    }

    public function test_authorized_user_can_access_print_card()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('students.print-card', $this->student->id));

        $response->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_access_print_card()
    {
        // No roles assigned
        $unauthorizedUser = User::factory()->create();

        $response = $this->actingAs($unauthorizedUser)
            ->get(route('students.print-card', $this->student->id));

        $response->assertStatus(403);
    }

    public function test_open_semester_calculates_points_live()
    {
        DisciplineEvent::create([
            'semester_id' => $this->activeSemester->id,
            'student_id' => $this->student->id,
            'event_date' => '2026-02-01',
            'event_type' => 'violation',
            'violation_id' => $this->violation->id,
            'points_effect' => 15,
            'description' => 'Test event',
            'recorded_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('students.print-card', [
                'student' => $this->student->id,
                'semester_id' => $this->activeSemester->id,
            ]));

        $response->assertStatus(200);

        // Assert inertia props have live calculation
        $response->assertInertia(fn ($page) => $page
            ->where('summary.violation_points', 15)
            ->where('summary.net_points', 15)
            ->has('events', 1)
        );
    }

    public function test_closed_semester_uses_snapshot_data()
    {
        // Snapshot points are 50, but actual events are 0.
        // This ensures the system reads from snapshot for closed semesters.
        SemesterSnapshot::create([
            'semester_id' => $this->closedSemester->id,
            'student_id' => $this->student->id,
            'class_room_id' => $this->classRoom->id,
            'violation_points' => 50,
            'achievement_points' => 0,
            'net_points' => 50,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('students.print-card', [
                'student' => $this->student->id,
                'semester_id' => $this->closedSemester->id,
            ]));

        $response->assertStatus(200);

        // Should fetch the snapshot 50, not the live 0
        $response->assertInertia(fn ($page) => $page
            ->where('summary.violation_points', 50)
            ->where('summary.net_points', 50)
            ->where('summary.risk_status', 'Selesai (Diarsipkan)')
        );
    }

    public function test_missing_snapshot_for_closed_semester_returns_error()
    {
        // No snapshot created
        $response = $this->actingAs($this->adminUser)
            ->get(route('students.print-card', [
                'student' => $this->student->id,
                'semester_id' => $this->closedSemester->id,
            ]));

        $response->assertStatus(404);
    }
}
