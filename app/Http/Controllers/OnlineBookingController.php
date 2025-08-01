<?php

namespace App\Http\Controllers;

use App\OnlineBooking;
use Illuminate\Http\Request;

class OnlineBookingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_slug' => 'required|string',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1',
            'rooms' => 'required|integer|min:1',
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'email' => 'nullable|email',
            'number_of_days' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'price_per_room' => 'required|numeric|min:0',
            'is_double_occupancy' => 'required|boolean',
        ]);

        $booking = OnlineBooking::create($validated);

        return response()->json([
            'message' => 'Booking created successfully.',
            'booking' => $booking,
        ], 201);
    }
}
