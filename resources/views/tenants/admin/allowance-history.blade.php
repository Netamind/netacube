@extends('tenants.admin.dashboard')
@section('content')
@php
    $history = DB::connection('tenant')
        ->table('employee_allowance_history')
        ->join('users', 'users.id', '=', 'employee_allowance_history.employee_id')
        ->select('employee_allowance_history.*', 'users.name as employee_name')
        ->orderBy('employee_allowance_history.created_at', 'desc')
        ->get();
@endphp

<style>
.dt-buttons .btn {
  background: transparent !important; background-image: none !important;
  box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }
.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff;
}
.card-body { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card-header .btn-light {
  height: 28px; padding: 0 10px;
  display: flex; align-items: center; justify-content: center; line-height: 1;
}
.card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color 0.2s ease-in-out; }
.card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; }
table.dataTable.fixedHeader-floating,
table.dataTable.fixedHeader-locked { background: #fff !important; border-bottom: none !important; }
table.dataTable thead th.fixedHeader-floating { background: #e2e2e9 !important; }
#historytable td.num { text-align:center; font-variant-numeric: tabular-nums; font-size:13px; }
#historytable th.num { text-align:center; }
.badge-reason { background:#eef0fc; color:#4B5EBD; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600; }
</style>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <i class="ri-history-line me-1"></i> Allowance Change History
    </h4>
    <div class="d-flex align-items-center">
      <a href="{{ route('tenant.admin.hr.allowances', ['tenantName' => request()->route('tenantName')]) }}"
         class="btn btn-light text-primary fs-16 mx-1" title="Back to Allowances">
        <i class="ri-arrow-left-line"></i>
      </a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Export Table">
        <i class="ri-table-line"></i>
      </a>
    </div>
    <?php $tableTitle = "Allowance Change History"; ?>
  </div>

  <div class="card-body">
    <table id="historytable" class="table table-sm table-striped row-border order-column w-100">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Employee</th>
          <th class="num">Housing</th>
          <th class="num">Transport</th>
          <th class="num">Medical</th>
          <th class="num">Meal</th>
          <th class="num">Other Rec.</th>
          <th class="num">Acting</th>
          <th class="num">Commission</th>
          <th class="num">Other Var.</th>
          <th style="text-align:center">Effective From</th>
          <th style="text-align:center">Change Reason</th>
          <th style="text-align:center">Changed By</th>
          <th style="text-align:center">Recorded At</th>
        </tr>
      </thead>
      <tbody>
        @foreach($history as $h)
        <tr>
          <td><strong>{{ $h->employee_name }}</strong></td>
          <td class="num">{{ number_format($h->housing_allowance,         2) }}</td>
          <td class="num">{{ number_format($h->transport_allowance,       2) }}</td>
          <td class="num">{{ number_format($h->medical_allowance,         2) }}</td>
          <td class="num">{{ number_format($h->meal_allowance,            2) }}</td>
          <td class="num">{{ number_format($h->other_recurring_allowance, 2) }}</td>
          <td class="num">{{ number_format($h->acting_allowance,          2) }}</td>
          <td class="num">{{ number_format($h->commissions,               2) }}</td>
          <td class="num">{{ number_format($h->other_variable_allowance,  2) }}</td>
          <td style="text-align:center">
            {{ \Carbon\Carbon::parse($h->effective_from)->format('d M Y') }}
          </td>
          <td style="text-align:center">
            @if($h->change_reason)
              <span class="badge-reason">{{ $h->change_reason }}</span>
            @else
              <span class="text-muted">—</span>
            @endif
          </td>
          <td style="text-align:center">{{ $h->changed_by ?? '—' }}</td>
          <td style="text-align:center">
            {{ $h->created_at ? \Carbon\Carbon::parse($h->created_at)->format('d M Y H:i') : '—' }}
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

</div>
</div></div></div>


{{-- EXPORT MODAL --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Download</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p class="mb-2">Click a button to download history data.</p>
      <div class="buttons"></div>
    </div>
  </div></div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    var tableTitle = @json($tableTitle);

    var table = $('#historytable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        fixedColumns: { left: 1 },
        scrollX: true,
        order: [[12, 'desc']],
        buttons: [
            { extend: 'excelHtml5', title: tableTitle, exportOptions: { columns: ':visible' } },
            { extend: 'csvHtml5',   title: tableTitle, exportOptions: { columns: ':visible' } },
            { extend: 'pdfHtml5',   title: tableTitle, exportOptions: { columns: ':visible' },
              orientation: 'landscape', pageSize: 'A4',
              customize: function(doc) {
                  doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
              }
            }
        ]
    });
    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    $('#tableButtonsBtn').click(function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

});
</script>
@endsection