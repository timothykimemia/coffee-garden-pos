<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OnlineBooking extends Model
{
    protected $fillable = [
        'room_slug',
        'check_in',
        'check_out',
        'adults',
        'rooms',
        'name',
        'phone_number',
        'gender',
        'email',
        'number_of_days',
        'total_price',
        'price_per_room',
        'is_double_occupancy',
    ];
}
