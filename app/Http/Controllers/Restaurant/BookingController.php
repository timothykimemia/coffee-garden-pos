<?php

namespace App\Http\Controllers\Restaurant;

use App\BusinessLocation;
use App\Contact;
use App\CustomerGroup;
use App\Models\GuestRegistration;
use App\Restaurant\Booking;
use App\User;
use App\Utils\Util;
use App\Utils\RestaurantUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Mail;

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

    public function getTodaysBookings()
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');
            $today = \Carbon::now()->format('Y-m-d');
            $query = Booking::where('business_id', $business_id)
                           ->where('booking_status', 'booked')
                           ->whereDate('booking_start', $today)
                           ->with(['table', 'customer', 'correspondent', 'waiter', 'location']);

            if (!empty(request()->location_id)) {
                $query->where('location_id', request()->location_id);
            }

            if (!auth()->user()->hasPermissionTo('crud_all_bookings') && !$this->commonUtil->is_admin(auth()->user(), $business_id)) {
                $query->where(function ($query) use ($user_id) {
                    $query->where('created_by', $user_id)
                          ->orWhere('correspondent_id', $user_id)
                          ->orWhere('waiter_id', $user_id);
                });
            }

            return Datatables::of($query)
                ->editColumn('table', function ($row) {
                    return !empty($row->table->name) ? $row->table->name : ($row->room_number ?? '--');
                })
                ->editColumn('customer', function ($row) {
                    return !empty($row->customer->name) ? $row->customer->name : '--';
                })
                ->editColumn('correspondent', function ($row) {
                    return !empty($row->correspondent->user_full_name) ? $row->correspondent->user_full_name : '--';
                })
                ->editColumn('waiter', function ($row) {
                    return !empty($row->waiter->user_full_name) ? $row->waiter->user_full_name : '--';
                })
                ->editColumn('location', function ($row) {
                    return !empty($row->location->name) ? $row->location->name : '--';
                })
                ->editColumn('booking_start', function ($row) {
                    return $this->commonUtil->format_date($row->booking_start, true);
                })
                ->editColumn('booking_end', function ($row) {
                    return $this->commonUtil->format_date($row->booking_end, true);
                })
                ->removeColumn('id')
                ->make(true);
        }
    }
}