<?php

use Illuminate\Support\Str;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParentPortal;
use App\Http\Controllers\School;
use App\Http\Controllers\Teacher;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
// Phone app file links (the signature in the URL proves it came from the API)
Route::get('files/{attachment}', [\App\Http\Controllers\AttachmentController::class, 'signed'])
    ->middleware('signed')
    ->name('files.signed');

// Installable app (PWA)
Route::get('manifest.webmanifest', function () {
    $name = config('app.name', 'School');

    return response()->json([
        'id'               => '/',
        'name'             => $name,
        'short_name'       => Str::limit($name, 12, ''),
        'description'      => "Announcements, homework and messages from your child's school.",
        'start_url'        => '/dashboard?source=pwa',
        'scope'            => '/',
        'display'          => 'standalone',
        'background_color' => '#f3f6fc',
        'theme_color'      => '#0a1a42',
        'lang'             => 'en-ZA',
        'categories'       => ['education'],
        'icons'            => [
            ['src' => '/icons/icon-192.png',    'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/icons/icon-512.png',    'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/icons/maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
        'shortcuts'        => [
            ['name' => 'Messages',      'url' => '/messages',      'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
            ['name' => 'Notifications', 'url' => '/notifications', 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_SLASHES);
})->name('pwa.manifest');

Route::view('offline', 'offline')->name('pwa.offline');

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
    Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
        // Log out from the phone app (a plain form post)
    Route::post('me/logout', function (\App\Livewire\Actions\Logout $logout) {
        $logout();

        return redirect('/');
    })->name('me.logout');
    // Notifications (every logged-in user)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('settings', [NotificationController::class, 'settings'])->name('settings');
        Route::put('settings', [NotificationController::class, 'updateSettings'])->name('settings.update');
        Route::post('read-all', [NotificationController::class, 'readAll'])->name('read-all');
        Route::get('{id}/open', [NotificationController::class, 'open'])->name('open');
    });



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
        Route::get('subjects/{subject}/teachers', [School\SubjectTeacherController::class, 'edit'])->name('subjects.teachers.edit');
        Route::put('subjects/{subject}/teachers', [School\SubjectTeacherController::class, 'update'])->name('subjects.teachers.update');

        Route::resource('students', School\StudentController::class)->except('show');
        Route::put('students/{student}/subjects', [School\StudentSubjectController::class, 'update'])->name('students.subjects.update');
        Route::post('students/{student}/parents', [School\StudentParentController::class, 'store'])->name('students.parents.store');
        Route::delete('students/{student}/parents/{parent}', [School\StudentParentController::class, 'destroy'])->name('students.parents.destroy');
        
        Route::post('parents/{parent}/invite', [School\ParentController::class, 'invite'])->name('parents.invite');
        Route::resource('parents', School\ParentController::class)->except('show');

        Route::resource('announcements', School\AnnouncementController::class)->except('show');
        Route::post('announcements/{announcement}/pin', [School\AnnouncementController::class, 'pin'])->name('announcements.pin');
        
        // Import learners and parents from Excel or CSV
        Route::get('import', [School\ImportController::class, 'index'])->name('import.index');
        Route::get('import/template', [School\ImportController::class, 'template'])->name('import.template');
        Route::post('import', [School\ImportController::class, 'store'])->name('import.store');
        Route::get('import/{import}', [School\ImportController::class, 'show'])->name('import.show');
        Route::post('import/{import}/confirm', [School\ImportController::class, 'confirm'])->name('import.confirm');
        Route::get('import/{import}/issues', [School\ImportController::class, 'issues'])->name('import.issues');
        Route::post('import/{import}/invite', [School\ImportController::class, 'invite'])->name('import.invite');
        Route::delete('import/{import}', [School\ImportController::class, 'destroy'])->name('import.destroy');
    });

    // Teacher (class teachers and subject teachers)
    Route::middleware(['role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('dashboard', [Teacher\DashboardController::class, 'index'])->name('dashboard');

        // Class teacher
        Route::get('classes', [Teacher\ClassController::class, 'index'])->name('classes.index');
        Route::get('classes/{class}', [Teacher\ClassController::class, 'show'])->name('classes.show');
        Route::get('classes/{class}/learners/add', [Teacher\ClassLearnerController::class, 'create'])->name('classes.learners.create');
        Route::post('classes/{class}/learners', [Teacher\ClassLearnerController::class, 'store'])->name('classes.learners.store');
        Route::delete('classes/{class}/learners/{student}', [Teacher\ClassLearnerController::class, 'destroy'])->name('classes.learners.destroy');

        // Subject teacher
        Route::get('subjects', [Teacher\SubjectController::class, 'index'])->name('subjects.index');
        Route::get('subjects/{subject}', [Teacher\SubjectController::class, 'show'])->name('subjects.show');

        // Notices addressed to me (school, teachers only, SMT, my classes)
        Route::get('notices', [Teacher\NoticeController::class, 'index'])->name('notices.index');

        Route::resource('announcements', Teacher\AnnouncementController::class)->except('show');
    });

        // Parent
        Route::middleware(['role:parent'])->prefix('parent')->name('parent.')->group(function () {
        Route::get('dashboard', [ParentPortal\DashboardController::class, 'index'])->name('dashboard');
        Route::get('announcements', [ParentPortal\AnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('announcements/{announcement}', [ParentPortal\AnnouncementController::class, 'show'])->name('announcements.show');
        Route::post('announcements/{announcement}/done', [ParentPortal\AnnouncementController::class, 'done_'])->name('announcements.done');
        Route::delete('announcements/{announcement}/done', [ParentPortal\AnnouncementController::class, 'undone'])->name('announcements.undone');
        Route::view('me', 'parent.me')->name('me');
        Route::post('announcements/clear', [ParentPortal\AnnouncementController::class, 'dismissAll'])->name('announcements.clear');
        Route::delete('announcements/{announcement}', [ParentPortal\AnnouncementController::class, 'dismiss'])->name('announcements.dismiss');
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