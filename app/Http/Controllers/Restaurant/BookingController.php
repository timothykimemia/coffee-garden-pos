<?php

namespace App\Http\Controllers\Restaurant;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;

use Yajra\DataTables\Facades\DataTables;

use App\User;
use App\Contact;
use App\Utils\Util;
use App\GuestCheckin;
use App\CustomerGroup;
use App\OnlineBooking;
use App\BusinessLocation;
use App\Utils\ContactUtil;
use App\GuestRegistration;
use App\Restaurant\Booking;
use App\Utils\RestaurantUtil;

class BookingController extends Controller
{
    protected $commonUtil;
    protected $restUtil;

    public function __construct(Util $commonUtil, RestaurantUtil $restUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->restUtil = $restUtil;
    }

    public function index()
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $user_id = request()->has('user_id') ? request()->user_id : null;
        if (!auth()->user()->hasPermissionTo('crud_all_bookings') && !$this->restUtil->is_admin(auth()->user(), $business_id)) {
            $user_id = request()->session()->get('user.id');
        }
        if (request()->ajax()) {
            $filters = [
                'start_date' => request()->start,
                'end_date' => request()->end,
                'user_id' => $user_id,
                'location_id' => !empty(request()->location_id) ? request()->location_id : null,
                'business_id' => $business_id
            ];

            $events = $this->restUtil->getBookingsForCalendar($filters);

            return $events;
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id, false);
        $correspondents = User::forDropdown($business_id, false);
        $types = Contact::getContactTypes();
        $customer_groups = CustomerGroup::forDropdown($business_id);
        $rooms = [];

        $roomDefinitions = [
            101 => ['type' => 'Double Standard', 'price' => 5000],
            102 => ['type' => 'Double Standard', 'price' => 5000],
            103 => ['type' => 'Double Standard', 'price' => 5000],
            104 => ['type' => 'Double Standard', 'price' => 5000],

            105 => ['type' => 'Single', 'price' => 4000],
            106 => ['type' => 'Single', 'price' => 4000],
            107 => ['type' => 'Single', 'price' => 4000],
            108 => ['type' => 'Single', 'price' => 4000],

            201 => ['type' => 'Double Standard', 'price' => 5000],
            202 => ['type' => 'Double Standard', 'price' => 5000],
            203 => ['type' => 'Double Standard', 'price' => 5000],
            204 => ['type' => 'Double Standard', 'price' => 5000],

            205 => ['type' => 'Single', 'price' => 4000],
            206 => ['type' => 'Single', 'price' => 4000],
            207 => ['type' => 'Single', 'price' => 4000],
            208 => ['type' => 'Single', 'price' => 4000],

            209 => ['type' => 'Deluxe', 'price' => 7000],
            210 => ['type' => 'Executive', 'price' => 12000],
            211 => ['type' => 'Executive', 'price' => 12000],
            212 => ['type' => 'Deluxe', 'price' => 7000],
        ];

        foreach ($roomDefinitions as $roomNumber => $details) {
            $label = 'Room ' . $roomNumber . ' - ' . $details['type'] . ' - KES ' . number_format($details['price']);
            $rooms[$roomNumber] = $label;
        }

