{{-- resources/views/master/migrations.blade.php --}}
@extends('master.dashboard')

@section('content')
<style>
    /* ────────────────────────────────────────────────────────────────
       Consistent styling across all master pages
       ──────────────────────────────────────────────────────────────── */
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
    }
    .card-body { padding: 0 1.5rem 1.5rem 1.5rem !important; }
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
    .card-header h4 i { margin-right: .25rem; }

    table.dataTable.fixedHeader-floating,
    table.dataTable.fixedHeader-locked { background:#fff!important; border-bottom:none!important; }
    table.dataTable thead th.fixedHeader-floating { background:#e2e2e9!important; }
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none;position:fixed;top:0;left:0;width:100%;z-index:9999">
    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width:100%"></div>
</div>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="row mb-3"></div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">
                        Tenant Migration Manager
                    </h4>
                    <div class="d-flex align-items-center">
                        <!-- Info Button with Remix Icon -->
                        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info">
                            <i class="ri-information-line"></i>
                        </a>
                        <!-- Download Button with Remix Icon -->
                        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download">
                            <i class="ri-download-line"></i>
                        </a>
                    </div>

                    <?php 
                        use Illuminate\Support\Facades\DB;
                        $tenantsRaw = DB::table('tenants')
                            ->select('id', 'business_name', 'full_name', 'data', 'db_user')
                            ->orderBy('id')
                            ->get();
                        $maintableTitle = "Tenant Migration Manager";
                    ?>
                </div>

                <div class="card-body">
                    <table id="maintable" class="table table-sm table-striped row-border order-column w-100">
                        <thead style="background-color:#e2e2e9">
                            <tr>
                                <th>Tenant</th>
                                <th style="text-align:center">Database</th>
                                <th style="text-align:center">Total Migrations</th>
                                <th style="text-align:center">Migrated</th>
                                <th style="text-align:center">Pending</th>
                                <th style="text-align:center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tbody">
                        @foreach($tenantsRaw as $tenant)
                            <?php
                                $intendedDbName = $tenant->data ?? 'tenant_'.$tenant->id;

                                $dbNameToShow   = 'N/A';
                                $totalMigrations = 0;
                                $migrated       = 0;
                                $pending        = 0;

                                $migrationPath = database_path('migrations/tenant');
                                if (is_dir($migrationPath)) {
                                    $totalMigrations = count(\File::files($migrationPath));
                                }

                                // NOTE: We no longer check existence via DB::connection()->select("SHOW DATABASES...")
                                // because on shared hosting (cPanel/iFastNet) the app's default DB user often
                                // cannot see databases owned by other DB users via SHOW DATABASES, even though
                                // they genuinely exist. Instead we attempt a real connection AS the tenant,
                                // using the same db_user/password the approve flow created it with.
                                // This mirrors the fix applied in InitializeTenancyByPath middleware.
                                $isLocal = app()->environment('local');
                                $dbExists = false;

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
                                    $pending  = $totalMigrations;
                                }

                                DB::purge('tenant');
                            ?>
                            <tr id="row{{ $tenant->id }}">
                                <td>
                                    <strong>{{ $tenant->business_name }}</strong><br>
                                    <small class="text-muted">{{ $tenant->full_name }}</small>
                                </td>
                                <td style="text-align:center;font-family:monospace;font-size:0.9em;color:#495057;">
                                    {{ $dbNameToShow }}
                                </td>
                                <td style="text-align:center">{{ $totalMigrations }}</td>
                                <td style="text-align:center"><strong>{{ $migrated }}</strong></td>
                                <td style="text-align:center">
                                    <strong class="{{ $pending > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $pending }}
                                    </strong>
                                </td>
                                <td style="text-align:center">
                                    <!-- ONLY ONE BUTTON – YOUR ORIGINAL REMIX ICON -->
                                    <a href="{{ route('master.tenant.migrations.actions', $tenant->id) }}"
                                       class="btn btn-sm btn-light border"
                                       title="Manage Migrations & Schema">
                                        <i class="ri-settings-3-line" style="font-size:18px; color:#6c757d;"></i>
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

{{-- Modals – Only Info & Download --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Download</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="mb-2">Click to download tenant migration report</p>
                <div class="buttons"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Tenant Migration Manager</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                Click the <i class="ri-settings-3-line"></i> button to manage migrations, run pending ones, reset database, or edit table structure.
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {
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
            lengthMenu: [[100, 250, 500, -1], [100, 250, 500, "All"]],
            fixedColumns: { left: 1 },
            scrollX: true,
            buttons: [
                { extend: 'excelHtml5', title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
                { extend: 'csvHtml5',  title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
                { extend: 'pdfHtml5',  title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } }
            ]
        });
        table.buttons().container().appendTo($('#buttonsModal .buttons'));
    }

    $('#infoBtn').click(e => { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').click(e => { e.preventDefault(); $('#buttonsModal').modal('show'); });

    initDataTable();
});
</script>
@endsection