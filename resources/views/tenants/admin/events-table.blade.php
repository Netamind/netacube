@extends('tenants.admin.dashboard')
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

.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0);
  color: #fff;
}

.card-body {
  padding: 0 1.5rem 1.5rem 1.5rem !important;
}

.card-header .btn-light {
  height: 28px;
  padding: 0 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.card-header .btn-light:hover {
  background-color: #f8f9fa;
  transition: background-color 0.2s ease-in-out;
}

.card-header .btn-light.text-primary:hover i,
.card-header .btn-light.text-danger:hover i {
  color: #0a58ca;
}

#deleteSelectedBtn {
  height: 28px;
  padding: 0 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  position: relative;
}

#deleteSelectedBtn .badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background-color: #dc3545;
  color: #fff;
  font-size: 12px;
  padding: 2px 6px;
  border-radius: 50%;
  display: none;
}

#deleteSelectedBtn:hover {
  background-color: #f8f9fa;
}

.card {
  border: none;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  border-radius: 10px;
}

.card-header h4 {
  color: #fff;
  font-weight: 600;
  margin-bottom: 0;
  display: flex;
  align-items: center;
}

.card-header h4 i {
  margin-right: 0.25rem;
}

.card-body {
  padding: 0 1.5rem 1.5rem 1.5rem;
}
</style>

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height: 8px; transform: rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">

<!-- start page title -->
<div class="row mb-3">
</div>
<!-- end page title -->

<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<h4 class="header-title mb-0">
<i class="ri-calendar-event-line"></i> Event Management
</h4>
<div class="d-flex align-items-center">
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="deleteSelectedBtn" title="Selected Actions"><i class="ri-checkbox-circle-line"></i><span class="badge" id="selectedCount">0</span></a>
    <a href="{{ route('master.events') }}" class="btn btn-light text-primary fs-16 mx-1" title="Back to Calendar"><i class="ri-arrow-go-back-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn" title="Add new event"><i class="ri-add-circle-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info"><i class="ri-information-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download options"><i class="ri-download-line"></i></a>
</div>
 <?php
    $maintableTitle = "Events";
    $events = DB::table('events')->where('user_id', Auth::user()->id)->get();
   ?>
</div>
<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
        <thead style="background-color:#e2e2e9">
        <tr>
            <th><input type="checkbox" id="selectAll">&nbsp;&nbsp;Description</th>
            <th style="text-align:center">Start Date</th>
            <th style="text-align:center">End Date</th>
            <th style="text-align:center">Time</th>
            <th style="text-align:center">Action</th>
        </tr>
        </thead>
        <tbody id="tbody">
         @foreach($events as $event)
            <?php $row = "row".$event->id ?>
            <tr id="{{$row}}">
            <td><input type="checkbox" class="selectRow" value="{{ $event->id }}" data-row-id="{{ $row }}">&nbsp;{{ $event->description }}</td>
            <td style="text-align:center">{{ $event->start_date }}</td>
            <td style="text-align:center">{{ $event->end_date }}</td>
            <td style="text-align:center">
                @if($event->all_day)
                All Day
                @else
                {{ $event->start_time ?: '07:00' }} - {{ $event->end_time ?: '22:00' }}
                @endif
            </td>
            <td style="text-align:center">
                <a href="#" 
                   class="editDataBtn"
                   editId="{{ $event->id }}"
                   editRow="{{ $row }}"
                   editDescription="{{ $event->description }}"
                   editBgColor="{{ $event->bg_color }}"
                   editStartDate="{{ $event->start_date }}"
                   editEndDate="{{ $event->end_date }}"
                   editStartTime="{{ $event->start_time ?: '07:00' }}"
                   editEndTime="{{ $event->end_time ?: '22:00' }}"
                   editAllDay="{{ $event->all_day }}">
                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;color:blue2"></i>
                </a> 
                <a href="#" 
                   class="deleteDataBtn" 
                   deleteLabel="{{ $event->description }}" 
                   deleteId="{{ $event->id }}" 
                   deleteRow="{{ $row }}">
                   <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;color:red2"></i>
               </a>
            </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>

