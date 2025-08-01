<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

use App\Utils\Util;
use App\GuestCheckin;
use App\Restaurant\Booking;

class GuestCheckinController extends Controller
{
    protected $commonUtil;
    protected $roomDefinitions;

    public function __construct()
    {
        $this->commonUtil = new Util();

        // Define room mappings
        $this->roomDefinitions = [
            101 => ['type' => 'Double Standard'],
            102 => ['type' => 'Double Standard'],
            103 => ['type' => 'Double Standard'],
            104 => ['type' => 'Double Standard'],
            105 => ['type' => 'Single'],
            106 => ['type' => 'Single'],
            107 => ['type' => 'Single'],
            108 => ['type' => 'Single'],
            201 => ['type' => 'Double Standard'],
            202 => ['type' => 'Double Standard'],
            203 => ['type' => 'Double Standard'],
            204 => ['type' => 'Double Standard'],
            205 => ['type' => 'Single'],
            206 => ['type' => 'Single'],
            207 => ['type' => 'Single'],
            208 => ['type' => 'Single'],
            209 => ['type' => 'Deluxe'],
            210 => ['type' => 'Executive'],
            211 => ['type' => 'Executive'],
            212 => ['type' => 'Deluxe'],
        ];
    }

    public function store(Request $request)
    {
        try {
            if ($request->ajax()) {
                $business_id = $request->session()->get('user.business_id') ?? 1;
                $user_id = $request->session()->get('user.id') ?? 1;

                $validator = Validator::make($request->all(), [
                    'room_slug' => 'required|string',
                    'check_in' => 'required|date',
                    'check_out' => 'required|date|after:check_in',
                    'adults' => 'required|integer|min:1',
                    'rooms' => 'required|integer|min:1',
                    'name' => 'required|string|max:255',
                    'phone_number' => 'required|string|max:20',
                    'gender' => 'required|in:Male,Female',
                    'email' => 'required|email|max:255',
                    'number_of_days' => 'required|integer|min:1',
                    'total_price' => 'required|numeric|min:0',
                    'price_per_room' => 'required|numeric|min:0',
                    'is_double_occupancy' => 'required|boolean',
                    'checkin_id' => 'required|exists:guest_checkins,id'
                ]);

                if ($validator->fails()) {
                    \Log::error('Validation failed in store: ' . $validator->errors()->first());
                    return response()->json([
                        'success' => 0,
                        'msg' => 'Validation failed: ' . $validator->errors()->first()
                    ], 422);
                }

                $input = $request->all();
                $booking_start = $this->commonUtil->uf_date($input['check_in'], true);
                $booking_end = $this->commonUtil->uf_date($input['check_out'], true);

                $room_number = null;
                foreach ($this->roomDefinitions as $number => $details) {
                    if (strtolower(str_replace(' ', '-', $details['type'])) == strtolower($input['room_slug'])) {
                        $room_number = $number;
                        break;
                    }
                }

                if (!$room_number) {
                    \Log::error('Invalid room type selected: ' . $input['room_slug']);
                    return response()->json([
                        'success' => 0,
                        'msg' => 'Invalid room type selected.'
                    ], 422);
                }

                // Time gap between check and booking creation
                $query = Booking::where('business_id', $business_id)
                    ->where('room_number', $room_number)
                    ->whereDate('booking_start', '<=', \Carbon\Carbon::parse($booking_end)->toDateString())
                    ->whereDate('booking_end', '>=', \Carbon\Carbon::parse($booking_start)->toDateString())
                    ->where('booking_status', '!=', 'cancelled');

                if ($query->exists()) {
                    \Log::error('Room not available for booking: ' . $room_number . ' from ' . $booking_start . ' to ' . $booking_end);
                    return response()->json([
                        'success' => 0,
                        'msg' => trans("restaurant.booking_not_available", ['booking_time_range' => "$booking_start ~ $booking_end"])
                    ], 422);
                }

                $checkin = GuestCheckin::findOrFail($input['checkin_id']);
                $checkin->update([
                    'business_id' => $business_id,
                    'staff_acknowledged' => true
                ]);

                $bookingData = [
                    'business_id' => $business_id,
                    'location_id' => $input['location_id'] ?? 1,
                    'booking_start' => $booking_start,
                    'booking_end' => $booking_end,
                    'created_by' => $user_id,
                    'booking_status' => 'booked',
                    'room_number' => $room_number,
                    'price' => $input['total_price'],
                    'guest_checkin_id' => $checkin->id // Set the guest_checkin_id
                ];

                $booking = Booking::create($bookingData);

                if ($input['email'] && ($input['send_notification'] ?? 1)) {
                    try {
                        Mail::send([], [], function ($message) use ($checkin, $booking, $input, $room_number) {
                            $room_type = $this->roomDefinitions[$room_number]['type'];
                            $html = '
                            <div style="font-family: Arial, sans-serif; color: #333; padding: 20px; background: #f7f7f7;">
                                <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                                    <h2 style="color: #0055aa;">Booking Confirmation, ' . $checkin->name . '</h2>
                                    <p>Your booking has been confirmed. Here are the details:</p>
                                    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                                        <tr><td><strong>Name:</strong></td><td>' . $checkin->surname . ' ' . $checkin->name . '</td></tr>
                                        <tr><td><strong>Check-in:</strong></td><td>' . $input['check_in'] . '</td></tr>
                                        <tr><td><strong>Check-out:</strong></td><td>' . $input['check_out'] . '</td></tr>
                                        <tr><td><strong>Room Number:</strong></td><td>' . $room_number . '</td></tr>
                                        <tr><td><strong>Room Type:</strong></td><td>' . $room_type . '</td></tr>
                                        <tr><td><strong>Occupancy:</strong></td><td>' . ($input['is_double_occupancy'] ? 'Double' : 'Single') . '</td></tr>
                                        <tr><td><strong>Price per Room per Day:</strong></td><td>KES ' . number_format($input['price_per_room']) . '</td></tr>
                                        <tr><td><strong>Total Price:</strong></td><td>KES ' . number_format($input['total_price']) . '</td></tr>
                                        <tr><td><strong>Number of Days:</strong></td><td>' . $input['number_of_days'] . '</td></tr>
                                        <tr><td><strong>Adults:</strong></td><td>' . $input['adults'] . '</td></tr>
                                        <tr><td><strong>Rooms:</strong></td><td>' . $input['rooms'] . '</td></tr>
                                    </table>
                                    <p style="margin-top: 20px;">If you have any questions, contact us at <a href="mailto:info@example.com">info@example.com</a>.</p>
                                    <p style="margin-top: 30px;">Warm regards,<br/>The Hotel Team</p>
                                </div>
                            </div>';
                            $message->to($checkin->email)
                                ->subject('Booking Confirmation')
                                ->setBody($html, 'text/html');
                        });
                    } catch (\Exception $e) {
                        \Log::error('Failed to send booking confirmation email: ' . $e->getMessage());
                    }
                }

                return response()->json([
                    'success' => 1,
                    'msg' => trans("lang_v1.added_success"),
                    'booking_id' => $booking->id,
                    'checkin_id' => $checkin->id,
                ]);
            }

            return response()->json([
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ], 400);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
            return response()->json([
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ], 500);
        }
    }
}