@extends('operations.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    $pref = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();

    $branchId      = $pref->branch_id      ?? null;
    $fstCustomDate = $pref->fst_custom_date ?? null;
    $isCustom      = ! empty($fstCustomDate);
    $date          = $isCustom ? $fstCustomDate : Carbon::today()->toDateString();
    $displayDate   = Carbon::parse($date)->format('d M Y');

    $branches = DB::connection('tenant')->table('branches')
        ->where('sector', 'Retail')
        ->where('status', 'active')
        ->orderBy('name')
        ->get();

    $branchName = $branchId
        ? (DB::connection('tenant')->table('branches')->where('id', $branchId)->value('name') ?? 'Branch not found')
        : 'Branch not selected';

    $isRectified = $branchId && DB::connection('tenant')
        ->table('retail_fullstocktaking_summary')
        ->where('branch_id', $branchId)
        ->where('date', $date)
        ->exists();

    $data          = collect();
    $expectedValue = $foundValue = 0;

    if ($branchId) {
        $data = DB::connection('tenant')->table('retail_fullstocktaking')
            ->where('branch_id', $branchId)
            ->where('date', $date)
            ->orderBy('product_name')
            ->get();

        foreach ($data as $d) {
            $expectedValue += $d->expected_at_count * $d->price;
            $foundValue    += $d->found * $d->price;
        }
    }

    $title = $branchName . ' Full Stocktaking ' . $displayDate;
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ── Card chrome ─────────────────────────────────────────────────────── */
.card-header {
    padding: 0.5rem 1.5rem !important;
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    color: #fff;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}
.card-body { padding: 0 !important; }
.card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; gap: 6px; }

.card-header .btn-light {
    height: 28px;
    padding: 0 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    font-size: 16px;
}
.card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color 0.2s; }

/* ── Action bar ─────────────────────────────────────────────────────── */
.fst-action-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #9098a8;
    padding: 8px 14px;
    border-bottom: 1px solid #7a8090;
    gap: 10px;
    flex-wrap: wrap;
}
.fst-left  { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; flex-wrap: wrap; }
.fst-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

#fstBranchSelect {
    border: 1.5px solid rgba(255,255,255,0.35);
    background: #9098a8;
    border-radius: 7px;
    padding: 5px 10px;
    font-size: 12.5px;
    font-weight: 600;
    color: #dde0e8;
    max-width: 220px;
    height: 32px;
}

