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

class SemesterClosingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->artisan('db:seed');
        
        $this->school = School::first();
        $this->academicYear = AcademicYear::first();
        
        $this->semester = Semester::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Ganjil 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => 'Active'
        ]);

        $this->classRoom = ClassRoom::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => '7A',
            'grade_level' => '7'
        ]);
        
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Super Admin');
    }

    public function test_super_admin_can_close_semester_and_generate_snapshots()
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'class_room_id' => $this->classRoom->id,
            'nis' => '111',
            'name' => 'Active Student',
            'status' => 'Active'
        ]);

        $violation = Violation::create([
            'code' => 'V1',
            'name' => 'Viol',
            'category' => 'Cat',
            'points' => 15
        ]);

        DisciplineEvent::create([
            'semester_id' => $this->semester->id,
            'student_id' => $student->id,
            'event_date' => '2026-08-01',
            'event_type' => 'violation',
            'violation_id' => $violation->id,
            'points_effect' => 15,
            'description' => 'Test event',
            'recorded_by' => $this->adminUser->id
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('semesters.close', $this->semester->id));
            
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->semester->refresh();
        $this->assertEquals('Closed', $this->semester->status);
        $this->assertNotNull($this->semester->closed_at);
        $this->assertEquals($this->adminUser->id, $this->semester->closed_by);

        $snapshot = SemesterSnapshot::where('semester_id', $this->semester->id)
            ->where('student_id', $student->id)
            ->first();
            
        $this->assertNotNull($snapshot);
        $this->assertEquals(15, $snapshot->violation_points);
        $this->assertEquals(0, $snapshot->achievement_points);
        $this->assertEquals(15, $snapshot->net_points);
        $this->assertEquals($this->classRoom->id, $snapshot->class_room_id);
    }
}
