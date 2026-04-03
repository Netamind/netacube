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
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
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

    .card-header .btn-light.text-primary:hover i {
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
</style>

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height: 8px; transform: rotate(180deg); display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
</div>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <div class="row mb-3"></div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">
                        <i class="ri-shield-user-line"></i> System Roles
                    </h4>
                    <div class="d-flex align-items-center">
                       <!-- <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download options">
                            <i class="ri-download-line"></i>
                        </a>-->
                        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="managementBtn" title="Info">
                            <i class="ri-information-line"></i>
                        </a>
                    </div>
                    <?php
                    $maintableTitle = "System Roles";
                    $roles = DB::connection('tenant')->table('roles')->get();
                    ?>
                </div>

                <div class="card-body">
                    <div class="table-responsive-sm">
                        <table id="maintable" class="table table-sm table-striped row-border order-column w-100">
                            <thead style="background-color:#e2e2e9">
                                <tr>
                                    <th>Role</th>
                                    <th style="text-align:center">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $item)
                                <tr>
                                    <td>{{ $item->role }}</td>
                                    <td style="text-align:center">{{ $item->description }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- Modals -->
<!-- ============================================================== -->

<section>
    <div class="modal fade" id="managementModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">System Roles</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
    
                    <p>
                        As an administrator, you <strong>do not have the authority</strong> to create, edit, or delete roles within the system.
                    </p>
                    <p>
                        The available roles are <strong>pre-defined and managed by the development team</strong>.
                    </p>
                    <p>
                        If you identify a need for an additional role, please <strong>contact the development team through the email given below</strong>. We will be happy to assist you in adding the necessary role. 
                        
                    </p>
                    <p>Email: 
                    <a href="mailto:info@netamind.com" class="text-primary">
                        info@netamind.com
                    </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

@section('scripts')
<script>
    $(document).ready(function() {
        var table = $('#maintable').DataTable({
            dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
            lengthChange: true,
            lengthMenu: [[100, 250, 500, -1], [100, 250, 500, "All"]],
            fixedColumns: { "leftColumns": 1 },
            scrollX: true,
            buttons: [
                {
                    extend: 'excelHtml5',
                    title: @json($maintableTitle),
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'csvHtml5',
                    title: @json($maintableTitle),
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'pdfHtml5',
                    title: @json($maintableTitle),
                    exportOptions: { columns: ':visible' },
                    customize: function(doc) {
                        doc.content[1].table.widths = ['*', '*'];
                        doc.content[1].table.body.forEach(function(row) {
                            row[0].alignment = 'left';
                            row[1].alignment = 'center';
                        });
                    }
                },
                {
                    extend: 'print',
                    title: @json($maintableTitle),
                    exportOptions: { columns: ':visible' }
                }
            ]
        });

        $('#managementBtn').click(function() { $('#managementModal').modal('show'); });
    });
</script>
@endsection
@endsection