        return view('restaurant.booking.index', compact('business_locations', 'customers', 'correspondents', 'types', 'customer_groups', 'rooms'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if ($request->ajax()) {
                $business_id = request()->session()->get('user.business_id');
                $user_id = request()->session()->get('user.id');

                $input = $request->input();
                $booking_start = $this->commonUtil->uf_date($input['booking_start'], true);
                $booking_end = $this->commonUtil->uf_date($input['booking_end'], true);
                $date_range = [$booking_start, $booking_end];

                // $query = Booking::where('business_id', $business_id)
                //                 ->where('location_id', $input['location_id'])
                //                 ->where('contact_id', $input['contact_id'])
                //                 ->where(function ($q) use ($date_range) {
                //                     $q->whereBetween('booking_start', $date_range)
                //                       ->orWhereBetween('booking_end', $date_range);
                //                 });

                $query = Booking::where('business_id', $business_id)
                    ->where('room_number', $input['room_number'])
                    ->whereDate('booking_start', '<=', \Carbon\Carbon::parse($booking_end)->toDateString())
                    ->whereDate('booking_end', '>=', \Carbon\Carbon::parse($booking_start)->toDateString());

                if (isset($input['room_number'])) {
                    $query->where('room_number', $input['room_number']);
                }

                $existing_booking = $query->first();
                if (empty($existing_booking)) {
                    $input['business_id'] = $business_id;
                    $input['created_by'] = $user_id;
                    $input['booking_start'] = $booking_start;
                    $input['booking_end'] = $booking_end;
                    $input['room_number'] = $request->input('room_number');
                    $booking = Booking::createBooking($input);

                    if ($request->input('registration_id')) {
                        $registration = GuestRegistration::find($request->input('registration_id'));
                        if ($registration) {
                            $registration->update(['booking_id' => $booking->id]);

                            // Send guest booking confirmation email
                            if ($registration->email && $request->input('send_notification')) {
                                Mail::send([], [], function ($message) use ($registration, $booking, $input) {
                                    $html = '
                                        <div style="font-family: Arial, sans-serif; color: #333; padding: 20px; background: #f7f7f7;">
                                            <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                                                <h2 style="color: #0055aa;">Booking Confirmation, ' . $registration->name . '</h2>
                                                <p>Your booking has been confirmed. Here are the details:</p>
                                                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                                                    <tr><td><strong>Name:</strong></td><td>' . $registration->surname . ' ' . $registration->name . '</td></tr>
                                                    <tr><td><strong>Check-in:</strong></td><td>' . $input['booking_start'] . '</td></tr>
                                                    <tr><td><strong>Check-out:</strong></td><td>' . $input['booking_end'] . '</td></tr>
                                                    <tr><td><strong>Room Number:</strong></td><td>' . $input['room_number'] . '</td></tr>
                                                    <tr><td><strong>Purpose:</strong></td><td>' . $registration->stay_purpose . '</td></tr>
                                                    <tr><td><strong>Payment Method:</strong></td><td>' . $registration->payment_method . '</td></tr>
                                                </table>
                                                <p style="margin-top: 20px;">If you have any questions, contact us at <a href="mailto:info@example.com">info@example.com</a>.</p>
                                                <p style="margin-top: 30px;">Warm regards,<br/>The Hotel Team</p>
                                            </div>
                                        </div>';
                                    $message->to($registration->email)
                                        ->subject('Booking Confirmation')
                                        ->setBody($html, 'text/html');
                                });
                            }
                        }
                    }

                    $output = ['success' => 1, 'msg' => trans("lang_v1.added_success")];

                    if (isset($input['send_notification']) && $input['send_notification'] == 1) {
                        $output['send_notification'] = 1;
                        $output['notification_url'] = action('NotificationController@getTemplate', ["transaction_id" => $booking->id, "template_for" => "new_booking"]);
                    }
                } else {
                    $time_range = $this->commonUtil->format_date($existing_booking->booking_start, true) . ' ~ ' .
                        $this->commonUtil->format_date($existing_booking->booking_end, true);
                    $output = ['success' => 0, 'msg' => trans("restaurant.booking_not_available", [
                        'customer_name' => $existing_booking->customer->name,
                        'booking_time_range' => $time_range
                    ])];
                }
            } else {
                $output = ['success' => 0, 'msg' => __("messages.something_went_wrong")];
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __("messages.something_went_wrong")];
        }
        return $output;
    }

