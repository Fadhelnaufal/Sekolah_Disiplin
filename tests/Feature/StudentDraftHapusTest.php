<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Semester;
use App\Models\SemesterSnapshot;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentDraftHapusTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Student $student;

    protected ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Super Admin');

        $school = School::create([
            'name' => 'SMP Malang',
            'address' => 'Jl. Pendidikan No. 1',
            'city' => 'Malang',
            'application_name' => 'Disiplin Pro',
        ]);

        $ay = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'Active',
        ]);

        $this->semester = Semester::create([
            'academic_year_id' => $ay->id,
            'name' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => 'Active',
        ]);

        $this->classRoom = ClassRoom::create([
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'name' => '7A',
            'grade_level' => '7',
            'status' => 'Active',
        ]);

        $this->student = Student::create([
            'school_id' => $school->id,
            'class_room_id' => $this->classRoom->id,
            'nis' => '1001',
            'name' => 'Budi Santoso',
            'status' => 'Active',
        ]);
    }

    public function test_student_deletion_moves_to_draft_hapus()
    {
        $response = $this->actingAs($this->adminUser)->delete(route('students.destroy', $this->student));
        $response->assertRedirect();

        // Student is soft-deleted (Draft Hapus)
        $this->assertSoftDeleted('students', ['id' => $this->student->id]);
        $this->assertEquals(0, Student::count());
        $this->assertEquals(1, Student::onlyTrashed()->count());
    }

    public function test_can_restore_student_from_draft_hapus()
    {
        $this->student->delete();

        $response = $this->actingAs($this->adminUser)->post(route('students.restore', $this->student->id));
        $response->assertRedirect();

        $this->student->refresh();
        $this->assertNull($this->student->deleted_at);
        $this->assertEquals(1, Student::count());
    }

    public function test_can_permanently_delete_student_without_snapshot()
    {
        $this->student->delete();

        $response = $this->actingAs($this->adminUser)->delete(route('students.force-delete', $this->student->id));
        $response->assertRedirect();

        $this->assertDatabaseMissing('students', ['id' => $this->student->id]);
    }

    public function test_cannot_permanently_delete_student_with_semester_snapshot()
    {
        $this->student->delete();

        // Create snapshot for closed semester
        SemesterSnapshot::create([
            'semester_id' => $this->semester->id,
            'student_id' => $this->student->id,
            'class_room_id' => $this->classRoom->id,
            'violation_points' => 10,
            'achievement_points' => 0,
            'net_points' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(route('students.force-delete', $this->student->id));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Student is still in trash, not destroyed
        $this->assertDatabaseHas('students', ['id' => $this->student->id]);
        $this->assertEquals(1, Student::onlyTrashed()->count());
    }

    public function test_can_download_student_template()
    {
        $response = $this->actingAs($this->adminUser)->get(route('students.template'));
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_can_update_class_name()
    {
        $response = $this->actingAs($this->adminUser)->post(route('students.update-class-name'), [
            'class_room_id' => $this->classRoom->id,
            'name' => '7 Unggulan A',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('class_rooms', [
            'id' => $this->classRoom->id,
            'name' => '7 Unggulan A',
        ]);
    }
}
