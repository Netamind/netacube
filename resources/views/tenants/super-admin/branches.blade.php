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

<div class="row mb-3"></div>

<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<h4 class="header-title mb-0">
<i class="ri-building-line"></i> Branch Management
</h4>
<div class="d-flex align-items-center">
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info"><i class="ri-information-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download options"><i class="ri-download-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn" title="Add new branch"><i class="ri-add-circle-line"></i></a>
</div>
<?php
    $maintableTitle = "Branches";
    $branches = DB::connection('tenant')->table('branches')->get();
?>
</div>
<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
    <thead style="background-color:#e2e2e9">
    <tr>
        <th>Branch Name</th>
        <th style="text-align:center">Sector</th>
        <th style="text-align:center">Category</th>
        <th style="text-align:center">Status</th>
        <th style="text-align:center">Settings</th>
    </tr>
    </thead>
    <tbody id="tbody">
    @foreach($branches as $branch)
        <?php $row = "row".$branch->id ?>
        <tr id="{{ $row }}">
            <td>{{ $branch->name }}</td>
            <td style="text-align:center">{{ $branch->sector }}</td>
            <td style="text-align:center">{{ $branch->category }}</td>
            <td style="text-align:center">
                <span class="badge bg-{{ $branch->status == 'active' ? 'success' : ($branch->status == 'inactive' ? 'warning' : 'secondary') }}">
                    {{ ucfirst($branch->status) }}
                </span>
            </td>
            <td style="text-align:center">
                <a href="{{ route('tenant.super.admin.branch.details') }}?id={{ $branch->id }}" title="Branch Details">
                    <i class="ri-settings-4-line text-primary" style="font-weight:bold;font-size:20px;"></i>
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

<!-- Modals (kept identical to event example structure) -->
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Branch Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Manage your branches by adding new ones or viewing details.<br>
                Click the settings icon to see full branch information.
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Click on respective button to download branches data</p>
                <div class="buttons"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="newDataForm">
                    @csrf
                    <div class="mb-3">
                        <label class="control-label form-label">Branch Name <span class="text-danger">*</span></label>
                        <input class="form-control" placeholder="Enter branch name" type="text" name="name" id="branch-name" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Physical Address</label>
                        <textarea class="form-control" placeholder="Enter full address" name="address" id="branch-address" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">City</label>
                        <input class="form-control" type="text" name="city" id="branch-city" placeholder="e.g. Lilongwe"/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Phone Number</label>
                        <input class="form-control" type="text" name="phone" id="branch-phone" placeholder="e.g. +265 999 123 456"/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Email</label>
                        <input class="form-control" type="email" name="email" id="branch-email" placeholder="branch@example.com"/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Sector <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="sector" id="branch-sector" required placeholder="e.g. Retail"/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Category <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="category" id="branch-category" required placeholder="e.g. Supermarket"/>
                    </div>
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelDataBtn">Clear</a>
                </form>
            </div>
        </div>
    </div>
</div>

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

        // Open add modal
        $('#newDataBtn').click(function(e) {
            e.preventDefault();
            $('#newDataForm')[0].reset();
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

        // === ADD NEW BRANCH ===
        $('#submitDataBtn').click(function(e) {
            e.preventDefault();
            var self = $(this);
            self.prop('disabled', true);
            var form = $('#newDataForm');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                type: 'POST',
                url: '{{ route("tenant.super.admin.branch.insert") }}',
                data: form.serialize(),
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
                        toastr.success(data.success || 'Branch created successfully', 'Success', {
                            timeOut: 5000,
                            progressBar: true
                        });

                        var b = data.branch;
                        var statusBadge = b.status == 'active' ? 'success' : (b.status == 'inactive' ? 'warning' : 'secondary');
                        var statusText = b.status.charAt(0).toUpperCase() + b.status.slice(1);

                        var newRow = `
                            <tr id="row${b.id}">
                                <td>${b.name}</td>
                                <td style="text-align:center">${b.sector}</td>
                                <td style="text-align:center">${b.category}</td>
                                <td style="text-align:center">
                                    <span class="badge bg-${statusBadge}">${statusText}</span>
                                </td>
                                <td style="text-align:center">
                                    <a href="{{ route('tenant.super.admin.branch.details', '') }}/${b.id}" title="Branch Details">
                                        <i class="ri-settings-4-line text-primary" style="font-weight:bold;font-size:20px;"></i>
                                    </a>
                                </td>
                            </tr>`;

                        table.row.add($(newRow)).draw(false);
                        $('#newDataModal').modal('hide');
                    } else if (data.status === 422) {
                        var errorPassage = '';
                        $.each(data.errors || {}, function(key, value) {
                            errorPassage += value + '<br>';
                        });
                        toastr.error(errorPassage || 'Validation failed.', 'Error', {
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
                        $.each(xhr.responseJSON.errors || {}, function(key, value) {
                            errorPassage += value + '<br>';
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
    }

    initDataTable();
});
</script>
@endsection