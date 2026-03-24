@extends('tenants.super-admin.dashboard')
@section('content')
<style>
.dt-buttons .btn {
  background: transparent !important;
  background-image: none !important;
  box-shadow: none !important;
  border-color: #5bc0de;
  color: #5bc0de;
}

.dt-buttons .btn:hover {
  background: #5bc0de !important;
  color: #fff;
}

/* Custom FullCalendar styling */
#calendar .fc-toolbar-title {
    font-size: 1.25rem;
    font-weight: 600;
}

#calendar .fc-button {
    background-color: #364152 !important;
    border-color: #364152 !important;
    color: #fff !important;
    border-radius: 0.375rem !important;
    padding: 0.5rem 0.75rem !important;
    font-size: 0.875rem !important;
    font-weight: 500 !important;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

#calendar .fc-button:hover {
    background-color: #2d3748 !important;
    border-color: #2d3748 !important;
    color: #fff !important;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.15) !important;
}

#calendar .fc-button:focus {
    box-shadow: 0 0 0 0.2rem rgba(54, 65, 82, 0.25) !important;
}

#calendar .fc-button.fc-button-active {
    background-color: #2b7be3 !important;
    border-color: #2b7be3 !important;
}

#calendar .fc-button.fc-button-active:hover {
    background-color: #1e5bb7 !important;
    border-color: #1e5bb7 !important;
}

/* Style for listMonth button */
#calendar .fc-listMonth-button {
    padding: 0.5rem 0.75rem !important;
    display: flex;
    align-items: center;
    justify-content: center;
}

#calendar .fc-listMonth-button:hover {
    filter: brightness(120%) !important; /* Subtle brightness for enhanced visibility */
}

/* Ensure toolbar buttons are tightly grouped */
.fc-header-toolbar .fc-toolbar-chunk:last-child {
    display: flex;
    align-items: center;
    gap: 0.5rem; /* Reduced gap for tighter button grouping */
}

/* Override hover in list view to make text bold */
.fc-list-event:hover td {
    background-color: inherit !important; /* Prevent background change */
    font-weight: bold !important; /* Make text bold on hover */
}

/* Calendar event styling */
.fc-event {
    cursor: pointer !important;
    border-radius: 4px !important;
    padding: 2px 5px !important;
    font-size: 0.875rem !important;
    color: #fff !important; /* Ensure text is readable */
}

.fc-daygrid-event {
    margin: 1px 0 !important;
}

/* Ensure Bootstrap classes work for light backgrounds */
.fc-event.bg-warning {
    color: #000 !important; /* Black text for warning to improve readability */
}

