@extends('sales.retail.dashboard')
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
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075) !important;
}
#calendar .fc-button:hover {
    background-color: #2d3748 !important;
    border-color: #2d3748 !important;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.15) !important;
}
#calendar .fc-button:focus {
    box-shadow: 0 0 0 0.2rem rgba(54,65,82,0.25) !important;
}
#calendar .fc-button.fc-button-active {
    background-color: #2b7be3 !important;
    border-color: #2b7be3 !important;
}
#calendar .fc-button.fc-button-active:hover {
    background-color: #1e5bb7 !important;
    border-color: #1e5bb7 !important;
}
.fc-header-toolbar .fc-toolbar-chunk:last-child {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.fc-list-event:hover td {
    background-color: inherit !important;
    font-weight: bold !important;
}
.fc-event {
    cursor: pointer !important;
    border-radius: 4px !important;
    padding: 2px 5px !important;
    font-size: 0.875rem !important;
    color: #fff !important;
}
.fc-daygrid-event { margin: 1px 0 !important; }
.fc-event.bg-warning { color: #000 !important; }
.card-header {
    padding: 0.5rem 1rem !important;
    background: linear-gradient(to right, #4B5EBD, #576CC0) !important;
    color: #fff !important;
    border-bottom: 1px solid #dee2e6 !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.card-body { padding-top: 0.5rem !important; }
.card-header .btn-light {
    padding: 0.25rem 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    border-radius: 0.25rem;
    font-size: 1rem;
}
.card-header .btn-light:hover {
    background-color: #e9ecef;
    transition: background-color 0.2s ease-in-out;
}
.fc-list-event-dot { display: none !important; }
.fc-list-event-title,
.fc-daygrid-event .fc-event-title,
.fc-timegrid-event .fc-event-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.fc-list-table tr > *,
.fc-daygrid-event,
.fc-timegrid-event { position: relative; }
</style>

<div class="progress" id="progressBar" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height:8px;transform:rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="row mb-3"></div>
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
                                <a href="#" class="btn btn-light text-secondary fs-16" data-bs-toggle="modal" data-bs-target="#how-it-works-modal" title="How It Works">
                                    <i class="ri-question-line"></i>
                                </a>
                                <a href="#" class="btn btn-light text-info fs-16" id="btn-refresh-calendar" title="Refresh Calendar">
                                    <i class="ri-refresh-line"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>

                    {{-- Add / Edit Modal --}}
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
                                                    <label class="form-label">Event Description</label>
                                                    <input class="form-control" placeholder="Enter event description" type="text" name="description" id="event-description" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Start Date</label>
                                                    <input class="form-control" type="date" name="start_date" id="event-start-date" />
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">End Date</label>
                                                    <input class="form-control" type="date" name="end_date" id="event-end-date" />
                                                </div>
                                            </div>
                                            <div class="col-12" id="event-time-container">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Start Time</label>
                                                            <input class="form-control" type="time" name="start_time" id="event-start-time" value="07:00" />
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">End Time</label>
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
                                                    <label class="form-label">Background Color</label>
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
                                                <button type="button" class="btn btn-danger" id="btn-delete-event" style="display:none">Delete</button>
                                            </div>
                                            <div class="col-6 text-end">
                                                <button type="button" class="btn btn-light me-1" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary" id="btn-save-event">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Delete Confirm Modal --}}
                    <div class="modal fade" id="deleteEventModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog" style="max-width:350px;margin:1.75rem auto;">
                            <div class="modal-content">
                                <div class="modal-body text-center pb-4">
                                    <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                                    <form id="deleteEventForm">
                                        @csrf
                                        <h4 class="mt-2">Are you sure you want to delete <span id="displayDeleteEventLabel"></span>?</h4>
                                        <h5>You won't be able to revert this!</h5>
                                        <input type="hidden" id="deleteEventId">
                                        <a href="#" class="btn btn-danger mt-3 me-2" id="submitDeleteEventBtn">Yes, Delete it</a>
                                        <a href="#" class="btn btn-info mt-3"        id="keepEventBtn">No, Keep it</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- How It Works Modal --}}
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

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton: true, progressBar: true, showMethod: 'slideDown', timeOut: 5000 };

    // ── Route base URLs (tenantName already baked in by Laravel) ─────────────
    // These resolve to e.g. /mycompany/sales/retail/event-update/0
    // We replace the trailing /0 with the real ID at call time.
    var urlUpdate = '{{ route("retail.sales.update.event", ["tenantName" => session("tenant_code"), "id" => "__ID__"]) }}';
    var urlDelete = '{{ route("retail.sales.delete.event", ["tenantName" => session("tenant_code"), "id" => "__ID__"]) }}';
    var urlCreate = '{{ route("retail.sales.add.event",    ["tenantName" => session("tenant_code")]) }}';
    var urlFetch  = '{{ route("retail.sales.fetch.events", ["tenantName" => session("tenant_code")]) }}';
    var urlTable  = '{{ route("retail.sales.events.table", ["tenantName" => session("tenant_code")]) }}';

    function makeUpdateUrl(id) { return urlUpdate.replace('__ID__', id); }
    function makeDeleteUrl(id) { return urlDelete.replace('__ID__', id); }

    // ── Shared AJAX error handler ─────────────────────────────────────────────
    function handleAjaxError(xhr, status, revertFn) {
        if (status === 'timeout') {
            toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error');
        } else if (xhr.status === 0) {
            toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error');
        } else if (xhr.status === 422) {
            var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : [];
            toastr.error(errors.length ? errors.join('<br>') : 'Validation failed.', 'Validation Error');
        } else if (xhr.status === 401) {
            toastr.error('Session expired. Please log in again.', 'Unauthorised');
        } else if (xhr.status === 404) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.error) || 'Event not found.', 'Not Found');
        } else if (xhr.status === 500) {
            toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error');
        } else {
            toastr.error('Unspecified error occurred. Try again later.', 'Error');
        }
        if (typeof revertFn === 'function') revertFn();
    }

    var isProcessing = false;

    // ── FullCalendar ──────────────────────────────────────────────────────────
    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        themeSystem: 'bootstrap',
        bootstrapFontAwesome: false,
        slotDuration: '00:15:00',
        slotMinTime: '08:00:00',
        slotMaxTime: '19:00:00',
        buttonText: { today: 'Today', month: 'Month', week: 'Week', day: 'Day', list: 'List' },
        initialView: 'dayGridMonth',
        handleWindowResize: true,
        height: $(window).height() - 200,
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        displayEventTime: false,

        events: {
            url: urlFetch,
            method: 'GET',
            failure: function () {
                toastr.error('Failed to load events. Please try refreshing the page.', 'Error');
            },
            success: function (response) {
                if (response.status === 201 && response.events) return response.events;
                toastr.error('Invalid event data received from server.', 'Error');
                return [];
            }
        },

        editable:   true,
        selectable: true,

        select: function (info) {
            resetForm();
            $('#btn-delete-event').hide();
            $('#modal-title').text('Add New Event');
            var d = moment(info.startStr).format('YYYY-MM-DD');
            $('#event-start-date').val(d);
            $('#event-end-date').val(d);
            $('#event-modal').modal('show');
            calendar.unselect();
        },

        eventClick: function (info) {
            resetForm();
            $('#event-id').val(info.event.id);
            $('#event-description').val(info.event.title);
            $('#event-start-date').val(moment(info.event.start).format('YYYY-MM-DD'));
            $('#event-end-date').val(
                info.event.end
                    ? moment(info.event.end).format('YYYY-MM-DD')
                    : moment(info.event.start).format('YYYY-MM-DD')
            );
            $('#event-start-time').val(info.event.extendedProps.start_time || '07:00');
            $('#event-end-time').val(info.event.extendedProps.end_time     || '22:00');
            $('#event-all-day').prop('checked', info.event.allDay);
            $('#event-bg-color').val(info.event.extendedProps.bg_color);
            $('#event-time-container').toggle(!info.event.allDay);
            $('#btn-delete-event').show();
            $('#modal-title').text('Edit Event');
            $('#event-modal').modal('show');
        },

        eventDrop: function (info) {
            if (isProcessing) { info.revert(); return; }
            isProcessing = true;
            doCalendarUpdate(info.event,
                function () { isProcessing = false; },
                function () { info.revert(); isProcessing = false; }
            );
        },

        eventResize: function (info) {
            if (isProcessing) { info.revert(); return; }
            isProcessing = true;
            doCalendarUpdate(info.event,
                function () { isProcessing = false; },
                function () { info.revert(); isProcessing = false; }
            );
        },

        eventContent: function (arg) {
            var html = '<div class="fc-' + (arg.view.type === 'listMonth' ? 'list-event-title' : 'event-title') + '">'
                     + '<i class="ri-checkbox-circle-fill"></i><span>' + arg.event.title + '</span></div>';
            return { html: html };
        },

        eventDidMount: function (info) {
            if (info.event.classNames && info.event.classNames.length) {
                $(info.el).addClass(info.event.classNames.join(' '));
            }
            $(info.el).css({ cursor: 'pointer', color: '#fff' });
            var titleSel = info.view.type === 'listMonth' ? '.fc-list-event-title' : '.fc-event-title';
            $(info.el).find(titleSel).css({ display: 'flex', alignItems: 'center', gap: '0.5rem' });
            $(info.el).find(titleSel + ' i').css({ color: '#fff', fontSize: info.view.type === 'listMonth' ? '1rem' : '0.875rem' });
        }
    });

    calendar.render();

    // ── Shared drag/resize update ─────────────────────────────────────────────
    function doCalendarUpdate(fcEvent, onSuccess, onError) {
        var startDate = moment(fcEvent.start).format('YYYY-MM-DD');
        var endDate   = fcEvent.end ? moment(fcEvent.end).format('YYYY-MM-DD') : startDate;
        if (moment(endDate).isBefore(startDate)) endDate = startDate;

        $.ajax({
            type: 'POST',
            url: makeUpdateUrl(fcEvent.id),
            data: {
                _token:      '{{ csrf_token() }}',
                description: fcEvent.title,
                start_date:  startDate,
                end_date:    endDate,
                start_time:  fcEvent.extendedProps.start_time || '07:00',
                end_time:    fcEvent.extendedProps.end_time   || '22:00',
                all_day:     fcEvent.allDay ? 1 : 0,
                bg_color:    fcEvent.extendedProps.bg_color
            },
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    if (typeof onSuccess === 'function') onSuccess();
                } else {
                    toastr.error(data.error || 'Unexpected response.', 'Error');
                    if (typeof onError === 'function') onError();
                }
            },
            error: function (xhr, status) { handleAjaxError(xhr, status, onError); }
        });
    }

    // ── Form helpers ──────────────────────────────────────────────────────────
    function resetForm() {
        $('#form-event')[0].reset();
        $('#event-id').val('');
        var today = moment().format('YYYY-MM-DD');
        $('#event-start-date').val(today);
        $('#event-end-date').val(today);
        $('#event-start-time').val('07:00');
        $('#event-end-time').val('22:00');
        $('#event-all-day').prop('checked', false);
        $('#event-time-container').show();
    }

    function getFormData() {
        var data = $('#form-event').serializeArray().filter(function (i) { return i.name !== 'all_day'; });
        data.push({ name: 'all_day', value: $('#event-all-day').prop('checked') ? 1 : 0 });
        return data;
    }

    // ── All-day toggle ────────────────────────────────────────────────────────
    $('#event-all-day').on('change', function () {
        var checked = $(this).prop('checked');
        $('#event-time-container').toggle(!checked);
        if (!checked) {
            $('#event-start-time').val('07:00');
            $('#event-end-time').val('22:00');
        }
    });

    $('#event-start-date').on('change', function () { $('#event-end-date').val($(this).val()); });

    $('#event-end-date').on('change', function () {
        var start = $('#event-start-date').val();
        if (start && $(this).val() && moment($(this).val()).isBefore(start)) $(this).val(start);
    });

    // ── Toolbar buttons ───────────────────────────────────────────────────────
    $('#btn-new-event').on('click', function (e) {
        e.preventDefault();
        resetForm();
        $('#btn-delete-event').hide();
        $('#modal-title').text('Add New Event');
        $('#event-modal').modal('show');
    });

    $('#btn-view-all-events').on('click', function (e) {
        e.preventDefault();
        window.location.href = urlTable;
    });

    $('#btn-refresh-calendar').on('click', function (e) {
        e.preventDefault();
        calendar.refetchEvents();
    });

    // ── Save (add or edit) ────────────────────────────────────────────────────
    $('#form-event').on('submit', function (e) {
        e.preventDefault();
        var self   = $('#btn-save-event');
        var isEdit = $('#event-id').val() !== '';
        var url    = isEdit ? makeUpdateUrl($('#event-id').val()) : urlCreate;

        self.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: url,
            data: getFormData(),
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var ev        = data.event;
                    var startTime = ev.extendedProps ? ev.extendedProps.start_time : null;
                    var endTime   = ev.extendedProps ? ev.extendedProps.end_time   : null;
                    var bgColor   = ev.extendedProps ? ev.extendedProps.bg_color   : (ev.classNames ? ev.classNames[0] : '');

                    if (isEdit) {
                        var calEv = calendar.getEventById(String(ev.id));
                        if (calEv) {
                            calEv.setProp('title', ev.title);
                            calEv.setProp('classNames', ev.classNames);
                            calEv.setExtendedProp('bg_color',   bgColor);
                            calEv.setExtendedProp('start_time', startTime);
                            calEv.setExtendedProp('end_time',   endTime);
                            calEv.setStart(ev.start);
                            if (ev.end) calEv.setEnd(ev.end);
                            calEv.setAllDay(ev.allDay);
                        }
                    } else {
                        var existing = calendar.getEventById(String(ev.id));
                        if (existing) existing.remove();
                        calendar.addEvent({
                            id:            String(ev.id),
                            title:         ev.title,
                            start:         ev.start,
                            end:           ev.end,
                            allDay:        ev.allDay,
                            classNames:    ev.classNames,
                            extendedProps: { start_time: startTime, end_time: endTime, bg_color: bgColor }
                        });
                    }
                    $('#event-modal').modal('hide');

                } else if (data.status === 422) {
                    var msg = data.errors ? data.errors.join('<br>') : (data.error || 'Validation failed.');
                    toastr.error(msg, 'Validation Error');
                } else {
                    toastr.error(data.error || 'Unexpected response.', 'Error');
                }
            },
            error: handleAjaxError
        });
    });

    // ── Delete (from inside edit modal) ──────────────────────────────────────
    $('#btn-delete-event').on('click', function (e) {
        e.preventDefault();
        var eventId = $('#event-id').val();
        if (!eventId) { toastr.error('No event selected.', 'Error'); return; }
        $('#event-modal').modal('hide');
        $('#displayDeleteEventLabel').html($('#event-description').val());
        $('#deleteEventId').val(eventId);
        $('#deleteEventModal').modal('show');
    });

    $('#keepEventBtn').on('click', function (e) {
        e.preventDefault();
        toastr.info('Your event is safe', 'Great!');
        $('#deleteEventModal').modal('hide');
    });

    $('#submitDeleteEventBtn').on('click', function (e) {
        e.preventDefault();
        var self    = $(this);
        var eventId = $('#deleteEventId').val();
        self.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: makeDeleteUrl(eventId),
            data: { _token: '{{ csrf_token() }}' },
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var calEv = calendar.getEventById(String(eventId));
                    if (calEv) calEv.remove();
                    $('#deleteEventModal').modal('hide');
                } else {
                    toastr.error(data.error || 'Could not delete event.', 'Error');
                }
            },
            error: handleAjaxError
        });
    });

});
</script>
@endsection





