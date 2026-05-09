@extends('operations.retail.dashboard')
@section('content')
@php
    $branches       = DB::connection('tenant')->table('branches')->orderBy('name')->get();
    $pref           = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();
    $selectedBranch = null;
    $selectedDate   = null;

    if ($pref && $pref->branch_id) {
        $selectedBranch = DB::connection('tenant')->table('branches')->find($pref->branch_id);
    }
    if ($pref && $pref->date) {
        $selectedDate = $pref->date;
    }

    $datesWithLogs = collect();
    if ($selectedBranch) {
        $datesWithLogs = DB::connection('tenant')
            ->table('retail_inventory_logs')
            ->where('branch_id', $selectedBranch->id)
            ->where('log_date', '>=', now()->subMonths(3)->toDateString())
            ->distinct()
            ->orderByDesc('log_date')
            ->pluck('log_date');

        if (!$selectedDate && $datesWithLogs->isNotEmpty()) {
            $selectedDate = $datesWithLogs->first();
        }
    }

    $logs       = collect();
    $summaryIn  = 0;
    $summaryOut = 0;
    $summaryNet = 0;

    $operationTypes = [
        'Inbound'            => ['StockDelivery','TransferIn','FoundStock','ReturnFromCustomer','ProductionIn','OpeningStock'],
        'Outbound'           => ['Sale','TransferOut','Damage','Expired','Usage','Theft','Wastage','Donation','ReturnToSupplier','Recall','Sample','WriteOff','Loss'],
        'Neutral/Corrective' => ['Adjustment','Recount','Reversal','Others'],
    ];

    if ($selectedBranch && $selectedDate) {
        $logs = DB::connection('tenant')
            ->table('retail_inventory_logs as ril')
            ->join('retail_base_products as rbp', 'rbp.id', '=', 'ril.product_id')
            ->join('users as u', 'u.id', '=', 'ril.user_id')
            ->where('ril.branch_id', $selectedBranch->id)
            ->where('ril.log_date',  $selectedDate)
            ->select(
                'ril.*',
                'rbp.name  as product_name',
                'rbp.code  as product_code',
                'rbp.unit  as product_unit',
                'u.name    as user_name'
            )
            ->orderByDesc('ril.log_time')
            ->get();

        foreach ($logs as $log) {
            $change = (float) $log->stock_change;
            $price  = (float) ($log->selling_price ?? 0);
            $value  = abs($change) * $price;
            if ($change > 0) $summaryIn  += $value;
            else             $summaryOut += $value;
        }
        $summaryNet = $summaryIn - $summaryOut;
    }

    $maintableTitle = 'Audit Logs — ' . ($selectedBranch->name ?? 'All') . ' — ' . ($selectedDate ?? '');
@endphp

<style>
/* ── DataTable export buttons ───────────────────────────────────────────── */
.dt-buttons .btn {
  background: transparent !important; background-image: none !important;
  box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

/* ── Card chrome ────────────────────────────────────────────────────────── */
.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff;
  border-radius: 10px 10px 0 0 !important;
}
.card-body  { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card       { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header h4 i { margin-right: 0.25rem; }
.card-header .btn-light {
  height:28px; padding:0 10px;
  display:flex; align-items:center; justify-content:center; line-height:1;
}
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

/* ── Branch select in header ────────────────────────────────────────────── */
#branchSelectHeader {
  border: none; background: transparent; color: #fff;
  font-size: 18px; font-weight: 600; cursor: pointer;
  padding: 0; outline: none; max-width: 300px;
}
#branchSelectHeader option { color: #1e293b; background: #fff; font-size: 14px; }

/* ── Selection badge ────────────────────────────────────────────────────── */
#selectionBadge {
  display: none; align-items: center; gap: 4px;
  background: rgba(255,255,255,0.18); border-radius: 20px;
  padding: 3px 10px 3px 7px; cursor: pointer;
  border: 1px solid rgba(255,255,255,0.30); transition: background .15s;
}
#selectionBadge:hover { background: rgba(255,255,255,0.28); }
#selectionBadge.visible { display: flex; }
#selectionBadge i { font-size: 15px; color: #fff; }
#selectionBadge .sel-count { font-size: 12px; font-weight: 700; color: #fff; line-height: 1; }

/* ── Bulk bar (hidden, kept for JS compatibility) ───────────────────────── */
#bulkBar { display: none !important; }

/* ── Table alignment ────────────────────────────────────────────────────── */
#maintable thead th,
table.dataTable thead th { text-align:center !important; vertical-align:middle !important; }
#maintable thead th:first-child,
table.dataTable thead th:first-child { text-align:left !important; }
#maintable tbody td,
table.dataTable tbody td { text-align:center !important; vertical-align:middle !important; }
#maintable tbody td:first-child,
table.dataTable tbody td:first-child { text-align:left !important; }

