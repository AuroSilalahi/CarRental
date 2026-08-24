<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CarController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RentalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "api" middleware group.
|
*/

// API v1 routes
Route::prefix('v1')->name('api.v1.')->group(function () {

    // -------------------------------------------------------------------------
    // Authentication — Task 4.1: Registrasi & Verifikasi Email
    // -------------------------------------------------------------------------

    // Public registration endpoint
    Route::post('register', [AuthController::class, 'register'])->name('register');

    // Email verification — signed URL, valid 24 hours (Requirements 5.3, 5.4)
    Route::get(
        'email/verify/{id}/{hash}',
        [AuthController::class, 'verifyEmail']
    )->name('email.verify');

    // Resend verification email (Requirements 5.4)
    Route::post('email/resend', [AuthController::class, 'resendVerification'])->name('email.resend');

    // -------------------------------------------------------------------------
    // Authentication — Task 4.3: Login & Logout
    // -------------------------------------------------------------------------

    // Public login endpoint (Requirements 5.6, 5.7, 5.8)
    Route::post('login', [AuthController::class, 'login'])->name('login');

    // Protected routes — require valid Sanctum token
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // -------------------------------------------------------------------------
        // Profile — Task 4.7: Profil customer (GET & PUT)
        // -------------------------------------------------------------------------

        // Get authenticated customer's profile (Requirements 9.1, 9.2)
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');

        // Update authenticated customer's profile (Requirements 9.1, 9.2)
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        // -------------------------------------------------------------------------
        // Profile — Task 4.5: Upload dokumen identitas (KTP)
        // -------------------------------------------------------------------------

        // Upload identity document (Requirements 5.9, 5.10)
        Route::post('profile/identity', [ProfileController::class, 'uploadIdentity'])->name('profile.identity.upload');
    });

    // -------------------------------------------------------------------------
    // Cars — Task 5.1: List and detail (public, no auth required)
    // -------------------------------------------------------------------------

    // Public car listing (Requirements: 6.2, 6.3, 6.5, 6.6)
    Route::get('cars', [CarController::class, 'index'])->name('cars.index');

    // Public car detail (Requirements: 6.2, 6.3)
    Route::get('cars/{id}', [CarController::class, 'show'])->name('cars.show');

    // -------------------------------------------------------------------------
    // Protected routes — require valid Sanctum token
    // -------------------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {

        // -------------------------------------------------------------------------
        // Rentals — Task 5.3: Create booking; Task 5.6: List and detail
        // -------------------------------------------------------------------------

        // Create a new rental booking (Requirements: 7.1, 7.2, 7.4, 7.5, 7.6, 7.7)
        Route::post('rentals', [RentalController::class, 'store'])->name('rentals.store');

        // List authenticated customer's rentals (Requirements: 9.1, 9.2, 9.4)
        Route::get('rentals', [RentalController::class, 'index'])->name('rentals.index');

        // Rental detail — owner only (Requirements: 9.1, 9.2, 9.4)
        Route::get('rentals/{id}', [RentalController::class, 'show'])->name('rentals.show');

        // -------------------------------------------------------------------------
        // Payments — Task 5.5: Detail and process payment
        // -------------------------------------------------------------------------

        // Payment detail for a rental — owner only (Requirements: 8.1)
        Route::get('payments/{rental}', [PaymentController::class, 'show'])->name('payments.show');

        // Process payment for a rental — owner only (Requirements: 8.2, 8.3, 8.4)
        Route::post('payments/{rental}/pay', [PaymentController::class, 'pay'])->name('payments.pay');
    });
});
