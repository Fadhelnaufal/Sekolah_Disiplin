<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    // Super Admin Only
    Route::middleware(['role:Super Admin'])->group(function () {
        Route::resource('academic-years', \App\Http\Controllers\AcademicYearController::class);
        Route::resource('semesters', \App\Http\Controllers\SemesterController::class);
        Route::post('semesters/{semester}/close', [\App\Http\Controllers\SemesterController::class, 'close'])->name('semesters.close');
        
        Route::get('audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    // Super Admin & Tata Tertib
    Route::middleware(['role:Super Admin|Tata Tertib'])->group(function () {
        Route::resource('teachers', \App\Http\Controllers\TeacherController::class);
        Route::resource('class-rooms', \App\Http\Controllers\ClassRoomController::class);
        Route::resource('violations', \App\Http\Controllers\ViolationController::class);
        Route::resource('achievements', \App\Http\Controllers\AchievementController::class);
        Route::resource('sanction-rules', \App\Http\Controllers\SanctionRuleController::class);
    });

    // Students (Write)
    Route::middleware(['role:Super Admin|Tata Tertib'])->group(function () {
        Route::resource('students', \App\Http\Controllers\StudentController::class)->except(['index', 'show']);
    });

    // Students (Read)
    Route::middleware(['role:Super Admin|Tata Tertib|BK|Teacher|Principal'])->group(function () {
        Route::get('students', [\App\Http\Controllers\StudentController::class, 'index'])->name('students.index');
        Route::get('students/{student}', [\App\Http\Controllers\StudentController::class, 'show'])->name('students.show');
        Route::get('students/{student}/print-card', [\App\Http\Controllers\StudentController::class, 'printCard'])->name('students.print-card');
    });

    // Discipline Events (Read)
    Route::middleware(['role:Super Admin|Tata Tertib|BK|Teacher|Principal'])->group(function () {
        Route::get('discipline-events', [\App\Http\Controllers\DisciplineEventController::class, 'index'])->name('discipline-events.index');
    });

    // Discipline Events (Create)
    Route::middleware(['role:Super Admin|Tata Tertib|Teacher'])->group(function () {
        Route::get('discipline-events/create', [\App\Http\Controllers\DisciplineEventController::class, 'create'])->name('discipline-events.create');
        Route::post('discipline-events', [\App\Http\Controllers\DisciplineEventController::class, 'store'])->name('discipline-events.store');
    });

    // Discipline Events (Destroy)
    Route::middleware(['role:Super Admin|Tata Tertib'])->group(function () {
        Route::delete('discipline-events/{discipline_event}', [\App\Http\Controllers\DisciplineEventController::class, 'destroy'])->name('discipline-events.destroy');
    });

    // Counseling Records
    Route::middleware(['role:Super Admin|BK|Principal'])->group(function () {
        Route::resource('counseling-records', \App\Http\Controllers\CounselingRecordController::class);
    });

    // Letters
    Route::middleware(['role:Super Admin|BK|Principal|Tata Tertib'])->group(function () {
        Route::resource('letters', \App\Http\Controllers\LetterController::class)->only(['index', 'create', 'store', 'show']);
    });

    // Excel Import
    Route::middleware(['role:Super Admin|Tata Tertib|BK'])->group(function () {
        Route::get('imports', [\App\Http\Controllers\ImportController::class, 'index'])->name('imports.index');
        Route::post('imports/upload', [\App\Http\Controllers\ImportController::class, 'upload'])->name('imports.upload');
        Route::post('imports/commit', [\App\Http\Controllers\ImportController::class, 'commit'])->name('imports.commit');
    });

    // Reports & Analytics
    Route::middleware(['role:Super Admin|BK|Principal|Tata Tertib|Teacher'])->group(function () {
        Route::get('reports/monthly', [\App\Http\Controllers\ReportController::class, 'monthly'])->name('reports.monthly');
        Route::get('reports/classes', [\App\Http\Controllers\ReportController::class, 'classes'])->name('reports.classes');
        Route::get('reports/homerooms', [\App\Http\Controllers\ReportController::class, 'homerooms'])->name('reports.homerooms');
        Route::get('reports/analytics', [\App\Http\Controllers\ReportController::class, 'analytics'])->name('reports.analytics');
    });
});