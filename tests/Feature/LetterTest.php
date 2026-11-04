<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\CounselingRecord;
use App\Models\DisciplineEvent;
use App\Models\Letter;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Violation;
use App\Services\LetterService;
use App\Services\SanctionEngineService;
use App\Services\StudentPointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LetterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    private function createDependencies()
    {
        $school = School::create([
            'name' => 'SMK Test',
            'application_name' => 'Disiplin Pro',
            'address' => 'Jl. Test',
            'city' => 'Jakarta',
            'principal_name' => 'Kepsek Test',
            'vice_principal_name' => 'Waka Kesiswaan Test',
            'counseling_officer_name' => 'Guru BK Test',
        ]);

        $academicYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'start_date' => now()->subMonths(3)->format('Y-m-d'),
            'end_date' => now()->addMonths(3)->format('Y-m-d'),
            'status' => 'Active',
        ]);

        $semester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Ganjil',
            'start_date' => now()->subMonths(3)->format('Y-m-d'),
            'end_date' => now()->addMonths(3)->format('Y-m-d'),
            'status' => 'Active',
        ]);

        $bkUser = User::factory()->create();
        $bkUser->assignRole('BK');

        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('Teacher');
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '12345',
            'name' => 'Guru Test',
            'status' => 'Active',
        ]);

        $classRoom = ClassRoom::create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => '10 RPL 1',
            'grade_level' => '10',
        ]);

        $student = Student::create([
            'school_id' => $school->id,
            'class_room_id' => $classRoom->id,
            'nis' => '100100',
            'name' => 'Siswa Test',
            'status' => 'Active',
        ]);

        return [$semester, $student, $bkUser, $teacherUser];
    }

    public function test_authorized_role_can_view_letters_index()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();

        $response = $this->actingAs($bkUser)->get(route('letters.index'));
        $response->assertStatus(200);
    }

    public function test_unauthorized_role_cannot_view_letters()
    {
        [$semester, $student, $bkUser, $teacherUser] = $this->createDependencies();

        // Teacher cannot view letters index
        $response = $this->actingAs($teacherUser)->get(route('letters.index'));
        $response->assertStatus(403);
    }

    public function test_authorized_role_can_create_letter_and_points_are_computed_from_backend()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();

        $violation = Violation::create([
            'code' => 'TEST-VIO',
            'name' => 'Test Vio',
            'category' => 'Ringan',
            'points' => 15,
        ]);

        DisciplineEvent::create([
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'event_date' => now(),
            'event_type' => 'violation',
            'violation_id' => $violation->id,
            'points_effect' => 15,
            'recorded_by' => $bkUser->id,
        ]);

        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'parent_call',
            'subject' => 'Panggilan Orang Tua',
            'points' => 999, // Malicious injection attempt
        ]);

        $response->assertRedirect(route('letters.index'));

        $this->assertDatabaseHas('letters', [
            'student_id' => $student->id,
            'type' => 'parent_call',
            'status' => 'Generated',
        ]);

        $letter = Letter::first();
        // Point stored in metadata must be 15, not 999
        $this->assertEquals(15, $letter->metadata['net_points']);
    }

    public function test_recent_events_are_limited_to_5()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();

        $violation = Violation::create([
            'code' => 'TEST-VIO',
            'name' => 'Test Vio',
            'category' => 'Ringan',
            'points' => 5,
        ]);

        // Insert 10 events
        for ($i = 0; $i < 10; $i++) {
            DisciplineEvent::create([
                'student_id' => $student->id,
                'semester_id' => $semester->id,
                'event_date' => now()->subDays(10 - $i),
                'event_type' => 'violation',
                'violation_id' => $violation->id,
                'points_effect' => 5,
                'recorded_by' => $bkUser->id,
            ]);
        }

        $service = new LetterService(
            new StudentPointService,
            new SanctionEngineService(new StudentPointService)
        );

        $data = $service->buildLetterData($student, $semester, 'notification');

        // Verify recent events is exactly 5
        $this->assertCount(5, $data['recent_events']);

        // Ensure net points is 50
        $this->assertEquals(50, $data['net_points']);
    }

    public function test_letter_generation_with_empty_data()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();

        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'notification',
        ]);

        $response->assertRedirect(route('letters.index'));

        $letter = Letter::first();
        $this->assertEquals(0, $letter->metadata['net_points']);
    }

    public function test_fails_when_signatory_is_missing()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();

        $school = School::first();
        // Clear counseling officer name
        $school->update(['counseling_officer_name' => null]);

        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'call_1',
        ]);

        $response->assertSessionHasErrors(['type']);
        $this->assertDatabaseCount('letters', 0);
    }

    public function test_stores_metadata_signatory_and_saves_to_local_disk()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();

        $school = School::first();
        $school->update([
            'vice_principal_name' => 'Waka Kesiswaan Test',
            'principal_name' => 'Kepsek Test',
        ]);

        Storage::fake('local');

        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'call_2',
        ]);

        $response->assertRedirect(route('letters.index'));

        $letter = Letter::first();

        // Metadata saved correctly
        $this->assertEquals('Wakil Kepala Sekolah Bidang Kesiswaan', $letter->metadata['signatory_title']);
        $this->assertEquals('Waka Kesiswaan Test', $letter->metadata['signatory_name']);

        // File exists on local disk
        Storage::disk('local')->assertExists($letter->pdf_path);
    }

    public function test_cleans_up_orphaned_file_on_db_failure()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();

        $school = School::first();
        $school->update([
            'principal_name' => 'Kepsek Test',
        ]);

        Storage::fake('local');

        // Force DB creation to fail
        Letter::saving(function () {
            throw new \Exception('Simulated DB Failure');
        });

        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'call_3',
        ]);

        $response->assertStatus(500); // Because we threw an unhandled exception

        // Ensure no files are left in the local disk
        $files = Storage::disk('local')->files('letters');
        $this->assertEmpty($files, 'Orphaned PDF file was not cleaned up.');
    }

    public function test_authorized_user_can_update_letter_template()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();

        $response = $this->actingAs($bkUser)->post(route('letters.update-template'), [
            'name' => 'SMA Negeri 1 Sambit',
            'npsn' => '123123',
            'phone' => '1234566',
            'address' => 'Jl. Ponorogo - Trenggalek, Besuki, Kec. Sambit',
            'city' => 'Ponorogo',
            'province' => 'Jawa Timur',
            'postal_code' => '63474',
            'email' => 'tes@gmail.com',
            'website' => 'https://sman1sambit.sch.id',
            'principal_name' => 'Fadhel',
            'principal_nip' => '198701012010011001',
            'signer_title' => 'Kepala Sekolah',
            'letter_city' => 'Ponorogo',
            'classification_code' => '421.3',
            'school_code' => 'SMA-NEGERI-1-SAMBIT',
            'template_style' => 'formal_indonesia',
            'show_npsn' => 1,
            'show_phone' => 1,
            'show_email' => 1,
            'show_website' => 0,
            'show_signature_image' => 1,
            'show_stamp' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('schools', [
            'name' => 'SMA Negeri 1 Sambit',
            'npsn' => '123123',
            'city' => 'Ponorogo',
            'province' => 'Jawa Timur',
            'classification_code' => '421.3',
            'school_code' => 'SMA-NEGERI-1-SAMBIT',
        ]);
    }

    public function test_unauthorized_user_cannot_update_letter_template()
    {
        [$semester, $student, $bkUser, $teacherUser] = $this->createDependencies();

        $response = $this->actingAs($teacherUser)->post(route('letters.update-template'), [
            'name' => 'Hacked School Name',
            'address' => 'Test Address',
            'city' => 'Test City',
        ]);

        $response->assertStatus(403);
    }

    public function test_generate_parent_notification_with_custom_incident_summary()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();
        Storage::fake('local');

        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'parent_notification',
            'subject' => 'Surat Pemberitahuan Pelanggaran Khusus',
            'incident_summary' => 'Siswa terlambat 3 kali berturut-turut pada jam pertama.',
            'school_action' => 'Diberikan teguran lisan dan bimbingan wali kelas.',
            'expected_follow_up' => 'Orang tua diminta mendampingi jam tidur siswa.',
        ]);

        $response->assertRedirect(route('letters.index'));
        $this->assertDatabaseHas('letters', [
            'student_id' => $student->id,
            'type' => 'parent_notification',
            'subject' => 'Surat Pemberitahuan Pelanggaran Khusus',
        ]);

        $letter = Letter::first();
        $this->assertEquals('parent_notification', $letter->metadata['canonical_type']);
        $this->assertStringContainsString('3 kali berturut-turut', $letter->metadata['custom_payload']['incident_summary']);
        Storage::disk('local')->assertExists($letter->pdf_path);
    }

    public function test_generate_student_agreement_with_commitments_and_evaluation_period()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();
        Storage::fake('local');

        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'student_agreement',
            'subject' => 'Surat Perjanjian dan Pernyataan Siswa',
            'problem_summary' => 'Terlibat perkelahian di luar pagar sekolah.',
            'student_commitments' => "1. Berjanji tidak mengulangi perkelahian.\n2. Siap mematuhi tata tertib.",
            'evaluation_period' => '60 hari kalender',
            'sanction_consequence' => 'Skorsing 1 minggu bila melanggar kembali.',
        ]);

        $response->assertRedirect(route('letters.index'));

        $letter = Letter::first();
        $this->assertEquals('student_agreement', $letter->metadata['canonical_type']);
        $this->assertEquals('60 hari kalender', $letter->metadata['custom_payload']['evaluation_period']);
        Storage::disk('local')->assertExists($letter->pdf_path);
    }

    public function test_generate_parent_call_with_meeting_schedule_and_venue()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();
        Storage::fake('local');

        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'parent_call',
            'call_level' => 'call_1',
            'subject' => 'Panggilan Orang Tua Siswa - Panggilan I',
            'reason' => 'Konsultasi ketidakhadiran tanpa keterangan 4 hari.',
            'meeting_day_date' => 'Kamis, 15 Oktober 2026',
            'meeting_time' => '09.00 WIB s/d selesai',
            'meeting_venue' => 'Ruang Bimbingan Konseling',
            'contact_person' => 'Ibu Guru BK (08123456789)',
        ]);

        $response->assertRedirect(route('letters.index'));

        $letter = Letter::first();
        $this->assertEquals('parent_call', $letter->metadata['canonical_type']);
        $this->assertEquals('Kamis, 15 Oktober 2026', $letter->metadata['custom_payload']['meeting_day_date']);
        $this->assertEquals('Ruang Bimbingan Konseling', $letter->metadata['custom_payload']['meeting_venue']);
        Storage::disk('local')->assertExists($letter->pdf_path);
    }

    public function test_generate_discipline_history_includes_events_and_preserves_counseling_confidentiality()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();
        Storage::fake('local');

        $violation = Violation::create([
            'code' => 'TEST-VIO-HIST',
            'name' => 'Bolos Sekolah',
            'category' => 'Sedang',
            'points' => 20,
        ]);

        DisciplineEvent::create([
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'event_date' => now()->subDays(2),
            'event_type' => 'violation',
            'violation_id' => $violation->id,
            'points_effect' => 20,
            'recorded_by' => $bkUser->id,
        ]);

        // Create confidential counseling record
        CounselingRecord::create([
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'counselor_id' => $bkUser->id,
            'original_recorder_name' => 'Guru BK',
            'date_scheduled' => now()->toDateString(),
            'category' => 'Konseling Pribadi',
            'reason' => 'Masalah rahasia keluarga siswa yang sangat sensitif',
            'summary' => 'Catatan super rahasia tidak boleh bocor ke publik',
            'confidentiality_level' => 'strict',
            'status' => 'resolved',
            'created_by' => $bkUser->id,
        ]);

        $response = $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'discipline_history',
            'subject' => 'Laporan Rekam Jejak Kedisiplinan Siswa',
            'include_achievements' => '1',
            'evaluation_notes' => 'Siswa dalam pembinaan berkelanjutan.',
        ]);

        $response->assertRedirect(route('letters.index'));

        $letter = Letter::first();
        $this->assertEquals('discipline_history', $letter->metadata['canonical_type']);
        $this->assertEquals(20, $letter->metadata['net_points']);

        // Inspect letter data from LetterService to ensure private counseling notes are not included
        $service = app(LetterService::class);
        $data = $service->buildLetterData($student, $semester, 'discipline_history');

        $this->assertNotEmpty($data['all_events']);
        $this->assertEquals('Bolos Sekolah', $data['all_events'][0]['name']);

        // Ensure confidential text is NEVER present in the document payload or events
        $serialized = json_encode($data);
        $this->assertStringNotContainsString('Masalah rahasia keluarga siswa yang sangat sensitif', $serialized);
        $this->assertStringNotContainsString('Catatan super rahasia tidak boleh bocor ke publik', $serialized);

        Storage::disk('local')->assertExists($letter->pdf_path);
    }

    public function test_document_preserves_immutable_school_snapshot_when_school_is_updated()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();
        Storage::fake('local');

        $school = School::first();
        $school->update([
            'name' => 'Nama Sekolah Versi 1 (Asli)',
            'principal_name' => 'Drs. Kepala Lama, M.Pd.',
        ]);

        $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'parent_notification',
            'subject' => 'Surat Pemberitahuan Awal',
        ]);

        $letter = Letter::first();
        $this->assertEquals('Nama Sekolah Versi 1 (Asli)', $letter->metadata['school_snapshot']['name']);
        $this->assertEquals('Drs. Kepala Lama, M.Pd.', $letter->metadata['school_snapshot']['principal_name']);

        // School updates name and principal later
        $school->update([
            'name' => 'Nama Sekolah Versi 2 (Ganti Nama)',
            'principal_name' => 'Kepala Baru, S.Pd.',
        ]);

        // Historical letter metadata snapshot remains intact!
        $letter->refresh();
        $this->assertEquals('Nama Sekolah Versi 1 (Asli)', $letter->metadata['school_snapshot']['name']);
        $this->assertEquals('Drs. Kepala Lama, M.Pd.', $letter->metadata['school_snapshot']['principal_name']);
    }

    public function test_export_word_returns_doc_file()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();
        Storage::fake('local');

        $this->actingAs($bkUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'parent_call',
            'call_level' => 'call_1',
        ]);

        $letter = Letter::first();

        $response = $this->actingAs($bkUser)->get(route('letters.word', $letter));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/msword; charset=utf-8');
    }

    public function test_student_data_endpoint_returns_json_summary()
    {
        [$semester, $student, $bkUser] = $this->createDependencies();

        $response = $this->actingAs($bkUser)->get(route('letters.student-data', $student));
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $student->id,
            'nis' => $student->nis,
            'name' => $student->name,
            'net_points' => 0,
        ]);
    }

    public function test_tatib_can_create_and_update_letters()
    {
        [$semester, $student] = $this->createDependencies();

        $tatibUser = User::factory()->create();
        $tatibUser->assignRole('Tata Tertib');

        Storage::fake('local');

        $response = $this->actingAs($tatibUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'parent_call',
            'call_level' => 'call_1',
        ]);

        $response->assertRedirect(route('letters.index'));
        $letter = Letter::first();
        $this->assertNotNull($letter);

        // Tatib can view letter
        $this->assertTrue($tatibUser->can('view', $letter));
        // Tatib can update letter
        $this->assertTrue($tatibUser->can('update', $letter));
    }

    public function test_principal_can_create_and_update_letters()
    {
        [$semester, $student] = $this->createDependencies();

        $principalUser = User::factory()->create();
        $principalUser->assignRole('Principal');

        Storage::fake('local');

        $response = $this->actingAs($principalUser)->post(route('letters.store'), [
            'student_id' => $student->id,
            'type' => 'parent_call',
            'call_level' => 'call_1',
        ]);

        $response->assertRedirect(route('letters.index'));
        $letter = Letter::first();
        $this->assertNotNull($letter);

        // Principal can view letter
        $this->assertTrue($principalUser->can('view', $letter));
        // Principal can update letter
        $this->assertTrue($principalUser->can('update', $letter));
    }
}
