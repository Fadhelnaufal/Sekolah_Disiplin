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

    public function test_bk_can_access_create_discipline_events()
    {
        $school = School::create(['npsn' => '123', 'name' => 'Test', 'address' => 'Test', 'city' => 'Test', 'application_name' => 'Test']);
        $academicYear = AcademicYear::create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        Semester::create(['academic_year_id' => $academicYear->id, 'name' => 'Ganjil', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'status' => 'Active']);

        $bk = User::factory()->create();
        $bk->assignRole('BK');

        $response = $this->actingAs($bk)->get('/discipline-events/create');
        $response->assertStatus(200);

        // Principal cannot access create discipline events
        $principal = User::factory()->create();
        $principal->assignRole('Principal');
        $this->actingAs($principal)->get('/discipline-events/create')->assertStatus(403);
    }

    public function test_teacher_cannot_destroy_discipline_events()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        // Create valid dependencies to avoid 404
        $school = School::create([
            'npsn' => '123', 'name' => 'Test', 'address' => 'Test', 'city' => 'Test', 'application_name' => 'Test',
        ]);
        $academicYear = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active',
        ]);
        $semester = Semester::create([
            'academic_year_id' => $academicYear->id, 'name' => 'Ganjil', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'status' => 'Active',
        ]);
        $classRoom = ClassRoom::create([
            'school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name' => 'Class', 'grade_level' => '10', 'status' => 'active',
        ]);
        $student = Student::create([
            'school_id' => $school->id, 'nis' => '111', 'name' => 'Student', 'class_room_id' => $classRoom->id, 'status' => 'Active',
        ]);
        $event = DisciplineEvent::create([
            'student_id' => $student->id, 'semester_id' => $semester->id, 'event_date' => '2026-01-01', 'event_type' => 'violation', 'points_effect' => 5,
        ]);

        $response = $this->actingAs($teacher)->delete('/discipline-events/'.$event->id);
        $response->assertStatus(403);
    }

    public function test_teacher_cannot_access_student_outside_homeroom()
    {
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('Teacher');

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'employee_number' => '123',
            'name' => 'Test Teacher',
            'status' => 'active',
        ]);

        $school = School::create([
            'npsn' => '124', 'name' => 'Test2', 'address' => 'Test', 'city' => 'Test', 'application_name' => 'Test',
        ]);
        $academicYear = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active',
        ]);

        $otherClass = ClassRoom::create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Other Class',
            'grade_level' => '10',
            'status' => 'active',
        ]);

        $student = Student::create([
            'school_id' => $school->id,
            'nis' => '9999',
            'name' => 'Test Student',
            'class_room_id' => $otherClass->id,
            'status' => 'Active',
        ]);

        $response = $this->actingAs($teacherUser)->get('/students/'.$student->id);
        $response->assertStatus(403);
    }

    public function test_teacher_can_report_student_outside_homeroom()
    {
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('Teacher');

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'employee_number' => '123',
            'name' => 'Test Teacher',
            'status' => 'active',
        ]);

        $school = School::create([
            'npsn' => '125', 'name' => 'Test3', 'address' => 'Test', 'city' => 'Test', 'application_name' => 'Test',
        ]);
        $academicYear = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active',
        ]);
        $semester = Semester::create([
            'academic_year_id' => $academicYear->id, 'name' => 'Ganjil', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'status' => 'Active',
        ]);

        $otherClass = ClassRoom::create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Other Class 2',
            'grade_level' => '10',
            'status' => 'active',
        ]);

        $student = Student::create([
            'school_id' => $school->id,
            'nis' => '8888',
            'name' => 'Test Student 2',
            'class_room_id' => $otherClass->id,
            'status' => 'Active',
        ]);

        $violation = Violation::create([
            'code' => 'V1', 'name' => 'Test', 'category' => 'Test', 'points' => 5,
        ]);

        $response = $this->actingAs($teacherUser)->post('/discipline-events', [
            'student_id' => $student->id,
            'event_date' => '2026-01-01',
            'event_type' => 'violation',
            'violation_id' => $violation->id,
            'description' => 'Test',
        ]);

        $response->assertRedirect('/discipline-events');
        $response->assertSessionHas('success');
    }

    public function test_bk_cannot_modify_students()
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        $school = School::create([
            'npsn' => '126', 'name' => 'Test4', 'address' => 'Test', 'city' => 'Test', 'application_name' => 'Test',
        ]);
        $academicYear = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active',
        ]);
        $classRoom = ClassRoom::create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Other Class 3',
            'grade_level' => '10',
            'status' => 'active',
        ]);
        $student = Student::create([
            'school_id' => $school->id,
            'nis' => '7777',
            'name' => 'Test Student 3',
            'class_room_id' => $classRoom->id,
            'status' => 'Active',
        ]);

        $responseCreate = $this->actingAs($bk)->get('/students/create');
        $responseCreate->assertStatus(403);

        $responseEdit = $this->actingAs($bk)->get('/students/'.$student->id.'/edit');
        $responseEdit->assertStatus(403);

        $responseDelete = $this->actingAs($bk)->delete('/students/'.$student->id);
        $responseDelete->assertStatus(403);
    }

    public function test_bk_can_access_all_students()
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');
        $bk->assignRole('Teacher'); // Testing dual role

        Teacher::create([
            'user_id' => $bk->id,
            'employee_number' => '1234',
            'name' => 'Test BK',
            'status' => 'active',
        ]);

        $school = School::create([
            'npsn' => '127', 'name' => 'Test5', 'address' => 'Test', 'city' => 'Test', 'application_name' => 'Test',
        ]);
        $academicYear = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active',
        ]);
        $classRoom = ClassRoom::create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Other Class 4',
            'grade_level' => '10',
            'status' => 'active',
        ]);
        $student = Student::create([
            'school_id' => $school->id,
            'nis' => '6666',
            'name' => 'Test Student 4',
            'class_room_id' => $classRoom->id,
            'status' => 'Active',
        ]);

        $response = $this->actingAs($bk)->get('/students/'.$student->id);
        $response->assertStatus(200);
    }

    public function test_teacher_can_access_master_data_catalog_as_read_only()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $response = $this->actingAs($teacher)->get('/master-data');
        $response->assertStatus(200);
    }

    public function test_teacher_cannot_access_create_master_data()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $this->actingAs($teacher)->get('/violations/create')->assertStatus(403);
        $this->actingAs($teacher)->get('/achievements/create')->assertStatus(403);
        $this->actingAs($teacher)->get('/sanction-rules/create')->assertStatus(403);
    }

    public function test_teacher_cannot_create_or_edit_student()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $school = School::create([
            'npsn' => '999', 'name' => 'School Test', 'address' => 'Test', 'city' => 'Test', 'application_name' => 'Test',
        ]);
        $ay = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active',
        ]);
        $class = ClassRoom::create([
            'school_id' => $school->id, 'academic_year_id' => $ay->id, 'name' => '7-A', 'grade_level' => '7', 'status' => 'active',
        ]);
        $student = Student::create([
            'school_id' => $school->id, 'class_room_id' => $class->id, 'name' => 'Siswa Test', 'nis' => '9988', 'status' => 'Active',
        ]);

        $this->actingAs($teacher)->get('/students/create')->assertStatus(403);
        $this->actingAs($teacher)->get('/students/'.$student->id.'/edit')->assertStatus(403);
    }

    public function test_legacy_master_routes_redirect_to_unified_master_data_for_authorized_users()
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        $this->actingAs($bk)->get('/violations')
            ->assertRedirect(route('master-data.index', ['tab' => 'violations']));

        $this->actingAs($bk)->get('/achievements')
            ->assertRedirect(route('master-data.index', ['tab' => 'achievements']));

        $this->actingAs($bk)->get('/sanction-rules')
            ->assertRedirect(route('master-data.index', ['tab' => 'sanctions']));
    }

    public function test_admin_store_and_update_violation_redirects_to_master_data()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->post('/violations', [
            'code' => 'V-TEST-99',
            'name' => 'Uji Pelanggaran',
            'category' => 'RINGAN',
            'points' => 5,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('master-data.index', ['tab' => 'violations']));
        $this->assertDatabaseHas('violations', ['code' => 'V-TEST-99']);
    }

    public function test_bk_can_crud_master_data()
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        // 1. Can access create forms
        $this->actingAs($bk)->get('/violations/create')->assertStatus(200);
        $this->actingAs($bk)->get('/achievements/create')->assertStatus(200);
        $this->actingAs($bk)->get('/sanction-rules/create')->assertStatus(200);

        // 2. Can create violation
        $violationRes = $this->actingAs($bk)->post('/violations', [
            'code' => 'BK-V01',
            'name' => 'Pelanggaran Dibuat BK',
            'category' => 'SEDANG',
            'points' => 15,
            'is_active' => true,
        ]);
        $violationRes->assertRedirect(route('master-data.index', ['tab' => 'violations']));
        $violation = Violation::where('code', 'BK-V01')->firstOrFail();

        // 3. Can edit and update violation
        $this->actingAs($bk)->get('/violations/'.$violation->id.'/edit')->assertStatus(200);
        $updateRes = $this->actingAs($bk)->put('/violations/'.$violation->id, [
            'code' => 'BK-V01',
            'name' => 'Pelanggaran Diperbarui BK',
            'category' => 'BERAT',
            'points' => 25,
            'is_active' => true,
        ]);
        $updateRes->assertRedirect(route('master-data.index', ['tab' => 'violations']));
        $this->assertDatabaseHas('violations', ['name' => 'Pelanggaran Diperbarui BK', 'points' => 25]);

        // 4. Can delete violation
        $deleteRes = $this->actingAs($bk)->delete('/violations/'.$violation->id);
        $deleteRes->assertRedirect(route('master-data.index', ['tab' => 'violations']));
        $this->assertDatabaseMissing('violations', ['id' => $violation->id]);
    }
}