.fst-date-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(255,255,255,0.12);
    border: 1.5px solid rgba(255,255,255,0.3);
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 600;
    color: #dde0e8;
    cursor: pointer;
    white-space: nowrap;
    user-select: none;
    height: 32px;
}
.fst-date-chip.custom-mode { background: rgba(252,211,77,0.2); border-color: #fcd34d; color: #fef3c7; }
.fst-date-chip .mode-badge { font-size: 9px; padding: 1px 5px; border-radius: 8px; background: rgba(255,255,255,0.2); font-weight: 700; color: #dde0e8; }
.fst-date-chip.custom-mode .mode-badge { background: rgba(252,211,77,0.35); color: #fef3c7; }
.fst-edit-pencil { font-size: 10px; opacity: .8; }
.rectified-tag { font-size: 9px; font-weight: 700; background: #d1fae5; color: #065f46; border-radius: 5px; padding: 2px 7px; display: inline-flex; align-items: center; gap: 3px; }

/* Icon buttons in action bar */
.fst-icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 7px;
    background: rgba(255,255,255,0.12);
    border: 1.5px solid rgba(255,255,255,0.3);
    color: #dde0e8;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 15px;
    text-decoration: none;
    position: relative;
}
.fst-icon-btn:hover { background: rgba(255,255,255,0.22); }
.fst-icon-btn.has-pending { background: rgba(252,211,77,0.25); border-color: #fcd34d; color: #fef3c7; }
.fst-pending-badge {
    position: absolute;
    top: -6px; right: -6px;
    background: #dc2626; color: #fff;
    font-size: 9px; font-weight: 700;
    min-width: 16px; height: 16px;
    border-radius: 8px;
    display: none; align-items: center; justify-content: center;
    padding: 0 4px; border: 1px solid #fff;
}
.fst-pending-badge.show { display: flex; }

/* ── Tabs ─────────────────────────────────────────────────────────────── */
.tab-header-container { background: #cccccc; border-bottom: 1px solid #b3b3b3; }
.nav-pills .nav-link {
    border-radius: 0 !important;
    padding: .65rem 1rem;
    font-weight: 500;
    color: #495057;
    border-bottom: none;
    transition: all .2s;
    font-size: 12.5px;
}
.nav-pills .nav-link:hover  { background: #b8b8b8; color: #4B5EBD; }
.nav-pills .nav-link.active { background: transparent !important; color: #4B5EBD !important; border-bottom: none; font-weight: 600; }
.nav-pills .nav-link i { font-size: 1rem; margin-right: .3rem; }

/* ── Table — matches Branch Products table style exactly ─────────────── */
.dt-buttons .btn {
  background: transparent !important; background-image: none !important;
  box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

.table-wrapper { overflow-x: auto; padding: 0 16px 20px; }
table.dataTable tbody td { padding-top: 6px !important; padding-bottom: 6px !important; }

#mergedDataTable thead th,
table.dataTable thead th { text-align: center !important; vertical-align: middle !important; }
#mergedDataTable thead th:first-child,
table.dataTable thead th:first-child { text-align: left !important; }
#mergedDataTable tbody td,
table.dataTable tbody td { text-align: center !important; vertical-align: middle !important; }
#mergedDataTable tbody td:first-child,
table.dataTable tbody td:first-child { text-align: left !important; }

.fst-diff-pos  { color: #059669; font-weight: 700; }
.fst-diff-neg  { color: #dc2626; font-weight: 700; }
.fst-diff-zero { color: #64748b; }
.action-icon { cursor: pointer; padding: 4px 6px; border-radius: 6px; }
.action-icon:hover { background: #f0f0f0; }
.fst-row-dirty { background: #fffbeb !important; }
.fst-edited-tag { font-size: 8px; font-weight: 700; background: #fde68a; color: #92400e; border-radius: 4px; padding: 1px 5px; margin-left: 4px; }

/* ── Modal headers ───────────────────────────────────────────────────── */
.mh-blue  { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; }
.mh-amber { background: linear-gradient(135deg,#d97706,#f59e0b); padding: 12px 18px !important; border-bottom: none; }
.mh-title { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }
.info-list li { margin-bottom: 8px; }

/* ── Totals modal ────────────────────────────────────────────────────── */
.totals-stat-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
.totals-stat-row:last-child { border-bottom: none; }
.totals-stat-row .lbl { color: #64748b; }
.totals-stat-row .val { font-weight: 800; color: #1e293b; font-size: 14px; }
.totals-stat-row .val.diff-pos { color: #059669; }
.totals-stat-row .val.diff-neg { color: #dc2626; }
.totals-stat-row .val.accent { color: #3b4fa0; }

/* ── Date modal ──────────────────────────────────────────────────────── */
.dmc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.dmc { padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer; }
.dmc.active-sys { border-color: #4B5EBD; background: #eff3ff; }
.dmc.active-cus { border-color: #d97706; background: #fffbeb; }
.dmc-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.dmc-val { font-size: 13px; font-weight: 600; color: #64748b; }
.dmc.active-sys .dmc-val { color: #4B5EBD; }
.dmc.active-cus .dmc-val { color: #d97706; }
</style>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="ri-scales-3-line me-1"></i> Full Stocktaking</h4>
        <div class="d-flex align-items-center" style="gap:4px;">
            <a href="{{ route('retail.operations.fullstocktaking.history') }}" class="btn btn-light text-primary" title="History">
                <i class="ri-history-line"></i>
            </a>
            <a href="#" class="btn btn-light text-primary" id="fstInfoBtn" title="About this section">
                <i class="ri-information-line"></i>
            </a>
        </div>
    </div>

    <div class="fst-action-bar">
        <div class="fst-left">
            <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="fstBranchForm" style="margin:0;">
                @csrf
                <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                <select name="branch_id" id="fstBranchSelect" onchange="document.getElementById('fstBranchForm').submit()">
                    <option value="" hidden>{{ $branchId ? '' : '— Select Branch —' }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </form>
            <div class="fst-date-chip {{ $isCustom ? 'custom-mode' : '' }}" id="fstDateChip" title="Change stocktaking date">
                <i class="ri-calendar-line" style="font-size:11px;"></i> <span>{{ $displayDate }}</span>
                <span class="mode-badge">{{ $isCustom ? 'Custom' : 'Today' }}</span>
                <i class="ri-pencil-line fst-edit-pencil"></i>
            </div>
            @if($isRectified)<span class="rectified-tag"><i class="ri-lock-line"></i> Rectified</span>@endif
        </div>
        <div class="fst-right">
            @if($branchId)
            <a href="#" class="fst-icon-btn" id="mdTotalsBtn" title="View totals"><i class="ri-bar-chart-box-line"></i></a>
            @endif
            <a href="#" class="fst-icon-btn" id="downloadModalBtn" title="Download"><i class="ri-download-line"></i></a>
            <a href="#" class="fst-icon-btn" id="mdSyncBtn" title="Sync offline changes">
                <i class="ri-upload-cloud-2-line"></i>
                <span class="fst-pending-badge" id="mdPendingBadge"></span>
            </a>
        </div>
    </div>

    <div class="tab-header-container">
        <ul class="nav nav-pills nav-justified mb-0">
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking') }}" class="nav-link"><i class="ri-scales-3-line"></i> Stocktaking</a></li>
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking.merged-data') }}" class="nav-link active"><i class="ri-stack-line"></i> Merged Data</a></li>
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking.missing-products') }}" class="nav-link"><i class="ri-error-warning-line"></i> Missing Products</a></li>
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking.actions-and-info') }}" class="nav-link"><i class="ri-flashlight-line"></i> Actions &amp; Info</a></li>
        </ul>
    </div>

    <div class="card-body">
        @if(!$branchId)
            <div style="padding:48px 20px;text-align:center;color:#94a3b8;">
                <i class="ri-store-2-line" style="font-size:48px;display:block;margin-bottom:12px;color:#c8d0ed;"></i>
                <div style="font-size:15px;font-weight:600;color:#64748b;">No Branch Selected</div>
                <div style="font-size:13px;margin-top:4px;">Select a branch above to view merged data.</div>
            </div>
        @else

        <div class="table-wrapper" style="margin-top:16px;">
            <table id="mergedDataTable" class="table table-sm table-striped row-border order-column w-100">
                <thead style="background-color:#e2e2e9">
                    <tr>
                        <th>Product</th>
                        <th>Unit</th><th>Price</th><th>Expected</th><th>Found</th><th>Difference</th>
                        <th>Merges</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $d)
                    <tr id="row{{ $d->id }}" data-id="{{ $d->id }}" data-expected="{{ $d->expected_at_count }}" data-found="{{ $d->found }}">
                        <td>{{ $d->product_name }} <span class="fst-edited-tag" id="editedTag{{ $d->id }}" style="display:none;">Edited (unsynced)</span></td>
                        <td>{{ $d->unit }}</td>
                        <td>{{ number_format($d->price, 2) }}</td>
                        <td id="cellExpected{{ $d->id }}">{{ number_format($d->expected_at_count, 2) }}</td>
                        <td id="cellFound{{ $d->id }}">{{ number_format($d->found, 2) }}</td>
                        @php $diff = $d->found - $d->expected_at_count; @endphp
                        <td class="{{ $diff > 0 ? 'fst-diff-pos' : ($diff < 0 ? 'fst-diff-neg' : 'fst-diff-zero') }}" id="cellDiff{{ $d->id }}">{{ number_format($diff, 2) }}</td>
                        <td>{{ $d->merge_count }}</td>
                        <td>
                            <i class="ri-edit-2-line action-icon text-primary editBtn"
                               data-id="{{ $d->id }}" data-product="{{ $d->product_name }}"
                               data-expected="{{ $d->expected_at_count }}" data-found="{{ $d->found }}"></i>
                            <i class="ri-delete-bin-line action-icon text-danger deleteBtn"
                               data-id="{{ $d->id }}" data-product="{{ $d->product_name }}"></i>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
</div></div></div>

{{-- ══ TOTALS MODAL ════════════════════════════════════════════════════ --}}
<div class="modal fade" id="mdTotalsModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:380px;">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-bar-chart-box-line"></i> Merged Data Totals</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div class="totals-stat-row">
                    <span class="lbl">Branch</span>
                    <span class="val" style="font-size:13px;">{{ $branchName }}</span>
                </div>
                <div class="totals-stat-row">
                    <span class="lbl">Date</span>
                    <span class="val" style="font-size:13px;">{{ $displayDate }}</span>
                </div>
                <div class="totals-stat-row">
                    <span class="lbl">Lines</span>
                    <span class="val accent">{{ $data->count() }}</span>
                </div>
                <div class="totals-stat-row">
                    <span class="lbl">Expected Value (EV)</span>
                    <span class="val">{{ number_format($expectedValue, 2) }}</span>
                </div>
                <div class="totals-stat-row">
                    <span class="lbl">Found Value (FV)</span>
                    <span class="val">{{ number_format($foundValue, 2) }}</span>
                </div>
                @php $diffVal = $foundValue - $expectedValue; @endphp
                <div class="totals-stat-row">
                    <span class="lbl">Difference (FV − EV)</span>
                    <span class="val {{ $diffVal > 0 ? 'diff-pos' : ($diffVal < 0 ? 'diff-neg' : '') }}">{{ number_format($diffVal, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══ EDIT MODAL ══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="editRowModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-edit-2-line"></i> Edit Count</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <input type="hidden" id="editRowId">
                <div class="mb-2"><label class="form-label fw-semibold" style="font-size:12px;">Product</label><input type="text" class="form-control" id="editRowProduct" readonly></div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px;">Expected</label><input type="number" class="form-control" id="editRowExpected"></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px;">Found</label><input type="number" class="form-control" id="editRowFound"></div>
                </div>
                <div class="alert alert-warning border-0 mt-3 py-2 px-3" style="font-size:11.5px;border-radius:6px;">
                    <i class="ri-information-line me-1"></i> This is queued on this device. Tap the cloud icon to sync when ready.
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="editRowSubmitBtn"><i class="ri-check-line"></i> Queue Edit</button>
            </div>
        </div>
    </div>
</div>

{{-- ══ DELETE CONFIRM MODAL ════════════════════════════════════════════ --}}
<div class="modal fade" id="deleteRowModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:380px;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
                <h5 class="mt-2">Delete <span id="deleteRowProductLabel" class="text-danger"></span>?</h5>
                <p class="text-muted" style="font-size:12px;">Queued offline — this count will reappear under Missing Products once synced.</p>
                <a href="#" class="btn btn-danger me-2 mt-2" id="deleteRowConfirmBtn">Yes, Queue Delete</a>
                <a href="#" class="btn btn-secondary mt-2" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>

{{-- ══ SYNC CONFIRM MODAL ══════════════════════════════════════════════ --}}
<div class="modal fade" id="mdSyncModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header mh-amber">
                <h5 class="modal-title mh-title"><i class="ri-upload-cloud-2-line"></i> Sync Offline Changes</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;">
                <p>You have <strong id="mdSyncCount">0</strong> unsynced change(s) on this device. Sync now to upload them and reload with the latest data?</p>
                @if($isRectified)
                <div class="alert alert-info border-0 py-2 px-3" style="font-size:11.5px;border-radius:6px;">
                    <i class="ri-information-line me-1"></i> This date is rectified — syncing will recompute the final figures and update the History record.
                </div>
                @endif
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Not Yet</button>
                <button type="button" class="btn btn-primary btn-sm" id="mdSyncConfirmBtn"><i class="ri-upload-cloud-2-line"></i> Sync Now</button>
            </div>
        </div>
    </div>
</div>

{{-- ══ DOWNLOAD MODAL ══════════════════════════════════════════════════ --}}
<div class="modal fade" id="downloadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-download-line"></i> Download Merged Data</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" style="font-size:13px;">Click a button to download merged data in spreadsheet/table form, or generate a PDF stocktaking report.</p>
                <div class="dt-buttons" id="mergedDataButtons"></div>
                <hr style="margin:14px 0;">
                <form action="{{ route('retail.operations.fullstocktaking.report.merged-data') }}" method="POST" target="_blank">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">
                    <input type="hidden" name="date" value="{{ $date }}">
                    <button type="submit" class="ai-dl-btn w-100" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;border:1px solid #d8ddf0;background:#fff;cursor:pointer;width:100%;">
                        <i class="ri-file-pdf-2-line" style="font-size:20px;color:#4B5EBD;flex-shrink:0;"></i>
                        <span style="flex:1;text-align:left;">
                            <span style="font-size:12.5px;font-weight:600;color:#1e293b;display:block;line-height:1.2;">Merged Data PDF Report</span>
                            <span style="font-size:11px;color:#64748b;display:block;margin-top:2px;">All merged lines for this branch and date</span>
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ══ DATE MODAL ═══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="fstDateModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog" style="max-width:400px;">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-calendar-event-line"></i> Stocktaking Date</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div class="dmc-grid">
                    <div class="dmc {{ !$isCustom ? 'active-sys' : '' }}" id="fstDmcSystem" onclick="fstSetDateMode('system')">
                        <div class="dmc-label">System date</div>
                        <div class="dmc-val">{{ Carbon::today()->format('d M Y') }}</div>
                    </div>
                    <div class="dmc {{ $isCustom ? 'active-cus' : '' }}" id="fstDmcCustom" onclick="fstSetDateMode('custom')">
                        <div class="dmc-label">Custom date</div>
                        <div class="dmc-val" id="fstDmcCustomVal">{{ $isCustom ? $displayDate : 'Pick a date' }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="fstDateForm">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                    <input type="hidden" name="fst_custom_date" id="fstDateFormValue" value="">
                    <div id="fstCustomDateRow" style="{{ !$isCustom ? 'display:none;' : '' }}">
                        <input type="date" class="form-control" id="fstCustomDateInput" value="{{ $date }}" oninput="fstPreviewDate(this.value)">
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ri-check-line"></i> Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ══ INFO MODAL ═══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="fstInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Merged Data</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;line-height:1.6;">
                <ul class="info-list mb-3">
                    <li>This is every product line merged from the Stocktaking tab for the selected branch + date.</li>
                    <li><strong>Edits and deletes work offline</strong> — change a row and it's queued on this device, then tap the cloud icon above to sync.</li>
                    <li>A deleted row reappears under Missing Products the next time that tab refreshes.</li>
                    <li>Tap the chart icon in the top bar to view current totals (Expected Value, Found Value, Difference).</li>
                </ul>
                @if($isRectified)
                <div class="alert alert-info border-0 py-2 px-3" style="font-size:12px;border-radius:6px;">
                    <i class="ri-information-line me-1"></i> This date is rectified. Edits are still queued offline — syncing will re-run the sales-netting calculation and update the History record automatically.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
'use strict';

const MD_BRANCH_ID = '{{ $branchId }}';
const MD_DATE       = '{{ $date }}';
const MD_QUEUE_KEY  = 'fullstocktaking_merged_data_queue_' + MD_BRANCH_ID + '_' + MD_DATE;

let mdQueue = [];

function mdUuid() { return 'md_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 9); }
function mdCsrf() { return document.querySelector('meta[name="csrf-token"]').getAttribute('content'); }
function loadMdQueue() { try { mdQueue = JSON.parse(localStorage.getItem(MD_QUEUE_KEY) || '[]'); } catch (e) { mdQueue = []; } }
function saveMdQueue() { localStorage.setItem(MD_QUEUE_KEY, JSON.stringify(mdQueue)); updateMdBadge(); }

function updateMdBadge() {
    const badge = document.getElementById('mdPendingBadge');
    const btn   = document.getElementById('mdSyncBtn');
    if (!badge || !btn) return;
    if (mdQueue.length > 0) { badge.textContent = mdQueue.length; badge.classList.add('show'); btn.classList.add('has-pending'); }
    else { badge.classList.remove('show'); btn.classList.remove('has-pending'); }
}

function mdQueueUpdate(rowId, expected, found) {
    const idx = mdQueue.findIndex(op => op.type === 'update' && op.id === rowId);
    const op = { client_uuid: mdUuid(), type: 'update', id: rowId, expected, found };
    if (idx >= 0) { mdQueue[idx] = op; } else { mdQueue.push(op); }
    saveMdQueue();
}
function mdQueueDelete(rowId) {
    mdQueue = mdQueue.filter(op => op.id !== rowId);
    mdQueue.push({ client_uuid: mdUuid(), type: 'delete', id: rowId });
    saveMdQueue();
}

function mdMarkRowDirty(rowId, expected, found) {
    const row = document.getElementById('row' + rowId);
    if (row) { row.classList.add('fst-row-dirty'); row.dataset.expected = expected; row.dataset.found = found; }
    const tag = document.getElementById('editedTag' + rowId);
    if (tag) tag.style.display = 'inline';
    const cellExp   = document.getElementById('cellExpected' + rowId);
    const cellFound = document.getElementById('cellFound' + rowId);
    const cellDiff  = document.getElementById('cellDiff' + rowId);
    if (cellExp)  cellExp.textContent  = parseFloat(expected).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (cellFound) cellFound.textContent = parseFloat(found).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (cellDiff) {
        const diff = parseFloat(found) - parseFloat(expected);
        cellDiff.textContent = diff.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        cellDiff.className = diff > 0 ? 'fst-diff-pos' : (diff < 0 ? 'fst-diff-neg' : 'fst-diff-zero');
    }
    const editBtn = row ? row.querySelector('.editBtn') : null;
    if (editBtn) { editBtn.dataset.expected = expected; editBtn.dataset.found = found; }
}

$(document).ready(function () {
    var csrf = $('meta[name="csrf-token"]').attr('content');
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf } });

    loadMdQueue(); updateMdBadge();

    var table = $('#mergedDataTable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        pageLength: 100,
        lengthMenu: [[50,100,250,-1],[50,100,250,'All']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  }
        ],
        buttons: [
            { extend: 'excelHtml5', title: @json($title ?? 'Full Stocktaking'), text: 'Excel', exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: @json($title ?? 'Full Stocktaking'), text: 'CSV',   exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: @json($title ?? 'Full Stocktaking'), text: 'PDF',    exportOptions: { columns: ':visible:not(:last-child)' } },
        ]
    });
    table.buttons().container().appendTo('#mergedDataButtons');

    $('#downloadModalBtn').on('click', function (e) { e.preventDefault(); $('#downloadModal').modal('show'); });

    document.getElementById('mdTotalsBtn')?.addEventListener('click', function (e) { e.preventDefault(); $('#mdTotalsModal').modal('show'); });

    mdQueue.forEach(function (op) {
        if (op.type === 'update') { mdMarkRowDirty(op.id, op.expected, op.found); }
        if (op.type === 'delete') { var row = document.getElementById('row' + op.id); if (row) row.style.opacity = '0.4'; }
    });

    $(document).on('click', '.editBtn', function () {
        $('#editRowId').val($(this).data('id'));
        $('#editRowProduct').val($(this).data('product'));
        $('#editRowExpected').val($(this).data('expected'));
        $('#editRowFound').val($(this).data('found'));
        $('#editRowModal').modal('show');
    });

    $('#editRowSubmitBtn').on('click', function () {
        var id       = parseInt($('#editRowId').val(), 10);
        var expected = parseFloat($('#editRowExpected').val());
        var found    = parseFloat($('#editRowFound').val());
        if (isNaN(expected) || expected < 0 || isNaN(found) || found < 0) { toastr.warning('Both values must be 0 or greater.'); return; }
        mdQueueUpdate(id, expected, found);
        mdMarkRowDirty(id, expected, found);
        $('#editRowModal').modal('hide');
        toastr.info('Edit queued offline. Sync to apply.', 'Queued');
    });

    var deleteId = null;
    $(document).on('click', '.deleteBtn', function () {
        deleteId = parseInt($(this).data('id'), 10);
        $('#deleteRowProductLabel').text($(this).data('product'));
        $('#deleteRowModal').modal('show');
    });

    $('#deleteRowConfirmBtn').on('click', function (e) {
        e.preventDefault();
        $('#deleteRowModal').modal('hide');
        mdQueueDelete(deleteId);
        var row = document.getElementById('row' + deleteId);
        if (row) row.style.opacity = '0.4';
        toastr.info('Removal queued offline. Sync to apply.', 'Queued');
    });

    document.getElementById('mdSyncBtn')?.addEventListener('click', function (e) {
        e.preventDefault();
        if (!mdQueue.length) { toastr.info('Nothing to sync — no offline changes pending.'); return; }
        document.getElementById('mdSyncCount').textContent = mdQueue.length;
        $('#mdSyncModal').modal('show');
    });

    document.getElementById('mdSyncConfirmBtn')?.addEventListener('click', function () {
        var btn = this; btn.disabled = true;
        fetch('{{ route("retail.operations.fullstocktaking.merged-data.sync") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': mdCsrf() },
            body: JSON.stringify({ ops: mdQueue }),
        })
        .then(res => res.json().then(data => ({ status: res.status, data })))
        .then(({ status, data }) => {
            btn.disabled = false;
            if (status === 200) { toastr.success(data.message, 'Synced'); mdQueue = []; saveMdQueue(); $('#mdSyncModal').modal('hide'); setTimeout(function () { location.reload(); }, 700); }
            else { toastr.error(data.message || 'Sync failed.', 'Error'); }
        })
        .catch(() => { btn.disabled = false; toastr.error('Could not reach the server. Your changes remain queued offline.', 'Network Error'); });
    });

    document.getElementById('fstInfoBtn')?.addEventListener('click', function (e) { e.preventDefault(); $('#fstInfoModal').modal('show'); });

    @if(Session::has('message'))
    toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
    @endif
});

function fstSetDateMode(mode) {
    document.getElementById('fstDmcSystem').classList.toggle('active-sys', mode === 'system');
    document.getElementById('fstDmcSystem').classList.toggle('active-cus', false);
    document.getElementById('fstDmcCustom').classList.toggle('active-cus', mode === 'custom');
    document.getElementById('fstDmcCustom').classList.toggle('active-sys', false);
    document.getElementById('fstCustomDateRow').style.display = mode === 'custom' ? '' : 'none';
    document.getElementById('fstDateFormValue').value = mode === 'system' ? '' : document.getElementById('fstCustomDateInput').value;
}
function fstPreviewDate(val) {
    if (!val) return;
    document.getElementById('fstDateFormValue').value = val;
    const d = new Date(val + 'T00:00:00');
    const mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    document.getElementById('fstDmcCustomVal').textContent = d.getDate() + ' ' + mo[d.getMonth()] + ' ' + d.getFullYear();
}
document.getElementById('fstDateChip')?.addEventListener('click', () => {
    document.getElementById('fstDateFormValue').value = '{{ $isCustom ? $date : "" }}';
    $('#fstDateModal').modal('show');
});
</script>
@endsection