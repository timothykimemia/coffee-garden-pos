<?php
namespace App\Http\Controllers\Restaurant;

use App\Contact;
use App\GuestRegistration; // Updated
use App\Restaurant\Booking;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller; // Add this import

class GuestRegistrationController extends Controller
{
    protected $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    public function getGuestRegistrations(Request $request)
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            return response()->json([
                'draw' => $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unauthorized access.'
            ], 403);
        }

        if ($request->ajax()) {
            try {
                $business_id = $request->session()->get('user.business_id');
                $query = GuestRegistration::where('business_id', $business_id)
                    ->with(['contact', 'booking']);

                if ($request->location_id) {
                    $query->whereHas('booking', function ($q) use ($request) {
                        $q->where('location_id', $request->location_id);
                    });
                }

                return DataTables::of($query)
                    ->editColumn('customer', function ($row) {
                        return $row->surname . ' ' . $row->name;
                    })
                    ->editColumn('email', function ($row) {
                        return $row->email ?? '--';
                    })
                    ->editColumn('phone', function ($row) {
                        return $row->phone ?? '--';
                    })
                    ->editColumn('nationality', function ($row) {
                        return $row->nationality ?? '--';
                    })
                    ->editColumn('stay_purpose', function ($row) {
                        return $row->stay_purpose ?? '--';
                    })
                    ->editColumn('payment_method', function ($row) {
                        return $row->payment_method ?? '--';
                    })
                    ->editColumn('status', function ($row) {
                        return ucfirst(str_replace('_', ' ', $row->status));
                    })
                    ->editColumn('action', function ($row) {
                        $html = '<button class="btn btn-xs btn-primary add-booking" data-contact-id="' . $row->contact_id . '" data-registration-id="' . $row->id . '">Add Booking</button>';
                        return $html;
                    })
                    ->removeColumn('id')
                    ->make(true);
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
                return response()->json([
                    'draw' => $request->input('draw', 1),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'An error occurred while fetching registrations: ' . $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'draw' => $request->input('draw', 1),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Invalid request.'
        ], 400);
    }

    /**
     * Store guest registration from React frontend
     * This is the main API endpoint that your React component calls
     */
    public function store(Request $request)
    {
        try {
            // Get business_id and user_id from session or headers
            $business_id = $request->session()->get('user.business_id') ?? $request->header('Business-Id') ?? 1; // Default to 1 for demo
            $user_id = $request->session()->get('user.id') ?? $request->header('User-Id', auth()->id()) ?? 1; // Default to 1 for demo

            // Validate the incoming data from React
            $request->validate([
                'surname' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255', // Made required since React sends it
                'phone' => 'required|string|max:20',
                'gender' => 'required|in:Male,Female',
                'nationality' => 'required|string|max:255',
                'id_type' => 'required|in:ID,Visa',
                'id_number' => 'required_if:id_type,ID|string|nullable|max:255',
                'passport_no' => 'required_if:id_type,Visa|string|nullable|max:255',
                'stay_purpose' => 'required|string|max:255',
                'payment_method' => 'required|string|max:255',
                'company' => 'nullable|string|max:255',
                'remarks' => 'nullable|string',
                'staff_acknowledged' => 'required|boolean',
                'guest_acknowledged' => 'required|boolean',
            ]);

            // Create or find contact based on email
            $contact = Contact::firstOrCreate(
                [
                    'email' => $request->email, 
                    'business_id' => $business_id
                ],
                [
                    'business_id' => $business_id,
                    'type' => 'customer',
                    'name' => $request->surname . ' ' . $request->name,
                    'mobile' => $request->phone,
                    'created_by' => $user_id,
                    'customer_group_id' => null,
                    'contact_id' => $this->generateContactId($business_id),
                ]
            );

            // Create guest registration record
            $registrationData = $request->only([
                'surname', 'name', 'email', 'phone', 'gender', 'nationality',
                'id_type', 'id_number', 'passport_no', 'stay_purpose',
                'payment_method', 'company', 'remarks', 'staff_acknowledged',
                'guest_acknowledged'
            ]);
            
            $registrationData['business_id'] = $business_id;
            $registrationData['contact_id'] = $contact->id;
            $registrationData['booking_id'] = null; // Will be set later when admin creates booking
            
            $registration = GuestRegistration::create($registrationData);

            return response()->json([
                'success' => 1,
                'msg' => 'Registration submitted successfully! Awaiting admin confirmation.',
                'registration_id' => $registration->id,
                'contact_id' => $contact->id,
                'data' => [
                    'registration' => $registration,
                    'contact' => $contact
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => 0,
                'msg' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => 0,
                'msg' => 'Error submitting registration. Please try again.'
            ], 500);
        }
    }

    /**
     * Get available rooms for booking
     * This can be called from React or the booking interface
     */
    public function getAvailableRooms(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id') ?? $request->header('Business-Id') ?? 1;
            
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

            // Get currently booked rooms (if date range is provided)
            $booked_rooms = [];
            if ($request->has('start_date') && $request->has('end_date')) {
                $start_date = $request->start_date;
                $end_date = $request->end_date;
                
                $booked_rooms = Booking::where('business_id', $business_id)
                    ->whereIn('booking_status', ['waiting', 'booked'])
                    ->whereDate('booking_start', '<=', $end_date)
                    ->whereDate('booking_end', '>=', $start_date)
                    ->pluck('room_number')
                    ->toArray();
            } else {
                // Get rooms booked for today if no date range specified
                $booked_rooms = Booking::where('business_id', $business_id)
                    ->whereIn('booking_status', ['waiting', 'booked'])
                    ->whereDate('booking_start', '<=', now()->toDateString())
                    ->whereDate('booking_end', '>=', now()->toDateString())
                    ->pluck('room_number')
                    ->toArray();
            }

            $rooms = [];
            foreach ($roomDefinitions as $roomNumber => $details) {
                $isAvailable = !in_array((string)$roomNumber, $booked_rooms);
                $rooms[] = [
                    'number' => (string)$roomNumber,
                    'type' => $details['type'],
                    'price' => $details['price'],
                    'available' => $isAvailable,
                    'label' => "Room {$roomNumber} - {$details['type']} - KES " . number_format($details['price']) . ($isAvailable ? '' : ' (Booked)'),
                ];
            }

            return response()->json([
                'success' => 1, 
                'rooms' => $rooms,
                'available_rooms' => array_filter($rooms, function($room) { return $room['available']; })
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => 0,
                'msg' => 'Error fetching available rooms.'
            ], 500);
        }
    }

    /**
     * Get all guest registrations (for admin dashboard)
     */
    public function index(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id') ?? $request->header('Business-Id') ?? 1;
            
            $registrations = GuestRegistration::where('business_id', $business_id)
                ->with(['contact', 'booking'])
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedRegistrations = $registrations->map(function($registration) {
                return [
                    'id' => $registration->id,
                    'full_name' => $registration->surname . ' ' . $registration->name,
                    'email' => $registration->email,
                    'phone' => $registration->phone,
                    'nationality' => $registration->nationality,
                    'stay_purpose' => $registration->stay_purpose,
                    'payment_method' => $registration->payment_method,
                    'company' => $registration->company,
                    'remarks' => $registration->remarks,
                    'contact_id' => $registration->contact_id,
                    'booking_id' => $registration->booking_id,
                    'status' => $registration->booking_id ? 'Booked' : 'Pending',
                    'created_at' => $registration->created_at->format('Y-m-d H:i:s'),
                    'booking' => $registration->booking ? [
                        'id' => $registration->booking->id,
                        'room_number' => $registration->booking->room_number,
                        'booking_start' => $registration->booking->booking_start,
                        'booking_end' => $registration->booking->booking_end,
                        'booking_status' => $registration->booking->booking_status,
                    ] : null
                ];
            });

            return response()->json([
                'success' => 1,
                'data' => $formattedRegistrations
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => 0,
                'msg' => 'Error fetching registrations.'
            ], 500);
        }
    }

    /**
     * Send email (called from React frontend)
     */
    public function sendEmail(Request $request)
    {
        try {
            $request->validate([
                'to' => 'required|email',
                'subject' => 'required|string',
                'html' => 'required|string',
            ]);

            Mail::send([], [], function ($message) use ($request) {
                $message->to($request->to)
                        ->subject($request->subject)
                        ->setBody($request->html, 'text/html');
            });

            return response()->json([
                'success' => true,
                'msg' => 'Email sent successfully'
            ]);
        } catch (\Exception $e) {
            \Log::emergency("Email Error - File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate unique contact ID
     */
    private function generateContactId($business_id)
    {
        $lastContact = Contact::where('business_id', $business_id)
            ->whereNotNull('contact_id')
            ->orderBy('contact_id', 'desc')
            ->first();
        
        if ($lastContact && is_numeric($lastContact->contact_id)) {
            return (string)((int)$lastContact->contact_id + 1);
        }
        
        return '1000'; // Starting contact ID
    }
}