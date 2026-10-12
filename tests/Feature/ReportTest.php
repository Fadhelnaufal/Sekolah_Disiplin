<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Semester;
use App\Models\School;
use Spatie\Permission\Models\Role;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Basic setup for tests
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        
        School::create([
            'application_name' => 'Disiplin Pro',
            'name' => 'Test School',
            'address' => 'Test Address',
            'city' => 'City',
            'province' => 'Province',
            'postal_code' => '12345',
            'phone' => '123456',
            'email' => 'test@school.com',
            'principal_name' => 'Kepsek',
            'principal_nip' => '12345'
        ]);
    }

    public function test_guest_cannot_access_reports()
    {
        $response = $this->get(route('reports.monthly'));
        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_access_monthly_reports()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $ay = \App\Models\AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);

        $response = $this->actingAs($user)->get(route('reports.monthly', ['semester_id' => $semester->id]));
        $response->assertStatus(200);
    }
    
    public function test_teacher_can_access_homerooms_report()
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');

        $ay = \App\Models\AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);

        $response = $this->actingAs($user)->get(route('reports.homerooms', ['semester_id' => $semester->id]));
        $response->assertStatus(200);
    }

    public function test_metric_accuracy_and_teacher_scoping()
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        $teacher = \App\Models\Teacher::create(['user_id' => $user->id, 'name' => 'Pak Guru', 'employee_number' => '123', 'status' => 'Active']);

        $ay = \App\Models\AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);

        // Teacher's class
        $myClass = \App\Models\ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '10', 'name' => 'Class A', 'homeroom_teacher_id' => $teacher->id, 'status' => 'Active']);
        // Other class
        $otherClass = \App\Models\ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '10', 'name' => 'Class B', 'homeroom_teacher_id' => null, 'status' => 'Active']);

        $student1 = \App\Models\Student::create(['school_id' => 1, 'class_room_id' => $myClass->id, 'name' => 'Student 1', 'nis' => '001', 'status' => 'Active']);
        $student2 = \App\Models\Student::create(['school_id' => 1, 'class_room_id' => $otherClass->id, 'name' => 'Student 2', 'nis' => '002', 'status' => 'Active']);

        $violation = \App\Models\Violation::create(['school_id' => 1, 'code' => 'V1', 'name' => 'V1', 'category' => 'Ringan', 'points' => 10, 'status' => 'Active']);
        $achievement = \App\Models\Achievement::create(['code' => 'A1', 'name' => 'A1', 'level' => 'Sekolah', 'deduction_points' => 5, 'is_active' => true]);

        // Insert events for Student 1
        \App\Models\DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student1->id, 'event_date' => '2026-08-01', 'event_type' => 'violation', 'violation_id' => 1, 'points_effect' => 10, 'description' => 'A', 'recorded_by' => $user->id, 'status' => 'approved'
        ]);
        \App\Models\DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student1->id, 'event_date' => '2026-08-02', 'event_type' => 'achievement', 'achievement_id' => 1, 'points_effect' => -5, 'description' => 'B', 'recorded_by' => $user->id, 'status' => 'approved'
        ]);

        // Insert event for Student 2
        \App\Models\DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student2->id, 'event_date' => '2026-08-01', 'event_type' => 'violation', 'violation_id' => 1, 'points_effect' => 20, 'description' => 'C', 'recorded_by' => $user->id, 'status' => 'approved'
        ]);

        $service = new \App\Services\ReportService();

        // 1. Accuracy test
        $aggregates = $service->getStudentAggregates($semester);
        $s1Agg = $aggregates->firstWhere('id', $student1->id);
        $this->assertEquals(10, $s1Agg->violation_points);
        $this->assertEquals(-5, $s1Agg->achievement_points);
        $this->assertEquals(5, $s1Agg->net_points); // 10 - |-5|

        // Floor at 0 test
        \App\Models\DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student1->id, 'event_date' => '2026-08-03', 'event_type' => 'achievement', 'achievement_id' => 1, 'points_effect' => -10, 'description' => 'D', 'recorded_by' => $user->id, 'status' => 'approved'
        ]);
        $aggregates = $service->getStudentAggregates($semester);
        $s1Agg = $aggregates->firstWhere('id', $student1->id);
        $this->assertEquals(0, $s1Agg->net_points); // 10 - |-15| = -5 -> floor 0

        // 2. Scoping test (teacher only sees Student 1's class)
        $teacherScope = $service->getStudentAggregates($semester, null, $teacher->id);
        $this->assertCount(1, $teacherScope);
        $this->assertEquals($student1->id, $teacherScope->first()->id);

        // Teacher endpoint test
        $response = $this->actingAs($user)->get(route('reports.classes', ['semester_id' => $semester->id]));
        // Since classes uses getClassAggregates:
        $classesScope = $service->getClassAggregates($semester, $teacher->id);
        $this->assertCount(1, $classesScope);
        $this->assertEquals($myClass->id, $classesScope->first()->id);
    }

    public function test_analytics_dashboard_and_monthly_trend()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $ay = \App\Models\AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);

        $myClass = \App\Models\ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '10', 'name' => 'Class A', 'homeroom_teacher_id' => null, 'status' => 'Active']);
        $student1 = \App\Models\Student::create(['school_id' => 1, 'class_room_id' => $myClass->id, 'name' => 'Student 1', 'nis' => '001', 'status' => 'Active']);

        $violation = \App\Models\Violation::create(['school_id' => 1, 'code' => 'V1', 'name' => 'V1', 'category' => 'Ringan', 'points' => 10, 'status' => 'Active']);

        // Insert events in different months
        \App\Models\DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student1->id, 'event_date' => '2026-08-15', 'event_type' => 'violation', 'violation_id' => $violation->id, 'points_effect' => 10, 'description' => 'A', 'recorded_by' => $user->id, 'status' => 'approved'
        ]);
        \App\Models\DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student1->id, 'event_date' => '2026-08-20', 'event_type' => 'violation', 'violation_id' => $violation->id, 'points_effect' => 10, 'description' => 'B', 'recorded_by' => $user->id, 'status' => 'approved'
        ]);
        \App\Models\DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student1->id, 'event_date' => '2026-09-05', 'event_type' => 'violation', 'violation_id' => $violation->id, 'points_effect' => 10, 'description' => 'C', 'recorded_by' => $user->id, 'status' => 'approved'
        ]);

        $service = new \App\Services\ReportService();
        $trend = $service->getMonthlyTrend($semester);

        $this->assertCount(2, $trend);
        $this->assertEquals('2026-08', $trend[0]->month);
        $this->assertEquals(2, $trend[0]->count);
        $this->assertEquals('2026-09', $trend[1]->month);
        $this->assertEquals(1, $trend[1]->count);

        $response = $this->actingAs($user)->get(route('reports.analytics', ['semester_id' => $semester->id]));
        $response->assertStatus(200);
    }
}
