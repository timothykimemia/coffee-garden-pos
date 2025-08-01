@extends('layouts.app')
@section('title', __('restaurant.booking_details'))

@section('content')
<section class="content-header">
    <h1>@lang('restaurant.booking_details')</h1>
</section>
<section class="content">
    <div class="row">
        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Booking ID: {{ $booking->id }}</h3>
                </div>
                <div class="box-body">
                    <p><strong>Customer:</strong> {{ $booking->customer ? $booking->customer->name : 'N/A' }}</p>
                    <p><strong>Booking Start:</strong> {{ \App\Utils\Util::format_date($booking->booking_start, true) }}</p>
                    <p><strong>Booking End:</strong> {{ \App\Utils\Util::format_date($booking->booking_end, true) }}</p>
                    <p><strong>Room Number:</strong> {{ $booking->room_number ?? 'N/A' }}</p>
                    <p><strong>Table:</strong> {{ $booking->table ? $booking->table->name : 'N/A' }}</p>
                    <p><strong>Location:</strong> {{ $booking->location ? $booking->location->name : 'N/A' }}</p>
                    <p><strong>Waiter:</strong> {{ $booking->waiter ? $booking->waiter->name : 'N/A' }}</p>
                    <p><strong>Status:</strong> {{ $booking->booking_status }}</p>
                    <p><strong>Price:</strong> KES {{ number_format($booking->price) }}</p>
                    <div class="btn-group">
                        <a href="{{ route('bookings.index') }}" class="btn btn-default">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection