<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\ClassRoom;
use App\Models\SanctionRule;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $roles = ['Super Admin', 'Tata Tertib', 'Teacher', 'BK', 'Principal'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // 2. Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@disiplin.pro'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('Super Admin');

        // 3. School, Academic Year, Semester
        $school = School::firstOrCreate(
            ['npsn' => '12345678'],
            [
                'name' => 'SMA Negeri 1 Disiplin',
                'address' => 'Jl. Pendidikan No. 1',
                'city' => 'Jakarta',
                'application_name' => 'Disiplin Pro'
            ]
        );

        $academicYear = AcademicYear::firstOrCreate(
            ['school_id' => $school->id, 'name' => '2026/2027'],
            [
                'start_date' => '2026-07-15',
                'end_date' => '2027-06-15',
                'status' => 'active'
            ]
        );

        $semester = Semester::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'name' => 'Ganjil'],
            [
                'start_date' => '2026-07-15',
                'end_date' => '2026-12-15',
                'status' => 'active'
            ]
        );

        // 4. Other Roles Users
        $teacherUser = User::firstOrCreate(
            ['email' => 'guru@disiplin.pro'],
            [
                'name' => 'Budi Santoso, S.Pd',
                'password' => Hash::make('password'),
            ]
        );
        $teacherUser->assignRole('Teacher');

        $bkUser = User::firstOrCreate(
            ['email' => 'bk@disiplin.pro'],
            [
                'name' => 'Dra. Ani Suryani (BK)',
                'password' => Hash::make('password'),
            ]
        );
        $bkUser->assignRole('BK');

        $tataTertibUser = User::firstOrCreate(
            ['email' => 'tatatertib@disiplin.pro'],
            [
                'name' => 'Pak Tatib',
                'password' => Hash::make('password'),
            ]
        );
        $tataTertibUser->assignRole('Tata Tertib');

        $principalUser = User::firstOrCreate(
            ['email' => 'kepsek@disiplin.pro'],
            [
                'name' => 'Dr. Kepala Sekolah',
                'password' => Hash::make('password'),
            ]
        );
        $principalUser->assignRole('Principal');

        $teacher = Teacher::firstOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'employee_number' => '198001012005011001',
                'name' => 'Budi Santoso, S.Pd',
                'phone' => '081234567890',
                'status' => 'active'
            ]
        );

        $classRoom = ClassRoom::firstOrCreate(
            ['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name' => 'X MIPA 1'],
            [
                'grade_level' => '10',
                'homeroom_teacher_id' => $teacher->id,
                'status' => 'active'
            ]
        );

        // 5. Students
        $students = [
            ['nis' => '262710001', 'name' => 'Agus Pratama'],
            ['nis' => '262710002', 'name' => 'Bunga Lestari'],
            ['nis' => '262710003', 'name' => 'Citra Amelia'],
        ];

        foreach ($students as $studentData) {
            Student::firstOrCreate(
                ['school_id' => $school->id, 'nis' => $studentData['nis']],
                [
                    'name' => $studentData['name'],
                    'class_room_id' => $classRoom->id,
                    'status' => 'Active'
                ]
            );
        }

        // 6. Violations
        $violations = [
            ['code' => 'P-101', 'name' => 'Terlambat masuk sekolah', 'category' => 'Ringan', 'points' => 5],
            ['code' => 'P-102', 'name' => 'Tidak memakai seragam lengkap', 'category' => 'Ringan', 'points' => 10],
            ['code' => 'P-201', 'name' => 'Bolos sekolah', 'category' => 'Sedang', 'points' => 20],
            ['code' => 'P-301', 'name' => 'Berkelahi di lingkungan sekolah', 'category' => 'Berat', 'points' => 50],
        ];

        foreach ($violations as $v) {
            Violation::firstOrCreate(['code' => $v['code']], $v);
        }

        // 7. Achievements
        $achievements = [
            ['code' => 'R-01', 'name' => 'Juara 1 Lomba Akademik Tingkat Kota', 'level' => 'Kota', 'deduction_points' => 20],
            ['code' => 'R-02', 'name' => 'Juara 1 Lomba Akademik Tingkat Provinsi', 'level' => 'Provinsi', 'deduction_points' => 50],
        ];

        foreach ($achievements as $a) {
            Achievement::firstOrCreate(['code' => $a['code']], $a);
        }

        // 8. Sanction Rules
        $rules = [
            ['level' => 1, 'min_points' => 0, 'max_points' => 9, 'action' => 'Pembinaan lisan', 'executor' => 'Wali Kelas'],
            ['level' => 2, 'min_points' => 10, 'max_points' => 24, 'action' => 'Teguran tertulis + panggilan siswa', 'executor' => 'Wali Kelas / BK'],
            ['level' => 3, 'min_points' => 25, 'max_points' => 49, 'action' => 'Surat pemberitahuan ke orang tua', 'executor' => 'Tata Tertib'],
            ['level' => 4, 'min_points' => 50, 'max_points' => 74, 'action' => 'Panggilan orang tua ke sekolah', 'executor' => 'BK'],
            ['level' => 5, 'min_points' => 75, 'max_points' => 99, 'action' => 'Skorsing 3 hari + perjanjian', 'executor' => 'Kepala Sekolah'],
            ['level' => 6, 'min_points' => 100, 'max_points' => 149, 'action' => 'Skorsing 1 minggu + pembinaan intensif', 'executor' => 'Kepala Sekolah'],
            ['level' => 7, 'min_points' => 150, 'max_points' => null, 'action' => 'Dikembalikan kepada orang tua', 'executor' => 'Kepala Sekolah'],
        ];

        foreach ($rules as $r) {
            SanctionRule::firstOrCreate(['level' => $r['level']], $r);
        }
    }
}
