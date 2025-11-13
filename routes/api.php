<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingBackupController;
use App\Http\Controllers\Api\TourController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ContactSubmissionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Public routes
Route::get('tours', [TourController::class, 'index'])->name('tours.index');
Route::get('tours/{id}', [TourController::class, 'show'])->name('tours.show');
Route::get('tours/featured/list', [TourController::class, 'index'])->name('tours.featured');
Route::post('contact', [ContactController::class, 'store'])->name('contact.store');
Route::post('contact-submissions', [ContactSubmissionController::class, 'store'])->name('contact-submissions.store');
Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');

// Public review routes
Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::post('reviews', [ReviewController::class, 'store'])->name('reviews.store');
Route::get('reviews/{id}', [ReviewController::class, 'show'])->name('reviews.show');
Route::get('tours/{tourId}/rating-summary', [ReviewController::class, 'getTourRatingSummary'])->name('tours.rating-summary');

// Authentication routes
Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

// Protected admin routes
Route::middleware('auth.api')->group(function () {
    // Auth routes
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
    
    // Tours management (admin only)
    Route::post('tours', [TourController::class, 'store'])->name('tours.store');
    Route::put('tours/{id}', [TourController::class, 'update'])->name('tours.update');
    Route::delete('tours/{id}', [TourController::class, 'destroy'])->name('tours.destroy');
    
    // Bookings management (admin only)
    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('bookings/{id}', [BookingController::class, 'show'])->name('bookings.show');
    Route::put('bookings/{id}', [BookingController::class, 'update'])->name('bookings.update');
    Route::delete('bookings/{id}', [BookingController::class, 'destroy'])->name('bookings.destroy');
    
    // Users management (admin only)
    Route::apiResource('users', UserController::class);
    
    // File upload (admin only)
    Route::post('upload/image', [FileUploadController::class, 'uploadImage'])->name('upload.image');
    Route::delete('upload/image', [FileUploadController::class, 'deleteImage'])->name('upload.delete');
    
    // Reviews management (admin only)
    Route::put('reviews/{id}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('reviews/{id}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    
    // Contact Submissions management (admin only)
    Route::get('contact-submissions', [ContactSubmissionController::class, 'index'])->name('contact-submissions.index');
    Route::get('contact-submissions/{contactSubmission}', [ContactSubmissionController::class, 'show'])->name('contact-submissions.show');
    Route::post('contact-submissions/{contactSubmission}/mark-as-read', [ContactSubmissionController::class, 'markAsRead'])->name('contact-submissions.mark-as-read');
});

// Backup routes
Route::prefix('backups')->group(function () {
    // Create backups
    Route::post('/booking', [BookingBackupController::class, 'createBackup']);
    Route::post('/tour', [BookingBackupController::class, 'createTourBackup']);
    Route::post('/auto-backup', [BookingBackupController::class, 'autoBackupBeforeUpdate']);
    
    // List and view backups
    Route::get('/', [BookingBackupController::class, 'index']);
    Route::get('/{id}', [BookingBackupController::class, 'show']);
    
    // Restore backup
    Route::post('/{id}/restore', [BookingBackupController::class, 'restore']);
    
    // Delete backup
    Route::delete('/{id}', [BookingBackupController::class, 'destroy']);
});