</div>
</div>
</div>

<section>
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                 Click on respective button to download events data
                </p>
                <div class="buttons">
                </div>
            </div>
        </div>
    </div>
</section>

<section>
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Events Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Manage your events by adding, editing, or deleting them.
            </div>
        </div>
    </div>
</section>

<section>
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="newDataForm">
                    @csrf
                    <div class="mb-3">
                        <label class="control-label form-label">Event Description </label>
                        <input class="form-control" placeholder="Enter event description" type="text" name="description" id="event-description" autocomplete="off" required/>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label form-label">Start Date </label>
                                <input class="form-control" type="date" name="start_date" id="event-start-date" required />
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label form-label">End Date</label>
                                <input class="form-control" type="date" name="end_date" id="event-end-date" required />
                            </div>
                        </div>
                    </div>
                    <div id="event-time-container">
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label form-label">Start Time</label>
                                    <input class="form-control" type="time" name="start_time" id="event-start-time" value="07:00" data-require-if-not-all-day required />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label form-label">End Time</label>
                                    <input class="form-control" type="time" name="end_time" id="event-end-time" value="22:00" data-require-if-not-all-day required />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="all_day" id="event-all-day" value="1">
                            <label class="form-check-label" for="event-all-day">All Day Event</label>
                        </div>
                    </div>
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
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelDataBtn">Clear</a>
                </form>
            </div>
        </div>
    </div>
</section>

<section>
<div class="modal fade" id="singleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:350px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                <form action="#" method="post" id="singleDeleteDataForm">
                    @csrf
                    <div class="form-group">
                      <h4>Are you sure you want to delete <span id="singleDisplayDeleteLabel"></span>?</h4>  
                    </div>
                    <div class="form-group">
                        <h5>You won't be able to revert this!</h5>
                    </div>
                    <div class="form-group">
                        <input type="hidden" id="singleDeleteId" name="id">
                        <input type="hidden" id="singleDeleteRow">
                    </div>
                    <div class="form-group">
                        <a href="#" class="btn btn-danger" id="submitSingleDeleteDataBtn" style="margin-top:10px;margin-bottom:10px;margin-right:5px">Yes, Delete it</a>
                        <a href="#" class="btn btn-info" id="keepSingleDataBtn" style="margin-top:10px;margin-bottom:10px;">No, Keep it</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<section>
