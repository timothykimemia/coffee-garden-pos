<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Restaurant\Booking;

class GuestCheckin extends Model
{
    protected $table = 'guest_checkins';

    protected $fillable = [
        'business_id',
        'surname',
        'name', 
        'email',
        'phone',
        'gender',
        'nationality',
        'id_type',
        'id_number',
        'passport_no',
        'stay_purpose',
        'payment_method',
        'company',
        'remarks',
        'staff_acknowledged',
        'guest_acknowledged'
    ];

    protected $casts = [
        'staff_acknowledged' => 'boolean',
        'guest_acknowledged' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relationship with Bookings
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'guest_checkin_id');
    }

    /**
     * Get the current/active booking for this guest
     */
    public function currentBooking()
    {
        return $this->hasOne(Booking::class, 'guest_checkin_id')
                    ->where('booking_status', '!=', 'cancelled')
                    ->where('booking_start', '<=', now())
                    ->where('booking_end', '>=', now());
    }

    /**
     * Get the latest booking for this guest
     */
    public function latestBooking()
    {
        return $this->hasOne(Booking::class, 'guest_checkin_id')
                    ->latest('booking_start');
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute()
    {
        return $this->surname . ' ' . $this->name;
    }

    /**
     * Check if guest has any bookings
     */
    public function getHasBookingsAttribute()
    {
        return $this->bookings()->count() > 0;
    }

    /**
     * Get booking status for this guest
     */
    public function getBookingStatusAttribute()
    {
        $currentBooking = $this->currentBooking;
        if ($currentBooking) {
            return $currentBooking->booking_status;
        }

        $latestBooking = $this->latestBooking;
        if ($latestBooking) {
            return $latestBooking->booking_status;
        }

        return 'no_booking';
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
            case 'no_booking':
                return 'label-default';
            default:
                return 'label-info';
        }
    }

}