<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\CounselingRecord;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

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

        [$semester, $student] = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'status' => 'scheduled',
            'confidentiality_level' => 'strict',
            'summary' => 'Highly confidential text',
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

        [$semester, $student] = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk1->id,
            'status' => 'in_progress',
            'confidentiality_level' => 'normal', // even normal is blocked for other BK
            'summary' => 'Text',
        ]);

        $this->assertFalse($bk2->can('view', $record));
        $this->assertFalse($bk2->can('update', $record));
    }

    public function test_teacher_cannot_view_counseling_records()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        [$semester, $student] = $this->createDependencies();

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

        [$semester, $student] = $this->createDependencies();

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
        [$semester, $student] = $this->createDependencies();

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

        [$semester, $student] = $this->createDependencies();

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

        [$semester, $student] = $this->createDependencies();

        $strictRecord = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'status' => 'in_progress',
            'confidentiality_level' => 'strict',
            'summary' => 'Secret',
            'reason' => 'Secret reason',
        ]);

        $normalRecord = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'status' => 'resolved',
            'confidentiality_level' => 'normal',
            'summary' => 'Public summary',
            'reason' => 'Public reason',
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

    public function test_counseling_records_can_be_filtered_by_student_search(): void
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        [$semester, $student] = $this->createDependencies();

        CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'status' => 'resolved',
            'confidentiality_level' => 'normal',
            'summary' => 'Searchable Summary',
            'reason' => 'Reason',
        ]);

        $response = $this->actingAs($bk)->get(route('counseling-records.index', ['search' => 'Student']));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('CounselingRecords/Index')
            ->has('records.data', 1)
        );

        $responseEmpty = $this->actingAs($bk)->get(route('counseling-records.index', ['search' => 'NonExistentXYZ']));
        $responseEmpty->assertOk();
        $responseEmpty->assertInertia(fn ($page) => $page
            ->component('CounselingRecords/Index')
            ->has('records.data', 0)
        );
    }

    public function test_bk_can_store_counseling_record_via_modal_fields(): void
    {
        $bk = User::factory()->create(['name' => 'Ibu Rahmawati BK']);
        $bk->assignRole('BK');

        [$semester, $student] = $this->createDependencies();

        $response = $this->actingAs($bk)->post(route('counseling-records.store'), [
            'student_id' => $student->id,
            'category' => 'Konseling',
            'date_scheduled' => '2026-10-12',
            'reason' => 'Siswa sering terlambat ke sekolah',
            'summary' => 'Siswa berkomitmen bangun lebih pagi dan dipantau wali kelas',
            'original_recorder_name' => 'Ibu Rahmawati BK',
        ]);

        $response->assertRedirect(route('counseling-records.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('counseling_records', [
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'category' => 'Konseling',
            'original_recorder_name' => 'Ibu Rahmawati BK',
        ]);

        $record = CounselingRecord::where('student_id', $student->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals('Siswa sering terlambat ke sekolah', $record->reason);
        $this->assertEquals('Siswa berkomitmen bangun lebih pagi dan dipantau wali kelas', $record->summary);
    }

    public function test_store_validation_requires_student_id(): void
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        [$semester, $student] = $this->createDependencies();

        $response = $this->actingAs($bk)->post(route('counseling-records.store'), [
            'student_id' => '',
            'date_scheduled' => '2026-10-12',
            'category' => 'Konseling',
        ]);

        $response->assertSessionHasErrors('student_id');
    }

    public function test_bk_can_update_counseling_record(): void
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        [$semester, $student] = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'status' => 'scheduled',
            'confidentiality_level' => 'normal',
            'category' => 'Konseling',
            'date_scheduled' => '2026-10-12',
            'reason' => 'Catatan awal',
        ]);

        $response = $this->actingAs($bk)->put(route('counseling-records.update', $record), [
            'date_scheduled' => '2026-10-12',
            'category' => 'Bimbingan Pribadi',
            'reason' => 'Alasan diperbarui',
            'summary' => 'Hasil konseling tuntas',
            'status' => 'resolved',
        ]);

        $response->assertRedirect(route('counseling-records.index'));
        $response->assertSessionHas('success');

        $record->refresh();
        $this->assertEquals('Bimbingan Pribadi', $record->category);
        $this->assertEquals('Alasan diperbarui', $record->reason);
        $this->assertEquals('Hasil konseling tuntas', $record->summary);
        $this->assertEquals('resolved', $record->status);

        // Ensure no duplicate records created
        $this->assertEquals(1, CounselingRecord::where('student_id', $student->id)->count());
    }

    public function test_bk_can_delete_scheduled_counseling_record(): void
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        [$semester, $student] = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'status' => 'scheduled',
            'category' => 'Konseling',
            'date_scheduled' => '2026-10-12',
        ]);

        $response = $this->actingAs($bk)->delete(route('counseling-records.destroy', $record));
        $response->assertRedirect(route('counseling-records.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('counseling_records', [
            'id' => $record->id,
        ]);
    }

    public function test_cannot_delete_record_on_closed_semester(): void
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        [$semester, $student] = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'status' => 'scheduled',
            'category' => 'Konseling',
            'date_scheduled' => '2026-10-12',
        ]);

        $semester->update(['status' => 'Closed']);

        $response = $this->actingAs($bk)->delete(route('counseling-records.destroy', $record));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('counseling_records', [
            'id' => $record->id,
        ]);
    }

    public function test_unauthorized_teacher_cannot_store_or_delete_counseling_record(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        [$semester, $student] = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'status' => 'scheduled',
            'category' => 'Konseling',
            'date_scheduled' => '2026-10-12',
        ]);

        $responseStore = $this->actingAs($teacher)->post(route('counseling-records.store'), [
            'student_id' => $student->id,
            'date_scheduled' => '2026-10-12',
        ]);
        $responseStore->assertStatus(403);

        $responseDelete = $this->actingAs($teacher)->delete(route('counseling-records.destroy', $record));
        $responseDelete->assertStatus(403);
    }

    public function test_counseling_create_accepts_student_id_query_parameter(): void
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        [$semester, $student] = $this->createDependencies();

        $response = $this->actingAs($bk)->get('/counseling-records/create?student_id='.$student->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CounselingRecords/Create')
            ->where('default_student_id', $student->id)
        );
    }

    public function test_bk_can_access_student_show_and_counseling_actions(): void
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        [$semester, $student] = $this->createDependencies();

        $this->actingAs($bk)->get('/students/'.$student->id)->assertStatus(200);
        $this->actingAs($bk)->get('/counseling-records/create?student_id='.$student->id)->assertStatus(200);
        $this->actingAs($bk)->get('/reports?student_id='.$student->id)->assertStatus(200);
    }

    public function test_student_show_includes_counseling_records_for_bk_and_principal_but_not_teacher(): void
    {
        $bk = User::factory()->create();
        $bk->assignRole('BK');

        $principal = User::factory()->create();
        $principal->assignRole('Principal');

        [$semester, $student] = $this->createDependencies();

        CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'status' => 'scheduled',
            'category' => 'Perilaku',
            'confidentiality_level' => 'normal',
            'reason' => 'Sering terlambat masuk kelas',
            'summary' => 'Diberikan arahan manajemen waktu',
            'follow_up_action' => 'Evaluasi 1 minggu',
        ]);

        // BK sees counselingRecords
        $responseBk = $this->actingAs($bk)->get('/students/'.$student->id);
        $responseBk->assertStatus(200);
        $responseBk->assertInertia(fn ($page) => $page
            ->component('Students/Show')
            ->has('counselingRecords', 1)
            ->where('counselingRecords.0.category', 'Perilaku')
            ->where('counselingRecords.0.status', 'scheduled')
        );

        // Principal sees counselingRecords
        $responsePrincipal = $this->actingAs($principal)->get('/students/'.$student->id);
        $responsePrincipal->assertStatus(200);
        $responsePrincipal->assertInertia(fn ($page) => $page
            ->component('Students/Show')
            ->has('counselingRecords', 1)
        );

        // Teacher (with homeroom for this student) does not receive counselingRecords (null)
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('Teacher');
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'employee_number' => 'T123',
            'name' => 'Wali Kelas Test',
            'status' => 'active',
        ]);
        $student->classRoom->update(['homeroom_teacher_id' => $teacher->id]);

        $responseTeacher = $this->actingAs($teacherUser)->get('/students/'.$student->id);
        $responseTeacher->assertStatus(200);
        $responseTeacher->assertInertia(fn ($page) => $page
            ->component('Students/Show')
            ->where('counselingRecords', null)
        );
    }

    public function test_bk_can_schedule_counseling_with_minimal_payload(): void
    {
        $bk = User::factory()->create(['name' => 'Ibu Guru BK']);
        $bk->assignRole('BK');

        [$semester, $student] = $this->createDependencies();

        $response = $this->actingAs($bk)->post(route('counseling-records.store'), [
            'student_id' => $student->id,
            'date_scheduled' => '2026-11-05',
        ]);

        $response->assertRedirect(route('counseling-records.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('counseling_records', [
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'original_recorder_name' => 'Ibu Guru BK',
            'status' => 'scheduled',
            'category' => 'Konseling',
        ]);
    }

    public function test_counseling_record_detail_screen_renders_for_authorized_bk(): void
    {
        $bk = User::factory()->create(['name' => 'Konselor BK']);
        $bk->assignRole('BK');

        [$semester, $student] = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'original_recorder_name' => 'Konselor BK',
            'date_scheduled' => '2026-11-05',
            'category' => 'Kedisiplinan',
            'reason' => 'Sering datang terlambat ke sekolah',
            'summary' => 'Siswa diberikan konseling pribadi dan menandatangani komitmen kedisiplinan',
            'follow_up_action' => 'Pemantauan absensi selama 14 hari ke depan',
            'status' => 'resolved',
            'confidentiality_level' => 'normal',
        ]);

        $response = $this->actingAs($bk)->get(route('counseling-records.show', $record));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CounselingRecords/Show')
            ->has('record')
            ->where('record.id', $record->id)
            ->where('record.reason', 'Sering datang terlambat ke sekolah')
            ->where('record.summary', 'Siswa diberikan konseling pribadi dan menandatangani komitmen kedisiplinan')
            ->where('record.follow_up_action', 'Pemantauan absensi selama 14 hari ke depan')
            ->has('studentSummary')
            ->has('can')
            ->where('can.update', true)
            ->where('can.delete', false)
        );

        // Teacher is forbidden to view counseling record details
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $this->actingAs($teacher)
            ->get(route('counseling-records.show', $record))
            ->assertStatus(403);
    }

    public function test_counseling_record_edit_screen_renders_for_authorized_bk(): void
    {
        $bk = User::factory()->create(['name' => 'Konselor BK']);
        $bk->assignRole('BK');

        [$semester, $student] = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'original_recorder_name' => 'Konselor BK',
            'date_scheduled' => '2026-11-05',
            'category' => 'Kedisiplinan',
            'reason' => 'Sering datang terlambat',
            'summary' => 'Konseling awal',
            'status' => 'in_progress',
            'confidentiality_level' => 'normal',
        ]);

        $response = $this->actingAs($bk)->get(route('counseling-records.edit', $record));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CounselingRecords/Edit')
            ->has('record')
            ->where('record.id', $record->id)
            ->has('categories')
            ->has('statuses')
        );

        // Teacher is forbidden to edit counseling record
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $this->actingAs($teacher)
            ->get(route('counseling-records.edit', $record))
            ->assertStatus(403);
    }

    public function test_counseling_record_update_persists_changes_and_redirects(): void
    {
        $bk = User::factory()->create(['name' => 'Konselor BK']);
        $bk->assignRole('BK');

        [$semester, $student] = $this->createDependencies();

        $record = CounselingRecord::create([
            'semester_id' => $semester->id,
            'student_id' => $student->id,
            'counselor_id' => $bk->id,
            'date_scheduled' => '2026-11-05',
            'status' => 'scheduled',
            'confidentiality_level' => 'normal',
        ]);

        $response = $this->actingAs($bk)->put(route('counseling-records.update', $record), [
            'date_scheduled' => '2026-11-06',
            'date_completed' => '2026-11-06',
            'category' => 'Perilaku dan kedisiplinan',
            'status' => 'resolved',
            'reason' => 'Alasan diperbarui',
            'summary' => 'Hasil sesi diperbarui dengan lengkap',
            'follow_up_action' => 'Tindak lanjut komitmen bersama orang tua',
            'confidentiality_level' => 'strict',
        ]);

        $response->assertRedirect(route('counseling-records.index'));
        $response->assertSessionHas('success');

        $record->refresh();
        $this->assertEquals('resolved', $record->status);
        $this->assertEquals('strict', $record->confidentiality_level);
        $this->assertEquals('Alasan diperbarui', $record->reason);
        $this->assertEquals('Hasil sesi diperbarui dengan lengkap', $record->summary);
        $this->assertEquals('Tindak lanjut komitmen bersama orang tua', $record->follow_up_action);
    }
}
