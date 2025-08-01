@extends('layouts.app')
@section('title', __('restaurant.bookings'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('restaurant.bookings') & Guest Management</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        @if(count($business_locations) > 1)
        <div class="col-sm-12">
            <select id="business_location_id" class="select2" style="width:50%">
                <option value="">@lang('purchase.business_location')</option>
                @foreach($business_locations as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>
    <br>
    
    <!-- Today's Bookings with Guest Information -->
    <div class="row">
        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-calendar-check-o"></i> @lang('restaurant.todays_bookings') with Guest Details
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed table-striped" id="todays_bookings_table">
                            <thead>
                            <tr>
                                <th>Guest Name</th>
                                <th>Contact Info</th>
                                <th>Guest Details</th>
                                <th>Room Details</th>
                                <th>Booking Times</th>
                                <th>Table</th>
                                <th>Location</th>
                                <th>Staff</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Guest Check-ins Summary -->
    <div class="row">
        <div class="col-sm-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-users"></i> Recent Guest Check-ins
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed table-striped" id="guest_checkins_table">
                            <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Gender</th>
                                <th>Nationality</th>
                                <th>Stay Purpose</th>
                                <th>Payment Method</th>
                                <th>Staff Ack.</th>
                                <th>Guest Ack.</th>
                                <th>Booking Status</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar View -->
    <div class="row">
        <div class="col-sm-10">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-calendar"></i> Booking Calendar
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-12 text-right">
                            <button type="button" class="btn btn-primary" id="add_new_booking_btn">
                                <i class="fa fa-plus"></i> @lang('restaurant.add_booking')
                            </button>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-12">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-2">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">Legend</h3>
                </div>
                <div class="box-body">
                    <div class="external-event bg-yellow text-center" style="position: relative;">
                        <small>@lang('lang_v1.waiting')</small>
                    </div>
                    <div class="external-event bg-light-blue text-center" style="position: relative;">
                        <small>@lang('restaurant.booked')</small>
                    </div>
                    <div class="external-event bg-green text-center" style="position: relative;">
                        <small>@lang('restaurant.completed')</small>
                    </div>
                    <div class="external-event bg-red text-center" style="position: relative;">
                        <small>@lang('restaurant.cancelled')</small>
                    </div>
                    <small>
                        <p class="help-block">
                            <i>@lang('restaurant.click_on_any_booking_to_view_or_change_status')<br><br>
                            @lang('restaurant.double_click_on_any_day_to_add_new_booking')</i>
                        </p>
                    </small>
                </div>
            </div>
        </div>
    </div>

    @include('restaurant.booking.create')
    
    <!-- Contact Modal -->
    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('contact.create', ['quick_add' => true])
    </div>
    
    <!-- Guest Details Modal -->
    <div class="modal fade" id="view_guest_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">
                        <i class="fa fa-user"></i> Guest Details
                    </h4>
                </div>
                <div class="modal-body" id="guest_details_content">
                    <!-- Guest details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal fade view_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <!-- Booking details will be loaded here -->
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var clickCount = 0;

        // Initialize FullCalendar
        try {
            $('#calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay,listWeek'
                },
                eventLimit: 2,
                events: '/bookings',
                eventRender: function(event, element) {
                    var title_html = event.customer_name || 'Unknown';
                    if (event.room_number) {
                        title_html += '<br>Room: ' + event.room_number;
                    }
                    element.find('.fc-title').html(title_html);
                    element.attr('data-href', event.url);
                    element.attr('data-container', '.view_modal');
                    element.addClass('btn-modal');
                },
                dayClick: function(date, jsEvent, view) {
                    clickCount++;
                    if (clickCount == 2) {
                        $('#add_booking_modal').modal('show');
                        $('form#add_booking_form #start_time').data("DateTimePicker").date(date).ignoreReadonly(true);
                        $('form#add_booking_form #end_time').data("DateTimePicker").date(date).ignoreReadonly(true);
                    }
                    var clickTimer = setInterval(function() {
                        clickCount = 0;
                        clearInterval(clickTimer);
                    }, 500);
                },
                eventSources: [{
                    url: '/bookings',
                    data: function() {
                        return {
                            location_id: $('#business_location_id').val()
                        };
                    },
                    error: function() {
                        toastr.error('Failed to load calendar events. Check console.');
                    }
                }]
            });
        } catch (e) {
            console.error('FullCalendar initialization failed:', e);
            toastr.error('Calendar failed to load. Check console.');
        }

        // Initialize Today's Bookings Table with Enhanced Columns
        try {
            var todays_bookings_table = $('#todays_bookings_table').DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                searching: true,
                pageLength: 10,
                scrollX: true,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fa fa-file-excel-o"></i> Export Excel',
                        className: 'btn btn-success btn-sm'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fa fa-file-pdf-o"></i> Export PDF',
                        className: 'btn btn-danger btn-sm'
                    }
                ],
                ajax: {
                    url: "/bookings/get-todays-bookings",
                    data: function(d) {
                        d.location_id = $('#business_location_id').val();
                    },
                    error: function(xhr) {
                        toastr.error('Failed to load today\'s bookings. Check console.');
                        console.error(xhr.responseText);
                    }
                },
                columns: [
                    { data: 'customer', width: '12%' },
                    { data: 'contact_info', width: '15%' },
                    { data: 'guest_info', width: '15%' },
                    { data: 'room_details', width: '12%' },
                    { 
                        data: null,
                        render: function(data, type, row) {
                            return '<strong>In:</strong> ' + row.booking_start + '<br><strong>Out:</strong> ' + row.booking_end;
                        },
                        width: '15%'
                    },
                    { data: 'table', width: '8%' },
                    { data: 'location', width: '8%' },
                    { data: 'waiter', width: '8%' },
                    { data: 'price', width: '8%' },
                    { data: 'status', width: '7%' },
                    { data: 'action', orderable: false, searchable: false, width: '5%' }
                ]
            });
        } catch (e) {
            console.error('Today\'s Bookings Table initialization failed:', e);
            toastr.error('Today\'s bookings table failed to load. Check console.');
        }

        // Initialize Enhanced Guest Check-ins Table
        try {
            var guest_checkins_table = $('#guest_checkins_table').DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                searching: true,
                pageLength: 10,
                scrollX: true,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fa fa-file-excel-o"></i> Export Excel',
                        className: 'btn btn-success btn-sm'
                    }
                ],
                ajax: {
                    url: "/bookings/guest-checkins",
                    data: function(d) {
                        d.location_id = $('#business_location_id').val();
                    },
                    error: function(xhr) {
                        toastr.error('Failed to load guest check-ins. Check console.');
                        console.error(xhr.responseText);
                    }
                },
                columns: [
                    { data: 'full_name' },
                    { data: 'email' },
                    { data: 'phone' },
                    { data: 'gender' },
                    { data: 'nationality' },
                    { data: 'stay_purpose' },
                    { data: 'payment_method' },
                    { data: 'staff_acknowledged' },
                    { data: 'guest_acknowledged' },
                    { 
                        data: null,
                        render: function(data, type, row) {
                            // Check if this guest has an associated booking
                            return '<span class="label label-info">Checking...</span>';
                        }
                    },
                    { data: 'created_at' },
                    { data: 'action', orderable: false, searchable: false }
                ]
            });
        } catch (e) {
            console.error('Guest Check-ins Table initialization failed:', e);
            toastr.error('Guest check-ins table failed to load. Check console.');
        }

        // View Guest Details
        $(document).on('click', '.view-guest-details', function() {
            var guestId = $(this).data('id');
            
            $.ajax({
                method: "GET",
                url: '/bookings/guest-checkins/' + guestId,
                dataType: "json",
                success: function(data) {
                    var html = `
                        <div class="row">
                            <div class="col-md-6">
                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <h4 class="panel-title"><i class="fa fa-user"></i> Personal Information</h4>
                                    </div>
                                    <div class="panel-body">
                                        <table class="table table-condensed">
                                            <tr><td><strong>Full Name:</strong></td><td>${data.surname} ${data.name}</td></tr>
                                            <tr><td><strong>Email:</strong></td><td>${data.email || 'N/A'}</td></tr>
                                            <tr><td><strong>Phone:</strong></td><td>${data.phone}</td></tr>
                                            <tr><td><strong>Gender:</strong></td><td>${data.gender}</td></tr>
                                            <tr><td><strong>Nationality:</strong></td><td>${data.nationality}</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="panel panel-info">
                                    <div class="panel-heading">
                                        <h4 class="panel-title"><i class="fa fa-id-card"></i> Identification</h4>
                                    </div>
                                    <div class="panel-body">
                                        <table class="table table-condensed">
                                            <tr><td><strong>ID Type:</strong></td><td>${data.id_type || 'N/A'}</td></tr>
                                            <tr><td><strong>ID Number:</strong></td><td>${data.id_number || 'N/A'}</td></tr>
                                            <tr><td><strong>Passport No:</strong></td><td>${data.passport_no || 'N/A'}</td></tr>
                                            <tr><td><strong>Company:</strong></td><td>${data.company || 'N/A'}</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="panel panel-success">
                                    <div class="panel-heading">
                                        <h4 class="panel-title"><i class="fa fa-suitcase"></i> Stay Details</h4>
                                    </div>
                                    <div class="panel-body">
                                        <table class="table table-condensed">
                                            <tr><td><strong>Purpose:</strong></td><td>${data.stay_purpose}</td></tr>
                                            <tr><td><strong>Payment Method:</strong></td><td>${data.payment_method}</td></tr>
                                            <tr><td><strong>Check-in Time:</strong></td><td>${data.created_at}</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="panel panel-warning">
                                    <div class="panel-heading">
                                        <h4 class="panel-title"><i class="fa fa-check-circle"></i> Acknowledgments</h4>
                                    </div>
                                    <div class="panel-body">
                                        <table class="table table-condensed">
                                            <tr>
                                                <td><strong>Staff Acknowledged:</strong></td>
                                                <td>
                                                    ${data.staff_acknowledged ? 
                                                        '<span class="label label-success">Yes</span>' : 
                                                        '<span class="label label-warning">No</span>'}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Guest Acknowledged:</strong></td>
                                                <td>
                                                    ${data.guest_acknowledged ? 
                                                        '<span class="label label-success">Yes</span>' : 
                                                        '<span class="label label-warning">No</span>'}
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        ${data.remarks ? `
                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title"><i class="fa fa-comments"></i> Remarks</h4>
                                    </div>
                                    <div class="panel-body">
                                        <p>${data.remarks}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        ` : ''}`;
                    
                    $('#guest_details_content').html(html);
                    $('#view_guest_modal').modal('show');
                },
                error: function(xhr) {
                    toastr.error('Failed to load guest details. Check console.');
                    console.error(xhr.responseText);
                }
            });
        });

        // View Checkin from guest checkins table
       $(document).on('click', '.view-checkin', function() {
    var id = $(this).data('id');
    $.ajax({
        method: "GET",
        url: '/bookings/guest-checkins/' + id,
        dataType: "json",
        success: function(data) {
            var html = `
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title"><i class="fa fa-user-check"></i> Guest Check-in Details</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><strong>Personal Information</strong></h5>
                                <p><strong>Full Name:</strong> ${data.full_name}</p>
                                <p><strong>Email:</strong> ${data.email}</p>
                                <p><strong>Phone:</strong> ${data.phone}</p>
                                <p><strong>Gender:</strong> ${data.gender}</p>
                                <p><strong>Nationality:</strong> ${data.nationality}</p>
                            </div>
                            <div class="col-md-6">
                                <h5><strong>Stay Details</strong></h5>
                                <p><strong>Stay Purpose:</strong> ${data.stay_purpose}</p>
                                <p><strong>Payment Method:</strong> ${data.payment_method}</p>
                                <p><strong>Room Number:</strong> ${data.room_number}</p>
                                <p><strong>Check-in:</strong> ${data.booking_start}</p>
                                <p><strong>Check-out:</strong> ${data.booking_end}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <h5><strong>Status</strong></h5>
                                <p><strong>Staff Acknowledged:</strong> ${data.staff_acknowledged}</p>
                                <p><strong>Guest Acknowledged:</strong> ${data.guest_acknowledged}</p>
                                <p><strong>Booking Status:</strong> ${data.booking_status}</p>
                                <p><strong>Created At:</strong> ${data.created_at}</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="createBookingFromGuest(${data.id})">
                            <i class="fa fa-plus"></i> Create Booking
                        </button>
                    </div>
                </div>`;
            $('#view_checkin_modal').html(html).modal('show');
        },
        error: function(xhr) {
            toastr.error('Failed to load check-in details. Check console.');
            console.error(xhr.responseText);
        }
    });
});

        // Enhanced Add Booking Modal
        $('#add_booking_modal').on('shown.bs.modal', function(e) {
            getLocationTables($('select#booking_location_id').val());
            $(this).find('select').each(function() {
                if (!$(this).hasClass('select2')) {
                    $(this).select2({
                        dropdownParent: $('#add_booking_modal')
                    });
                }
            });
        });

        $('#add_booking_modal').on('hidden.bs.modal', function(e) {
            reset_booking_form();
        });

        // Enhanced datetime pickers
        $('form#add_booking_form #start_time').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            minDate: moment(),
            ignoreReadonly: true
        });

        $('form#add_booking_form #end_time').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            minDate: moment(),
            ignoreReadonly: true
        });

        // Edit Booking Modal with Guest Details
        $('.view_modal').on('shown.bs.modal', function(e) {
            $('form#edit_booking_form').validate({
                submitHandler: function(form) {
                    var data = $(form).serialize();
                    $.ajax({
                        method: "PUT",
                        url: $(form).attr("action"),
                        dataType: "json",
                        data: data,
                        beforeSend: function(xhr) {
                            __disable_submit_button($(form).find('button[type="submit"]'));
                        },
                        success: function(result) {
                            if (result.success) {
                                $('div.view_modal').modal('hide');
                                toastr.success(result.msg);
                                $('#calendar').fullCalendar('refetchEvents');
                                todays_bookings_table.ajax.reload();
                                guest_checkins_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                            $(form).find('button[type="submit"]').removeAttr('disabled');
                        },
                        error: function(xhr) {
                            toastr.error('An error occurred. Check console.');
                            console.error(xhr.responseText);
                        }
                    });
                }
            });
        });

        // Add New Booking Button
        $('button#add_new_booking_btn').click(function() {
            $('div#add_booking_modal').modal('show');
        });

        // Location Change - Refresh All Tables
        $(document).on('change', 'select#business_location_id', function() {
            $('#calendar').fullCalendar('refetchEvents');
            todays_bookings_table.ajax.reload();
            guest_checkins_table.ajax.reload();
        });

        // Delete Booking
        $(document).on('click', 'button#delete_booking', function() {
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true
            }).then((willDelete) => {
                if (willDelete) {
                    var href = $(this).data('href');
                    $.ajax({
                        method: "DELETE",
                        url: href,
                        dataType: "json",
                        success: function(result) {
                            if (result.success) {
                                $('div.view_modal').modal('hide');
                                toastr.success(result.msg);
                                $('#calendar').fullCalendar('refetchEvents');
                                todays_bookings_table.ajax.reload();
                                guest_checkins_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                        error: function(xhr) {
                            toastr.error('An error occurred. Check console.');
                            console.error(xhr.responseText);
                        }
                    });
                }
            });
        });

        // Helper Functions
        function getLocationTables(location_id) {
            if (!location_id) return;
            
            $.ajax({
                method: "GET",
                url: '/modules/data/get-pos-details',
                data: { 'location_id': location_id },
                dataType: "html",
                success: function(result) {
                    $('div#restaurant_module_span').html(result);
                },
                error: function(xhr) {
                    console.error('Failed to load location tables:', xhr.responseText);
                }
            });
        }

        function reset_booking_form() {
            if (typeof $('form#add_booking_form')[0] !== 'undefined') {
                $('form#add_booking_form')[0].reset();
            }
            $('select#booking_location_id').val('').trigger('change');
            $('select#correspondent').val('').trigger('change');
            $('#booking_note, #start_time, #end_time').val('');
        }

        // Create booking from guest check-in
        window.createBookingFromGuest = function(guestId) {
            $('#view_checkin_modal').modal('hide');
            $('#add_booking_modal').modal('show');
            
            // Pre-fill form with guest data
            $.ajax({
                method: "GET",
                url: '/bookings/guest-checkins/' + guestId,
                dataType: "json",
                success: function(data) {
                    // Pre-fill guest information if form fields exist
                    setTimeout(function() {
                        if ($('#surname').length) $('#surname').val(data.surname);
                        if ($('#name').length) $('#name').val(data.name);
                        if ($('#email').length) $('#email').val(data.email);
                        if ($('#phone_number').length) $('#phone_number').val(data.phone);
                        if ($('#gender').length) $('#gender').val(data.gender).trigger('change');
                        if ($('#nationality').length) $('#nationality').val(data.nationality);
                        if ($('#stay_purpose').length) $('#stay_purpose').val(data.stay_purpose).trigger('change');
                        if ($('#payment_method').length) $('#payment_method').val(data.payment_method).trigger('change');
                        if ($('#company').length) $('#company').val(data.company);
                        if ($('#remarks').length) $('#remarks').val(data.remarks);
                        if ($('#id_type').length) $('#id_type').val(data.id_type).trigger('change');
                        if ($('#id_number').length) $('#id_number').val(data.id_number);
                        if ($('#passport_no').length) $('#passport_no').val(data.passport_no);
                        
                        // Set hidden field to link to existing guest
                        if ($('#existing_guest_id').length) {
                            $('#existing_guest_id').val(guestId);
                        }
                        
                        toastr.info('Guest information has been pre-filled. Please complete booking details.');
                    }, 500);
                },
                error: function(xhr) {
                    console.error('Failed to load guest data:', xhr.responseText);
                }
            });
        };

        // Quick Add Contact for traditional bookings
        $(document).on('click', '.add_new_customer', function() {
            $('.contact_modal').find('select#contact_type').val('customer').closest('div.contact_type_div').addClass('hide');
            $('.contact_modal').modal('show');
        });

        $('form#quick_add_contact').submit(function(e) {
            e.preventDefault();
        }).validate({
            rules: {
                contact_id: {
                    remote: {
                        url: '/contacts/check-contacts-id',
                        type: 'post',
                        data: {
                            contact_id: function() {
                                return $('#contact_id').val();
                            },
                            hidden_id: function() {
                                return $('#hidden_id').length ? $('#hidden_id').val() : '';
                            }
                        }
                    }
                }
            },
            messages: {
                contact_id: {
                    remote: LANG.contact_id_already_exists
                }
            },
            submitHandler: function(form) {
                var data = $(form).serialize();
                $.ajax({
                    method: 'POST',
                    url: $(form).attr('action'),
                    dataType: 'json',
                    data: data,
                    beforeSend: function(xhr) {
                        __disable_submit_button($(form).find('button[type="submit"]'));
                    },
                    success: function(result) {
                        if (result.success) {
                            $('select#booking_customer_id').append(
                                $('<option>', { value: result.data.id, text: result.data.name })
                            );
                            $('select#booking_customer_id').val(result.data.id).trigger('change');
                            $('div.contact_modal').modal('hide');
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('An error occurred. Check console.');
                        console.error(xhr.responseText);
                    }
                });
            }
        });

        $('.contact_modal').on('hidden.bs.modal', function() {
            $('form#quick_add_contact').find('button[type="submit"]').removeAttr('disabled');
            $('form#quick_add_contact')[0].reset();
        });
    });
</script>
@endsection