/* Card header styling */
.card-header {
    padding: 0.5rem 1rem !important;
    background: linear-gradient(to right, #4B5EBD, #576CC0) !important;
    color: #fff !important;
    border-bottom: 1px solid #dee2e6 !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Card body styling */
.card-body {
    padding-top: 0.5rem !important; /* Reduced top padding */
}

/* Button styling for icons in card-header */
.card-header .btn-light {
    padding: 0.25rem 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    border-radius: 0.25rem;
    font-size: 1rem; /* Match fs-16 from events-table.blade.php */
}

.card-header .btn-light:hover {
    background-color: #e9ecef;
    transition: background-color 0.2s ease-in-out;
}

/* List view event icon styling */
.fc-list-event-dot {
    display: none !important; /* Hide default dot */
}

.fc-list-event-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.fc-list-event-title::before {
    content: '\e9d0'; /* Unicode for ri-checkbox-circle-fill */
    font-family: 'RemixIcon' !important;
    color: #fff; /* White icon */
    font-size: 1rem;
}

/* Calendar view event icon styling */
.fc-daygrid-event .fc-event-title,
.fc-timegrid-event .fc-event-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.fc-daygrid-event .fc-event-title::before,
.fc-timegrid-event .fc-event-title::before {
    content: '\e9d0'; /* Unicode for ri-checkbox-circle-fill */
    font-family: 'RemixIcon' !important;
    color: #fff; /* White icon */
    font-size: 0.875rem;
}

/* Ensure icon visibility for all events */
.fc-list-table tr > *,
.fc-daygrid-event,
.fc-timegrid-event {
    position: relative;
}
</style>

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height: 8px; transform: rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
</div>

<div class="content-page">
    <div class="content">
        <!-- Start Content-->
        <div class="container-fluid">
            
            <!-- start page title -->
            <div class="row mb-3">
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <a href="#" class="btn btn-light text-primary fs-16" id="btn-new-event" title="Create New Event">
                                    <i class="ri-add-circle-line"></i>
                                </a>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="#" class="btn btn-light text-success fs-16" id="btn-view-all-events" title="Manage Events">
                                    <i class="ri-settings-3-line"></i>
                                </a>
                                <a href="#" class="btn btn-light text-secondary fs-16" id="btn-how-it-works" data-bs-toggle="modal" data-bs-target="#how-it-works-modal" title="How It Works">
                                    <i class="ri-question-line"></i>
                                </a>
                                <a href="#" class="btn btn-light text-info fs-16" id="btn-refresh-calendar" title="Refresh Calendar">
                                    <i class="ri-refresh-line"></i>
                                </a>
                                <a href="#" class="btn btn-light text-warning fs-16" id="btn-export-events" title="Export Events">
                                    <i class="ri-download-line"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div> <!-- end card body-->
                    </div> <!-- end card -->

                    <!-- Add New Event MODAL -->
                    <div class="modal fade" id="event-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header py-3 px-4 border-bottom-0">
                                    <h5 class="modal-title" id="modal-title">Event</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body px-4 pb-4 pt-0">
                                    <form name="event-form" id="form-event">
                                        @csrf
                                        <input type="hidden" id="event-id" name="id">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="control-label form-label">Event Description </label>
                                                    <input class="form-control" placeholder="Enter event description" type="text" name="description" id="event-description" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="mb-3">
                                                            <label class="control-label form-label">Start Date </label>
                                                            <input class="form-control" type="date" name="start_date" id="event-start-date" />
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="mb-3">
                                                            <label class="control-label form-label">End Date</label>
                                                            <input class="form-control" type="date" name="end_date" id="event-end-date" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12" id="event-time-container">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="mb-3">
                                                            <label class="control-label form-label">Start Time</label>
                                                            <input class="form-control" type="time" name="start_time" id="event-start-time" value="07:00" />
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="mb-3">
                                                            <label class="control-label form-label">End Time</label>
                                                            <input class="form-control" type="time" name="end_time" id="event-end-time" value="22:00" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="all_day" id="event-all-day" value="1">
                                                        <label class="form-check-label" for="event-all-day">All Day Event</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="control-label form-label">Background Color</label>
                                                    <select class="form-select" name="bg_color" id="event-bg-color" required>
                                                        <option value="bg-danger">Danger</option>
                                                        <option value="bg-success">Success</option>
                                                        <option value="bg-primary">Primary</option>
                                                        <option value="bg-info">Info</option>
                                                        <option value="bg-dark">Dark</option>
                                                        <option value="bg-warning">Warning</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <button type="button" class="btn btn-danger" id="btn-delete-event" style="display: none;">Delete</button>
                                            </div>
                                            <div class="col-6 text-end">
                                                <button type="button" class="btn btn-light me-1" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary" id="btn-save-event">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div> <!-- end modal-content-->
                        </div> <!-- end modal dialog-->
                    </div>
                    <!-- end modal-->

                    <!-- Delete Event MODAL -->
                    <div class="modal fade" id="deleteEventModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog" style="max-width:350px; margin:1.75rem auto;">
                            <div class="modal-content">
                                <div class="modal-body text-center pb-4">
                                    <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                                    <form action="#" method="post" id="deleteEventForm">
                                        @csrf
                                        <div class="form-group">
                                            <h4>Are you sure you want to delete <span id="displayDeleteEventLabel"></span>?</h4>
                                        </div>
                                        <div class="form-group">
                                            <h5>You won't be able to revert this!</h5>
                                        </div>
                                        <div class="form-group">
                                            <input type="hidden" id="deleteEventId" name="id">
                                        </div>
                                        <div class="form-group">
                                            <a href="#" class="btn btn-danger" id="submitDeleteEventBtn" style="margin-top:10px;margin-bottom:10px;margin-right:5px">Yes, Delete it</a>
                                            <a href="#" class="btn btn-info" id="keepEventBtn" style="margin-top:10px;margin-bottom:10px;">No, Keep it</a>
                                        </div>
                                    </form>
                                </div>
                            </div> <!-- end modal-content-->
                        </div> <!-- end modal dialog-->
                    </div>
                    <!-- end delete event modal-->

                    <!-- How It Works MODAL -->
                    <div class="modal fade" id="how-it-works-modal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header py-3 px-4 border-bottom-0">
                                    <h5 class="modal-title">How It Works</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body px-4 pb-4 pt-0">
                                    <p>1. Click on a date or use the <strong>Create New Event</strong> icon to add a new event.</p>
                                    <p>2. Click on an event to edit or delete it.</p>
                                    <p>3. Drag events on the calendar to reschedule them.</p>
                                    <p><strong>Note:</strong> You can have more than one event on the same date.</p>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end how it works modal -->
                </div>
                <!-- end col-12 -->
            </div> <!-- end row -->
        </div> <!-- container -->
    </div> <!-- content -->
</div>

<!-- ============================================================== -->
<!-- End Page content -->
<!-- ============================================================== -->
@endsection
@section('scripts')
<script>
$(document).ready(function() {
    // Initialize Toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        showMethod: 'slideDown',
        timeOut: 5000
    };

    // Debounce flag to prevent multiple simultaneous requests
    let isProcessing = false;

    // Initialize FullCalendar
    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        themeSystem: 'bootstrap',
        bootstrapFontAwesome: false,
        slotDuration: '00:15:00',
        slotMinTime: '08:00:00',
        slotMaxTime: '19:00:00',
        buttonText: {
            today: 'Today',
            month: 'Month',
            week: 'Week',
            day: 'Day',
            list: 'List'
        },
        initialView: 'dayGridMonth',
        handleWindowResize: true,
        height: $(window).height() - 200,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        displayEventTime: false,
        events: {
            url: '{{ route("master.fetch.events") }}',
            method: 'GET',
            failure: function(error) {
                console.error('Failed to fetch events:', error);
                toastr.error('Failed to load events. Please try refreshing the page.', 'Error', {
                    timeOut: 5000,
                    progressBar: true
                });
            },
            success: function(response) {
                console.log('Fetch Events Response:', response);
                if (response.status === 201 && response.events) {
                    return response.events;
                } else {
                    console.error('Invalid response format:', response);
                    toastr.error('Invalid event data received from server.', 'Error', {
                        timeOut: 5000,
                        progressBar: true
                    });
                    return [];
                }
            }
        },
        editable: true,
        selectable: true,
        select: function(info) {
            $('#form-event')[0].reset();
            $('#event-id').val('');
            $('#btn-delete-event').hide();
            $('#modal-title').text('Add New Event');
            $('#event-start-date').val(moment(info.startStr).format('YYYY-MM-DD'));
            $('#event-end-date').val(moment(info.startStr).format('YYYY-MM-DD'));
            $('#event-start-time').val('07:00');
            $('#event-end-time').val('22:00');
            $('#event-all-day').prop('checked', false);
            $('#event-time-container').show();
            $('#event-modal').modal('show');
            calendar.unselect();
        },
        eventClick: function(info) {
            $('#form-event')[0].reset();
            $('#event-id').val(info.event.id);
            $('#event-description').val(info.event.title);
            $('#event-start-date').val(moment(info.event.start).format('YYYY-MM-DD'));
            $('#event-end-date').val(info.event.end ? moment(info.event.end).format('YYYY-MM-DD') : moment(info.event.start).format('YYYY-MM-DD'));
            $('#event-start-time').val(info.event.extendedProps.start_time || '07:00');
            $('#event-end-time').val(info.event.extendedProps.end_time || '22:00');
            $('#event-all-day').prop('checked', info.event.allDay);
            $('#event-bg-color').val(info.event.extendedProps.bg_color);
            $('#event-time-container').toggle(!info.event.allDay);
            $('#btn-delete-event').show();
            $('#modal-title').text('Edit Event');
            $('#event-modal').modal('show');
        },
        eventDrop: function(info) {
            if (isProcessing) return;
            isProcessing = true;

            var startTime = info.event.extendedProps.start_time || '07:00';
            var endTime = info.event.extendedProps.end_time || '22:00';
            var startDate = moment(info.event.start).format('YYYY-MM-DD');
            var endDate = info.event.end ? moment(info.event.end).format('YYYY-MM-DD') : startDate;
            if (moment(endDate).isBefore(startDate)) {
                endDate = startDate;
            }

            $.ajax({
                type: 'POST',
                url: '{{ route("master.update.event", ":id") }}'.replace(':id', info.event.id),
                data: {
                    description: info.event.title,
                    start_date: startDate,
                    end_date: endDate,
                    start_time: startTime,
                    end_time: endTime,
                    all_day: info.event.allDay ? 1 : 0,
                    bg_color: info.event.extendedProps.bg_color,
                    _token: '{{ csrf_token() }}'
                },
                timeout: 60000,
                beforeSend: function() {
                    $('#progressBar').show();
                },
                complete: function() {
                    $('#progressBar').hide();
                    isProcessing = false;
                },
                success: function(data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success', { timeOut: 5000, progressBar: true });
                    } else if (data.status === 409) {
                        toastr.info(data.error, 'No Changes', { timeOut: 5000, progressBar: true });
                    } else {
                        toastr.info('Unexpected response.', 'Info', { timeOut: 5000, progressBar: true });
                    }
                },
                error: function(xhr, status, error) {
                    if (status === 'timeout') {
                        toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 0) {
                        toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 422) {
                        var errorPassage = '';
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            errorPassage += value + '\n';
                        });
                        toastr.error(errorPassage, 'Validation Errors', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 404) {
                        toastr.error(xhr.responseJSON?.error || 'Event not found.', 'Not Found', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 500) {
                        toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error', { timeOut: 5000, progressBar: true });
                    } else {
                        toastr.error('Unspecified error occurred. Try again later.', 'Error', { timeOut: 5000, progressBar: true });
                    }
                    info.revert();
                }
            });
        },
        eventResize: function(info) {
            if (isProcessing) return;
            isProcessing = true;

            var startTime = info.event.extendedProps.start_time || '07:00';
            var endTime = info.event.extendedProps.end_time || '22:00';
            var startDate = moment(info.event.start).format('YYYY-MM-DD');
            var endDate = info.event.end ? moment(info.event.end).format('YYYY-MM-DD') : startDate;
            if (moment(endDate).isBefore(startDate)) {
                endDate = startDate;
            }

            $.ajax({
                type: 'POST',
                url: '{{ route("master.update.event", ":id") }}'.replace(':id', info.event.id),
                data: {
                    description: info.event.title,
                    start_date: startDate,
                    end_date: endDate,
                    start_time: startTime,
                    end_time: endTime,
                    all_day: info.event.allDay ? 1 : 0,
                    bg_color: info.event.extendedProps.bg_color,
                    _token: '{{ csrf_token() }}'
                },
                timeout: 60000,
                beforeSend: function() {
                    $('#progressBar').show();
                },
                complete: function() {
                    $('#progressBar').hide();
                    isProcessing = false;
                },
                success: function(data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success', { timeOut: 5000, progressBar: true });
                    } else if (data.status === 409) {
                        toastr.info(data.error, 'No Changes', { timeOut: 5000, progressBar: true });
                    } else {
                        toastr.info('Unexpected response.', 'Info', { timeOut: 5000, progressBar: true });
                    }
                },
                error: function(xhr, status, error) {
                    if (status === 'timeout') {
                        toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 0) {
                        toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 422) {
                        var errorPassage = '';
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            errorPassage += value + '\n';
                        });
                        toastr.error(errorPassage, 'Validation Errors', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 404) {
                        toastr.error(xhr.responseJSON?.error || 'Event not found.', 'Not Found', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 500) {
                        toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error', { timeOut: 5000, progressBar: true });
                    } else {
                        toastr.error('Unspecified error occurred. Try again later.', 'Error', { timeOut: 5000, progressBar: true });
                    }
                    info.revert();
                }
            });
        },
        eventContent: function(arg) {
            if (arg.view.type === 'listMonth') {
                return {
                    html: `
                        <div class="fc-list-event-title">
                            <i class="ri-checkbox-circle-fill"></i>
                            <span>${arg.event.title}</span>
                        </div>
                    `
                };
            } else {
                return {
                    html: `
                        <div class="fc-event-title">
                            <i class="ri-checkbox-circle-fill"></i>
                            <span>${arg.event.title}</span>
                        </div>
                    `
                };
            }
        },
        eventDidMount: function(info) {
            console.log('Event Rendered:', {
                id: info.event.id,
                title: info.event.title,
                classNames: info.event.classNames,
                bg_color: info.event.extendedProps.bg_color,
                start: info.event.start,
                end: info.event.end,
                allDay: info.event.allDay
            });
            if (!$(info.el).hasClass(info.event.classNames.join(' '))) {
                $(info.el).addClass(info.event.classNames.join(' '));
            }
            $(info.el).css({
                'cursor': 'pointer',
                'color': '#fff'
            });
            if (info.view.type === 'listMonth') {
                $(info.el).find('.fc-list-event-title').css({
                    'display': 'flex',
                    'align-items': 'center',
                    'gap': '0.5rem'
                });
                $(info.el).find('.fc-list-event-title i').css({
                    'color': '#fff',
                    'font-size': '1rem'
                });
            } else {
                $(info.el).find('.fc-event-title').css({
                    'display': 'flex',
                    'align-items': 'center',
                    'gap': '0.5rem',
                    'padding': '2px 5px'
                });
                $(info.el).find('.fc-event-title i').css({
                    'color': '#fff',
                    'font-size': '0.875rem'
                });
            }
        }
    });

    calendar.render();

    // Toggle time inputs
    $('#event-all-day').on('change', function() {
        $('#event-time-container').toggle(!$(this).prop('checked'));
        if ($(this).prop('checked')) {
            $('#event-start-time').val('07:00');
            $('#event-end-time').val('22:00');
        }
    });

    $('#event-start-date').on('change', function() {
        var startDate = $(this).val();
        $('#event-end-date').val(startDate);
    });

    $('#event-end-date').on('change', function() {
        var endDate = $(this).val();
        var startDate = $('#event-start-date').val();
        if (startDate && endDate && moment(endDate).isBefore(startDate)) {
            $('#event-end-date').val(startDate);
        }
    });

    $('#btn-new-event').click(function(e) {
        e.preventDefault();
        $('#form-event')[0].reset();
        $('#event-id').val('');
        $('#btn-delete-event').hide();
        $('#modal-title').text('Add New Event');
        var today = moment().format('YYYY-MM-DD');
        $('#event-start-date').val(today);
        $('#event-end-date').val(today);
        $('#event-start-time').val('07:00');
        $('#event-end-time').val('22:00');
        $('#event-all-day').prop('checked', false);
        $('#event-time-container').show();
        $('#event-modal').modal('show');
    });

    $('#btn-view-all-events').click(function(e) {
        e.preventDefault();
        window.location.href = '{{ route("master.events.table") }}';
    });

    $('#btn-refresh-calendar').click(function(e) {
        e.preventDefault();
        calendar.refetchEvents();
    });

    $('#form-event').on('submit', function(e) {
        e.preventDefault();
        var self = $('#btn-save-event');
        self.prop('disabled', true);
        var isEdit = $('#event-id').val() !== '';
        var url = isEdit ? '{{ route("master.update.event", ":id") }}'.replace(':id', $('#event-id').val()) : '{{ route("master.add.event") }}';

        var formData = $(this).serializeArray();
        formData = formData.filter(function(item) {
            return item.name !== 'all_day';
        });
        formData.push({
            name: 'all_day',
            value: $('#event-all-day').prop('checked') ? 1 : 0
        });

        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            timeout: 60000,
            beforeSend: function() {
                $('#progressBar').show();
            },
            complete: function() {
                $('#progressBar').hide();
                self.prop('disabled', false);
            },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success', { timeOut: 5000, progressBar: true });
                    if (isEdit) {
                        var event = calendar.getEventById(data.event.id);
                        event.setProp('title', data.event.title);
                        event.setProp('classNames', [data.event.classNames[0]]);
                        event.setExtendedProp('bg_color', data.event.classNames[0]);
                        event.setStart(data.event.start);
                        if (data.event.end) event.setEnd(data.event.end);
                        event.setAllDay(data.event.allDay);
                        event.setExtendedProp('start_time', data.event.start_time);
                        event.setExtendedProp('end_time', data.event.end_time);
                    } else {
                        calendar.getEventById(data.event.id)?.remove();
                        calendar.addEvent({
                            id: data.event.id,
                            title: data.event.title,
                            start: data.event.start,
                            end: data.event.end,
                            allDay: data.event.allDay,
                            classNames: data.event.classNames,
                            extendedProps: {
                                start_time: data.event.start_time,
                                end_time: data.event.end_time,
                                bg_color: data.event.classNames[0]
                            }
                        });
                    }
                    $('#event-modal').modal('hide');
                } else if (data.status === 409) {
                    toastr.info(data.error, 'No Changes', { timeOut: 5000, progressBar: true });
                } else {
                    toastr.info('Unexpected response.', 'Info', { timeOut: 5000, progressBar: true });
                }
            },
            error: function(xhr, status, error) {
                if (status === 'timeout') {
                    toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 0) {
                    toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 422) {
                    var errorPassage = '';
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        errorPassage += value + '\n';
                    });
                    toastr.error(errorPassage, 'Validation Errors', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 404) {
                    toastr.error(xhr.responseJSON?.error || 'Event not found.', 'Not Found', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 500) {
                    toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error', { timeOut: 5000, progressBar: true });
                } else {
                    toastr.error('Unspecified error occurred. Try again later.', 'Error', { timeOut: 5000, progressBar: true });
                }
            }
        });
    });

    $('#btn-delete-event').click(function(e) {
        e.preventDefault();
        var eventId = $('#event-id').val();
        var eventDescription = $('#event-description').val();
        if (!eventId) {
            toastr.error('No event selected for deletion.', 'Error', { timeOut: 5000, progressBar: true });
            return;
        }
        $('#event-modal').modal('hide');
        $('#displayDeleteEventLabel').html(eventDescription);
        $('#deleteEventId').val(eventId);
        $('#deleteEventModal').modal('show');
    });

    $('#keepEventBtn').click(function(e) {
        e.preventDefault();
        toastr.info('Your event is safe', 'Great!', { timeOut: 5000, progressBar: true });
        $('#deleteEventModal').modal('hide');
    });

    $('#submitDeleteEventBtn').click(function(e) {
        e.preventDefault();
        var self = $(this);
        self.prop('disabled', true);
        var eventId = $('#deleteEventId').val();

        $.ajax({
            type: 'POST',
            url: '{{ route("master.delete.event", ":id") }}'.replace(':id', eventId),
            data: { _token: '{{ csrf_token() }}' },
            timeout: 60000,
            beforeSend: function() {
                $('#progressBar').show();
            },
            complete: function() {
                $('#progressBar').hide();
                self.prop('disabled', false);
            },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success', { timeOut: 5000, progressBar: true });
                    var event = calendar.getEventById(eventId);
                    if (event) event.remove();
                    $('#deleteEventModal').modal('hide');
                } else if (data.status === 404) {
                    toastr.info(data.error, 'Not Found', { timeOut: 5000, progressBar: true });
                } else {
                    toastr.info('Unexpected response.', 'Info', { timeOut: 5000, progressBar: true });
                }
            },
            error: function(xhr, status, error) {
                if (status === 'timeout') {
                    toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 0) {
                    toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 422) {
                    var errorPassage = '';
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        errorPassage += value + '\n';
                    });
                    toastr.error(errorPassage, 'Validation Errors', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 404) {
                    toastr.error(xhr.responseJSON?.error || 'Event not found.', 'Not Found', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 500) {
                    toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error', { timeOut: 5000, progressBar: true });
                } else {
                    toastr.error('Unspecified error occurred. Try again later.', 'Error', { timeOut: 5000, progressBar: true });
                }
            }
        });
    });
});
</script>
@endsection