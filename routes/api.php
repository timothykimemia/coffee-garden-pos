<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\GuestCheckinController;
use App\Http\Controllers\OnlineBookingController;
use App\Http\Controllers\Restaurant\BookingController;
use App\Http\Controllers\Restaurant\GuestRegistrationController;


// ✅ Public access – Check-in route
Route::post('/guest-checkins', [GuestCheckinController::class, 'store']);
//Route::get('/guest-checkins', [GuestCheckinController::class, 'index'])->name('guest-checkins.index');

//online booking routes
Route::post('/online-bookings', [OnlineBookingController::class, 'store']);

// ✅ Guest Registration – public for initial booking
Route::post('/guest-registrations', [GuestRegistrationController::class, 'store']);
Route::get('/guest-registrations/available-rooms', [GuestRegistrationController::class, 'getAvailableRooms']);

Route::post('/bookings', [BookingController::class, 'store'])->middleware('auth:sanctum')->name('bookings.store');
//Route::get('/bookings/guest-checkins', [BookingController::class, 'getGuestCheckins'])->middleware('auth:sanctum')->name('bookings.guest_checkins');

// ✅ Email sending – can stay public with rate limiting
Route::post('/send-email', [GuestRegistrationController::class, 'sendEmail'])->middleware('throttle:60,1');

// 🔐 Protected – only for authenticated users like admin dashboard
Route::middleware('auth:api')->group(function () {
    Route::get('/guest-registrations', [GuestRegistrationController::class, 'index']);

    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/', [BookingController::class, 'store']);
        Route::get('/todays-bookings', [BookingController::class, 'getTodaysBookings']);
        Route::get('/guest-registrations', [BookingController::class, 'getGuestRegistrations']);
        Route::get('/{id}', [BookingController::class, 'show']);
        Route::put('/{id}', [BookingController::class, 'update']);
        Route::delete('/{id}', [BookingController::class, 'destroy']);
    });
});

// Health check (optional public endpoint)
Route::get('/health', function () {
    return response()->json(['status' => 'OK', 'timestamp' => now()->toISOString()]);
});
