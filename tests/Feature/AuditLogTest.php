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
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Super Admin');

        $this->teacherUser = User::factory()->create();
        $this->teacherUser->assignRole('Teacher');

        $this->school = School::first();
        $this->academicYear = AcademicYear::first();

        $this->classRoom = ClassRoom::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => '7A',
            'grade_level' => '7',
        ]);

        $this->activeSemester = Semester::firstOrCreate([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Ganjil Audit Test',
        ], [
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => 'Active',
        ]);

        // Ensure it's active
        $this->activeSemester->update(['status' => 'Active']);
    }

    public function test_student_creation_generates_audit_log()
    {
        $this->actingAs($this->adminUser)
            ->post(route('students.store'), [
                'nis' => '12345',
                'name' => 'Audit Test Student',
                'class_room_id' => $this->classRoom->id,
                'status' => 'Active',
            ]);

        $student = Student::where('nis', '12345')->first();
        $this->assertNotNull($student);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'subject_type' => Student::class,
            'subject_id' => $student->id,
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_discipline_event_creation_generates_audit_log()
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'class_room_id' => $this->classRoom->id,
            'nis' => '999',
            'name' => 'Test',
            'status' => 'Active',
        ]);

        $violation = Violation::create([
            'code' => 'V1',
            'name' => 'Test Violation',
            'category' => 'Test',
            'points' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('discipline-events.store'), [
                'student_id' => $student->id,
                'event_date' => '2026-08-01',
                'event_type' => 'violation',
                'violation_id' => $violation->id,
            ]);

        $response->assertSessionHasNoErrors();

        if ($response->isRedirect() && session()->has('error')) {
            dump(session()->get('error'));
        }

        $event = DisciplineEvent::where('student_id', $student->id)->first();
        $this->assertNotNull($event);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'subject_type' => DisciplineEvent::class,
            'subject_id' => $event->id,
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_authorized_user_can_view_audit_logs()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('audit-logs.index'));

        $response->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_view_audit_logs()
    {
        $response = $this->actingAs($this->teacherUser)
            ->get(route('audit-logs.index'));

        $response->assertStatus(403);
    }
}
