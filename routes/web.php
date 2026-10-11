<?php

use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ParentPortal;
use App\Http\Controllers\School;
use App\Http\Controllers\Teacher;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->hasRole('super-admin')) return redirect()->route('admin.dashboard');
    if ($user->hasRole('school-admin')) return redirect()->route('school.dashboard');
    if ($user->hasRole('teacher')) return redirect()->route('teacher.dashboard');
    if ($user->hasRole('parent')) return redirect()->route('parent.dashboard');

    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
        
// Attachments (access is checked inside the controller)
    Route::get('attachments/{attachment}', [\App\Http\Controllers\AttachmentController::class, 'show'])->name('attachments.show');

    


    // Super Admin (all schools)
    Route::middleware(['role:super-admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::resource('schools', Admin\SchoolController::class);
        Route::resource('school-admins', Admin\SchoolAdminController::class)
            ->parameters(['school-admins' => 'schoolAdmin'])
            ->except('show');
        Route::resource('classes', Admin\ClassRoomController::class);
        Route::resource('teachers', Admin\TeacherController::class)->except('show');
        Route::get('students', [Admin\StudentController::class, 'index'])->name('students.index');
        Route::get('parents', [Admin\ParentController::class, 'index'])->name('parents.index');
    });

    // School Admin (own school only)
    Route::middleware(['role:school-admin'])->prefix('school')->name('school.')->group(function () {
        Route::get('dashboard', [School\DashboardController::class, 'index'])->name('dashboard');

        Route::get('my-school', [School\MySchoolController::class, 'edit'])->name('my-school.edit');
        Route::put('my-school', [School\MySchoolController::class, 'update'])->name('my-school.update');

        Route::resource('classes', School\ClassRoomController::class)->except('show');
        Route::get('classes/{class}/subjects', [School\ClassSubjectController::class, 'edit'])->name('classes.subjects.edit');
        Route::put('classes/{class}/subjects', [School\ClassSubjectController::class, 'update'])->name('classes.subjects.update');

        Route::resource('teachers', School\TeacherController::class)->except('show');
        Route::resource('subjects', School\SubjectController::class)->except('show');

        Route::resource('students', School\StudentController::class)->except('show');
        Route::put('students/{student}/subjects', [School\StudentSubjectController::class, 'update'])->name('students.subjects.update');
        Route::post('students/{student}/parents', [School\StudentParentController::class, 'store'])->name('students.parents.store');
        Route::delete('students/{student}/parents/{parent}', [School\StudentParentController::class, 'destroy'])->name('students.parents.destroy');

        Route::resource('parents', School\ParentController::class)->except('show');

        Route::resource('announcements', School\AnnouncementController::class)->except('show');
    });

    // Teacher
    Route::middleware(['role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('dashboard', [Teacher\DashboardController::class, 'index'])->name('dashboard');

        Route::get('classes', [Teacher\ClassController::class, 'index'])->name('classes.index');
        Route::get('classes/{class}', [Teacher\ClassController::class, 'show'])->name('classes.show');

        Route::get('classes/{class}/learners/add', [Teacher\ClassLearnerController::class, 'create'])->name('classes.learners.create');
        Route::post('classes/{class}/learners', [Teacher\ClassLearnerController::class, 'store'])->name('classes.learners.store');
        Route::delete('classes/{class}/learners/{student}', [Teacher\ClassLearnerController::class, 'destroy'])->name('classes.learners.destroy');

        Route::resource('announcements', Teacher\AnnouncementController::class)->except('show');
    });

    // Parent
    Route::middleware(['role:parent'])->prefix('parent')->name('parent.')->group(function () {
        Route::get('dashboard', [ParentPortal\DashboardController::class, 'index'])->name('dashboard');
        Route::get('announcements', [ParentPortal\AnnouncementController::class, 'index'])->name('announcements.index');
    });

    // Messages: parents, teachers and school admins
    Route::middleware(['role:teacher|parent|school-admin'])->prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::delete('message/{message}', [MessageController::class, 'destroy'])->name('destroy');
        Route::get('{contact}/thread', [MessageController::class, 'thread'])->name('thread');
        Route::post('{contact}/close', [MessageController::class, 'close'])->name('close');
        Route::post('{contact}/reopen', [MessageController::class, 'reopen'])->name('reopen');
        Route::get('{contact}', [MessageController::class, 'show'])->name('show');
        Route::post('{contact}', [MessageController::class, 'store'])->name('store');
    });

    // Directory: learners with their parents' contact details, and staff
    Route::middleware(['role:teacher|school-admin'])->prefix('directory')->name('directory.')->group(function () {
        Route::get('learners', [DirectoryController::class, 'learners'])->name('learners');
        Route::get('staff', [DirectoryController::class, 'staff'])->name('staff');
    });
    
});

require __DIR__.'/auth.php';