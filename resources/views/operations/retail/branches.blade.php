@extends('operations.retail.dashboard')
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
 Retail Branches
</h4>
<div class="d-flex align-items-center">
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info"><i class="ri-information-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download options"><i class="ri-download-line"></i></a>
</div>
<?php
    $maintableTitle = "Retail Branches";
    $branches = DB::connection('tenant')->table('branches')->where('sector', 'Retail')->get();
?>
</div>
<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
    <thead style="background-color:#e2e2e9">
    <tr>
        <th>Branch Name</th>
        <th style="text-align:center">Category</th>
        <th style="text-align:center">Shop Values</th>
        <th style="text-align:center">No. of Employees</th>
    </tr>
    </thead>
    <tbody id="tbody">
    @foreach($branches as $branch)
        <?php $row = "row".$branch->id ?>
        <tr id="{{ $row }}">
            <td>{{ $branch->name }}</td>
            <td style="text-align:center">
                {{ DB::connection('tenant')->table('categories')->where('id', $branch->category)->value('category') }}
            </td>
            <td style="text-align:center">—</td>
            <td style="text-align:center">—</td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
</div>

</div>
</div>
</div>

<!-- Modals -->
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Retail Branches</h5>
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

        $('#infoBtn').click(function(e) {
            e.preventDefault();
            $('#infoModal').modal('show');
        });

        $('#tableButtonsBtn').click(function(e) {
            e.preventDefault();
            $('#buttonsModal').modal('show');
        });
    }

    initDataTable();
});
</script>
@endsection