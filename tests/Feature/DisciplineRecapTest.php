<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\ClassRoom;
use App\Models\DisciplineEvent;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisciplineRecapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Tata Tertib', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'BK', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Principal', 'guard_name' => 'web']);
    }

    private function createRecapDependencies(): array
    {
        $school = School::create([
            'application_name' => 'Sekolah Disiplin',
            'name' => 'SMK Disiplin Pro',
            'address' => 'Jl. Pendidikan No 10',
            'city' => 'Jakarta',
        ]);

        $academicYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'start_date' => now()->subMonths(2)->format('Y-m-d'),
            'end_date' => now()->addMonths(4)->format('Y-m-d'),
            'status' => 'Active',
        ]);

        $semester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Ganjil',
            'start_date' => now()->subMonths(2)->format('Y-m-d'),
            'end_date' => now()->addMonths(4)->format('Y-m-d'),
            'status' => 'Active',
        ]);

        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $classA = ClassRoom::create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'grade_level' => '10',
            'name' => '10 PPLG 1',
            'status' => 'Active',
        ]);

        $classB = ClassRoom::create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'grade_level' => '10',
            'name' => '10 TKJ 1',
            'status' => 'Active',
        ]);

        $studentA = Student::create([
            'school_id' => $school->id,
            'class_room_id' => $classA->id,
            'nis' => '101001',
            'name' => 'Aditya Pratama',
            'status' => 'Active',
        ]);

        $studentB = Student::create([
            'school_id' => $school->id,
            'class_room_id' => $classB->id,
            'nis' => '101002',
            'name' => 'Bintang Ramadhan',
            'status' => 'Active',
        ]);

        $studentC = Student::create([
            'school_id' => $school->id,
            'class_room_id' => $classA->id,
            'nis' => '101003',
            'name' => 'Citra Maharani',
            'status' => 'Active',
        ]);

        $violation = Violation::create([
            'code' => 'VIO-REC-01',
            'name' => 'Terlambat Masuk',
            'category' => 'Ringan',
            'points' => 10,
        ]);

        $achievement = Achievement::create([
            'code' => 'ACH-REC-01',
            'name' => 'Juara Olimpiade',
            'level' => 'Kota',
            'deduction_points' => 15,
            'is_active' => true,
        ]);

        return [$semester, $user, $classA, $classB, $studentA, $studentB, $studentC, $violation, $achievement];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('discipline-recap.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_discipline_recap_and_receives_inertia_component(): void
    {
        [$semester, $user] = $this->createRecapDependencies();

        $response = $this->actingAs($user)->get(route('discipline-recap.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('DisciplineRecap/Index')
            ->has('students')
            ->has('summary')
            ->has('classes')
            ->has('semesters')
            ->has('current_semester')
            ->has('filters')
        );
    }

    public function test_discipline_recap_calculates_summary_and_student_status_accurately(): void
    {
        [
            $semester,
            $user,
            $classA,
            $classB,
            $studentA,
            $studentB,
            $studentC,
            $violation,
            $achievement,
        ] = $this->createRecapDependencies();

        // Student A: 30 violation points (Status WARNING)
        DisciplineEvent::create([
            'semester_id' => $semester->id,
            'student_id' => $studentA->id,
            'event_date' => now()->format('Y-m-d'),
            'event_type' => 'violation',
            'violation_id' => $violation->id,
            'points_effect' => 30,
            'recorded_by' => $user->id,
        ]);

        // Student B: 10 violation points - 15 achievement deduction = 0 net (Status SAFE)
        DisciplineEvent::create([
            'semester_id' => $semester->id,
            'student_id' => $studentB->id,
            'event_date' => now()->format('Y-m-d'),
            'event_type' => 'violation',
            'violation_id' => $violation->id,
            'points_effect' => 10,
            'recorded_by' => $user->id,
        ]);
        DisciplineEvent::create([
            'semester_id' => $semester->id,
            'student_id' => $studentB->id,
            'event_date' => now()->format('Y-m-d'),
            'event_type' => 'achievement',
            'achievement_id' => $achievement->id,
            'points_effect' => -15,
            'recorded_by' => $user->id,
        ]);

        // Student C has 0 events (Status SAFE)

        $response = $this->actingAs($user)->get(route('discipline-recap.index', [
            'semester_id' => $semester->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('DisciplineRecap/Index')
            ->where('summary.total_students', 3)
            ->where('summary.total_violations', 2)
            ->where('summary.total_violation_points', 40)
            ->where('summary.total_achievements', 1)
            ->where('summary.total_achievement_points', 15)
            ->where('summary.need_guidance_count', 1)
            ->where('summary.safe_count', 2)
            ->where('summary.warning_count', 1)
        );
    }

    public function test_discipline_recap_filters_by_class_search_and_status(): void
    {
        [
            $semester,
            $user,
            $classA,
            $classB,
            $studentA,
            $studentB,
            $studentC,
            $violation,
            $achievement,
        ] = $this->createRecapDependencies();

        // Student A (Class A): 60 points -> CRITICAL
        DisciplineEvent::create([
            'semester_id' => $semester->id,
            'student_id' => $studentA->id,
            'event_date' => now()->format('Y-m-d'),
            'event_type' => 'violation',
            'violation_id' => $violation->id,
            'points_effect' => 60,
            'recorded_by' => $user->id,
        ]);

        // Filter by class_id = Class B
        $resClass = $this->actingAs($user)->get(route('discipline-recap.index', [
            'semester_id' => $semester->id,
            'class_id' => $classB->id,
        ]));
        $resClass->assertOk();
        $resClass->assertInertia(fn ($page) => $page
            ->has('students', 1)
            ->where('students.0.id', $studentB->id)
        );

        // Filter by search = 'Aditya'
        $resSearch = $this->actingAs($user)->get(route('discipline-recap.index', [
            'semester_id' => $semester->id,
            'search' => 'Aditya',
        ]));
        $resSearch->assertOk();
        $resSearch->assertInertia(fn ($page) => $page
            ->has('students', 1)
            ->where('students.0.id', $studentA->id)
        );

        // Filter by status = CRITICAL
        $resStatus = $this->actingAs($user)->get(route('discipline-recap.index', [
            'semester_id' => $semester->id,
            'status' => 'CRITICAL',
        ]));
        $resStatus->assertOk();
        $resStatus->assertInertia(fn ($page) => $page
            ->has('students', 1)
            ->where('students.0.id', $studentA->id)
        );
    }

    public function test_discipline_recap_supports_pdf_export(): void
    {
        [$semester, $user] = $this->createRecapDependencies();

        $pdfResponse = $this->actingAs($user)->get(route('discipline-recap.index', [
            'semester_id' => $semester->id,
            'export' => 'pdf',
        ]));
        $pdfResponse->assertOk();
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('content-type'));
    }
}
