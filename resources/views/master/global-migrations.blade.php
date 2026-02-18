{{-- resources/views/master/global-migrations.blade.php --}}
@extends('master.dashboard')

@section('content')
<style>
    .dt-buttons .btn {
        background: transparent !important;
        background-image: none !important;
        box-shadow: none !important;
        border-color: #5bc0de;
        color: #5bc0de;
        border-radius: 4px;
    }
    .dt-buttons .btn:hover {
        background: #5bc0de !important;
        color: #fff !important;
    }

    .card-header {
        padding: 0.5rem 1.5rem !important;
        background: linear-gradient(to right, #4B5EBD, #576CC0);
        color: #fff;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .card-header > * {
        flex-shrink: 0;
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
        transition: background-color .2s ease-in-out;
    }
    .card {
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,.1);
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
        margin-right: .25rem; 
    }

    table.dataTable.fixedHeader-floating,
    table.dataTable.fixedHeader-locked { 
        background:#fff!important; 
        border-bottom:none!important; 
    }
    table.dataTable thead th.fixedHeader-floating { 
        background:#e2e2e9!important; 
    }

    .action-btn {
        min-width: 220px;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0.5rem 1rem;
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
                        Global Migration & Schema Manager
                    </h4>
                    <div class="d-flex align-items-center">
                        <a href="#" class="btn btn-light text-primary fs-16 mx-1" data-bs-toggle="modal" data-bs-target="#infoModal" title="Info">
                            <i class="ri-information-line"></i>
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table id="globalActionsTable" class="table table-sm table-striped row-border order-column w-100">
                        <thead style="background-color:#e2e2e9">
                            <tr>
                                <th>Action</th>
                                <th>Description</th>
                                <th style="text-align:center; width:260px;">Execute</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Run Pending Migrations</strong></td>
                                <td>Executes all pending Laravel migrations on <strong>every tenant database</strong></td>
                                <td style="text-align:center">
                                    <button class="btn btn-success action-btn" data-bs-toggle="modal" data-bs-target="#runModal">
                                        <i class="ri-play-fill"></i> Run for All Tenants
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Info Modal -->
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Global Migration & Schema Manager</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This tool allows running pending migrations across <strong>all tenant databases</strong> at once.</p>
                <ul class="mb-3">
                    <li>Only applies migrations that haven't been run yet</li>
                    <li>Use carefully — especially in production</li>
                    <li>Monitor server logs after execution</li>
                </ul>
                <div class="text-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Run Pending Migrations Confirmation - exact copy as requested -->
<div class="modal fade" id="runModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 380px; margin: 1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4 pt-4">
                <i class="ri-play-circle-line text-success" style="font-size: 70px;"></i>
                <h4 class="mt-3">Run Pending Migrations?</h4>
                <p class="mb-1">
                    You are about to run pending migration(s) for <strong>all tenants</strong>
                </p>
                <p class="text-muted mb-4">This affects every tenant database</p>
                
                <button class="btn btn-success me-2" id="confirmRun" style="min-width: 140px;">
                    Yes, Run Now
                </button>
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal" style="min-width: 140px;">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
$(document).ready(function () {

    $('#globalActionsTable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        fixedColumns: { left: 1 },
        scrollX: true,
        paging: true,
        searching: true,
        info: true,
        buttons: [
            { extend: 'excelHtml5', title: 'Global Migration Actions - All Tenants' },
            { extend: 'csvHtml5',  title: 'Global Migration Actions - All Tenants' },
            { extend: 'pdfHtml5',  title: 'Global Migration Actions - All Tenants' }
        ]
    });

    $('#confirmRun').click(function (e) {
        e.preventDefault();
        var self = $(this);
        self.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: "{{ route('master.global.migrations.run-pending-all') }}",
            data: { _token: '{{ csrf_token() }}' },
            timeout: 300000,
            beforeSend: function () {
                $('#progressBar').show();
            },
            complete: function () {
                $('#progressBar').hide();
                self.prop('disabled', false);
            },
            success: function (data) {
                if (data.success) {
                    toastr.success(data.message || 'Migrations executed for all tenants', 'Success');
                } else {
                    toastr.warning(data.message || 'Operation completed with issues', 'Warning');
                }
            },
            error: function (xhr, status, error) {
                if (status === 'timeout') {
                    toastr.error('Operation timed out. Check server logs.', 'Timeout');
                } else if (xhr.status === 0) {
                    toastr.error('Connection failed. Check network.', 'Connection Error');
                } else if (xhr.status === 422) {
                    var msg = '';
                    $.each(xhr.responseJSON?.errors ?? {}, function (k, v) { msg += v + '\n'; });
                    toastr.error(msg || 'Validation error', 'Validation Errors');
                } else if (xhr.status === 500) {
                    toastr.error('Server error. Check logs.', 'Server Error');
                } else if (xhr.status === 419) {
                    toastr.error('Session expired. Refresh page.', 'Session Error');
                } else {
                    toastr.error('Unexpected error (' + xhr.status + ')', 'Error');
                }
            }
        });

        $('#runModal').modal('hide');
    });

});
</script>
@endsection