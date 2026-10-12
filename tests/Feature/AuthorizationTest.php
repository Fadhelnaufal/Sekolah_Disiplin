<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $roles = ['Super Admin', 'Tata Tertib', 'Teacher', 'BK', 'Principal'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    public function test_unauthenticated_user_cannot_access_dashboard()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_teacher_can_access_students_index()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $response = $this->actingAs($teacher)->get('/students');
        $response->assertStatus(200);
    }

    public function test_teacher_cannot_access_academic_years()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $response = $this->actingAs($teacher)->get('/academic-years');
        $response->assertStatus(403);
    }

    public function test_tata_tertib_can_access_teachers_index()
    {
        $tataTertib = User::factory()->create();
        $tataTertib->assignRole('Tata Tertib');

        $response = $this->actingAs($tataTertib)->get('/teachers');
        $response->assertStatus(200);
    }

    public function test_tata_tertib_cannot_access_academic_years()
    {
        $tataTertib = User::factory()->create();
        $tataTertib->assignRole('Tata Tertib');

        $response = $this->actingAs($tataTertib)->get('/academic-years');
        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_academic_years()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $response = $this->actingAs($superAdmin)->get('/academic-years');
        $response->assertStatus(200);
    }

    public function test_bk_cannot_access_create_discipline_events()
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        $response = $this->actingAs($bk)->get('/discipline-events/create');
        $response->assertStatus(403);
    }

    public function test_teacher_cannot_destroy_discipline_events()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        // Create valid dependencies to avoid 404
        $school = \App\Models\School::create([
            'npsn' => '123', 'name' => 'Test', 'address' => 'Test', 'city' => 'Test', 'application_name' => 'Test'
        ]);
        $academicYear = \App\Models\AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active'
        ]);
        $semester = \App\Models\Semester::create([
            'academic_year_id' => $academicYear->id, 'name' => 'Ganjil', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'status' => 'Active'
        ]);
        $classRoom = \App\Models\ClassRoom::create([
            'school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name' => 'Class', 'grade_level' => '10', 'status' => 'active'
        ]);
        $student = \App\Models\Student::create([
            'school_id' => $school->id, 'nis' => '111', 'name' => 'Student', 'class_room_id' => $classRoom->id, 'status' => 'Active'
        ]);
        $event = \App\Models\DisciplineEvent::create([
            'student_id' => $student->id, 'semester_id' => $semester->id, 'event_date' => '2026-01-01', 'event_type' => 'violation', 'points_effect' => 5
        ]);

        $response = $this->actingAs($teacher)->delete('/discipline-events/' . $event->id);
        $response->assertStatus(403);
    }

    public function test_teacher_cannot_access_student_outside_homeroom()
    {
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('Teacher');
        
        $teacher = \App\Models\Teacher::create([
            'user_id' => $teacherUser->id,
            'employee_number' => '123',
            'name' => 'Test Teacher',
            'status' => 'active'
        ]);

        $school = \App\Models\School::create([
            'npsn' => '124', 'name' => 'Test2', 'address' => 'Test', 'city' => 'Test', 'application_name' => 'Test'
        ]);
        $academicYear = \App\Models\AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active'
        ]);

        $otherClass = \App\Models\ClassRoom::create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Other Class',
            'grade_level' => '10',
            'status' => 'active'
        ]);

        $student = \App\Models\Student::create([
            'school_id' => $school->id,
            'nis' => '9999',
            'name' => 'Test Student',
            'class_room_id' => $otherClass->id,
            'status' => 'Active'
        ]);

        $response = $this->actingAs($teacherUser)->get('/students/' . $student->id);
        $response->assertStatus(403);
    }

    public function test_teacher_can_report_student_outside_homeroom()
    {
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('Teacher');
        
        $teacher = \App\Models\Teacher::create([
            'user_id' => $teacherUser->id,
            'employee_number' => '123',
            'name' => 'Test Teacher',
            'status' => 'active'
        ]);

        $school = \App\Models\School::create([
            'npsn' => '125', 'name' => 'Test3', 'address' => 'Test', 'city' => 'Test', 'application_name' => 'Test'
        ]);
        $academicYear = \App\Models\AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active'
        ]);
        $semester = \App\Models\Semester::create([
            'academic_year_id' => $academicYear->id, 'name' => 'Ganjil', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'status' => 'Active'
        ]);

        $otherClass = \App\Models\ClassRoom::create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Other Class 2',
            'grade_level' => '10',
            'status' => 'active'
        ]);

        $student = \App\Models\Student::create([
            'school_id' => $school->id,
            'nis' => '8888',
            'name' => 'Test Student 2',
            'class_room_id' => $otherClass->id,
            'status' => 'Active'
        ]);

        $violation = \App\Models\Violation::create([
            'code' => 'V1', 'name' => 'Test', 'category' => 'Test', 'points' => 5
        ]);

        $response = $this->actingAs($teacherUser)->post('/discipline-events', [
            'student_id' => $student->id,
            'event_date' => '2026-01-01',
            'event_type' => 'violation',
            'violation_id' => $violation->id,
            'description' => 'Test'
        ]);
        
        $response->assertRedirect('/discipline-events');
        $response->assertSessionHas('success');
    }

    public function test_bk_cannot_modify_students()
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        $school = \App\Models\School::create([
            'npsn' => '126', 'name' => 'Test4', 'address' => 'Test', 'city' => 'Test', 'application_name' => 'Test'
        ]);
        $academicYear = \App\Models\AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active'
        ]);
        $classRoom = \App\Models\ClassRoom::create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Other Class 3',
            'grade_level' => '10',
            'status' => 'active'
        ]);
        $student = \App\Models\Student::create([
            'school_id' => $school->id,
            'nis' => '7777',
            'name' => 'Test Student 3',
            'class_room_id' => $classRoom->id,
            'status' => 'Active'
        ]);

        $responseCreate = $this->actingAs($bk)->get('/students/create');
        $responseCreate->assertStatus(403);

        $responseEdit = $this->actingAs($bk)->get('/students/' . $student->id . '/edit');
        $responseEdit->assertStatus(403);

        $responseDelete = $this->actingAs($bk)->delete('/students/' . $student->id);
        $responseDelete->assertStatus(403);
    }

    public function test_bk_can_access_all_students()
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');
        $bk->assignRole('Teacher'); // Testing dual role
        
        \App\Models\Teacher::create([
            'user_id' => $bk->id,
            'employee_number' => '1234',
            'name' => 'Test BK',
            'status' => 'active'
        ]);

        $school = \App\Models\School::create([
            'npsn' => '127', 'name' => 'Test5', 'address' => 'Test', 'city' => 'Test', 'application_name' => 'Test'
        ]);
        $academicYear = \App\Models\AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active'
        ]);
        $classRoom = \App\Models\ClassRoom::create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Other Class 4',
            'grade_level' => '10',
            'status' => 'active'
        ]);
        $student = \App\Models\Student::create([
            'school_id' => $school->id,
            'nis' => '6666',
            'name' => 'Test Student 4',
            'class_room_id' => $classRoom->id,
            'status' => 'Active'
        ]);

        $response = $this->actingAs($bk)->get('/students/' . $student->id);
        $response->assertStatus(200);
    }
}