<div class="modal fade" id="multipleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:350px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                <form action="#" method="post" id="multipleDeleteDataForm">
                    @csrf
                    <div class="form-group">
                      <h4>Are you sure you want to delete <span id="multipleDisplayDeleteLabel"></span>?</h4>  
                    </div>
                    <div class="form-group">
                        <h5>You won't be able to revert this!</h5>
                    </div>
                    <div class="form-group">
                        <input type="hidden" id="multipleDeleteIds" name="ids[]">
                        <input type="hidden" id="multipleDeleteRows">
                    </div>
                    <div class="form-group">
                        <a href="#" class="btn btn-danger" id="submitMultipleDeleteDataBtn" style="margin-top:10px;margin-bottom:10px;margin-right:5px">Yes, Delete them</a>
                        <a href="#" class="btn btn-info" id="keepMultipleDataBtn" style="margin-top:10px;margin-bottom:10px;">No, Keep them</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<section>
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="editDataForm">
                    @csrf
                    <div class="form-group">
                        <input type="hidden" class="form-control" name="id" autocomplete="off" required id="editId">
                        <input type="hidden" class="form-control" name="editrow" autocomplete="off" required id="editRow">
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Event Description </label>
                        <input class="form-control" placeholder="Enter event description" type="text" name="description" id="editDescription" autocomplete="off" required/>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label form-label">Start Date </label>
                                <input class="form-control" type="date" name="start_date" id="editStartDate" required />
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label form-label">End Date</label>
                                <input class="form-control" type="date" name="end_date" id="editEndDate" required />
                            </div>
                        </div>
                    </div>
                    <div id="edit-time-container">
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label form-label">Start Time</label>
                                    <input class="form-control" type="time" name="start_time" id="editStartTime" value="07:00" data-require-if-not-all-day required />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label form-label">End Time</label>
                                    <input class="form-control" type="time" name="end_time" id="editEndTime" value="22:00" data-require-if-not-all-day required />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="all_day" id="editAllDay" value="1">
                            <label class="form-check-label" for="editAllDay">All Day Event</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Background Color</label>
                        <select class="form-select" name="bg_color" id="editBgColor" required>
                            <option value="bg-danger">Danger</option>
                            <option value="bg-success">Success</option>
                            <option value="bg-primary">Primary</option>
                            <option value="bg-info">Info</option>
                            <option value="bg-dark">Dark</option>
                            <option value="bg-warning">Warning</option>
                        </select>
                    </div>
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitUpdateDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelEditDataBtn">Clear</a>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
@section('scripts') 
<script>
    $(document).ready(function() {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            showMethod: 'slideDown',
            timeOut: 5000,
            allowHtml: true
        };

        function initDataTable() {
            var table = $('#maintable').DataTable({
                dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
                lengthChange: true,
                lengthMenu: [
                    [100, 250, 500, -1],
                    [100, 250, 500, "All"]
                ],
                fixedColumns: {
                    leftColumns: 1
                },
                scrollX: true,
                buttons: [
                    {
                        extend: 'excelHtml5',
                        title: @json($maintableTitle),
                        exportOptions: {
                            columns: ':visible:not(:last-child)'
                        }
                    },
                    {
                        extend: 'csvHtml5',
                        title: @json($maintableTitle),
                        exportOptions: {
                            columns: ':visible:not(:last-child)'
                        }
                    },
                    {
                        extend: 'pdfHtml5',
                        title: @json($maintableTitle),
                        exportOptions: {
                            columns: ':visible:not(:last-child)'
                        },
                        customize: function(doc) {
                            doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                        }
                    }
                ]
            });

            table.buttons().container().appendTo($('#buttonsModal .buttons'));

            // Open Modals
            $('#newDataBtn').click(function(e) {
                e.preventDefault();
                $('#newDataForm')[0].reset();
                var today = moment().format('YYYY-MM-DD');
                $('#event-start-date').val(today);
                $('#event-end-date').val(today);
                $('#event-start-time').val('07:00');
                $('#event-end-time').val('22:00');
                $('#event-all-day').prop('checked', false);
                $('#event-time-container').show();
                $('#newDataModal').modal('show');
            });

            $('#infoBtn').click(function(e) {
                e.preventDefault();
                $('#infoModal').modal('show');
            });

            $('#tableButtonsBtn').click(function(e) {
                e.preventDefault();
                $('#buttonsModal').modal('show');
            });

            // Toggle time fields
            $('#event-all-day').on('change', function() {
                $('#event-time-container').toggle(!$(this).prop('checked'));
                if ($(this).prop('checked')) {
                    $('#event-start-time').val('');
                    $('#event-end-time').val('');
                } else {
                    $('#event-start-time').val('07:00');
                    $('#event-end-time').val('22:00');
                }
            });

            $('#event-start-date').on('change', function() {
                $('#event-end-date').val($(this).val());
            });

            // === ADD NEW EVENT ===
            $('#submitDataBtn').click(function(e) {
                e.preventDefault();
                var self = $(this);
                self.prop('disabled', true);
                var form = $('#newDataForm');

                if (!$('#event-all-day').prop('checked')) {
                    if (!$('#event-start-time').val() || !$('#event-end-time').val()) {
                        toastr.error('Start time and end time are required for non-all-day events.', 'Validation Error', {
                            timeOut: 5000,
                            progressBar: true
                        });
                        self.prop('disabled', false);
                        return;
                    }
                }

                var formData = form.serializeArray();
                formData = formData.filter(function(item) {
                    return item.name !== 'all_day';
                });
                formData.push({
                    name: 'all_day',
                    value: $('#event-all-day').prop('checked') ? 1 : 0
                });

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    type: 'POST',
                    url: '{{ route("master.add.event") }}',
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
                            toastr.success(data.success, 'Success', {
                                timeOut: 5000,
                                progressBar: true
                            });

                            var event = data.event;
                            var timeDisplay = event.allDay ? 'All Day' : (event.extendedProps.start_time || '07:00') + ' - ' + (event.extendedProps.end_time || '22:00');
                            var newRow = `
                                <tr id="row${event.id}">
                                    <td><input type="checkbox" class="selectRow" value="${event.id}" data-row-id="row${event.id}">&nbsp;${event.title}</td>
                                    <td style="text-align:center">${event.start.split('T')[0]}</td>
                                    <td style="text-align:center">${event.end ? event.end.split('T')[0] : event.start.split('T')[0]}</td>
                                    <td style="text-align:center">${timeDisplay}</td>
                                    <td style="text-align:center">
                                        <a href="#" class="editDataBtn"
                                           editId="${event.id}"
                                           editRow="row${event.id}"
                                           editDescription="${event.title}"
                                           editBgColor="${event.classNames[0]}"
                                           editStartDate="${event.start.split('T')[0]}"
                                           editEndDate="${event.end ? event.end.split('T')[0] : event.start.split('T')[0]}"
                                           editStartTime="${event.extendedProps.start_time || '07:00'}"
                                           editEndTime="${event.extendedProps.end_time || '22:00'}"
                                           editAllDay="${event.allDay ? 1 : 0}">
                                           <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;color:blue2"></i>
                                        </a>
                                        <a href="#" class="deleteDataBtn" deleteLabel="${event.title}" deleteId="${event.id}" deleteRow="row${event.id}">
                                           <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;color:red2"></i>
                                        </a>
                                    </td>
                                </tr>
                            `;
                            table.row.add($(newRow)).draw(false);
                            $('#newDataModal').modal('hide');
                        } else if (data.status === 422) {
                            toastr.error(data.error || 'Validation failed.', 'Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else {
                            toastr.info('Unspecified error occurred.', 'Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        if (status === 'timeout') {
                            toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 0) {
                            toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 422) {
                            var errorPassage = '';
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                errorPassage += value + '\n';
                            });
                            toastr.error(errorPassage, 'Validation Errors', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 500) {
                            toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else {
                            toastr.error('Unspecified error occurred. Try again later.', 'Unspecified Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        }
                    }
                });
            });

            $('#cancelDataBtn').click(function(e) {
                e.preventDefault();
                $('#newDataForm')[0].reset();
                $('#newDataModal').modal('hide');
            });

            // === SINGLE DELETE ===
            $('#tbody').on('click', '.deleteDataBtn', function() {
                $('#singleDisplayDeleteLabel').html($(this).attr('deleteLabel'));
                $('#singleDeleteRow').val($(this).attr('deleteRow'));
                $('#singleDeleteId').val($(this).attr('deleteId'));
                $('#singleDeleteDataModal').modal('show');
            });

            $('#keepSingleDataBtn').click(function(e) {
                e.preventDefault();
                toastr.info('Your data is safe', 'Great!', {
                    timeOut: 5000,
                    progressBar: true
                });
                $('#singleDeleteDataModal').modal('hide');
            });

            $('#submitSingleDeleteDataBtn').click(function(e) {
                e.preventDefault();
                var self = $(this);
                self.prop('disabled', true);
                var row = $('#singleDeleteRow').val();
                var eventId = $('#singleDeleteId').val();
                var url = '{{ route("master.delete.event", ":id") }}'.replace(':id', eventId);

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    type: 'POST',
                    url: url,
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
                            toastr.success(data.success, 'Success', {
                                timeOut: 5000,
                                progressBar: true
                            });
                            $('#singleDeleteDataModal').modal('hide');
                            table.row('#' + row).remove().draw(false);
                            updateSelectedCount();
                        } else if (data.status === 422) {
                            toastr.error(data.error || 'Validation failed.', 'Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else {
                            toastr.info('Unspecified error occurred.', 'Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        if (status === 'timeout') {
                            toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 0) {
                            toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 422) {
                            var errorPassage = '';
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                errorPassage += value + '\n';
                            });
                            toastr.error(errorPassage, 'Validation Errors', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 500) {
                            toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else {
                            toastr.error('Unspecified error occurred. Try again later.', 'Unspecified Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        }
                    }
                });
            });

            // === EDIT EVENT ===
            $('#tbody').on('click', '.editDataBtn', function() {
                $('#editRow').val($(this).attr('editRow'));
                $('#editId').val($(this).attr('editId'));
                $('#editDescription').val($(this).attr('editDescription'));
                $('#editBgColor').val($(this).attr('editBgColor'));
                $('#editStartDate').val($(this).attr('editStartDate'));
                $('#editEndDate').val($(this).attr('editEndDate'));
                $('#editStartTime').val($(this).attr('editStartTime') || '07:00');
                $('#editEndTime').val($(this).attr('editEndTime') || '22:00');
                var allDay = $(this).attr('editAllDay') == 1;
                $('#editAllDay').prop('checked', allDay);
                $('#edit-time-container').toggle(!allDay);
                $('#editDataModal').modal('show');
            });

            $('#editAllDay').on('change', function() {
                $('#edit-time-container').toggle(!$(this).prop('checked'));
                if ($(this).prop('checked')) {
                    $('#editStartTime').val('');
                    $('#editEndTime').val('');
                } else {
                    $('#editStartTime').val('07:00');
                    $('#editEndTime').val('22:00');
                }
            });

            $('#submitUpdateDataBtn').click(function(e) {
                e.preventDefault();
                var self = $(this);
                self.prop('disabled', true);
                var form = $('#editDataForm');

                if (!$('#editAllDay').prop('checked')) {
                    if (!$('#editStartTime').val() || !$('#editEndTime').val()) {
                        toastr.error('Start time and end time are required for non-all-day events.', 'Validation Error', {
                            timeOut: 5000,
                            progressBar: true
                        });
                        self.prop('disabled', false);
                        return;
                    }
                }

                var eventId = $('#editId').val();
                var row = $('#editRow').val();
                var formData = form.serializeArray();
                formData = formData.filter(function(item) {
                    return item.name !== 'all_day';
                });
                formData.push({
                    name: 'all_day',
                    value: $('#editAllDay').prop('checked') ? 1 : 0
                });

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    type: 'POST',
                    url: '{{ route("master.update.event", ":id") }}'.replace(':id', eventId),
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
                            toastr.success(data.success, 'Success', {
                                timeOut: 5000,
                                progressBar: true
                            });
                            $('#editDataModal').modal('hide');
                            var event = data.event;
                            var timeDisplay = event.allDay ? 'All Day' : (event.extendedProps.start_time || '07:00') + ' - ' + (event.extendedProps.end_time || '22:00');
                            var updatedRow = `
                                <tr id="${row}">
                                    <td><input type="checkbox" class="selectRow" value="${event.id}" data-row-id="${row}">&nbsp;${event.title}</td>
                                    <td style="text-align:center">${event.start.split('T')[0]}</td>
                                    <td style="text-align:center">${event.end ? event.end.split('T')[0] : event.start.split('T')[0]}</td>
                                    <td style="text-align:center">${timeDisplay}</td>
                                    <td style="text-align:center">
                                        <a href="#" class="editDataBtn"
                                           editId="${event.id}"
                                           editRow="${row}"
                                           editDescription="${event.title}"
                                           editBgColor="${event.classNames[0]}"
                                           editStartDate="${event.start.split('T')[0]}"
                                           editEndDate="${event.end ? event.end.split('T')[0] : event.start.split('T')[0]}"
                                           editStartTime="${event.extendedProps.start_time || '07:00'}"
                                           editEndTime="${event.extendedProps.end_time || '22:00'}"
                                           editAllDay="${event.allDay ? 1 : 0}">
                                           <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;color:blue2"></i>
                                        </a>
                                        <a href="#" class="deleteDataBtn" deleteLabel="${event.title}" deleteId="${event.id}" deleteRow="${row}">
                                           <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;color:red2"></i>
                                        </a>
                                    </td>
                                </tr>
                            `;
                            table.row('#' + row).remove();
                            table.row.add($(updatedRow)).draw(false);
                            updateSelectedCount();
                        } else if (data.status === 422) {
                            toastr.error(data.error || 'Validation failed.', 'Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else {
                            toastr.info('Unspecified error occurred.', 'Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        if (status === 'timeout') {
                            toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 0) {
                            toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 422) {
                            var errorPassage = '';
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                errorPassage += value + '\n';
                            });
                            toastr.error(errorPassage, 'Validation Errors', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 500) {
                            toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else {
                            toastr.error('Unspecified error occurred. Try again later.', 'Unspecified Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        }
                    }
                });
            });

            $('#cancelEditDataBtn').click(function(e) {
                e.preventDefault();
                $('#editDataForm')[0].reset();
                $('#editDataModal').modal('hide');
            });

            // === BULK DELETE ===
            $('#deleteSelectedBtn').click(function() {
                var selected = [];
                var selectedRows = [];
                $('.selectRow:checked').each(function() {
                    selected.push($(this).val());
                    selectedRows.push($(this).data('row-id'));
                });
                if (selected.length === 0) {
                    toastr.warning('No events selected.', 'Warning', {
                        timeOut: 5000,
                        progressBar: true
                    });
                    return;
                }
                var count = selected.length;
                $('#multipleDisplayDeleteLabel').html('the selected ' + count + ' event' + (count > 1 ? 's' : ''));
                $('#multipleDeleteIds').val(selected.join(','));
                $('#multipleDeleteRows').val(selectedRows.join(','));
                $('#multipleDeleteDataModal').modal('show');
            });

            $('#keepMultipleDataBtn').click(function(e) {
                e.preventDefault();
                toastr.info('Your data is safe', 'Great!', {
                    timeOut: 5000,
                    progressBar: true
                });
                $('#multipleDeleteDataModal').modal('hide');
            });

            $('#submitMultipleDeleteDataBtn').click(function(e) {
                e.preventDefault();
                var self = $(this);
                self.prop('disabled', true);
                var ids = $('#multipleDeleteIds').val().split(',');
                var rows = $('#multipleDeleteRows').val().split(',');

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    type: 'POST',
                    url: '{{ route("master.bulk.delete.events") }}',
                    data: { ids: ids, _token: '{{ csrf_token() }}' },
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
                            toastr.success(data.success, 'Success', {
                                timeOut: 5000,
                                progressBar: true
                            });
                            for (var i = 0; i < rows.length; i++) {
                                table.row('#' + rows[i]).remove();
                            }
                            table.draw(false);
                            updateSelectedCount();
                            $('#multipleDeleteDataModal').modal('hide');
                        } else if (data.status === 422) {
                            toastr.error(data.error || 'Validation failed.', 'Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else {
                            toastr.info('Unspecified error occurred.', 'Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        if (status === 'timeout') {
                            toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 0) {
                            toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 422) {
                            var errorPassage = '';
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                errorPassage += value + '\n';
                            });
                            toastr.error(errorPassage, 'Validation Errors', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 500) {
                            toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else {
                            toastr.error('Unspecified error occurred. Try again later.', 'Unspecified Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        }
                    }
                });
            });

            // Select All & Count
            $('#selectAll').click(function() {
                $('.selectRow').prop('checked', this.checked);
                updateSelectedCount();
            });

            $('#tbody').on('click', '.selectRow', function() {
                updateSelectedCount();
            });

            function updateSelectedCount() {
                var count = $('.selectRow:checked').length;
                var badge = $('#selectedCount');
                badge.text(count);
                badge.toggle(count > 0);
            }
        }

        initDataTable();
    });
</script>
@endsection