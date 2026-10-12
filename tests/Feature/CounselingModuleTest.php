<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CounselingRecord;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class CounselingModuleTest extends TestCase
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

    private function createDependencies()
    {
        $school = School::create(['npsn' => '123', 'name' => 'Test', 'address' => 'Test', 'city' => 'Test', 'application_name' => 'Test']);
        $academicYear = AcademicYear::create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $semester = Semester::create(['academic_year_id' => $academicYear->id, 'name' => 'Ganjil', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'status' => 'Active']);
        $classRoom = ClassRoom::create(['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name' => 'Class', 'grade_level' => '10', 'status' => 'active']);
        $student = Student::create(['school_id' => $school->id, 'nis' => '111', 'name' => 'Student', 'class_room_id' => $classRoom->id, 'status' => 'Active']);

        return [$semester, $student];
    }

    public function test_bk_can_create_and_view_counseling_record()
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');
        
        list($semester, $student) = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'status' => 'scheduled',
            'confidentiality_level' => 'strict',
            'summary' => 'Highly confidential text'
        ]);

        $this->assertTrue($bk->can('view', $record));
        $this->assertTrue($bk->can('update', $record));
    }

    public function test_bk_cannot_view_strict_record_from_other_counselor()
    {
        $bk1 = User::factory()->create();
        $bk1->assignRole('BK');

        $bk2 = User::factory()->create();
        $bk2->assignRole('BK');
        
        list($semester, $student) = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk1->id,
            'status' => 'in_progress',
            'confidentiality_level' => 'normal', // even normal is blocked for other BK
            'summary' => 'Text'
        ]);

        $this->assertFalse($bk2->can('view', $record));
        $this->assertFalse($bk2->can('update', $record));
    }

    public function test_teacher_cannot_view_counseling_records()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');
        
        list($semester, $student) = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'status' => 'resolved',
            'confidentiality_level' => 'normal',
        ]);

        $this->assertFalse($teacher->can('view', $record));
        $this->assertFalse($teacher->can('viewAny', CounselingRecord::class));
    }

    public function test_super_admin_cannot_view_strict_record()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');
        
        list($semester, $student) = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'status' => 'in_progress',
            'confidentiality_level' => 'strict',
        ]);

        $this->assertFalse($superAdmin->can('view', $record));
    }

    public function test_counseling_summary_and_reason_are_encrypted()
    {
        list($semester, $student) = $this->createDependencies();

        $plainText = 'This is a very sensitive summary.';
        $reasonText = 'Sensitive reason.';
        
        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'status' => 'scheduled',
            'summary' => $plainText,
            'reason' => $reasonText,
        ]);

        $rawRecord = DB::table('counseling_records')->where('id', $record->id)->first();

        // Ensure the raw database value is not the plain text
        $this->assertNotEquals($plainText, $rawRecord->summary);
        $this->assertNotEquals($reasonText, $rawRecord->reason);

        // Ensure the model access decrypts it automatically
        $this->assertEquals($plainText, $record->fresh()->summary);
        $this->assertEquals($reasonText, $record->fresh()->reason);
    }
    
    public function test_bk_cannot_delete_completed_records()
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');
        
        list($semester, $student) = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'status' => 'resolved',
        ]);

        $this->assertFalse($bk->can('delete', $record));
        
        $record->update(['status' => 'scheduled']);
        $this->assertTrue($bk->can('delete', $record->fresh()));
    }

    public function test_inertia_does_not_leak_strict_records_to_super_admin()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');
        
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        list($semester, $student) = $this->createDependencies();

        $strictRecord = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'status' => 'in_progress',
            'confidentiality_level' => 'strict',
            'summary' => 'Secret',
            'reason' => 'Secret reason'
        ]);

        $normalRecord = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'status' => 'resolved',
            'confidentiality_level' => 'normal',
            'summary' => 'Public summary',
            'reason' => 'Public reason'
        ]);

        $response = $this->actingAs($superAdmin)->get(route('counseling-records.index'));
        $response->assertStatus(200);

        // Ensure strict record is NOT sent
        $response->assertDontSee('Secret reason');
        
        // Ensure normal record is sent
        $response->assertSee('Public reason');

        // Super Admin trying to direct visit show
        $responseShow = $this->actingAs($superAdmin)->get(route('counseling-records.show', $strictRecord));
        $responseShow->assertStatus(403);
    }
}