/* ── Stock change colours ───────────────────────────────────────────────── */
.change-in   { color: #16a34a; font-weight: 700; }
.change-out  { color: #dc2626; font-weight: 700; }
.change-zero { color: #94a3b8; font-weight: 600; }

/* ── Operation type badge ───────────────────────────────────────────────── */
.op-badge {
  display: inline-block; font-size: 10px; font-weight: 700;
  padding: 2px 8px; border-radius: 10px; white-space: nowrap;
}
.op-inbound  { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.op-outbound { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.op-neutral  { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.op-reversal { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }

/* ── No branch selected banner ──────────────────────────────────────────── */
.no-branch-wrap { padding: 48px 20px; text-align: center; color: #94a3b8; }
.no-branch-wrap i { font-size: 52px; display: block; margin-bottom: 12px; color: #c8d0ed; }
.no-branch-wrap h5 { color: #64748b; font-weight: 600; }

/* ── Reason cell ellipsis ────────────────────────────────────────────────── */
.reason-cell { max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }

/* ── Reversed badge ─────────────────────────────────────────────────────── */
.badge-reversed {
  background: #fef3c7; color: #92400e;
  border: 1px solid #fde68a;
  font-size: 10px; padding: 2px 7px; border-radius: 10px; font-weight: 600;
}

/* ── Modal header helpers ───────────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-danger { background:linear-gradient(135deg,#c0392b,#e74c3c); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-amber  { background:linear-gradient(135deg,#b45309,#d97706); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── View modal grid ─────────────────────────────────────────────────────── */
.view-grid { display:grid; grid-template-columns:1fr 1fr; gap:6px 14px; }
.view-item label { font-size:10px; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:2px; }
.view-item .view-val { font-size:13px; color:#1e293b; font-weight:500; }
.view-item.full { grid-column:1/-1; }

/* ── View modal section divider ─────────────────────────────────────────── */
.vw-section {
  font-size:10px; font-weight:700; text-transform:uppercase;
  letter-spacing:.8px; color:#94a3b8;
  margin: 12px 0 8px; padding-bottom:4px;
  border-bottom:1px solid #f1f5f9;
  display:flex; align-items:center; gap:5px;
}

/* ── Edit section title ─────────────────────────────────────────────────── */
.edit-section-title {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .7px; color: #6c757d;
  margin-bottom: 8px; margin-top: 4px;
  display: flex; align-items: center; gap: 5px;
}

/* ── Summary strip ──────────────────────────────────────────────────────── */
#summaryStrip {
  background: #f8f9ff; border-bottom: 1px solid #e2e6f0;
  padding: 10px 1.5rem; display: flex; gap: 10px;
  align-items: center; flex-wrap: wrap;
}
.sum-card {
  background: #fff; border-radius: 8px; padding: 8px 16px;
  display: flex; align-items: center; gap: 10px;
  border: 1px solid #e9ecef; box-shadow: 0 1px 4px rgba(0,0,0,0.05);
  min-width: 140px;
}
.sum-icon { font-size: 20px; flex-shrink: 0; }
.sum-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; }
.sum-value { font-size: 16px; font-weight: 700; line-height: 1.1; }
.sum-dl-btn {
  background: none; border: none; padding: 0 0 0 4px;
  cursor: pointer; line-height: 1; flex-shrink: 0; margin-left: auto;
  opacity: .45; transition: opacity .15s, transform .15s; display: flex; align-items: center;
}
.sum-dl-btn:hover { opacity: 1; transform: scale(1.18); }
.sum-dl-btn i { font-size: 17px; }
.sum-spacer { flex: 1; }
.sum-date-badge {
  background: linear-gradient(135deg,#4B5EBD,#576CC0);
  color: #fff; font-size: 12px; font-weight: 600;
  padding: 6px 14px; border-radius: 20px;
  display: flex; align-items: center; gap: 6px; white-space: nowrap;
  box-shadow: 0 1px 4px rgba(75,94,189,0.25);
}

/* ── Date chip picker ───────────────────────────────────────────────────── */
.date-chips-wrap { display: flex; flex-wrap: wrap; gap: 6px; }
.date-chip-btn {
  font-size: 13px; font-weight: 500; padding: 9px 14px;
  border-radius: 8px; cursor: pointer; border: 1.5px solid #d6daf0;
  background: #fff; color: #475569; transition: all .15s; white-space: nowrap;
  display: inline-flex; align-items: center; gap: 8px; width: 100%; text-align: left;
}
.date-chip-btn:hover { border-color: #4B5EBD; color: #4B5EBD; background: #eff2ff; }
.date-chip-btn.active {
  background: linear-gradient(135deg,#4B5EBD,#576CC0);
  color: #fff; border-color: transparent;
  box-shadow: 0 2px 6px rgba(75,94,189,0.30);
}
.date-chip-btn .chip-dot { width:7px; height:7px; border-radius:50%; background:#4ade80; flex-shrink:0; }
.date-chip-btn.active .chip-dot { background: rgba(255,255,255,0.7); }

/* ── Bulk section inside modal ──────────────────────────────────────────── */
.bulk-section { background:#f8f9fa; border-radius:8px; padding:12px 14px; margin-bottom:10px; }
.bulk-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6c757d; margin-bottom:10px; }

/* ── Loading overlay ────────────────────────────────────────────────────── */
#tableLoadingOverlay {
  display:none; position:absolute; inset:0;
  background:rgba(255,255,255,0.72); z-index:10;
  align-items:center; justify-content:center; border-radius:0 0 10px 10px;
}
#tableLoadingOverlay .spinner-border { color:#4B5EBD; }
#tableWrapper { position:relative; }


css/* ── DataTable empty-state message ─────────────────────────────────────── */
#maintable.dataTable tbody td.dataTables_empty {
  text-align: center !important;
}
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- ── Card header ─────────────────────────────────────────────────────── --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0" style="gap:8px;">
      <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
            id="headerBranchForm" style="margin:0;display:inline;">
        @csrf
        <input type="hidden" name="user_id" value="{{ Auth::id() }}">
        <select name="branch_id" id="branchSelectHeader"
                onchange="document.getElementById('headerBranchForm').submit()">
          <option value="" hidden>{{ $selectedBranch ? $selectedBranch->name : '— Select Branch —' }}</option>
          @foreach($branches as $b)
            <option value="{{ $b->id }}"
              {{ ($pref && $pref->branch_id == $b->id) ? 'selected' : '' }}>
              {{ $b->name }}
            </option>
          @endforeach
        </select>
      </form>
      <span style="font-size:13px;font-weight:400;opacity:.75;margin-left:2px;">— Audit Logs</span>
    </h4>

    <div class="d-flex align-items-center" style="gap:6px;">
      @if($selectedBranch)
      <div id="selectionBadge" title="Bulk actions">
        <i class="ri-checkbox-multiple-line"></i>
        <span class="sel-count" id="headerSelCount">0</span>
      </div>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="datePickerBarBtn" title="Select log date">
        <i class="ri-calendar-event-line"></i>
      </a>
      @endif
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="About Audit Logs">
        <i class="ri-information-line"></i>
      </a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download">
        <i class="ri-download-line"></i>
      </a>
    </div>
  </div>

  {{-- ── Bulk bar (hidden, kept for JS) ──────────────────────────────────── --}}
  @if($selectedBranch)
  <div id="bulkBar">
    <div id="bulkLeft">
      <button type="button" id="bulkTriggerBtn" style="display:none;">
        <i class="ri-checkbox-multiple-line"></i>
        <span id="selectedCount">0</span>
      </button>
    </div>
    <div id="bulkRight"></div>
  </div>
  @endif

  {{-- ── Summary strip ────────────────────────────────────────────────────── --}}
  <div id="summaryStrip" @if(!($selectedBranch && $logs->isNotEmpty())) style="display:none;" @endif>
    <div class="sum-card">
      <i class="ri-list-check-2 sum-icon" style="color:#0ea5e9"></i>
      <div>
        <div class="sum-label">Events</div>
        <div class="sum-value" style="color:#0ea5e9" id="sum-events">{{ $logs->count() }}</div>
      </div>
    </div>
    <div class="sum-card">
      <i class="ri-arrow-up-circle-line sum-icon" style="color:#16a34a"></i>
      <div>
        <div class="sum-label">Value Added (MWK)</div>
        <div class="sum-value" style="color:#16a34a" id="sum-in">+MWK {{ number_format($summaryIn, 2) }}</div>
      </div>
      <button type="button" class="sum-dl-btn" id="dlPositiveBtn" title="Download Added Items PDF">
        <i class="ri-file-download-line" style="color:#16a34a"></i>
      </button>
    </div>
    <div class="sum-card">
      <i class="ri-arrow-down-circle-line sum-icon" style="color:#dc2626"></i>
      <div>
        <div class="sum-label">Value Removed (MWK)</div>
        <div class="sum-value" style="color:#dc2626" id="sum-out">−MWK {{ number_format($summaryOut, 2) }}</div>
      </div>
      <button type="button" class="sum-dl-btn" id="dlNegativeBtn" title="Download Subtracted Items PDF">
        <i class="ri-file-download-line" style="color:#dc2626"></i>
      </button>
    </div>
    <div class="sum-card">
      <i class="ri-scales-3-line sum-icon" style="color:#4B5EBD"></i>
      <div>
        <div class="sum-label">Net Value (MWK)</div>
        <div class="sum-value" id="sum-net"
             style="color:{{ $summaryNet >= 0 ? '#16a34a' : '#dc2626' }}">
          {{ $summaryNet >= 0 ? '+MWK ' : '-MWK ' }}{{ number_format(abs($summaryNet), 2) }}
        </div>
      </div>
      <button type="button" class="sum-dl-btn" id="dlAllBtn" title="Download All Logs PDF">
        <i class="ri-file-download-line" style="color:#4B5EBD"></i>
      </button>
    </div>
    <div class="sum-spacer"></div>
    <div class="sum-date-badge" id="sumDateBadge"
         @if(!($selectedDate && $logs->isNotEmpty())) style="display:none;" @endif>
      <i class="ri-calendar-check-line"></i>
      <span id="sumDateText">{{ $selectedDate ? \Carbon\Carbon::parse($selectedDate)->format('d M Y') : '' }}</span>
    </div>
  </div>

  {{-- ── Table wrapper ────────────────────────────────────────────────────── --}}
  <div id="tableWrapper">
    <div id="tableLoadingOverlay">
      <div class="spinner-border" role="status" style="width:2.5rem;height:2.5rem;">
        <span class="visually-hidden">Loading…</span>
      </div>
    </div>

    <div class="card-body">

      {{-- No branch selected — shown before any branch is picked, hidden once one is --}}
      @if(!$selectedBranch)
        <div class="no-branch-wrap" id="noBranchState">
          <i class="ri-store-line"></i>
          <h5>No Branch Selected</h5>
          <p style="font-size:13px;">Select a branch from the header above to view its audit logs.</p>
        </div>
      @endif

      {{-- Table is always rendered when a branch is selected; DataTable shows its
           own "No data available in table" message when tbody is empty. --}}
      @if($selectedBranch)
      <table id="maintable"
             class="table table-sm table-striped row-border order-column w-100 mt-3">
        <thead style="background-color:#e2e2e9">
          <tr>
            <th><input type="checkbox" id="selectAll">&nbsp;&nbsp;Product Name</th>
            <th>Code</th>
            <th>Unit</th>
            <th>Type</th>
            <th>Time</th>
            <th>Price</th>
            <th>Before</th>
            <th>Change</th>
            <th>After</th>
            <th>Value</th>
            <th>Reason</th>
            <th>User</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="tbody">
          @foreach($logs as $log)
            @php
              $change        = (float) $log->stock_change;
              $changeClass   = $change > 0 ? 'change-in' : ($change < 0 ? 'change-out' : 'change-zero');
              $changePrefix  = $change > 0 ? '+' : '';
              $opType        = $log->operation_type ?? 'Others';
              $isReversed    = $opType === 'Reversal'
                            || str_contains(strtolower($log->action_reason ?? ''), 'reversed');
              $inboundTypes  = ['StockDelivery','TransferIn','FoundStock','ReturnFromCustomer','ProductionIn','OpeningStock'];
              $outboundTypes = ['Sale','TransferOut','Damage','Expired','Usage','Theft','Wastage','Donation','ReturnToSupplier','Recall','Sample','WriteOff','Loss'];
              $opClass       = $opType === 'Reversal' ? 'op-reversal'
                             : (in_array($opType, $inboundTypes)  ? 'op-inbound'
                             : (in_array($opType, $outboundTypes) ? 'op-outbound' : 'op-neutral'));
              $sellPrice     = (float) ($log->selling_price ?? 0);
              $rowValue      = abs($change) * $sellPrice;
            @endphp
            <tr id="row{{ $log->id }}">
              <td>
                <input type="checkbox" class="selectRow" value="{{ $log->id }}" data-row-id="row{{ $log->id }}">
                &nbsp;{{ $log->product_name }}
                @if($isReversed)<span class="badge-reversed ms-1">Reversed</span>@endif
              </td>
              <td>{{ $log->product_code ?? '—' }}</td>
              <td>{{ $log->product_unit ?? '—' }}</td>
              <td><span class="op-badge {{ $opClass }}">{{ $opType }}</span></td>
              <td>{{ \Carbon\Carbon::parse($log->log_time)->format('H:i:s') }}</td>
              <td>{{ number_format($sellPrice, 2) }}</td>
              <td>{{ number_format((float) $log->stock_before, 2) }}</td>
              <td><span class="{{ $changeClass }}">{{ $changePrefix }}{{ number_format($change, 2) }}</span></td>
              <td>{{ number_format((float) $log->stock_after, 2) }}</td>
              <td>{{ number_format($rowValue, 2) }}</td>
              <td><span class="reason-cell" title="{{ $log->action_reason }}">{{ $log->action_reason }}</span></td>
              <td>{{ $log->user_name }}</td>
              <td>
                {{-- VIEW --}}
                <a href="#" class="viewDataBtn"
                   data-id="{{ $log->id }}"
                   data-product="{{ $log->product_name }}"
                   data-code="{{ $log->product_code }}"
                   data-unit="{{ $log->product_unit }}"
                   data-optype="{{ $opType }}"
                   data-date="{{ $log->log_date }}"
                   data-time="{{ $log->log_time }}"
                   data-before="{{ $log->stock_before }}"
                   data-change="{{ $log->stock_change }}"
                   data-after="{{ $log->stock_after }}"
                   data-selling-price="{{ $log->selling_price ?? 0 }}"
                   data-cost-price="{{ $log->cost_price ?? 0 }}"
                   data-row-value="{{ $rowValue }}"
                   data-reason="{{ $log->action_reason }}"
                   data-user="{{ $log->user_name }}"
                   data-user-email="{{ $log->user_email ?? '' }}"
                   data-user-role="{{ $log->user_role ?? '' }}"
                   data-user-full-name="{{ $log->user_full_name ?? '' }}"
                   data-device="{{ $log->user_device_details ?? '' }}"
                   data-ip="{{ $log->ip_address ?? '' }}"
                   data-browser="{{ $log->browser ?? '' }}"
                   data-os="{{ $log->operating_system ?? '' }}"
                   data-device-type="{{ $log->device_type ?? '' }}"
                   data-session-id="{{ $log->session_id ?? '' }}"
                   data-source-type="{{ $log->source_type ?? '' }}"
                   data-source-id="{{ $log->source_id ?? '' }}"
                   data-created-at="{{ $log->created_at ?? '' }}"
                   data-reversed="{{ $isReversed ? 1 : 0 }}">
                  <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
                </a>
                {{-- EDIT --}}
                <a href="#" class="editDataBtn"
                   data-id="{{ $log->id }}"
                   data-product="{{ $log->product_name }}"
                   data-optype="{{ $opType }}"
                   data-before="{{ $log->stock_before }}"
                   data-change="{{ $log->stock_change }}"
                   data-after="{{ $log->stock_after }}"
                   data-selling-price="{{ $log->selling_price ?? 0 }}"
                   data-cost-price="{{ $log->cost_price ?? 0 }}"
                   data-reason="{{ $log->action_reason }}"
                   data-date="{{ $log->log_date }}"
                   data-time="{{ $log->log_time }}">
                  <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
                </a>
                {{-- REVERSE --}}
                <a href="#" class="reverseBtn"
                   data-id="{{ $log->id }}"
                   data-product="{{ $log->product_name }}"
                   data-change="{{ $log->stock_change }}"
                   data-reversed="{{ $isReversed ? 1 : 0 }}"
                   title="{{ $isReversed ? 'Already reversed' : 'Reverse this entry' }}">
                  <i class="ri-reply-line {{ $isReversed ? 'text-secondary' : 'text-warning' }}" style="font-weight:bold;font-size:17px"></i>
                </a>
                {{-- DELETE --}}
                <a href="#" class="deleteDataBtn"
                   data-id="{{ $log->id }}"
                   data-product="{{ $log->product_name }}"
                   data-row="row{{ $log->id }}">
                  <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
      @endif

    </div>
  </div>
</div>
</div></div></div>


{{-- ══════════════════════════════════════════════════════════════════
     DATE PICKER MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="datePickerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-calendar-event-line"></i> Select Log Date
          @if($selectedBranch)
            <span style="font-size:12px;font-weight:400;opacity:.8;">— {{ $selectedBranch->name }}</span>
          @endif
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px !important;max-height:480px;overflow-y:auto;">
        @if($datesWithLogs->isEmpty())
          <div style="text-align:center;padding:32px 0;color:#94a3b8;">
            <i class="ri-calendar-close-line" style="font-size:40px;display:block;margin-bottom:10px;color:#c8d0ed;"></i>
            <div style="font-size:13px;">No log dates found in the last 3 months.</div>
          </div>
        @else
          <div class="date-chips-wrap" style="flex-direction:column;gap:6px;">
            @foreach($datesWithLogs as $d)
            <button type="button" class="date-chip-btn {{ $selectedDate === $d ? 'active' : '' }}"
                    data-date="{{ $d }}">
              <span class="chip-dot"></span>
              {{ \Carbon\Carbon::parse($d)->format('l, d F Y') }}
            </button>
            @endforeach
          </div>
        @endif
        <hr style="margin:16px 0 12px;border-color:#e9ecef;">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#6c757d;margin-bottom:8px;">
          <i class="ri-calendar-edit-line me-1"></i>Custom Date
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
          <input type="date" id="customDateInput" class="form-control form-control-sm"
                 style="max-width:200px;" value="{{ $selectedDate ?? '' }}" />
          <button type="button" class="btn btn-primary btn-sm" id="customDateSubmitBtn">
            <i class="ri-check-line me-1"></i>Go
          </button>
        </div>
      </div>
      <div class="modal-footer" style="padding:10px 20px 14px;justify-content:space-between;align-items:center;">
        <span style="font-size:11px;color:#94a3b8;"><i class="ri-information-line me-1"></i>Showing last 3 months · or enter a custom date above</span>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     DOWNLOAD MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-download-line"></i> Download Audit Logs</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p class="mb-2" style="font-size:13px;">Click a button to download the currently loaded log data.</p>
      <div class="buttons"></div>
    </div>
  </div></div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     INFO MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Inventory Audit Logs</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:18px 20px;">
      <p class="mb-2"><strong>What are Audit Logs?</strong><br>
      Every stock movement — whether from a sale, manual adjustment, damage write-off, or transfer — is recorded here as an immutable audit trail.</p>
      <hr class="my-3">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <tbody>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;width:160px;border-bottom:1px solid #f1f5f9">Operation Type</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Classifies why stock moved. <span style="background:#dcfce7;color:#15803d;padding:1px 7px;border-radius:8px;font-size:11px;font-weight:700;">Green</span> = inbound, <span style="background:#fee2e2;color:#b91c1c;padding:1px 7px;border-radius:8px;font-size:11px;font-weight:700;">Red</span> = outbound, <span style="background:#f1f5f9;color:#475569;padding:1px 7px;border-radius:8px;font-size:11px;font-weight:700;">Grey</span> = neutral/corrective.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Price</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">The selling price <strong>at the time of the log entry</strong> — frozen as a snapshot. Value calculations remain accurate even if the product price changes later.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Value</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Qty change × snapshot selling price per row.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Stock Change</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9"><span style="color:#16a34a;font-weight:700">+Green</span> = stock added. <span style="color:#dc2626;font-weight:700">−Red</span> = stock removed.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Reverse</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Creates a compensating entry that negates the original change. The original record is kept intact.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Edit</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Allows correcting stock values, prices, operation type, or reason text directly.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569">Delete</td><td style="padding:8px 12px">Permanently removes a log entry. Cannot be undone.</td></tr>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     VIEW LOG MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="viewLogModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-eye-line"></i> Log Entry Details</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 20px !important;">

        <div class="mb-3 pb-2 border-bottom d-flex align-items-start justify-content-between">
          <div>
            <div style="font-size:17px;font-weight:700;color:#1e293b" id="vw-product"></div>
            <div style="font-size:12px;color:#6c757d" id="vw-meta-line"></div>
          </div>
          <div id="vw-badges" class="d-flex gap-2 flex-wrap justify-content-end"></div>
        </div>

        <div class="vw-section"><i class="ri-arrow-up-down-line"></i> Stock Movement</div>
        <div class="view-grid">
          <div class="view-item"><label>Log Date</label><div class="view-val" id="vw-date"></div></div>
          <div class="view-item"><label>Log Time</label><div class="view-val" id="vw-time"></div></div>
          <div class="view-item"><label>Operation Type</label><div class="view-val" id="vw-optype"></div></div>
          <div class="view-item"><label>Server Recorded At</label><div class="view-val" id="vw-created-at" style="font-size:11px;color:#94a3b8;"></div></div>
          <div class="view-item"><label>Stock Before</label><div class="view-val" id="vw-before"></div></div>
          <div class="view-item"><label>Stock Change</label><div class="view-val" id="vw-change"></div></div>
          <div class="view-item"><label>Stock After</label><div class="view-val" id="vw-after"></div></div>
          <div class="view-item"><label>Row Value (MWK)</label><div class="view-val" id="vw-row-value" style="color:#4B5EBD;font-weight:700;"></div></div>
        </div>

        <div class="vw-section"><i class="ri-price-tag-3-line"></i> Price Snapshot (at log time)</div>
        <div class="view-grid">
          <div class="view-item"><label>Selling Price</label><div class="view-val" id="vw-sell-price"></div></div>
          <div class="view-item"><label>Cost Price</label><div class="view-val" id="vw-cost-price"></div></div>
          <div class="view-item full"><label>Action Reason</label><div class="view-val" id="vw-reason" style="white-space:pre-wrap;font-size:12px;line-height:1.5;"></div></div>
          {{-- Source reference (replaces old reference_type / reference_id) --}}
          <div class="view-item full" id="vw-source-wrap" style="display:none;">
            <label>Source Reference</label>
            <div class="view-val" id="vw-source" style="font-size:12px;color:#64748b;"></div>
          </div>
        </div>

        <div class="vw-section"><i class="ri-user-line"></i> Recorded By</div>
        <div class="view-grid">
          <div class="view-item"><label>Name</label><div class="view-val" id="vw-user"></div></div>
          <div class="view-item"><label>Email</label><div class="view-val" id="vw-user-email" style="font-size:12px;"></div></div>
          <div class="view-item"><label>Role</label><div class="view-val" id="vw-user-role"></div></div>
          <div class="view-item"><label>IP Address</label><div class="view-val" id="vw-ip" style="font-size:12px;font-family:monospace;"></div></div>
          <div class="view-item"><label>Device Type</label><div class="view-val" id="vw-device-type"></div></div>
          <div class="view-item"><label>Browser</label><div class="view-val" id="vw-browser"></div></div>
          <div class="view-item"><label>Operating System</label><div class="view-val" id="vw-os"></div></div>
          <div class="view-item"><label>Session ID</label><div class="view-val text-muted" id="vw-session" style="font-size:10px;font-family:monospace;word-break:break-all;color:#94a3b8;"></div></div>
          <div class="view-item full"><label>User Agent</label><div class="view-val text-muted" id="vw-device" style="font-size:10px;word-break:break-all;color:#94a3b8;"></div></div>
        </div>

      </div>
      <div class="modal-footer" style="padding:10px 20px 14px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     EDIT LOG MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="editLogModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-edit-box-line"></i> Edit Log — <span id="editLogProductName"></span>
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:14px 18px 10px !important;">
        <div class="alert border-0 py-2 px-3 mb-3"
             style="background:#fffbeb;border-left:2px solid #f59e0b;border-radius:0 5px 5px 0;font-size:11px;color:#92400e;">
          <i class="ri-alert-line me-1"></i>
          Editing updates stored values directly. For a non-destructive correction, use <strong>Reverse</strong> instead.
        </div>
        <input type="hidden" id="editLogId">

        <div class="edit-section-title"><i class="ri-tag-line me-1"></i>Operation Type</div>
        <div class="mb-3">
          <select class="form-select form-select-sm" id="editLogOpType">
            @foreach($operationTypes as $group => $types)
              <optgroup label="{{ $group }}">
                @foreach($types as $t)
                  <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
        </div>

        <div class="edit-section-title"><i class="ri-stack-line me-1"></i>Stock Values</div>
        <div class="row g-2 mb-3">
          <div class="col-4">
            <label class="form-label fw-semibold" style="font-size:12px">Before</label>
            <input class="form-control form-control-sm" type="number" step="0.0001" id="editLogBefore" />
          </div>
          <div class="col-4">
            <label class="form-label fw-semibold" style="font-size:12px">Change</label>
            <input class="form-control form-control-sm" type="number" step="0.0001" id="editLogChange" />
          </div>
          <div class="col-4">
            <label class="form-label fw-semibold" style="font-size:12px">After</label>
            <input class="form-control form-control-sm" type="number" step="0.0001" id="editLogAfter" />
          </div>
        </div>

        <div class="edit-section-title"><i class="ri-price-tag-3-line me-1"></i>Price Snapshot</div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold" style="font-size:12px">Selling Price (MWK)</label>
            <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="editLogSellingPrice" />
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold" style="font-size:12px">Cost Price (MWK)</label>
            <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="editLogCostPrice" />
          </div>
        </div>

        <div class="edit-section-title"><i class="ri-file-text-line me-1"></i>Reason</div>
        <div class="mb-3">
          <textarea class="form-control form-control-sm" id="editLogReason" rows="3"
                    placeholder="Describe the reason for this stock movement…"
                    style="resize:vertical;"></textarea>
        </div>

        <div class="row g-2">
          <div class="col-6">
            <label class="form-label fw-semibold" style="font-size:12px">Log Date</label>
            <input class="form-control form-control-sm" type="date" id="editLogDate" />
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold" style="font-size:12px">Log Time</label>
            <input class="form-control form-control-sm" type="time" step="1" id="editLogTime" />
          </div>
        </div>
      </div>
      <div class="modal-footer" style="padding:10px 18px 14px;gap:8px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <a href="#" class="btn btn-primary btn-sm" id="submitEditLogBtn">
          <i class="ri-check-line me-1"></i> Save Changes
        </a>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     REVERSE MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="reverseModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:380px;margin:1.75rem auto;">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-amber">
        <h5 class="modal-title mh-title"><i class="ri-reply-line"></i> Reverse Log Entry</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="ri-reply-line text-warning" style="font-size:56px"></i>
        <h5 class="mt-2 mb-1">Reverse entry for <span id="reverseProductName" class="text-warning"></span>?</h5>
        <p style="font-size:13px;color:#6c757d;margin-bottom:0;">
          A compensating entry of <strong><span id="reverseChangeDesc"></span></strong> will be created.<br>
          The original record is <strong>not deleted</strong>.
        </p>
        <input type="hidden" id="reverseLogId">
      </div>
      <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;">
        <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
        <a href="#" class="btn btn-warning btn-sm px-4" id="submitReverseBtn">
          <i class="ri-reply-line me-1"></i> Yes, Reverse It
        </a>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     DELETE MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="deleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:380px;margin:1.75rem auto;">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-danger">
        <h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Delete Log Entry</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="ri-error-warning-line text-danger" style="font-size:56px"></i>
        <h5 class="mt-2 mb-1">Delete log for <span id="deleteProductName" class="text-danger"></span>?</h5>
        <p style="font-size:13px;color:#6c757d;margin-bottom:0;">
          This <strong>permanently removes</strong> the log entry. This cannot be undone.
        </p>
        <input type="hidden" id="deleteLogId">
        <input type="hidden" id="deleteRowId">
      </div>
      <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;">
        <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
        <a href="#" class="btn btn-danger btn-sm px-4" id="submitDeleteBtn">Yes, Delete</a>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     BULK ACTIONS MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="bulkActionsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-checkbox-multiple-line"></i>
          Bulk Actions — <span id="bulkActionsCount">0</span> log(s) selected
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 18px !important;">
        <div class="bulk-section">
          <div class="bulk-section-title"><i class="ri-reply-line me-1"></i>Reverse</div>
          <div class="d-grid">
            <a href="#" class="btn btn-warning btn-sm" id="bulkReverseBtn">
              <i class="ri-reply-line me-1"></i> Reverse Selected Entries
            </a>
          </div>
          <div class="mt-2" style="font-size:11px;color:#92400e;background:#fffbeb;border-radius:5px;padding:6px 10px;">
            <i class="ri-information-line me-1"></i>
            Already-reversed entries will be skipped. A compensating log will be created for each eligible entry.
          </div>
        </div>
        <div class="d-grid mt-1">
          <a href="#" class="btn btn-danger" id="bulkDeleteBtn">
            <i class="ri-delete-bin-line me-1"></i> Delete Selected Entries
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000, allowHtml:true };

    var _inboundTypes  = ['StockDelivery','TransferIn','FoundStock','ReturnFromCustomer','ProductionIn','OpeningStock'];
    var _outboundTypes = ['Sale','TransferOut','Damage','Expired','Usage','Theft','Wastage','Donation','ReturnToSupplier','Recall','Sample','WriteOff','Loss'];

    function opBadgeClass(opType) {
        if (opType === 'Reversal')                   return 'op-badge op-reversal';
        if (_inboundTypes.indexOf(opType)  >= 0)     return 'op-badge op-inbound';
        if (_outboundTypes.indexOf(opType) >= 0)     return 'op-badge op-outbound';
        return 'op-badge op-neutral';
    }

    function handleAjaxError(xhr, status) {
        if (status === 'timeout') { toastr.error('The request timed out.', 'Timeout'); }
        else if (xhr.status === 422) {
            var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
            var msg = ''; $.each(errors, function(k,v) { msg += v + '\n'; });
            toastr.error(msg || 'Validation failed.', 'Validation Errors');
        } else if (xhr.status === 500) { toastr.error('Server error.', 'Server Error'); }
        else { toastr.error('Unspecified error.', 'Error'); }
    }

    function fmtNum(val, dec) {
        dec = dec === undefined ? 2 : dec;
        if (val === null || val === '' || val === undefined) return '—';
        var n = parseFloat(val);
        return isNaN(n) ? '—' : n.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }

    function fmtDate(d) {
        if (!d) return '—';
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var parts = d.split('-');
        return parseInt(parts[2]) + ' ' + months[parseInt(parts[1])-1] + ' ' + parts[0];
    }

    function esc(v) {
        return (v || '').toString()
            .replace(/&/g,'&amp;').replace(/"/g,'&quot;')
            .replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function updateSelectedCount() {
        var count = $('.selectRow:checked').length;
        $('#selectedCount, #headerSelCount').text(count);
        if (count > 0) { $('#bulkTriggerBtn').show(); $('#selectionBadge').addClass('visible'); }
        else           { $('#bulkTriggerBtn').hide(); $('#selectionBadge').removeClass('visible'); }
    }

    // ── buildRow ──────────────────────────────────────────────────────────
    function buildRow(p) {
        var change      = parseFloat(p.stock_change || 0);
        var changeClass = change > 0 ? 'change-in' : (change < 0 ? 'change-out' : 'change-zero');
        var prefix      = change > 0 ? '+' : '';
        var opType      = p.operation_type || 'Others';
        var isReversed  = p.is_reversed || opType === 'Reversal'
                        || (p.action_reason && p.action_reason.toLowerCase().indexOf('reversed') >= 0);
        var revBadge    = isReversed ? '<span class="badge-reversed ms-1">Reversed</span>' : '';
        var revIcon     = isReversed ? 'text-secondary' : 'text-warning';
        var sellPrice   = parseFloat(p.selling_price || 0);
        var rowValue    = Math.abs(change) * sellPrice;

        return '<tr id="' + p.row + '">'
            + '<td><input type="checkbox" class="selectRow" value="' + p.id + '" data-row-id="' + p.row + '">&nbsp;'
            +   esc(p.product_name) + revBadge + '</td>'
            + '<td>' + esc(p.product_code || '—') + '</td>'
            + '<td>' + esc(p.product_unit || '—') + '</td>'
            + '<td><span class="' + opBadgeClass(opType) + '">' + esc(opType) + '</span></td>'
            + '<td>' + (p.log_time ? p.log_time.substring(0,8) : '—') + '</td>'
            + '<td>' + fmtNum(sellPrice) + '</td>'
            + '<td>' + fmtNum(p.stock_before) + '</td>'
            + '<td><span class="' + changeClass + '">' + prefix + fmtNum(change) + '</span></td>'
            + '<td>' + fmtNum(p.stock_after) + '</td>'
            + '<td>' + fmtNum(rowValue) + '</td>'
            + '<td><span class="reason-cell" title="' + esc(p.action_reason) + '">' + esc(p.action_reason || '') + '</span></td>'
            + '<td>' + esc(p.user_name || '—') + '</td>'
            + '<td>'
            +   '<a href="#" class="viewDataBtn"'
            +     ' data-id="'           + p.id                              + '"'
            +     ' data-product="'      + esc(p.product_name)               + '"'
            +     ' data-code="'         + esc(p.product_code)               + '"'
            +     ' data-unit="'         + esc(p.product_unit)               + '"'
            +     ' data-optype="'       + esc(opType)                       + '"'
            +     ' data-date="'         + (p.log_date        || '')         + '"'
            +     ' data-time="'         + (p.log_time        || '')         + '"'
            +     ' data-before="'       + (p.stock_before    || '')         + '"'
            +     ' data-change="'       + (p.stock_change    || '')         + '"'
            +     ' data-after="'        + (p.stock_after     || '')         + '"'
            +     ' data-selling-price="'+ (p.selling_price   || 0)          + '"'
            +     ' data-cost-price="'   + (p.cost_price      || 0)          + '"'
            +     ' data-row-value="'    + fmtNum(rowValue)                  + '"'
            +     ' data-reason="'       + esc(p.action_reason)              + '"'
            +     ' data-user="'         + esc(p.user_name)                  + '"'
            +     ' data-user-email="'   + esc(p.user_email)                 + '"'
            +     ' data-user-role="'    + esc(p.user_role)                  + '"'
            +     ' data-user-full-name="'+ esc(p.user_full_name)            + '"'
            +     ' data-device="'       + esc(p.user_device_details)        + '"'
            +     ' data-ip="'           + esc(p.ip_address)                 + '"'
            +     ' data-browser="'      + esc(p.browser)                    + '"'
            +     ' data-os="'           + esc(p.operating_system)           + '"'
            +     ' data-device-type="'  + esc(p.device_type)                + '"'
            +     ' data-session-id="'   + esc(p.session_id)                 + '"'
            +     ' data-source-type="'  + esc(p.source_type)                + '"'
            +     ' data-source-id="'    + (p.source_id       || '')         + '"'
            +     ' data-created-at="'   + (p.created_at      || '')         + '"'
            +     ' data-reversed="'     + (isReversed ? 1 : 0)              + '">'
            +     '<i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>'
            +   '</a> '
            +   '<a href="#" class="editDataBtn"'
            +     ' data-id="'           + p.id                   + '"'
            +     ' data-product="'      + esc(p.product_name)    + '"'
            +     ' data-optype="'       + esc(opType)            + '"'
            +     ' data-before="'       + (p.stock_before  || '') + '"'
            +     ' data-change="'       + (p.stock_change  || '') + '"'
            +     ' data-after="'        + (p.stock_after   || '') + '"'
            +     ' data-selling-price="'+ (p.selling_price || 0)  + '"'
            +     ' data-cost-price="'   + (p.cost_price    || 0)  + '"'
            +     ' data-reason="'       + esc(p.action_reason)   + '"'
            +     ' data-date="'         + (p.log_date  || '')    + '"'
            +     ' data-time="'         + (p.log_time  || '')    + '">'
            +     '<i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>'
            +   '</a> '
            +   '<a href="#" class="reverseBtn"'
            +     ' data-id="'      + p.id                   + '"'
            +     ' data-product="' + esc(p.product_name)    + '"'
            +     ' data-change="'  + (p.stock_change || '') + '"'
            +     ' data-reversed="'+ (isReversed ? 1 : 0)  + '">'
            +     '<i class="ri-reply-line ' + revIcon + '" style="font-weight:bold;font-size:17px"></i>'
            +   '</a> '
            +   '<a href="#" class="deleteDataBtn"'
            +     ' data-id="'      + p.id                + '"'
            +     ' data-product="' + esc(p.product_name) + '"'
            +     ' data-row="'     + p.row               + '">'
            +     '<i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>'
            +   '</a>'
            + '</td>'
            + '</tr>';
    }

    // ════════════════════════════════════════════════════════════════════════
    //  DataTable
    // ════════════════════════════════════════════════════════════════════════
    @if($selectedBranch)

    var _currentBranchId = {{ $selectedBranch->id }};
    var _currentDate     = {!! $selectedDate ? '"'.$selectedDate.'"' : 'null' !!};
    var table            = null;

    var dtConfig = {
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, 'All']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        order: [[4, 'desc']],
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  },
            { targets: [12],   orderable: false }
        ]
    };

    function makeDtButtons(titleStr) {
        return [
            { extend: 'excelHtml5', title: titleStr, exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: titleStr, exportOptions: { columns: ':visible:not(:last-child)' } },
            {
                extend: 'pdfHtml5', title: titleStr,
                exportOptions: { columns: ':visible:not(:last-child)' },
                customize: function(doc) {
                    doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                }
            }
        ];
    }

    table = $('#maintable').DataTable($.extend({}, dtConfig, { buttons: makeDtButtons(@json($maintableTitle)) }));
    window._dt = table;
    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    // ── Date picker ───────────────────────────────────────────────────────
    $('#datePickerBarBtn').on('click', function(e) { e.preventDefault(); $('#datePickerModal').modal('show'); });

    $(document).on('click', '.date-chip-btn', function(e) {
        e.preventDefault();
        var date = $(this).data('date');
        if (!date) return;
        $('.date-chip-btn').removeClass('active');
        $(this).addClass('active');
        _currentDate = date;
        $('#customDateInput').val(date);
        $('#datePickerModal').modal('hide');
        $.post('{{ route("tenant.admin.update.filters") }}',
               { user_id: {{ Auth::id() }}, date: date, _token: '{{ csrf_token() }}' });
        loadLogsForDate(date);
    });

    $('#customDateSubmitBtn').on('click', function() {
        var date = $('#customDateInput').val();
        if (!date) { toastr.warning('Please pick a date first.', 'No date'); return; }
        $('.date-chip-btn').removeClass('active');
        _currentDate = date;
        $('#datePickerModal').modal('hide');
        $.post('{{ route("tenant.admin.update.filters") }}',
               { user_id: {{ Auth::id() }}, date: date, _token: '{{ csrf_token() }}' });
        loadLogsForDate(date);
    });

    function reInitTable(rows, titleStr) {
        if ($.fn.DataTable.isDataTable('#maintable')) { $('#maintable').DataTable().destroy(); }
        table = null;
        $('#maintable tbody').html(rows);
        $('#maintable').show();
        table = $('#maintable').DataTable($.extend({}, dtConfig, { buttons: makeDtButtons(titleStr) }));
        window._dt = table;
        $('#buttonsModal .buttons').empty();
        table.buttons().container().appendTo($('#buttonsModal .buttons'));
    }

    function loadLogsForDate(date) {
        $('#tableLoadingOverlay').css('display','flex');
        $('#progressBar').show();
        $.ajax({
            type: 'GET', url: '{{ route("retail.auditlogs.bydate") }}', timeout: 60000,
            data: { branch_id: _currentBranchId, log_date: date },
            success: function(data) {
                if (data.status !== 200) { toastr.error('Failed to load logs.', 'Error'); return; }

                var net = parseFloat(data.summary_net);
                $('#sum-events').text(data.logs.length);
                $('#sum-in').text('+MWK ' + fmtNum(data.summary_in));
                $('#sum-out').text('−MWK ' + fmtNum(data.summary_out));
                $('#sum-net').text((net >= 0 ? '+' : '') + 'MWK ' + fmtNum(Math.abs(net)))
                             .css('color', net >= 0 ? '#16a34a' : '#dc2626');
                $('#sumDateText').text(fmtDate(date));

                // Show summary strip only when there are logs
                if (data.logs.length > 0) {
                    $('#summaryStrip').css('display','flex');
                    $('#sumDateBadge').show();
                } else {
                    $('#summaryStrip').hide();
                    $('#sumDateBadge').hide();
                }

                // Always rebuild the table — DataTable renders its own
                // "No data available in table" message when rows is empty.
                var html = '';
                data.logs.forEach(function(p) { html += buildRow(p); });
                reInitTable(html, 'Audit Logs — {{ addslashes($selectedBranch->name) }} — ' + date);

                updateSelectedCount();
            },
            error: handleAjaxError,
            complete: function() { $('#tableLoadingOverlay').hide(); $('#progressBar').hide(); }
        });
    }

    // ── PDF buttons ───────────────────────────────────────────────────────
    $('#dlPositiveBtn').on('click', function(e) {
        e.preventDefault();
        if (!_currentDate) { toastr.warning('Please select a date first.', 'No date'); return; }
        window.location.href = '{{ route("retail.auditlogs.downloadpdf") }}?branch_id=' + _currentBranchId + '&log_date=' + _currentDate + '&direction=positive';
    });
    $('#dlNegativeBtn').on('click', function(e) {
        e.preventDefault();
        if (!_currentDate) { toastr.warning('Please select a date first.', 'No date'); return; }
        window.location.href = '{{ route("retail.auditlogs.downloadpdf") }}?branch_id=' + _currentBranchId + '&log_date=' + _currentDate + '&direction=negative';
    });
    $('#dlAllBtn').on('click', function(e) {
        e.preventDefault();
        if (!_currentDate) { toastr.warning('Please select a date first.', 'No date'); return; }
        window.location.href = '{{ route("retail.auditlogs.downloadpdf") }}?branch_id=' + _currentBranchId + '&log_date=' + _currentDate + '&direction=all';
    });

    // ════════════════════════════════════════════════════════════════════════
    //  VIEW
    // ════════════════════════════════════════════════════════════════════════
    $('#tbody').on('click', '.viewDataBtn', function(e) {
        e.preventDefault();
        var b       = $(this);
        var change  = parseFloat(b.data('change'));
        var cc      = change > 0 ? 'change-in' : (change < 0 ? 'change-out' : 'change-zero');
        var pf      = change > 0 ? '+' : '';
        var isRev   = parseInt(b.data('reversed')) === 1;
        var opType  = b.data('optype') || 'Others';
        var srcType = b.data('source-type');
        var srcId   = b.data('source-id');

        $('#vw-product').text(b.data('product'));
        $('#vw-meta-line').text('Code: ' + (b.data('code') || '—') + ' · Unit: ' + (b.data('unit') || '—'));
        $('#vw-badges').html(
            '<span class="' + opBadgeClass(opType) + '">' + esc(opType) + '</span>'
            + (isRev ? ' <span class="badge-reversed">Reversed</span>' : '')
        );

        $('#vw-date').text(b.data('date') || '—');
        $('#vw-time').text(b.data('time') || '—');
        $('#vw-optype').html('<span class="' + opBadgeClass(opType) + '">' + esc(opType) + '</span>');
        $('#vw-created-at').text(b.data('created-at') || '—');
        $('#vw-before').text(fmtNum(b.data('before')));
        $('#vw-change').html('<span class="' + cc + '">' + pf + fmtNum(change) + '</span>');
        $('#vw-after').text(fmtNum(b.data('after')));
        $('#vw-row-value').text('MWK ' + fmtNum(b.data('row-value')));

        $('#vw-sell-price').text('MWK ' + fmtNum(b.data('selling-price')));
        $('#vw-cost-price').text('MWK ' + fmtNum(b.data('cost-price')));
        $('#vw-reason').text(b.data('reason') || '—');

        // Source reference (new schema: source_type + source_id)
        if (srcType && srcId) {
            $('#vw-source').text(srcType + ' #' + srcId);
            $('#vw-source-wrap').show();
        } else if (srcType) {
            $('#vw-source').text(srcType);
            $('#vw-source-wrap').show();
        } else {
            $('#vw-source-wrap').hide();
        }

        $('#vw-user').text(b.data('user') || b.data('user-full-name') || '—');
        $('#vw-user-email').text(b.data('user-email') || '—');
        $('#vw-user-role').text(b.data('user-role') || '—');
        $('#vw-ip').text(b.data('ip') || '—');
        $('#vw-device-type').text(b.data('device-type') || '—');
        $('#vw-browser').text(b.data('browser') || '—');
        $('#vw-os').text(b.data('os') || '—');
        $('#vw-session').text(b.data('session-id') || '—');
        $('#vw-device').text(b.data('device') || '—');

        $('#viewLogModal').modal('show');
    });

    // ════════════════════════════════════════════════════════════════════════
    //  EDIT
    // ════════════════════════════════════════════════════════════════════════
    $('#tbody').on('click', '.editDataBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        $('#editLogId').val(b.data('id'));
        $('#editLogProductName').text(b.data('product'));
        $('#editLogOpType').val(b.data('optype') || 'Others');
        $('#editLogBefore').val(b.data('before'));
        $('#editLogChange').val(b.data('change'));
        $('#editLogAfter').val(b.data('after'));
        $('#editLogSellingPrice').val(b.data('selling-price'));
        $('#editLogCostPrice').val(b.data('cost-price'));
        $('#editLogReason').val(b.data('reason'));
        $('#editLogDate').val(b.data('date'));
        $('#editLogTime').val(b.data('time'));
        $('#editLogModal').modal('show');
    });

    $('#submitEditLogBtn').on('click', function(e) {
        e.preventDefault();
        var reason = $('#editLogReason').val().trim();
        if (!reason) { toastr.warning('Reason is required.', 'Required'); $('#editLogReason').focus(); return; }
        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST', url: '{{ route("retail.auditlogs.update") }}', timeout: 60000,
            data: {
                id:             $('#editLogId').val(),
                operation_type: $('#editLogOpType').val(),
                stock_before:   $('#editLogBefore').val(),
                stock_change:   $('#editLogChange').val(),
                stock_after:    $('#editLogAfter').val(),
                selling_price:  $('#editLogSellingPrice').val(),
                cost_price:     $('#editLogCostPrice').val(),
                action_reason:  reason,
                log_date:       $('#editLogDate').val(),
                log_time:       $('#editLogTime').val(),
                _token:         '{{ csrf_token() }}'
            },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    if (data.log) {
                        var row = 'row' + data.log.id;
                        if (table) { if (table.row('#' + row).length) { table.row('#' + row).remove(); } table.row.add($(buildRow(data.log))).draw(false); }
                        else { $('#' + row).replaceWith($(buildRow(data.log))); }
                    }
                    $('#editLogModal').modal('hide');
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: handleAjaxError
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  REVERSE
    // ════════════════════════════════════════════════════════════════════════
    $('#tbody').on('click', '.reverseBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        if (parseInt(b.data('reversed')) === 1) { toastr.info('This entry has already been reversed.', 'Already Reversed'); return; }
        var change   = parseFloat(b.data('change'));
        var opposite = change * -1;
        var desc     = (opposite > 0 ? '+' : '') + fmtNum(opposite) + ' (' + (opposite > 0 ? 'stock restored' : 'stock removed') + ')';
        $('#reverseProductName').text(b.data('product'));
        $('#reverseChangeDesc').text(desc);
        $('#reverseLogId').val(b.data('id'));
        $('#reverseModal').modal('show');
    });

    $('#submitReverseBtn').on('click', function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        doReverse([$('#reverseLogId').val()], function(results) {
            self.prop('disabled', false);
            if (results.success) {
                toastr.success(results.message, 'Success');
                $('#reverseModal').modal('hide');
                results.logs.forEach(function(p) {
                    if (table) { table.row.add($(buildRow(p))).draw(false); }
                    else       { $('#tbody').append($(buildRow(p))); }
                });
                updateSelectedCount();
            }
        });
    });

    function doReverse(ids, callback) {
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST', url: '{{ route("retail.auditlogs.bulkreverse") }}', timeout: 60000,
            data: { ids: ids, _token: '{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); },
            success: function(data) {
                if (data.status === 201) { callback({ success:true, message:data.success, logs:data.logs||[] }); }
                else { toastr.error(data.error || 'Failed.', 'Error'); callback({ success:false }); }
            },
            error: function() { handleAjaxError.apply(this, arguments); callback({ success:false }); }
        });
    }

    // ════════════════════════════════════════════════════════════════════════
    //  DELETE
    // ════════════════════════════════════════════════════════════════════════
    $('#tbody').on('click', '.deleteDataBtn', function(e) {
        e.preventDefault();
        $('#deleteProductName').text($(this).data('product'));
        $('#deleteRowId').val($(this).data('row'));
        $('#deleteLogId').val($(this).data('id'));
        $('#deleteModal').modal('show');
    });

    $('#submitDeleteBtn').on('click', function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row  = $('#deleteRowId').val();
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST', url: '{{ route("retail.auditlogs.delete") }}', timeout: 60000,
            data: { id: $('#deleteLogId').val(), _token: '{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    if (table) { table.row('#' + row).remove().draw(false); } else { $('#' + row).remove(); }
                    updateSelectedCount();
                    $('#deleteModal').modal('hide');
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: handleAjaxError
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  CHECKBOXES & BULK
    // ════════════════════════════════════════════════════════════════════════
    $('#selectAll').on('click', function() { $('.selectRow').prop('checked', this.checked); updateSelectedCount(); });
    $('#tbody').on('change', '.selectRow', function() { updateSelectedCount(); });

    $('#selectionBadge, #bulkTriggerBtn').on('click', function(e) {
        e.preventDefault();
        if ($('.selectRow:checked').length === 0) return;
        $('#bulkActionsCount').text($('.selectRow:checked').length);
        $('#bulkActionsModal').modal('show');
    });

    function getSelectedIds()  { var ids=[]; $('.selectRow:checked').each(function(){ ids.push($(this).val()); }); return ids; }
    function getSelectedRows() { var rows=[]; $('.selectRow:checked').each(function(){ rows.push($(this).data('row-id')); }); return rows; }

    $('#bulkReverseBtn').on('click', function(e) {
        e.preventDefault();
        var ids = getSelectedIds();
        if (!ids.length) { toastr.warning('No entries selected.', 'Warning'); return; }
        var self = $(this); self.prop('disabled', true);
        $('#bulkActionsModal').modal('hide');
        doReverse(ids, function(results) {
            self.prop('disabled', false);
            if (results.success) {
                toastr.success(results.message, 'Success');
                results.logs.forEach(function(p) {
                    if (table) { table.row.add($(buildRow(p))).draw(false); } else { $('#tbody').append($(buildRow(p))); }
                });
                $('.selectRow').prop('checked', false); $('#selectAll').prop('checked', false); updateSelectedCount();
            }
        });
    });

    $('#bulkDeleteBtn').on('click', function(e) {
        e.preventDefault();
        var ids = getSelectedIds(); var rows = getSelectedRows();
        if (!ids.length) { toastr.warning('No entries selected.', 'Warning'); return; }
        if (!confirm('Permanently delete ' + ids.length + ' log entr' + (ids.length > 1 ? 'ies' : 'y') + '? This cannot be undone.')) return;
        $('#bulkActionsModal').modal('hide');
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST', url: '{{ route("retail.auditlogs.bulkdelete") }}', timeout: 60000,
            data: { ids: ids, _token: '{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    rows.forEach(function(r) { if (table) { table.row('#' + r).remove(); } else { $('#' + r).remove(); } });
                    if (table) { table.draw(false); }
                    $('.selectRow').prop('checked', false); $('#selectAll').prop('checked', false); updateSelectedCount();
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: handleAjaxError
        });
    });

    @endif

    $('#infoBtn').on('click',         function(e) { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').on('click', function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

});
</script>
@endsection