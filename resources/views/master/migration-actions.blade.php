{{-- resources/views/master/migration-actions.blade.php --}}
@extends('master.dashboard')
@section('content')
<style>
    .card-header {
        padding: 0.5rem 1.5rem !important;
        background: linear-gradient(to right, #4B5EBD, #576CC0);
        color: #fff;
    }
    .card-header .btn-light {
        height: 28px;
        width: 36px;
        padding: 0 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        border-radius: 6px;
    }
    .card-header .btn-light:hover {
        background-color: #f8f9fa !important;
    }
    .card-header .btn-light:hover i {
        color: #0d6efd !important;
    }
    .card {
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-radius: 10px;
        overflow: hidden;
    }
    .card-body {
        padding: 1.8rem !important;
    }

    .info-table {
        width: 100%;
        background: transparent;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    .info-table td {
        padding: 1rem 1.2rem;
        vertical-align: middle;
    }
    .info-table td:first-child {
        background: #e9ecef;
        font-weight: 600;
        color: #212529;
        width: 280px;
        border: none !important;
    }
    .info-table td:last-child {
        background: #f1f3f5;
        color: #495057;
        border-bottom: 1px solid #ced4da;
    }
    .info-table tr:last-child td:last-child {
        border-bottom: none;
    }
    .info-table .num {
        font-size: 1.5rem;
        font-weight: 700;
    }

    /* DANGER ZONE - Only left cell gets orange-red bg */
    .danger-row td:first-child {
        background: #fee2e2 !important;
        color: #991b1b !important;
        font-weight: 700;
    }
    /* Right cell stays exactly like normal rows */
    .danger-row td:last-child {
        background: #f1f3f5 !important;
        border-bottom: 1px solid #ced4da !important;
    }
</style>


<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height: 8px; transform: rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
</div>


<div class="content-page">
    <div class="content">
        <div class="container-fluid">


        
            <?php
                use Illuminate\Support\Facades\DB;
                use Illuminate\Support\Facades\File;

                $tenant = $tenant;
                $intendedDbName = $tenant->data ?? 'tenant_'.$tenant->id;

                $migrationPath = database_path('migrations/tenant');
                $totalMigrations = is_dir($migrationPath) ? count(File::files($migrationPath)) : 0;

                // NOTE: We no longer check existence via DB::connection()->select("SHOW DATABASES...")
                // because on shared hosting (cPanel/iFastNet) the app's default DB user often
                // cannot see databases owned by other DB users via SHOW DATABASES, even though
                // they genuinely exist. Instead we attempt a real connection AS the tenant,
                // using the same db_user/password the approve flow created it with.
                // This mirrors the fix applied in InitializeTenancyByPath middleware.
                $isLocal = app()->environment('local');

                $dbExists = false;
                $dbNameToShow = 'N/A';
                $migrated = 0;
                $pending = $totalMigrations;

                try {
                    if ($isLocal) {
                        config(['database.connections.tenant.database' => $intendedDbName]);
                    } else {
                        config([
                            'database.connections.tenant.host'     => env('TENANT_DB_HOST', config('database.connections.mysql.host')),
                            'database.connections.tenant.database' => $intendedDbName,
                            'database.connections.tenant.username' => $tenant->db_user,
                            'database.connections.tenant.password' => 'binto2020',
                        ]);
                    }
                    DB::purge('tenant');
                    DB::connection('tenant')->getPdo(); // real connection attempt = real existence check

                    $dbExists = true;
                    $dbNameToShow = $intendedDbName;

                    if (DB::connection('tenant')->getSchemaBuilder()->hasTable('migrations')) {
                        $migrated = DB::connection('tenant')->table('migrations')->count();
                    }
                    $pending = $totalMigrations - $migrated;
                } catch (\Exception $e) {
                    $dbExists = false;
                    $migrated = 0;
                    $pending = $totalMigrations;
                }

                DB::purge('tenant');
            ?>

            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">
                    <i class="ri-database-2-line me-2 fs-22"></i>Migration Actions
                    </h4>
                    <div class="d-flex align-items-center">
                        <a href="#" class="btn btn-light text-primary fs-16 mx-1" title="Info" data-bs-toggle="modal" data-bs-target="#infoModal">
                            <i class="ri-information-line"></i>
                        </a>
                        <a href="{{ route('master.tenant.migrations') }}" class="btn btn-light text-primary fs-16 mx-1" title="Back">
                            <i class="ri-arrow-left-line"></i>
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table class="info-table">
                        <tr>
                            <td>Tenant</td>
                            <td><strong>{{ $tenant->business_name }}</strong></td>
                        </tr>
                        <tr>
                            <td>Database</td>
                            <td>{{ $dbExists ? $dbNameToShow : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Total Number of tables</td>
                            <td><span class="num text-primary">{{ $totalMigrations }}</span></td>
                        </tr>
                        <tr>
                            <td>Pending Migrations</td>
                            <td>
                                <span class="num {{ $pending > 0 ? 'text-danger' : 'text-success' }}">{{ $pending }}</span>

                                @if($dbExists && $pending > 0)
                                    &nbsp;&nbsp;
                                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#runModal">
                                        <i class="ri-play-circle-line"></i> Run
                                    </button>
                                @elseif(!$dbExists)
                                    &nbsp;&nbsp;
                                    <button class="btn btn-outline-secondary btn-sm" disabled title="Database does not exist yet">
                                        <i class="ri-play-circle-line"></i> Run
                                    </button>
                                    <small class="text-muted ms-2">(Approve the tenant first so that database is created)</small>
                                @endif
                            </td>
                        </tr>
                        <tr class="danger-row">
                            <td>Danger Zone</td>
                            <td>
                                <button class="btn btn-danger {{ $migrated > 0 ? '' : 'disabled' }}" data-bs-toggle="modal" data-bs-target="#refreshModal">
                                    <i class="ri-refresh-line"></i> Refresh tables
                                </button>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals (plain text for DB name) -->
<section>
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Migrations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Run Now</strong>: Execute pending migrations</p>
                <p><strong>Refresh Migrations</strong>: Drop all tables and re-run all migrations (deletes all data)</p>
            </div>
        </div>
    </div>
</div>
</section>


<!-- Run Pending Migrations Confirmation -->
<div class="modal fade" id="runModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 380px; margin: 1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4 pt-4">
                <i class="ri-play-circle-line text-success" style="font-size: 70px;"></i>
                <h4 class="mt-3">Run Pending Migrations?</h4>
                <p class="mb-1">
                    You are about to run <strong>{{ $pending }}</strong> pending migration(s) for:
                </p>
                <p class="fw-bold mb-1">{{ $tenant->business_name }}</p>
                <p class="text-muted mb-4">{{ $dbExists ? $dbNameToShow : 'N/A' }}</p>
                
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

<!-- Refresh / Reset Confirmation (Danger) -->
<div class="modal fade" id="refreshModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 420px; margin: 1.75rem auto;">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">DANGER – PERMANENT DATA LOSS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pb-4 pt-4">
                <i class="ri-error-warning-line text-danger" style="font-size: 80px;"></i>
                <h4 class="mt-3 text-danger fw-bold">This will DELETE ALL DATA</h4>
                
                <p class="mb-1 fw-bold">for tenant:</p>
                <p class="fw-bold mb-2 text-primary fs-5">{{ $tenant->business_name }}</p>
                <p class="text-muted mb-4">{{ $dbExists ? $dbNameToShow : 'N/A' }}</p>

                <p class="text-danger small mb-3">
                    This action <strong>cannot be undone</strong>.<br>
                    All tables, records, users, transactions, etc. will be permanently deleted.
                </p>

                <p class="mb-3">
                    To confirm, type exactly:
                </p>
                <p class="fw-bold text-danger mb-2" id="confirmation-text">
                    YESDELETEDATAFOR{{ strtoupper(str_replace(' ', '', $tenant->business_name)) }}
                </p>

                <input type="text" class="form-control text-center mb-3" 
                       id="confirmation-input" 
                       placeholder="Type the text above here"
                       autocomplete="off">

                <div class="d-flex justify-content-center gap-3">
                    <button class="btn btn-danger" id="confirmRefreshFinal" disabled style="min-width: 160px;">
                        Confirm Delete
                    </button>
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal" style="min-width: 140px;">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {

    const tenantId = {{ $tenant->id }};

    // ── Run Pending Migrations ───────────────────────────────────────────────
    $('#confirmRun').click(function (e) {
        e.preventDefault();
        var self = $(this); 
        self.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: "{{ route('master.tenant.migrations.run', $tenant->id) }}",
            data: { _token: '{{ csrf_token() }}' },
            timeout: 90000,
            beforeSend: function () { 
                $('#progressBar').show(); 
            },
            complete: function () { 
                $('#progressBar').hide(); 
                self.prop('disabled', false); 
            },
            success: function (data) {
                if (data.success) {
                    toastr.success(data.message || 'Migrations completed successfully!', 'Success');
                    setTimeout(() => location.reload(), 1500);
                } 
                else if (data.status === 422 || !data.success) {
                    toastr.error(data.message || 'Migration could not be completed', 'Error');
                }
                else {
                    toastr.error(data.message || 'Unexpected response from server', 'Error');
                }
            },
            error: function (xhr, status, error) {
                if (status === 'timeout') {
                    toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout');
                } 
                else if (xhr.status === 0) {
                    toastr.error('Cannot connect to server. Check your internet or firewall.', 'Connection Failed');
                } 
                else if (xhr.status === 422) {
                    var errorPassage = ''; 
                    $.each(xhr.responseJSON?.errors ?? {}, function (k, v) { errorPassage += v + '\n'; });
                    toastr.error(errorPassage || 'Validation error occurred', 'Validation Errors');
                } 
                else if (xhr.status === 500) {
                    toastr.error('Server encountered an error. Please try again later.', 'Server Error');
                } 
                else if (xhr.status === 419) {
                    toastr.error('Session expired or invalid CSRF token. Please refresh the page.', 'CSRF / Session Error');
                } 
                else {
                    toastr.error('Unexpected error (' + xhr.status + '). Please try again.', 'Error');
                }
            }
        });

        $('#runModal').modal('hide');
    });

// ── Refresh / Reset Migrations ───────────────────────────────────────────
let expectedConfirmation = "YESDELETEDATAFOR" + "{{ strtoupper(str_replace(' ', '', $tenant->business_name)) }}";

$('#refreshModal').on('shown.bs.modal', function () {
    $('#confirmation-input').val('').focus();
    $('#confirmRefreshFinal').prop('disabled', true);
});

$('#confirmation-input').on('input', function () {
    let inputVal = $(this).val().trim().toUpperCase();
    $('#confirmRefreshFinal').prop('disabled', inputVal !== expectedConfirmation);
});

$('#confirmRefreshFinal').click(function (e) {
    e.preventDefault();
    var self = $(this); 
    self.prop('disabled', true).text('Deleting...');

    $.ajax({
        type: 'POST',
        url: "{{ route('master.tenant.migrations.reset', $tenant->id) }}",
        data: { 
            _token: '{{ csrf_token() }}',
            confirmation: $('#confirmation-input').val().trim()   // optional - can be checked backend too
        },
        timeout: 120000,
        beforeSend: function () { 
            $('#progressBar').show(); 
        },
        complete: function () { 
            $('#progressBar').hide(); 
            self.prop('disabled', false).text('Confirm Delete'); 
        },
        success: function (data) {
            if (data.success) {
                toastr.success(data.success || 'Database has been reset and migrations re-run!', 'Success');
                setTimeout(() => location.reload(), 2000);
            } else {
                toastr.error(data.error || 'Reset / re-migration failed', 'Error');
            }
        },
        error: function (xhr, status, error) {
            if (status === 'timeout') {
                toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout');
            } else if (xhr.status === 0) {
                toastr.error('Cannot connect to server. Check your internet or firewall.', 'Connection Failed');
            } else if (xhr.status === 422) {
                var errorPassage = ''; 
                $.each(xhr.responseJSON?.errors ?? {}, function (k, v) { errorPassage += v + '\n'; });
                toastr.error(errorPassage || 'Validation error occurred', 'Validation Errors');
            } else if (xhr.status === 500) {
                toastr.error('Server encountered an error. Please try again later.', 'Server Error');
            } else if (xhr.status === 419) {
                toastr.error('Session expired or invalid CSRF token. Please refresh the page.', 'CSRF / Session Error');
            } else {
                toastr.error('Unexpected error (' + xhr.status + '). Please try again.', 'Error');
            }
        }
    });

    $('#refreshModal').modal('hide');
});

// Disable old button or hide it if you want
$('#confirmRefresh').hide();  // ← optional: hide original button since we use new one

});
</script>
@endsection