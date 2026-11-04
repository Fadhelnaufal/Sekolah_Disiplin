<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\DisciplineEvent;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\EarlyWarningEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $teacherUser;

    protected School $school;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Super Admin');

        $this->teacherUser = User::factory()->create();
        $this->teacherUser->assignRole('Teacher');

        $this->school = School::create([
            'name' => 'SMP Malang',
            'address' => 'Jl. Pendidikan No. 1',
            'city' => 'Malang',
            'application_name' => 'Disiplin Pro',
        ]);
    }

    public function test_super_admin_can_access_school_settings()
    {
        $response = $this->actingAs($this->adminUser)->get(route('settings.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Settings/Index')
            ->has('school')
            ->where('school.name', 'SMP Malang')
        );
    }

    public function test_teacher_cannot_access_school_settings()
    {
        $response = $this->actingAs($this->teacherUser)->get(route('settings.index'));
        $response->assertStatus(403);
    }

    public function test_super_admin_can_update_school_settings()
    {
        $response = $this->actingAs($this->adminUser)->put(route('settings.update'), [
            'name' => 'SMP Negeri 1 Malang Terpadu',
            'npsn' => '20533812',
            'address' => 'Jl. Bandung No. 7',
            'city' => 'Kota Malang',
            'phone' => '0341-551234',
            'principal_name' => 'Dr. H. Sulaiman, M.Pd.',
            'vice_principal_name' => 'Budi Utomo, S.Pd.',
            'counseling_officer_name' => 'Siti Rahmawati, S.Psi.',
            'application_name' => 'Disiplin Pro',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('schools', [
            'name' => 'SMP Negeri 1 Malang Terpadu',
            'npsn' => '20533812',
            'city' => 'Kota Malang',
            'principal_name' => 'Dr. H. Sulaiman, M.Pd.',
        ]);
    }

    public function test_super_admin_can_upload_and_remove_school_logo()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('school_logo.png', 200, 200);

        $response = $this->actingAs($this->adminUser)->post(route('settings.update'), [
            '_method' => 'put',
            'name' => 'SMP Malang',
            'address' => 'Jl. Pendidikan No. 1',
            'city' => 'Malang',
            'application_name' => 'Disiplin Pro',
            'logo' => $file,
        ]);

        $response->assertRedirect();
        $this->school->refresh();
        $this->assertNotNull($this->school->logo_path);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $this->school->logo_path));

        // Now test removing the logo
        $removeResponse = $this->actingAs($this->adminUser)->post(route('settings.update'), [
            '_method' => 'put',
            'name' => 'SMP Malang',
            'address' => 'Jl. Pendidikan No. 1',
            'city' => 'Malang',
            'application_name' => 'Disiplin Pro',
            'remove_logo' => true,
        ]);

        $removeResponse->assertRedirect();
        $this->school->refresh();
        $this->assertNull($this->school->logo_path);
    }

    public function test_super_admin_can_update_early_warning_thresholds()
    {
        $response = $this->actingAs($this->adminUser)->put(route('settings.update'), [
            'name' => 'SMP Malang',
            'address' => 'Jl. Pendidikan No. 1',
            'city' => 'Malang',
            'application_name' => 'Disiplin Pro',
            'attention_threshold' => 25,
            'early_warning_days' => 14,
            'rapid_increase_threshold' => 10,
            'highest_point_threshold' => 100,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('schools', [
            'id' => $this->school->id,
            'attention_threshold' => 25,
            'early_warning_days' => 14,
            'rapid_increase_threshold' => 10,
            'highest_point_threshold' => 100,
        ]);
    }

    public function test_early_warning_engine_uses_school_configured_thresholds()
    {
        $this->school->update([
            'attention_threshold' => 10,
            'highest_point_threshold' => 50,
        ]);

        $academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'status' => 'Active',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $semester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Ganjil',
            'status' => 'Active',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'nis' => '10001',
            'name' => 'Budi Test',
            'status' => 'Active',
        ]);

        // Create violation event with 12 points (above attention_threshold 10, below default 20)
        DisciplineEvent::create([
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'event_date' => now()->format('Y-m-d'),
            'event_type' => 'violation',
            'points_effect' => 12,
        ]);

        $engine = app(EarlyWarningEngineService::class);
        $status = $engine->evaluateRiskStatus($student, $semester);

        $this->assertEquals(EarlyWarningEngineService::STATUS_ATTENTION, $status);
    }
}
