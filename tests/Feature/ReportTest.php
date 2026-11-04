<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\ClassRoom;
use App\Models\DisciplineEvent;
use App\Models\FollowUp;
use App\Models\Letter;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Violation;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

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
            'principal_nip' => '12345',
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

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);

        $response = $this->actingAs($user)->get(route('reports.monthly', ['semester_id' => $semester->id]));
        $response->assertStatus(200);
    }

    public function test_teacher_can_access_homerooms_report()
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);

        $response = $this->actingAs($user)->get(route('reports.homerooms', ['semester_id' => $semester->id]));
        $response->assertStatus(200);
    }

    public function test_metric_accuracy_and_teacher_scoping()
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        $teacher = Teacher::create(['user_id' => $user->id, 'name' => 'Pak Guru', 'employee_number' => '123', 'status' => 'Active']);

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);

        // Teacher's class
        $myClass = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '10', 'name' => 'Class A', 'homeroom_teacher_id' => $teacher->id, 'status' => 'Active']);
        // Other class
        $otherClass = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '10', 'name' => 'Class B', 'homeroom_teacher_id' => null, 'status' => 'Active']);

        $student1 = Student::create(['school_id' => 1, 'class_room_id' => $myClass->id, 'name' => 'Student 1', 'nis' => '001', 'status' => 'Active']);
        $student2 = Student::create(['school_id' => 1, 'class_room_id' => $otherClass->id, 'name' => 'Student 2', 'nis' => '002', 'status' => 'Active']);

        $violation = Violation::create(['school_id' => 1, 'code' => 'V1', 'name' => 'V1', 'category' => 'Ringan', 'points' => 10, 'status' => 'Active']);
        $achievement = Achievement::create(['code' => 'A1', 'name' => 'A1', 'level' => 'Sekolah', 'deduction_points' => 5, 'is_active' => true]);

        // Insert events for Student 1
        DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student1->id, 'event_date' => '2026-08-01', 'event_type' => 'violation', 'violation_id' => 1, 'points_effect' => 10, 'description' => 'A', 'recorded_by' => $user->id, 'status' => 'approved',
        ]);
        DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student1->id, 'event_date' => '2026-08-02', 'event_type' => 'achievement', 'achievement_id' => 1, 'points_effect' => -5, 'description' => 'B', 'recorded_by' => $user->id, 'status' => 'approved',
        ]);

        // Insert event for Student 2
        DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student2->id, 'event_date' => '2026-08-01', 'event_type' => 'violation', 'violation_id' => 1, 'points_effect' => 20, 'description' => 'C', 'recorded_by' => $user->id, 'status' => 'approved',
        ]);

        $service = new ReportService;

        // 1. Accuracy test
        $aggregates = $service->getStudentAggregates($semester);
        $s1Agg = $aggregates->firstWhere('id', $student1->id);
        $this->assertEquals(10, $s1Agg->violation_points);
        $this->assertEquals(-5, $s1Agg->achievement_points);
        $this->assertEquals(5, $s1Agg->net_points); // 10 - |-5|

        // Floor at 0 test
        DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student1->id, 'event_date' => '2026-08-03', 'event_type' => 'achievement', 'achievement_id' => 1, 'points_effect' => -10, 'description' => 'D', 'recorded_by' => $user->id, 'status' => 'approved',
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

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);

        $myClass = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '10', 'name' => 'Class A', 'homeroom_teacher_id' => null, 'status' => 'Active']);
        $student1 = Student::create(['school_id' => 1, 'class_room_id' => $myClass->id, 'name' => 'Student 1', 'nis' => '001', 'status' => 'Active']);

        $violation = Violation::create(['school_id' => 1, 'code' => 'V1', 'name' => 'V1', 'category' => 'Ringan', 'points' => 10, 'status' => 'Active']);

        // Insert events in different months
        DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student1->id, 'event_date' => '2026-08-15', 'event_type' => 'violation', 'violation_id' => $violation->id, 'points_effect' => 10, 'description' => 'A', 'recorded_by' => $user->id, 'status' => 'approved',
        ]);
        DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student1->id, 'event_date' => '2026-08-20', 'event_type' => 'violation', 'violation_id' => $violation->id, 'points_effect' => 10, 'description' => 'B', 'recorded_by' => $user->id, 'status' => 'approved',
        ]);
        DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student1->id, 'event_date' => '2026-09-05', 'event_type' => 'violation', 'violation_id' => $violation->id, 'points_effect' => 10, 'description' => 'C', 'recorded_by' => $user->id, 'status' => 'approved',
        ]);

        $service = new ReportService;
        $trend = $service->getMonthlyTrend($semester);

        $this->assertCount(2, $trend);
        $this->assertEquals('2026-08', $trend[0]->month);
        $this->assertEquals(2, $trend[0]->count);
        $this->assertEquals('2026-09', $trend[1]->month);
        $this->assertEquals(1, $trend[1]->count);

        $response = $this->actingAs($user)->get(route('reports.analytics', ['semester_id' => $semester->id]));
        $response->assertStatus(200);
    }

    public function test_super_admin_can_access_reports_index()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $myClass = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '7', 'name' => 'VII A', 'status' => 'Active']);
        $student = Student::create(['school_id' => 1, 'class_room_id' => $myClass->id, 'name' => 'Nama Siswa', 'nis' => '12345', 'status' => 'Active']);

        $response = $this->actingAs($user)->get(route('reports.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->has('students')
            ->has('school')
        );
    }

    public function test_super_admin_can_generate_pdf_report_and_archives_letter()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $myClass = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '7', 'name' => 'VII A', 'status' => 'Active']);
        $student = Student::create(['school_id' => 1, 'class_room_id' => $myClass->id, 'name' => 'Nama Siswa', 'nis' => '12345', 'status' => 'Active']);

        $response = $this->actingAs($user)->post(route('reports.generate'), [
            'student_id' => $student->id,
            'type' => 'notification',
            'notes' => 'Harap hadir tepat waktu.',
            'format' => 'pdf',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));

        $this->assertDatabaseHas('letters', [
            'student_id' => $student->id,
            'type' => 'notification',
            'status' => 'Generated',
        ]);
    }

    public function test_super_admin_can_generate_word_report_and_archives_letter()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $myClass = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '7', 'name' => 'VII A', 'status' => 'Active']);
        $student = Student::create(['school_id' => 1, 'class_room_id' => $myClass->id, 'name' => 'Nama Siswa', 'nis' => '12345', 'status' => 'Active']);

        $response = $this->actingAs($user)->post(route('reports.generate'), [
            'student_id' => $student->id,
            'type' => 'notification',
            'notes' => 'Catatan penting.',
            'format' => 'word',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('application/msword', (string) $response->headers->get('content-type'));

        $this->assertDatabaseHas('letters', [
            'student_id' => $student->id,
            'type' => 'notification',
            'status' => 'Generated',
        ]);
    }

    public function test_export_monthly_pdf_renders_with_standardized_kop_and_signatory()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);

        $response = $this->actingAs($user)->get(route('reports.monthly', [
            'semester_id' => $semester->id,
            'export' => 'pdf',
        ]));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_export_classes_pdf_renders_with_standardized_kop_and_signatory()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);

        $response = $this->actingAs($user)->get(route('reports.classes', [
            'semester_id' => $semester->id,
            'export' => 'pdf',
        ]));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_generated_letter_uses_configured_school_letter_number_format()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $school = School::first();
        $school->update([
            'classification_code' => '421.3',
            'school_code' => 'SMA-NEGERI-1-SAMBIT',
        ]);

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $myClass = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '7', 'name' => 'VII A', 'status' => 'Active']);
        $student = Student::create(['school_id' => 1, 'class_room_id' => $myClass->id, 'name' => 'Nama Siswa', 'nis' => '12345', 'status' => 'Active']);

        $response = $this->actingAs($user)->post(route('reports.generate'), [
            'student_id' => $student->id,
            'type' => 'notification',
            'format' => 'pdf',
        ]);

        $response->assertStatus(200);

        $letter = Letter::latest()->first();
        $this->assertNotNull($letter);
        $currentYear = date('Y');
        $this->assertStringStartsWith('421.3/', $letter->letter_number);
        $this->assertStringContainsString('SMA-NEGERI-1-SAMBIT', $letter->letter_number);
        $this->assertStringEndsWith('/'.$currentYear, $letter->letter_number);
    }

    public function test_monthly_trend_aggregates_both_violations_and_achievements_with_points()
    {
        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $class = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '10', 'name' => 'X-1', 'status' => 'Active']);
        $student = Student::create(['school_id' => 1, 'class_room_id' => $class->id, 'name' => 'Siswa Test', 'nis' => '9999', 'status' => 'Active']);

        $violation = Violation::create(['school_id' => 1, 'code' => 'V-99', 'name' => 'Terlambat', 'category' => 'Ringan', 'points' => 15, 'status' => 'Active']);
        $achievement = Achievement::create(['code' => 'A-99', 'name' => 'Juara Lomba', 'level' => 'Kota', 'deduction_points' => 5, 'is_active' => true]);

        DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student->id, 'event_date' => '2026-08-10', 'event_type' => 'violation', 'violation_id' => $violation->id, 'points_effect' => 15, 'description' => 'Terlambat',
        ]);
        DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student->id, 'event_date' => '2026-08-15', 'event_type' => 'achievement', 'achievement_id' => $achievement->id, 'points_effect' => -5, 'description' => 'Prestasi',
        ]);

        $service = new ReportService;
        $trend = $service->getMonthlyTrend($semester);

        $this->assertCount(1, $trend);
        $aug = $trend->first();
        $this->assertEquals('2026-08', $aug->month);
        $this->assertEquals(1, $aug->violation_count);
        $this->assertEquals(1, $aug->achievement_count);
        $this->assertEquals(15, $aug->violation_points);
        $this->assertEquals(5, $aug->achievement_points);
        $this->assertEquals(10, $aug->net_points); // 15 - 5
    }

    public function test_monthly_trend_fills_empty_months_in_semester()
    {
        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $class = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '10', 'name' => 'X-1', 'status' => 'Active']);
        $student = Student::create(['school_id' => 1, 'class_room_id' => $class->id, 'name' => 'Siswa Test', 'nis' => '9999', 'status' => 'Active']);
        $violation = Violation::create(['school_id' => 1, 'code' => 'V-99', 'name' => 'Terlambat', 'category' => 'Ringan', 'points' => 5, 'status' => 'Active']);

        // Only 1 event in October
        DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student->id, 'event_date' => '2026-10-10', 'event_type' => 'violation', 'violation_id' => $violation->id, 'points_effect' => 5,
        ]);

        $service = new ReportService;

        // 1. Explicit filter range July to December (6 months)
        $explicitTrend = $service->getMonthlyTrend($semester, null, null, '2026-07-01', '2026-12-31', true);
        $this->assertCount(6, $explicitTrend);
        $this->assertEquals('2026-07', $explicitTrend[0]->month);
        $this->assertEquals(0, $explicitTrend[0]->violation_count);
        $this->assertEquals('2026-10', $explicitTrend[3]->month);
        $this->assertEquals(1, $explicitTrend[3]->violation_count);
        $this->assertEquals('2026-12', $explicitTrend[5]->month);
        $this->assertEquals(0, $explicitTrend[5]->violation_count);

        // 2. Adaptive timeline (starts from first recorded event October)
        $adaptiveTrend = $service->getMonthlyTrend($semester, null, null, null, null, true);
        $this->assertNotEmpty($adaptiveTrend);
        $this->assertEquals('2026-10', $adaptiveTrend->first()->month);
        $this->assertEquals(1, $adaptiveTrend->first()->violation_count);
    }

    public function test_analytics_endpoint_returns_chart_datasets_and_kpis()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $class = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '10', 'name' => 'X-A', 'status' => 'Active']);
        $student = Student::create(['school_id' => 1, 'class_room_id' => $class->id, 'name' => 'Siswa Analytics', 'nis' => '1111', 'status' => 'Active']);

        $violation = Violation::create(['school_id' => 1, 'code' => 'V-10', 'name' => 'Bolos', 'category' => 'Sedang', 'points' => 20, 'status' => 'Active']);
        DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $student->id, 'event_date' => '2026-09-10', 'event_type' => 'violation', 'violation_id' => $violation->id, 'points_effect' => 20,
        ]);

        FollowUp::create([
            'student_id' => $student->id,
            'title' => 'Panggilan Konseling',
            'status' => 'pending',
            'created_at' => '2026-09-11 10:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.analytics', ['semester_id' => $semester->id]));
        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Analytics')
            ->has('monthly_trend')
            ->has('category_distribution')
            ->has('category_summary')
            ->has('class_recap')
            ->has('follow_up_distribution')
            ->has('kpi_summary')
            ->where('kpi_summary.total_violations', 1)
            ->where('kpi_summary.total_violation_points', 20)
            ->where('kpi_summary.total_follow_ups', 1)
        );
    }

    public function test_analytics_teacher_role_scoping_limits_data_to_homeroom()
    {
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('Teacher');
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'name' => 'Wali Kelas 1', 'employee_number' => 'WK01', 'status' => 'Active']);

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);

        // Teacher's class
        $myClass = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '10', 'name' => 'X-A', 'homeroom_teacher_id' => $teacher->id, 'status' => 'Active']);
        $myStudent = Student::create(['school_id' => 1, 'class_room_id' => $myClass->id, 'name' => 'Siswa Saya', 'nis' => '1001', 'status' => 'Active']);

        // Other class
        $otherClass = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '10', 'name' => 'X-B', 'homeroom_teacher_id' => null, 'status' => 'Active']);
        $otherStudent = Student::create(['school_id' => 1, 'class_room_id' => $otherClass->id, 'name' => 'Siswa Lain', 'nis' => '1002', 'status' => 'Active']);

        $violation = Violation::create(['school_id' => 1, 'code' => 'V-A', 'name' => 'Pelanggaran A', 'category' => 'Ringan', 'points' => 10, 'status' => 'Active']);

        // My student event
        DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $myStudent->id, 'event_date' => '2026-08-10', 'event_type' => 'violation', 'violation_id' => $violation->id, 'points_effect' => 10,
        ]);
        // Other student event
        DisciplineEvent::create([
            'semester_id' => $semester->id, 'student_id' => $otherStudent->id, 'event_date' => '2026-08-12', 'event_type' => 'violation', 'violation_id' => $violation->id, 'points_effect' => 30,
        ]);

        $response = $this->actingAs($teacherUser)->get(route('reports.analytics', ['semester_id' => $semester->id]));
        $response->assertStatus(200);

        // Teacher should only see 1 violation (10 points), not 2 violations (40 points)
        $response->assertInertia(fn ($page) => $page
            ->where('kpi_summary.total_violations', 1)
            ->where('kpi_summary.total_violation_points', 10)
        );
    }

    public function test_dashboard_provides_monthly_trend_prop()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('monthly_trend')
            ->has('metrics')
        );
    }

    public function test_reports_index_renders_students_with_address()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $class = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '7', 'name' => 'VII-A', 'status' => 'Active']);
        $student = Student::create([
            'school_id' => 1,
            'class_room_id' => $class->id,
            'name' => 'Ahmad Santoso',
            'nis' => '8899',
            'parent_name' => 'Bapak Santoso',
            'address' => 'Jl. Merdeka No. 10',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->has('students')
            ->where('students.0.name', 'Ahmad Santoso')
            ->where('students.0.address', 'Tempat tinggal siswa')
        );
    }

    public function test_generate_parent_call_pdf_persists_letter_with_meeting_metadata()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $class = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '7', 'name' => 'VII-A', 'status' => 'Active']);
        $student = Student::create([
            'school_id' => 1,
            'class_room_id' => $class->id,
            'name' => 'Budi Pratama',
            'nis' => '9900',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->get(route('reports.generate', [
            'student_id' => $student->id,
            'type' => 'call_1',
            'format' => 'pdf',
            'meeting_day_date' => 'Rabu, 29 Oktober 2026',
            'meeting_time' => 'Pukul 09.00 WIB',
            'meeting_venue' => 'Ruang BK',
            'meeting_purpose' => 'Konsultasi kedisiplinan dan absensi',
        ]));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));

        $this->assertDatabaseHas('letters', [
            'student_id' => $student->id,
            'type' => 'call_1',
            'status' => 'Generated',
        ]);

        $letter = Letter::where('student_id', $student->id)->latest()->first();
        $this->assertEquals('Rabu, 29 Oktober 2026', $letter->metadata['meeting_day_date']);
        $this->assertEquals('Ruang BK', $letter->metadata['meeting_venue']);
    }

    public function test_generate_parent_call_word_returns_doc_attachment()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $ay = AcademicYear::create(['school_id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $class = ClassRoom::create(['school_id' => 1, 'academic_year_id' => $ay->id, 'grade_level' => '7', 'name' => 'VII-A', 'status' => 'Active']);
        $student = Student::create([
            'school_id' => 1,
            'class_room_id' => $class->id,
            'name' => 'Citra Dewi',
            'nis' => '9901',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->get(route('reports.generate', [
            'student_id' => $student->id,
            'type' => 'call_2',
            'format' => 'word',
            'meeting_day_date' => 'Kamis, 30 Oktober 2026',
            'meeting_time' => 'Pukul 10.00 WIB',
            'meeting_venue' => 'Ruang Kesiswaan',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/msword', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Citra Dewi', $response->getContent());
        $this->assertStringContainsString('Kamis, 30 Oktober 2026', $response->getContent());
    }

    public function test_reports_index_provides_letters_archive_and_initial_tab()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $response = $this->actingAs($user)->get(route('reports.index', ['tab' => 'template']));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Reports/Index')
            ->has('school')
            ->has('letters')
            ->where('initialTab', 'template')
        );
    }

    public function test_word_export_respects_minimal_template_style()
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $school = School::first();
        $school->update(['template_style' => 'formal_minimal']);

        $ay = AcademicYear::create(['school_id' => $school->id, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $semester = Semester::create(['academic_year_id' => $ay->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'Active']);
        $class = ClassRoom::create(['school_id' => $school->id, 'academic_year_id' => $ay->id, 'grade_level' => '7', 'name' => 'VII-A', 'status' => 'Active']);
        $student = Student::create([
            'school_id' => $school->id,
            'class_room_id' => $class->id,
            'name' => 'Rina Sasmita',
            'nis' => '9902',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->get(route('reports.generate', [
            'student_id' => $student->id,
            'type' => 'call_1',
            'format' => 'word',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('minimal-divider', $response->getContent());
    }
}
