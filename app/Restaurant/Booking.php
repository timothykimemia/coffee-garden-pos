<?php

namespace App\Restaurant;

use Illuminate\Database\Eloquent\Model;
use App\GuestCheckin;
use App\Contact;
use App\User;
use App\BusinessLocation;

class Booking extends Model
{
    protected $table = 'bookings';

    protected $fillable = [
        'business_id',
        'location_id',
        'table_id',
        'waiter_id',
        'customer_id',
        'guest_checkin_id', // New field to link with guest check-in
        'booking_start',
        'booking_end',
        'created_by',
        'booking_status',
        'booking_note',
        'room_number',
        'price',
        'adults',
        'rooms',
        'is_double_occupancy',
        'number_of_days',
        'price_per_room'
    ];

    protected $dates = [
        'booking_start',
        'booking_end',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'is_double_occupancy' => 'boolean',
        'price' => 'decimal:2',
        'price_per_room' => 'decimal:2'
    ];

    /**
     * Relationship with GuestCheckin
     */
    public function guestCheckin()
    {
        return $this->belongsTo(GuestCheckin::class, 'guest_checkin_id');
    }

    /**
     * Relationship with Customer (Contact)
     */
    public function customer()
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    /**
     * Relationship with Location
     */
    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    /**
     * Relationship with Waiter (User)
     */
    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    /**
     * Relationship with Table (if you have a tables model)
     */
    public function table()
    {
        // Adjust this based on your table model
        return $this->belongsTo(\App\RestaurantTable::class, 'table_id');
    }

    /**
     * Relationship with the user who created the booking
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the guest name from either guest check-in or customer
     */
    public function getGuestNameAttribute()
    {
        if ($this->guestCheckin) {
            return $this->guestCheckin->surname . ' ' . $this->guestCheckin->name;
        } elseif ($this->customer) {
            return $this->customer->name;
        }
        return 'Unknown Guest';
    }

    /**
     * Get the contact information
     */
    public function getContactInfoAttribute()
    {
        if ($this->guestCheckin) {
            $contact = [];
            if ($this->guestCheckin->email) {
                $contact[] = $this->guestCheckin->email;
            }
            if ($this->guestCheckin->phone) {
                $contact[] = $this->guestCheckin->phone;
            }
            return implode(' | ', $contact);
        } elseif ($this->customer) {
            return $this->customer->contact_id ?? $this->customer->mobile ?? 'N/A';
        }
        return 'N/A';
    }

    /**
     * Get room type based on room number
     */
    public function getRoomTypeAttribute()
    {
        $roomDefinitions = [
            101 => 'Double Standard',
            102 => 'Double Standard',
            103 => 'Double Standard',
            104 => 'Double Standard',
            105 => 'Single',
            106 => 'Single',
            107 => 'Single',
            108 => 'Single',
            201 => 'Double Standard',
            202 => 'Double Standard',
            203 => 'Double Standard',
            204 => 'Double Standard',
            205 => 'Single',
            206 => 'Single',
            207 => 'Single',
            208 => 'Single',
            209 => 'Deluxe',
            210 => 'Executive',
            211 => 'Executive',
            212 => 'Deluxe',
        ];

        return $roomDefinitions[$this->room_number] ?? 'Unknown';
    }

    /**
     * Scope for today's bookings
     */
    public function scopeToday($query)
    {
        return $query->whereDate('booking_start', today());
    }

    /**
     * Scope for active bookings (not cancelled)
     */
    public function scopeActive($query)
    {
        return $query->where('booking_status', '!=', 'cancelled');
    }

    /**
     * Scope for specific business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('booking_start', [$startDate, $endDate]);
    }

    /**
     * Check if booking is for today
     */
    public function getIsTodayAttribute()
    {
        return $this->booking_start->isToday();
    }

    /**
     * Check if booking is upcoming
     */
    public function getIsUpcomingAttribute()
    {
        return $this->booking_start->isFuture();
    }

    /**
     * Check if booking is past
     */
    public function getIsPastAttribute()
    {
        return $this->booking_end->isPast();
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        switch ($this->booking_status) {
            case 'waiting':
                return 'label-warning';
            case 'booked':
                return 'label-primary';
            case 'completed':
                return 'label-success';
            case 'cancelled':
                return 'label-danger';
            default:
                return 'label-default';
        }
    }

    /**
     * Get total nights/days
     */
    public function getTotalNightsAttribute()
    {
        return $this->booking_start->diffInDays($this->booking_end);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        return 'KES ' . number_format($this->price, 2);
    }

    /**
     * Get booking duration in human readable format
     */
    public function getDurationAttribute()
    {
        $start = $this->booking_start;
        $end = $this->booking_end;
        
        $days = $start->diffInDays($end);
        $hours = $start->copy()->addDays($days)->diffInHours($end);
        
        if ($days > 0) {
            return $days . ' day' . ($days > 1 ? 's' : '') . 
                   ($hours > 0 ? ' ' . $hours . ' hour' . ($hours > 1 ? 's' : '') : '');
        }
        
        return $hours . ' hour' . ($hours > 1 ? 's' : '');
    }
}