<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

Route::get('/cars', fn () => view('cars.index'))->name('cars.index');
Route::get('/cars/{id}', [App\Http\Controllers\CarDetailController::class, 'show'])->name('cars.show');

// Customer Web Authentication Routes (Requirements 5.1, 5.6)
Route::middleware('guest')->group(function () {
    Route::get('/register', fn () => view('auth.register'))->name('register');
    Route::get('/login', fn () => view('auth.login'))->name('login');
});

Route::middleware('auth')->group(function () {
    Route::match(['get', 'post'], '/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');

    Route::get('/my-rentals', fn () => view('my-rentals.index'))->name('my-rentals.index');
    Route::get('/bookings', fn () => view('my-rentals.index'))->name('bookings.index');
    Route::get('/profile', fn () => view('profile'))->name('profile');

    Route::get('/bookings/{rental}', [BookingController::class, 'show'])
        ->name('bookings.show');

    Route::get('/payments/{rental}', [PaymentController::class, 'show'])
        ->name('payments.show');

    Route::post('/payments/{rental}/pay', [PaymentController::class, 'pay'])
        ->name('payments.pay');
});


