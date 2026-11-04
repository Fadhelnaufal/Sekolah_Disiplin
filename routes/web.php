<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\CounselingRecordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisciplineEventController;
use App\Http\Controllers\DisciplineRecapController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SanctionRuleController;
use App\Http\Controllers\SchoolSettingController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ViolationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile & Password Mandiri (All Roles)
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Super Admin Only
    Route::middleware(['role:Super Admin'])->group(function () {
        Route::resource('academic-years', AcademicYearController::class);
        Route::resource('semesters', SemesterController::class);
        Route::post('semesters/{semester}/close', [SemesterController::class, 'close'])->name('semesters.close');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    });

    // Master Data Legacy Index Redirects (All Roles)
    Route::middleware(['role:Super Admin|Tata Tertib|BK|Teacher|Principal'])->group(function () {
        Route::get('violations', [ViolationController::class, 'index'])->name('violations.index');
        Route::get('achievements', [AchievementController::class, 'index'])->name('achievements.index');
        Route::get('sanction-rules', [SanctionRuleController::class, 'index'])->name('sanction-rules.index');
    });

    // Master Data CRUD (Super Admin, Tata Tertib, BK)
    Route::middleware(['role:Super Admin|Tata Tertib|BK'])->group(function () {
        Route::resource('violations', ViolationController::class)->except(['index']);
        Route::resource('achievements', AchievementController::class)->except(['index']);
        Route::resource('sanction-rules', SanctionRuleController::class)->except(['index']);
    });

    // Super Admin & Tata Tertib
    Route::middleware(['role:Super Admin|Tata Tertib'])->group(function () {
        Route::resource('teachers', TeacherController::class);
        Route::resource('class-rooms', ClassRoomController::class);

        Route::get('settings', [SchoolSettingController::class, 'index'])->name('settings.index');
        Route::match(['put', 'post'], 'settings', [SchoolSettingController::class, 'update'])->name('settings.update');
    });

    // Students (Write & Bulk Actions)
    Route::middleware(['role:Super Admin|Tata Tertib'])->group(function () {
        Route::post('students/bulk-draft', [StudentController::class, 'bulkDraft'])->name('students.bulk-draft');
        Route::post('students/bulk-restore', [StudentController::class, 'bulkRestore'])->name('students.bulk-restore');
        Route::post('students/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');
        Route::delete('students/{id}/force', [StudentController::class, 'forceDelete'])->name('students.force-delete');
        Route::post('students/update-class-name', [StudentController::class, 'updateClassName'])->name('students.update-class-name');
        Route::resource('students', StudentController::class)->except(['index', 'show']);
    });

    // Students (Read) & Master Data Catalog (Read-Only)
    Route::middleware(['role:Super Admin|Tata Tertib|BK|Teacher|Principal'])->group(function () {
        Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data.index');
        Route::get('students/template', [StudentController::class, 'downloadTemplate'])->name('students.template');
        Route::get('students', [StudentController::class, 'index'])->name('students.index');
        Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('students/{student}/print-card', [StudentController::class, 'printCard'])->name('students.print-card');
    });

    // Discipline Events (Read)
    Route::middleware(['role:Super Admin|Tata Tertib|BK|Teacher|Principal'])->group(function () {
        Route::get('discipline-events', [DisciplineEventController::class, 'index'])->name('discipline-events.index');
    });

    // Discipline Events (Create)
    Route::middleware(['role:Super Admin|Tata Tertib|Teacher|BK'])->group(function () {
        Route::get('discipline-events/create', [DisciplineEventController::class, 'create'])->name('discipline-events.create');
        Route::post('discipline-events', [DisciplineEventController::class, 'store'])->name('discipline-events.store');
    });

    // Discipline Events (Destroy)
    Route::middleware(['role:Super Admin|Tata Tertib'])->group(function () {
        Route::delete('discipline-events/{discipline_event}', [DisciplineEventController::class, 'destroy'])->name('discipline-events.destroy');
    });

    // Counseling Records
    Route::middleware(['role:Super Admin|BK|Principal'])->group(function () {
        Route::resource('counseling-records', CounselingRecordController::class);
    });

    // Follow-ups (Tindak Lanjut)
    Route::middleware(['role:Super Admin|Tata Tertib|BK|Principal|Teacher'])->group(function () {
        Route::resource('follow-ups', FollowUpController::class);
    });

    // Letters
    Route::middleware(['role:Super Admin|BK|Principal|Tata Tertib'])->group(function () {
        Route::resource('letters', LetterController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('letters/{letter}/download', [LetterController::class, 'download'])->name('letters.download');
        Route::get('letters/{letter}/word', [LetterController::class, 'exportWord'])->name('letters.word');
        Route::get('letters/student-data/{student}', [LetterController::class, 'getStudentData'])->name('letters.student-data');
        Route::post('letters/template', [LetterController::class, 'updateTemplate'])->name('letters.update-template');
    });

    // Excel Import
    Route::middleware(['role:Super Admin|Tata Tertib|BK'])->group(function () {
        Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
        Route::post('imports/upload', [ImportController::class, 'upload'])->name('imports.upload');
        Route::post('imports/commit', [ImportController::class, 'commit'])->name('imports.commit');
    });

    // Reports & Analytics & Recap
    Route::middleware(['role:Super Admin|BK|Principal|Tata Tertib|Teacher'])->group(function () {
        Route::get('discipline-recap', [DisciplineRecapController::class, 'index'])->name('discipline-recap.index');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::match(['get', 'post'], 'reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
        Route::get('reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
        Route::get('reports/classes', [ReportController::class, 'classes'])->name('reports.classes');
        Route::get('reports/homerooms', [ReportController::class, 'homerooms'])->name('reports.homerooms');
        Route::get('reports/analytics', [ReportController::class, 'analytics'])->name('reports.analytics');
    });
});
