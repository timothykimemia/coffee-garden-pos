<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Restaurant\Booking;
use App\Models\GuestCheckin;

class GuestRegistration extends Model
{
    protected $fillable = [
        'business_id',
        'checkin_id',
        'contact_id',
        'booking_id',
        'surname',
        'name',
        'email',
        'phone',
        'nationality',
        'stay_purpose',
        'payment_method',
        'status',
        'company',
        'remarks'
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function checkin()
    {
        return $this->belongsTo(GuestCheckin::class, 'checkin_id');
    }
}