    public function show($id)
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $booking = Booking::where('business_id', $business_id)
                ->where('id', $id)
                ->with(['table', 'customer', 'correspondent', 'waiter', 'location'])
                ->first();
            if (!empty($booking)) {
                $booking_start = $this->commonUtil->format_date($booking->booking_start, true);
                $booking_end = $this->commonUtil->format_date($booking->booking_end, true);

                $booking_statuses = [
                    'waiting' => __('lang_v1.waiting'),
                    'booked' => __('restaurant.booked'),
                    'completed' => __('restaurant.completed'),
                    'cancelled' => __('restaurant.cancelled'),
                ];
                return view('restaurant.booking.show', compact('booking', 'booking_start', 'booking_end', 'booking_statuses'));
            }
        }
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $business_id = $request->session()->get('user.business_id');
            $booking = Booking::where('business_id', $business_id)
                ->find($id);
            if (!empty($booking)) {
                $booking->booking_status = $request->booking_status;
                $booking->save();
            }

            $output = ['success' => 1, 'msg' => trans("lang_v1.updated_success")];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __("messages.something_went_wrong")];
        }
        return $output;
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $business_id = request()->session()->get('user.business_id');
            Booking::where('business_id', $business_id)
                ->where('id', $id)
                ->delete();
            $output = ['success' => 1, 'msg' => trans("lang_v1.deleted_success")];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __("messages.something_went_wrong")];
        }
        return $output;
    }

    /**
     * Get count of pending online bookings
     */
    public function getOnlineBookingsCount()
    {
        $count = OnlineBooking::whereNull('converted_booking_id')
            ->where('status', '!=', 'converted')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Enhanced getTodaysBookings to include online bookings
     */
    public function getTodaysBookings()
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');
            $today = \Carbon::now()->format('Y-m-d');

            // Get regular bookings
            $regularBookingsQuery = Booking::where('business_id', $business_id)
                ->where('booking_status', 'booked')
                ->whereDate('booking_start', $today)
                ->with(['table', 'customer', 'correspondent', 'waiter', 'location', 'guestCheckin']);

            if (!empty(request()->location_id)) {
                $regularBookingsQuery->where('location_id', request()->location_id);
            }

            if (!auth()->user()->hasPermissionTo('crud_all_bookings') && !$this->restUtil->is_admin(auth()->user(), $business_id)) {
                $regularBookingsQuery->where(function ($query) use ($user_id) {
                    $query->where('created_by', $user_id)
                        ->orWhere('correspondent_id', $user_id)
                        ->orWhere('waiter_id', $user_id);
                });
            }

            $regularBookings = $regularBookingsQuery->get();

            // Get today's online bookings that haven't been converted
            $onlineBookings = OnlineBooking::whereDate('check_in', $today)
                ->whereNull('converted_booking_id') // Only unconverted ones
                ->get();

            // Combine data for DataTables
            $allBookings = [];

            // Add regular bookings
            foreach ($regularBookings as $booking) {
                $guestName = 'Unknown Guest';
                $contactInfo = 'N/A';
                $guestInfo = 'N/A';

                if ($booking->guestCheckin) {
                    $guestName = $booking->guestCheckin->surname . ' ' . $booking->guestCheckin->name;
                    $contactInfo = $booking->guestCheckin->email;
                    if ($booking->guestCheckin->phone) {
                        $contactInfo = $contactInfo ? $contactInfo . ' | ' . $booking->guestCheckin->phone : $booking->guestCheckin->phone;
                    }
                    $guestInfo = 'Guest Check-in | ' . $booking->guestCheckin->gender . ' | ' . $booking->guestCheckin->nationality;
                } elseif ($booking->customer) {
                    $guestName = $booking->customer->name;
                    $contactInfo = $booking->customer->email ?? $booking->customer->mobile ?? 'N/A';
                    $guestInfo = 'Regular Customer';
                }

                $allBookings[] = [
                    'id' => $booking->id,
                    'customer' => $guestName,
                    'contact_info' => $contactInfo,
                    'guest_info' => $guestInfo,
                    'room_details' => $booking->room_number ? 'Room ' . $booking->room_number . ' - ' . $booking->room_type : 'N/A',
                    'booking_start' => $this->commonUtil->format_date($booking->booking_start, true),
                    'booking_end' => $this->commonUtil->format_date($booking->booking_end, true),
                    'table' => $booking->table ? $booking->table->name : ($booking->room_number ?? '--'),
                    'location' => $booking->location ? $booking->location->name : '--',
                    'waiter' => $booking->waiter ? $booking->waiter->user_full_name : '--',
                    'price' => 'KES ' . number_format($booking->price, 2),
                    'status' => '<span class="label ' . $booking->status_badge_class . '">' . ucfirst($booking->booking_status) . '</span>',
                    'action' => '<button class="btn btn-xs btn-info btn-modal" data-href="' . route('bookings.show', $booking->id) . '" data-container=".view_modal"><i class="fa fa-eye"></i></button>',
                    'type' => 'regular'
                ];
            }

            // Add online bookings
            foreach ($onlineBookings as $onlineBooking) {
                $allBookings[] = [
                    'id' => 'online_' . $onlineBooking->id,
                    'customer' => $onlineBooking->name,
                    'contact_info' => ($onlineBooking->email ?? '') . ($onlineBooking->email && $onlineBooking->phone_number ? ' | ' : '') . ($onlineBooking->phone_number ?? ''),
                    'guest_info' => 'Online Booking | ' . ucfirst($onlineBooking->gender ?? 'N/A'),
                    'room_details' => 'Room Type: ' . str_replace('-', ' ', ucwords($onlineBooking->room_slug)),
                    'booking_start' => \Carbon::parse($onlineBooking->check_in)->format('Y-m-d H:i:s'),
                    'booking_end' => \Carbon::parse($onlineBooking->check_out)->format('Y-m-d H:i:s'),
                    'table' => 'Online Booking',
                    'location' => 'Online',
                    'waiter' => '--',
                    'price' => 'KES ' . number_format($onlineBooking->total_price, 2),
                    'status' => '<span class="label label-info">Online Booking</span>',
                    'action' => '<button class="btn btn-xs btn-success convert-online-booking" data-id="' . $onlineBooking->id . '"><i class="fa fa-exchange"></i> Convert</button>',
                    'type' => 'online'
                ];
            }

            return Datatables::of(collect($allBookings))
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
    }

    /**
     * Get guest check-ins for DataTables
     */
    public function getGuestCheckins()
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $query = GuestCheckin::where('business_id', $business_id)
                ->with(['bookings'])
                ->orderBy('created_at', 'desc');

            if (!empty(request()->location_id)) {
                // If you need location filtering, you might need to join with bookings table
                $query->whereHas('bookings', function($q) {
                    $q->where('location_id', request()->location_id);
                });
            }

            return Datatables::of($query)
                ->editColumn('full_name', function ($row) {
                    return $row->surname . ' ' . $row->name;
                })
                ->editColumn('staff_acknowledged', function ($row) {
                    return $row->staff_acknowledged ?
                        '<span class="label label-success">Yes</span>' :
                        '<span class="label label-warning">No</span>';
                })
                ->editColumn('guest_acknowledged', function ($row) {
                    return $row->guest_acknowledged ?
                        '<span class="label label-success">Yes</span>' :
                        '<span class="label label-warning">No</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('action', function ($row) {
                    return '<button type="button" class="btn btn-xs btn-info view-guest-details" data-id="' . $row->id . '">
                            <i class="fa fa-eye"></i> View
                        </button>';
                })
                ->rawColumns(['staff_acknowledged', 'guest_acknowledged', 'action'])
                ->make(true);
        }
    }

    /**
     * Show guest check-in details
     */
    public function showGuestCheckin($id)
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $guestCheckin = GuestCheckin::where('business_id', $business_id)
                ->where('id', $id)
                ->with(['bookings'])
                ->first();

            if (!$guestCheckin) {
                return response()->json(['error' => 'Guest check-in not found'], 404);
            }

            return response()->json([
                'id' => $guestCheckin->id,
                'full_name' => $guestCheckin->full_name,
                'surname' => $guestCheckin->surname,
                'name' => $guestCheckin->name,
                'email' => $guestCheckin->email,
                'phone' => $guestCheckin->phone,
                'gender' => $guestCheckin->gender,
                'nationality' => $guestCheckin->nationality,
                'id_type' => $guestCheckin->id_type,
                'id_number' => $guestCheckin->id_number,
                'passport_no' => $guestCheckin->passport_no,
                'stay_purpose' => $guestCheckin->stay_purpose,
                'payment_method' => $guestCheckin->payment_method,
                'company' => $guestCheckin->company,
                'remarks' => $guestCheckin->remarks,
                'staff_acknowledged' => $guestCheckin->staff_acknowledged,
                'guest_acknowledged' => $guestCheckin->guest_acknowledged,
                'created_at' => $guestCheckin->created_at->format('Y-m-d H:i:s'),
                'booking_status' => $guestCheckin->booking_status ?? 'no_booking',
                'has_bookings' => $guestCheckin->bookings->count() > 0
            ]);
        }
    }

    /**
     * Convert OnlineBooking to regular Booking
     */
    public function convertOnlineBookingToBooking($onlineBookingId)
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $onlineBooking = OnlineBooking::findOrFail($onlineBookingId);
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            // Create or find customer contact
            $customer = $this->createCustomerFromOnlineBooking($onlineBooking, $business_id);

            // Convert room_slug to room_number
            $roomNumber = $this->extractRoomNumberFromSlug($onlineBooking->room_slug);

            // Check room availability
            $booking_start = \Carbon::parse($onlineBooking->check_in)->startOfDay();
            $booking_end = \Carbon::parse($onlineBooking->check_out)->endOfDay();

            $existingBooking = Booking::where('business_id', $business_id)
                ->where('room_number', $roomNumber)
                ->where(function($query) use ($booking_start, $booking_end) {
                    $query->whereBetween('booking_start', [$booking_start, $booking_end])
                        ->orWhereBetween('booking_end', [$booking_start, $booking_end])
                        ->orWhere(function($q) use ($booking_start, $booking_end) {
                            $q->where('booking_start', '<=', $booking_start)
                                ->where('booking_end', '>=', $booking_end);
                        });
                })
                ->where('booking_status', '!=', 'cancelled')
                ->first();

            if ($existingBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room is not available for the selected dates.'
                ]);
            }

            // Create regular booking
            $booking = Booking::create([
                'business_id' => $business_id,
                'location_id' => 1, // Default location
                'customer_id' => $customer->id,
                'booking_start' => $booking_start,
                'booking_end' => $booking_end,
                'created_by' => $user_id,
                'booking_status' => 'booked',
                'room_number' => $roomNumber,
                'price' => $onlineBooking->total_price,
                'adults' => $onlineBooking->adults,
                'rooms' => $onlineBooking->rooms,
                'is_double_occupancy' => $onlineBooking->is_double_occupancy,
                'number_of_days' => $onlineBooking->number_of_days,
                'price_per_room' => $onlineBooking->price_per_room,
                'booking_note' => 'Converted from online booking #' . $onlineBooking->id
            ]);

            // Mark online booking as converted
            $onlineBooking->update([
                'converted_booking_id' => $booking->id,
                'status' => 'converted'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Online booking converted successfully.',
                'booking_id' => $booking->id
            ]);

        } catch (\Exception $e) {
            \Log::error("Error converting online booking: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error converting booking: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create customer contact from online booking
     */
    private function createCustomerFromOnlineBooking($onlineBooking, $business_id)
    {
        // Check if customer already exists by email or phone
        $existingCustomer = Contact::where('business_id', $business_id)
            ->where(function($query) use ($onlineBooking) {
                if ($onlineBooking->email) {
                    $query->where('email', $onlineBooking->email);
                }
                if ($onlineBooking->phone_number) {
                    $query->orWhere('mobile', $onlineBooking->phone_number);
                }
            })
            ->first();

        if ($existingCustomer) {
            return $existingCustomer;
        }

        // Create a new customer using ContactUtil
        $contactUtil = new ContactUtil();

        $contactData = [
            'business_id' => $business_id,
            'type' => 'customer',
            'name' => $onlineBooking->name,
            'email' => $onlineBooking->email,
            'mobile' => $onlineBooking->phone_number,
            'contact_status' => 'active',
            'created_by' => auth()->id() ?? 1
        ];

        $result = $contactUtil->createNewContact($contactData);

        return $result['data'];
    }

    /**
     * Extract room number from room slug
     */
    private function extractRoomNumberFromSlug($roomSlug)
    {
        // Map room slugs to room numbers based on your room definitions
        $roomMappings = [
            'executive-rooms' => 210, // Default to first executive room
            'deluxe-rooms' => 209,    // Default to first deluxe room
            'double-standard' => 101, // Default to first double standard
            'single' => 105,          // Default to first single
        ];

        // If direct mapping exists
        if (isset($roomMappings[$roomSlug])) {
            return $roomMappings[$roomSlug];
        }

        // Try to extract number from slug if it contains room number
        if (preg_match('/(\d+)/', $roomSlug, $matches)) {
            return (int)$matches[1];
        }

        // Default fallback
        return 101;
    }

    /**
     * Auto-process pending online bookings
     */
    public function processOnlineBookings()
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Get unprocessed online bookings
            $onlineBookings = OnlineBooking::whereNull('converted_booking_id')
                ->where('status', '!=', 'converted')
                ->get();

            $convertedCount = 0;
            $errors = [];

            foreach ($onlineBookings as $onlineBooking) {
                try {
                    $response = $this->convertOnlineBookingToBooking($onlineBooking->id);
                    $responseData = $response->getData(true);

                    if ($responseData['success']) {
                        $convertedCount++;
                    } else {
                        $errors[] = "Booking #{$onlineBooking->id}: " . $responseData['message'];
                    }

                } catch (\Exception $e) {
                    $errors[] = "Booking #{$onlineBooking->id}: " . $e->getMessage();
                    \Log::error("Failed to convert online booking {$onlineBooking->id}: " . $e->getMessage());
                    continue;
                }
            }

            $message = "Converted {$convertedCount} online bookings successfully.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= "... and " . (count($errors) - 3) . " more.";
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'converted_count' => $convertedCount,
                'error_count' => count($errors)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing online bookings: ' . $e->getMessage()
            ]);
        }
    }
}