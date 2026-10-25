<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post('login', [V1\AuthController::class, 'login'])->middleware('throttle:10,1');

    // Everything else needs a valid token, and only parents may use it
    Route::middleware(['auth:sanctum', 'role:parent'])->group(function () {
        Route::post('logout', [V1\AuthController::class, 'logout']);
        Route::get('home', V1\HomeController::class);

        Route::get('announcements', [V1\AnnouncementController::class, 'index']);
        Route::get('announcements/{id}', [V1\AnnouncementController::class, 'show'])->whereNumber('id');
        Route::post('announcements/{id}/done', [V1\AnnouncementController::class, 'done'])->whereNumber('id');
        Route::delete('announcements/{id}/done', [V1\AnnouncementController::class, 'undone'])->whereNumber('id');
        Route::get('pending', [V1\AnnouncementController::class, 'pending']);

        Route::get('notifications', [V1\NotificationController::class, 'index']);
        Route::post('notifications/read-all', [V1\NotificationController::class, 'readAll']);
        Route::post('notifications/{id}/read', [V1\NotificationController::class, 'read']);

        Route::get('contacts', [V1\MessageController::class, 'contacts']);
        Route::get('conversations', [V1\MessageController::class, 'index']);
        Route::get('conversations/{contact}', [V1\MessageController::class, 'show']);
        Route::post('conversations/{contact}', [V1\MessageController::class, 'store'])->middleware('throttle:30,1');

        Route::get('attachments/{attachment}/link', [V1\AttachmentController::class, 'link']);
    });
});