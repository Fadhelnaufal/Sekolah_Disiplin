<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\FollowUp;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FollowUpTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole('Super Admin');

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

        Semester::create([
            'academic_year_id' => $ay->id,
            'name' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => 'Active',
        ]);

        $class = ClassRoom::create([
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'name' => '7A',
            'grade_level' => '7',
            'status' => 'Active',
        ]);

        $this->student = Student::create([
            'school_id' => $school->id,
            'class_room_id' => $class->id,
            'nis' => '1001',
            'name' => 'Ahmad Santoso',
            'status' => 'Active',
        ]);
    }

    public function test_can_view_follow_ups_index()
    {
        $response = $this->actingAs($this->user)->get(route('follow-ups.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_follow_up_action()
    {
        $response = $this->actingAs($this->user)->post(route('follow-ups.store'), [
            'student_id' => $this->student->id,
            'title' => 'Pertemuan wali murid dan penandatanganan komitmen',
            'action_plan' => 'Memanggil wali murid pada hari Senin untuk evaluasi kedisiplinan',
            'due_date' => Carbon::tomorrow()->toDateString(),
            'status' => 'pending',
            'assigned_to' => $this->user->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('follow_ups', [
            'student_id' => $this->student->id,
            'title' => 'Pertemuan wali murid dan penandatanganan komitmen',
            'status' => 'pending',
        ]);
    }

    public function test_can_update_follow_up_status_and_outcome()
    {
        $followUp = FollowUp::create([
            'student_id' => $this->student->id,
            'title' => 'Monitoring harian kehadiran',
            'action_plan' => 'Pantau absensi kelas 7A selama 2 minggu',
            'due_date' => Carbon::now()->addDays(14)->toDateString(),
            'status' => 'in_progress',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->put(route('follow-ups.update', $followUp), [
            'title' => 'Monitoring harian kehadiran',
            'action_plan' => 'Pantau absensi kelas 7A selama 2 minggu',
            'due_date' => $followUp->due_date->toDateString(),
            'status' => 'completed',
            'outcome' => 'Siswa hadir tepat waktu setiap hari dan menunjukkan perbaikan sikap.',
        ]);

        $response->assertRedirect();
        $followUp->refresh();
        $this->assertEquals('completed', $followUp->status);
        $this->assertNotNull($followUp->completed_at);
        $this->assertStringContainsString('menunjukkan perbaikan', $followUp->outcome);
    }

    public function test_can_filter_overdue_follow_ups()
    {
        // Past due date
        $overdue = FollowUp::create([
            'student_id' => $this->student->id,
            'title' => 'Tugas refleksi tertunda',
            'due_date' => Carbon::yesterday()->toDateString(),
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        // Future due date
        $future = FollowUp::create([
            'student_id' => $this->student->id,
            'title' => 'Pertemuan pekan depan',
            'due_date' => Carbon::tomorrow()->toDateString(),
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($overdue->is_overdue);
        $this->assertFalse($future->is_overdue);

        $response = $this->actingAs($this->user)->get(route('follow-ups.index', ['status' => 'overdue']));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('FollowUps/Index')
            ->has('followUps.data', 1)
            ->where('followUps.data.0.id', $overdue->id)
        );
    }

    public function test_can_delete_follow_up()
    {
        $followUp = FollowUp::create([
            'student_id' => $this->student->id,
            'title' => 'Dibatalkan karena salah input',
            'status' => 'cancelled',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('follow-ups.destroy', $followUp));
        $response->assertRedirect();

        $this->assertSoftDeleted('follow_ups', ['id' => $followUp->id]);
    }
}
