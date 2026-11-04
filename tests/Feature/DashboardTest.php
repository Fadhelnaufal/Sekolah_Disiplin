<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;

    protected AcademicYear $academicYear;

    protected Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();

        $roles = ['Super Admin', 'Tata Tertib', 'Teacher', 'BK', 'Principal'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Negeri 1 Testing',
            'address' => 'Jl. Pendidikan No. 1',
            'city' => 'Malang',
            'application_name' => 'Disiplin Pro',
        ]);

        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'Active',
        ]);

        $this->semester = Semester::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Semester Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => 'Active',
        ]);
    }

    public function test_super_admin_receives_admin_role_context_and_quick_links()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('role_context.role', 'admin')
            ->where('role_context.banner.action_url', '/settings')
            ->has('role_context.quick_links', 4)
            ->where('role_context.quick_links.0.href', '/settings')
            ->where('role_context.quick_links.1.href', '/master-data')
            ->where('role_context.quick_links.2.href', '/imports')
            ->where('role_context.quick_links.3.href', '/audit-logs')
        );
    }

    public function test_bk_receives_bk_role_context_and_all_quick_links_accessible()
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        $response = $this->actingAs($bk)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('role_context.role', 'bk')
            ->where('role_context.banner.action_url', '/counseling-records/create')
            ->has('role_context.quick_links', 4)
            ->where('role_context.quick_links.0.href', '/counseling-records/create')
            ->where('role_context.quick_links.1.href', '/counseling-records')
            ->where('role_context.quick_links.2.href', '/reports')
            ->where('role_context.quick_links.3.href', '/students')
        );

        // Verify BK can access all 4 quick links without 403 Forbidden
        $this->actingAs($bk)->get('/counseling-records/create')->assertStatus(200);
        $this->actingAs($bk)->get('/counseling-records')->assertStatus(200);
        $this->actingAs($bk)->get('/reports')->assertStatus(200);
        $this->actingAs($bk)->get('/students')->assertStatus(200);
    }

    public function test_teacher_with_homeroom_class_receives_scoped_metrics_and_safe_quick_links()
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'employee_number' => 'GUR-001',
            'name' => 'Budi Santoso, S.Pd',
            'email' => $user->email,
            'status' => 'active',
        ]);

        $myClass = ClassRoom::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => '8-A',
            'grade_level' => 8,
            'homeroom_teacher_id' => $teacher->id,
            'status' => 'active',
        ]);

        $otherClass = ClassRoom::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => '8-B',
            'grade_level' => 8,
            'status' => 'active',
        ]);

        // 3 students in my class, 2 students in other class
        Student::create(['school_id' => $this->school->id, 'class_room_id' => $myClass->id, 'name' => 'Siswa 1', 'nis' => '1001', 'status' => 'Active']);
        Student::create(['school_id' => $this->school->id, 'class_room_id' => $myClass->id, 'name' => 'Siswa 2', 'nis' => '1002', 'status' => 'Active']);
        Student::create(['school_id' => $this->school->id, 'class_room_id' => $myClass->id, 'name' => 'Siswa 3', 'nis' => '1003', 'status' => 'Active']);
        Student::create(['school_id' => $this->school->id, 'class_room_id' => $otherClass->id, 'name' => 'Siswa 4', 'nis' => '1004', 'status' => 'Active']);
        Student::create(['school_id' => $this->school->id, 'class_room_id' => $otherClass->id, 'name' => 'Siswa 5', 'nis' => '1005', 'status' => 'Active']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('role_context.role', 'teacher')
            ->where('role_context.class_name', '8-A')
            ->where('metrics.total_students', 3) // strictly scoped to class 8-A!
            ->where('role_context.banner.action_url', '/discipline-events/create')
            ->has('role_context.quick_links', 4)
            ->where('role_context.quick_links.0.href', '/discipline-events/create')
            ->where('role_context.quick_links.1.href', '/students')
            ->where('role_context.quick_links.2.href', '/reports/homerooms')
            ->where('role_context.quick_links.3.href', '/follow-ups')
        );

        // Verify Teacher can access all 4 quick links without 403 Forbidden
        $this->actingAs($user)->get('/discipline-events/create')->assertStatus(200);
        $this->actingAs($user)->get('/students')->assertStatus(200);
        $this->actingAs($user)->get('/reports/homerooms')->assertStatus(200);
        $this->actingAs($user)->get('/follow-ups')->assertStatus(200);
    }

    public function test_tata_tertib_receives_tatib_role_context_and_safe_quick_links()
    {
        $tatib = User::factory()->create();
        $tatib->assignRole('Tata Tertib');

        $response = $this->actingAs($tatib)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('role_context.role', 'tatib')
            ->where('role_context.banner.action_url', '/discipline-events/create')
            ->has('role_context.quick_links', 4)
            ->where('role_context.quick_links.0.href', '/discipline-events/create')
            ->where('role_context.quick_links.1.href', '/master-data')
            ->where('role_context.quick_links.2.href', '/imports')
            ->where('role_context.quick_links.3.href', '/discipline-recap')
        );

        // Verify Tata Tertib can access all 4 quick links without 403 Forbidden
        $this->actingAs($tatib)->get('/discipline-events/create')->assertStatus(200);
        $this->actingAs($tatib)->get('/master-data')->assertStatus(200);
        $this->actingAs($tatib)->get('/imports')->assertStatus(200);
        $this->actingAs($tatib)->get('/discipline-recap')->assertStatus(200);
    }

    public function test_principal_receives_principal_role_context_and_safe_quick_links()
    {
        $principal = User::factory()->create();
        $principal->assignRole('Principal');

        $response = $this->actingAs($principal)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('role_context.role', 'principal')
            ->where('role_context.banner.action_url', '/reports/analytics')
            ->has('role_context.quick_links', 4)
            ->where('role_context.quick_links.0.href', '/reports/analytics')
            ->where('role_context.quick_links.1.href', '/reports/classes')
            ->where('role_context.quick_links.2.href', '/counseling-records')
            ->where('role_context.quick_links.3.href', '/discipline-recap')
        );

        // Verify Principal can access all 4 quick links without 403 Forbidden
        $this->actingAs($principal)->get('/reports/analytics')->assertStatus(200);
        $this->actingAs($principal)->get('/reports/classes')->assertStatus(200);
        $this->actingAs($principal)->get('/counseling-records')->assertStatus(200);
        $this->actingAs($principal)->get('/discipline-recap')->assertStatus(200);
    }
}
