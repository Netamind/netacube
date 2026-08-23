{{-- FILE: resources/views/operations/retail/partialstocktaking-data.blade.php --}}
@extends('operations.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    $pref = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();

    $branchId      = $pref->branch_id      ?? null;
    $pstCustomDate = $pref->pst_custom_date ?? null;
    $isCustom      = ! empty($pstCustomDate);
    $date          = $isCustom ? $pstCustomDate : Carbon::today()->toDateString();
    $displayDate   = Carbon::parse($date)->format('d M Y');

    $branches = DB::connection('tenant')->table('branches')
        ->where('sector', 'Retail')->where('status', 'active')->orderBy('name')->get();

    $branchName = $branchId
        ? (DB::connection('tenant')->table('branches')->where('id', $branchId)->value('name') ?? 'Branch not found')
        : null;

    $isRectified = $branchId && DB::connection('tenant')
        ->table('retail_partialstocktaking_summary')
        ->where('branch_id', $branchId)->where('date', $date)->where('status', 'completed')->exists();

    $data          = collect();
    $expectedValue = $foundValue = 0;

    if ($branchId) {
        // Newest-affected-first: ordered by last_activity_line_id (a central,
        // auto-increment sequence, not a device clock), so the product any
        // device just counted or edited is always the one on top — correct
        // even with several devices counting at once.
        $data = DB::connection('tenant')->table('retail_partialstocktaking')
            ->where('branch_id', $branchId)
            ->where('date', $date)
            ->orderByDesc('last_activity_line_id')
            ->orderByDesc('id')
            ->get();

        foreach ($data as $d) {
            // Expected is always expected_at_count — fixed, and only ever
            // moved by an explicit edit on this tab. expected_final is a
            // rectification-time-only figure and is never used for display.
            $expectedValue += $d->expected_at_count * $d->price;
            $foundValue    += $d->found * $d->price;
        }
    }

    $title = ($branchName ?? 'Branch') . ' Partial Stocktaking ' . $displayDate;
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ══════════════════════════════════════════════════════════════
   PARTIAL STOCKTAKING — STOCKTAKING DATA
══════════════════════════════════════════════════════════════ */
.content-page > .content > .container-fluid { padding-top: 16px; }

.pst-card { border: none; box-shadow: none; border-radius: 0; overflow: hidden; display: flex; flex-direction: column; background-color: transparent; height: auto; }

.pst-card-header { padding: 4px 10px !important; background-color: silver; color: #666666; display: flex; align-items: center; justify-content: space-between; flex: 0 0 auto; gap: 8px; }

#pstDateChip { height: 28px; padding: 0 8px; border-radius: 4px; background: none; color: #666666; border: none; font-weight: bold; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; white-space: nowrap; }
#pstDateChip:hover { color: #333333; }
#pstDateChip .pst-mode-tag { font-size: 9px; font-weight: 700; letter-spacing: .3px; padding: 2px 6px; border-radius: 8px; background: rgba(255,255,255,.35); color: #555555; text-transform: uppercase; }
#pstDateChip.custom-mode .pst-mode-tag { background: #fcd34d; color: #7c4a03; }
#pstDateChip .pst-edit-pencil { font-size: 11px; opacity: .65; }

.pst-hdr-left { display: flex; align-items: center; gap: 8px; min-width: 0; }
#pstActionsBtn { height: 28px; padding: 0 10px; border-radius: 4px; border: 1px solid rgba(102,102,102,.35); background: rgba(255,255,255,.35); color: #555555; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; white-space: nowrap; text-decoration: none; flex-shrink: 0; }
#pstActionsBtn i { font-size: 14px; }
#pstActionsBtn:hover { background: rgba(255,255,255,.6); color: #333333; }

.pst-hdr-actions { display: flex; align-items: center; gap: 2px; flex-shrink: 0; }
.pst-hdr-btn { height: 24px; width: 24px; border: none; background: none; display: inline-flex; align-items: center; justify-content: center; border-radius: 0; color: #666666; font-size: 16px; cursor: pointer; position: relative; padding: 1px; text-decoration: none; }
.pst-hdr-btn:hover { color: #333333; }
.pst-hdr-divider { width: 1px; height: 16px; background: #8a8a8a; margin: 0 6px; opacity: .6; }

.pst-branch-row { background: linear-gradient(to right, #4B5EBD, #576CC0); padding: 7px 10px; flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: nowrap; }
.pst-branch-left { display: flex; flex-direction: row; align-items: center; gap: 10px; min-width: 0; flex: 1 1 auto; }
#pstBranchForm { margin: 0; display: inline-flex; align-items: center; min-width: 0; }
#pstBranchSelect { border: none; background: transparent; color: silver; font-size: 16px; font-weight: 600; cursor: pointer; padding: 0 0 0 2px; outline: none; max-width: 280px; }
#pstBranchSelect option { color: #1e293b; background: #fff; font-size: 14px; }
#pstBranchSelect:disabled { opacity: 1; color: silver; -webkit-text-fill-color: silver; cursor: not-allowed; }

.pst-rectified-tag { font-size: 10px; font-weight: 700; background: #d1fae5; color: #065f46; border-radius: 5px; padding: 3px 8px; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0; }

.pst-branch-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.pst-page-label { font-size: 12px; font-weight: 600; color: silver; white-space: nowrap; letter-spacing: .2px; }
.pst-bar-icon-btn { height: 30px; width: 30px; border: none; background: none; display: inline-flex; align-items: center; justify-content: center; color: silver; font-size: 17px; cursor: pointer; flex-shrink: 0; border-radius: 4px; padding: 0; position: relative; text-decoration: none; }
.pst-bar-icon-btn:hover { background: rgba(255,255,255,.12); }
.pst-pending-badge { position: absolute; top: -5px; right: -5px; background: #dc2626; color: #fff; font-size: 9px; font-weight: 700; min-width: 16px; height: 16px; border-radius: 8px; display: none; align-items: center; justify-content: center; padding: 0 4px; border: 1px solid #fff; }
.pst-pending-badge.show { display: flex; }

.pst-card-body { flex: 0 1 auto; min-height: 0; display: flex; flex-direction: column; padding: 0 !important; overflow: hidden; background-color: #fff; border-radius: 0 0 10px 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }

.pst-placeholder-wrap { padding: 48px 20px; text-align: center; color: #94a3b8; }
.pst-placeholder-wrap i { font-size: 52px; display: block; margin-bottom: 12px; color: #c8d0ed; }
.pst-placeholder-wrap h5 { color: #64748b; font-weight: 600; }

.pst-table-wrap { flex: 0 1 auto; min-height: 0; overflow-x: auto; padding: 0 1.5rem 1.5rem 1.5rem; }
.pst-table-wrap table.dataTable { margin-top: 0 !important; }

.dt-buttons .btn { background: transparent !important; background-image: none !important; box-shadow: none !important; border-color: #5bc0de; color: #5bc0de; }
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

#pstDataTable thead th, table.dataTable thead th { text-align: center !important; vertical-align: middle !important; }
#pstDataTable thead th:first-child, table.dataTable thead th:first-child { text-align: left !important; }
#pstDataTable tbody td, table.dataTable tbody td { text-align: center !important; vertical-align: middle !important; }
#pstDataTable tbody td:first-child, table.dataTable tbody td:first-child { text-align: left !important; }

/* ── Fixed column style (matches history tables) ─────────────────── */
table.dataTable.fixedHeader-floating,
table.dataTable.fixedHeader-locked { background:#fff !important; border-bottom:none !important; }
table.dataTable thead th.fixedHeader-floating { background:#e2e2e9 !important; }

.pst-diff-pos { color: #059669; font-weight: 700; }
.pst-diff-neg { color: #dc2626; font-weight: 700; }
.pst-diff-zero { color: #64748b; }

.pd-inline-input { width: 90px; border: 1px solid silver; border-radius: 4px; padding: 3px 6px; font-size: 12px; font-weight: 600; text-align: center; color: #1a1a1a; background: #fff; outline: none; transition: border-color .15s; }
.pd-inline-input:focus { border-color: silver; background: #fff; outline: none; box-shadow: none; }

.pst-row-edited-badge { font-size: 9px; font-weight: 700; background: #fde68a; color: #92400e; border-radius: 5px; padding: 2px 7px; margin-left: 6px; display: none; }
.pst-row-deleted-badge { font-size: 9px; font-weight: 700; background: #fecaca; color: #991b1b; border-radius: 5px; padding: 2px 7px; margin-left: 6px; display: none; }
.pst-row-new-badge { font-size: 9px; font-weight: 700; background: #dbeafe; color: #1d4ed8; border-radius: 5px; padding: 2px 7px; margin-left: 6px; }

.action-icon { cursor: pointer; padding: 4px 6px; border-radius: 6px; }
.action-icon:hover { background: #f0f0f0; }

.mh-pos { background: linear-gradient(to right, #4B5EBD, #576CC0); padding: 10px 16px !important; border-bottom: none; }
.mh-pos-title { color: #fff; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.mh-amber { background: linear-gradient(135deg,#d97706,#f59e0b); padding: 12px 18px !important; border-bottom: none; }
.mh-title { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close-w { filter: brightness(0) invert(1); opacity: .8; }
.mh-close-w:hover { opacity: 1; }
.info-list li { margin-bottom: 8px; }

.pst-action-link { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 8px; background: #f8f9fa; border: 1px solid #e2e8f0; text-decoration: none; color: #1e293b; transition: background .15s; }
.pst-action-link:hover { background: #f1f5ff; color: #1e293b; }
.pst-action-link .fal-icon { font-size: 20px; flex-shrink: 0; }
.pst-action-link .fal-title { font-size: 13px; font-weight: 600; }
.pst-action-link .fal-sub { font-size: 11px; color: #64748b; }
.pst-action-link .fal-arrow { margin-left: auto; color: #94a3b8; font-size: 18px; flex-shrink: 0; }

.totals-stat-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
.totals-stat-row:last-child { border-bottom: none; }
.totals-stat-row .lbl { color: #64748b; }
.totals-stat-row .val { font-weight: 800; color: #1e293b; font-size: 14px; }
.totals-stat-row .val.diff-pos { color: #059669; }
.totals-stat-row .val.diff-neg { color: #dc2626; }
.totals-stat-row .val.accent { color: #3b4fa0; }

.date-mode-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.dmc { padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer; }
.dmc.active-sys { border-color: #4B5EBD; background: #eff3ff; }
.dmc.active-cus { border-color: #d97706; background: #fffbeb; }
.dmc-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.dmc-val { font-size: 13px; font-weight: 600; color: #64748b; }
.dmc.active-sys .dmc-val { color: #4B5EBD; }
.dmc.active-cus .dmc-val { color: #d97706; }

input[type=number]::-webkit-outer-spin-button, input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }

@media (max-width: 768px) {
    .pst-card { height: auto !important; margin: 8px; }
    .content-page { padding: 0 !important; }
    .content { padding: 0 !important; }
    .content-page > .content > .container-fluid { padding-top: 0 !important; padding-left: 0 !important; padding-right: 0 !important; }
    .pst-table-wrap { padding: 0 10px 12px; }
    .modal-dialog { margin: 1.25rem auto !important; max-width: calc(100% - 24px) !important; }
    .modal-content { border-radius: 10px !important; }
    #pstActionsBtn { font-size: 11px; padding: 0 8px; height: 26px; }
    .pst-card-header { gap: 4px; }
    .pst-hdr-actions { gap: 0; }
    .pst-hdr-btn { width: 24px; height: 24px; font-size: 15px; padding: 0; }
    .pst-hdr-divider { margin: 0 3px; }
    .pst-branch-row { gap: 6px; }
    .pst-page-label { font-size: 11px; }
    .pst-bar-icon-btn { height: 26px; width: 26px; font-size: 15px; }
    .pd-inline-input { width: 72px; font-size: 12px; }
}
</style>

<div class="progress" id="progressBar" role="progressbar" style="height:8px;transform:rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">

<div class="pst-card">

    <div class="pst-card-header">
        <div class="pst-hdr-left">
            <button type="button" id="pstDateChip" class="{{ $isCustom ? 'custom-mode' : '' }}" title="Change stocktaking date">
                <i class="ri-calendar-line"></i> {{ $displayDate }}
                <span class="pst-mode-tag">{{ $isCustom ? 'Custom' : 'Today' }}</span>
                <i class="ri-pencil-line pst-edit-pencil"></i>
            </button>
            <button type="button" id="pstActionsBtn" onclick="$('#pstNavActionsModal').modal('show')" title="Quick navigation">
                <i class="ri-layout-grid-line"></i> <span class="fab-label">PS Actions</span>
            </button>
        </div>
        <div class="pst-hdr-actions">
            @if($branchId)
                <button type="button" class="pst-hdr-btn" id="pdTotalsBtn" title="Stocktaking Data Totals"><i class="ri-bar-chart-box-line"></i></button>
                <span class="pst-hdr-divider"></span>
            @endif
            <button type="button" class="pst-hdr-btn" id="pstInfoBtn" title="About Stocktaking Data"><i class="ri-information-line"></i></button>
        </div>
    </div>

    <div class="pst-branch-row">
        <div class="pst-branch-left">
            <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="pstBranchForm">
                @csrf
                <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                <select name="branch_id" id="pstBranchSelect" disabled onchange="document.getElementById('pstBranchForm').submit()">
                    <option value="" hidden>{{ $branchName ?? '— Select Branch —' }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </form>
            @if($isRectified)
                <span class="pst-rectified-tag"><i class="ri-lock-line"></i> Rectified</span>
            @endif
        </div>
        <div class="pst-branch-right">
            <span class="pst-page-label">Stocktaking Data</span>
            <a href="#" class="pst-bar-icon-btn" id="pdSyncBtn" title="Sync offline changes">
                <i class="ri-upload-cloud-2-line"></i>
                <span class="pst-pending-badge" id="pdPendingBadge"></span>
            </a>
        </div>
    </div>

    <div class="pst-card-body">
        @if(!$branchId)
            <div class="pst-placeholder-wrap">
                <i class="ri-store-2-line"></i>
                <h5>No Branch Selected</h5>
                <p style="font-size:13px;">Select a branch from the bar above to view stocktaking data.</p>
            </div>
        @elseif($data->isEmpty())
            <div class="pst-placeholder-wrap">
                <i class="ri-stack-line"></i>
                <h5>Nothing Counted Yet</h5>
                <p style="font-size:13px;">No products have been counted live for {{ $branchName }} on {{ $displayDate }}.</p>
            </div>
        @else
        <div class="pst-table-wrap">
            <table id="pstDataTable" class="table table-sm table-striped row-border order-column w-100">
                <thead style="background-color:#e2e2e9">
                    <tr>
                        <th>Product</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Expected</th>
                        <th>Found</th>
                        <th>Diff</th>
                        <th>Del</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $d)
                    @php $exp = $d->expected_at_count; $diff = $d->found - $exp; @endphp
                    <tr id="row{{ $d->id }}" data-id="{{ $d->id }}">
                        <td>
                            <span data-bs-toggle="tooltip" data-bs-placement="top"
                                  title="Merged from {{ $d->merge_count }} line(s)">{{ $d->product_name }}</span>
                            <span class="pst-row-edited-badge" id="editedTag{{ $d->id }}">Edited</span>
                            <span class="pst-row-deleted-badge" id="deletedTag{{ $d->id }}">Deleted</span>
                        </td>
                        <td>{{ $d->unit }}</td>
                        <td>{{ number_format($d->price, 2) }}</td>
                        <td data-order="{{ number_format($d->expected_at_count, 2, '.', '') }}">
                            <input type="number" class="pd-inline-input" id="inpExpected{{ $d->id }}"
                                   data-id="{{ $d->id }}" data-field="expected"
                                   value="{{ number_format($d->expected_at_count, 2, '.', '') }}" step="0.01" min="0">
                        </td>
                        <td data-order="{{ number_format($d->found, 2, '.', '') }}">
                            <input type="number" class="pd-inline-input" id="inpFound{{ $d->id }}"
                                   data-id="{{ $d->id }}" data-field="found"
                                   value="{{ number_format($d->found, 2, '.', '') }}" step="0.01" min="0">
                        </td>
                        <td class="{{ $diff > 0 ? 'pst-diff-pos' : ($diff < 0 ? 'pst-diff-neg' : 'pst-diff-zero') }}" id="cellDiff{{ $d->id }}">
                            {{ number_format($diff, 2) }}
                        </td>
                        <td>
                            <i class="ri-delete-bin-line action-icon text-danger deleteBtn" data-id="{{ $d->id }}" data-product="{{ $d->product_name }}"></i>
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


{{-- ══ NAV ACTIONS MODAL ══ --}}
<div class="modal fade" id="pstNavActionsModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:360px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-layout-grid-line"></i> Quick Actions</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
                <a href="{{ route('retail.operations.partialstocktaking') }}" class="pst-action-link">
                    <i class="ri-scales-3-line fal-icon" style="color:#4B5EBD;"></i>
                    <div><div class="fal-title">Stocktaking</div><div class="fal-sub">Count products live for this branch and date</div></div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
                <a href="{{ route('retail.operations.partialstocktaking.actions-and-info') }}" class="pst-action-link">
                    <i class="ri-flashlight-line fal-icon" style="color:#059669;"></i>
                    <div><div class="fal-title">Actions &amp; Info</div><div class="fal-sub">Summary, rectify, remarks and reports</div></div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
            </div>
        </div>
    </div>
</div>


@if($branchId)
{{-- ══ TOTALS MODAL ══ --}}
<div class="modal fade" id="pdTotalsModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:380px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-bar-chart-box-line"></i> Stocktaking Data Totals</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div class="totals-stat-row"><span class="lbl">Branch</span><span class="val" style="font-size:13px;">{{ $branchName }}</span></div>
                <div class="totals-stat-row"><span class="lbl">Date</span><span class="val" style="font-size:13px;">{{ $displayDate }}</span></div>
                <div class="totals-stat-row"><span class="lbl">Lines</span><span class="val accent">{{ $data->count() }}</span></div>
                <div class="totals-stat-row"><span class="lbl">Expected Value (EV)</span><span class="val">{{ number_format($expectedValue, 2) }}</span></div>
                <div class="totals-stat-row"><span class="lbl">Found Value (FV)</span><span class="val">{{ number_format($foundValue, 2) }}</span></div>
                @php $diffVal = $foundValue - $expectedValue; @endphp
                <div class="totals-stat-row">
                    <span class="lbl">Difference (FV − EV)</span>
                    <span class="val {{ $diffVal > 0 ? 'diff-pos' : ($diffVal < 0 ? 'diff-neg' : '') }}">{{ number_format($diffVal, 2) }}</span>
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


{{-- ══ DELETE CONFIRM MODAL ══ --}}
<div class="modal fade" id="deleteRowModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:380px;margin:1.75rem auto;">
        <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
            <div class="modal-body text-center pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                <h4 class="mt-2">Delete <span id="deleteRowProductLabel" class="text-danger"></span>?</h4>
                <h5 style="font-size:13px;color:#64748b;font-weight:400;">Queued offline — this record will be removed once synced.</h5>
                <a href="#" class="btn btn-danger me-2 mt-3" id="deleteRowConfirmBtn">Yes, Queue Delete</a>
                <a href="#" class="btn btn-info mt-3" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>


{{-- ══ SYNC CONFIRM MODAL ══ --}}
<div class="modal fade" id="pdSyncModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-amber">
                <h5 class="modal-title mh-title"><i class="ri-upload-cloud-2-line"></i> Sync Offline Changes</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;">
                <p>You have <strong id="pdSyncCount">0</strong> unsynced change(s) on this device. Sync now to upload them, auto-resolve against live sales, and reload with the latest data?</p>
                @if($isRectified)
                <div class="alert alert-info border-0 py-2 px-3" style="font-size:11.5px;border-radius:6px;">
                    <i class="ri-information-line me-1"></i> This date is rectified — syncing still pushes the corrected figure straight to live stock and refreshes the summary totals.
                </div>
                @endif
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Not Yet</button>
                <button type="button" class="btn btn-primary btn-sm" id="pdSyncConfirmBtn"><i class="ri-upload-cloud-2-line"></i> Sync Now</button>
            </div>
        </div>
    </div>
</div>


{{-- ══ DOWNLOAD MODAL ══ --}}
<div class="modal fade" id="downloadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-download-line"></i> Download Stocktaking Data</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <p class="mb-2" style="font-size:13px;color:#475569;">Download this table as a spreadsheet. For the formatted PDF report with remarks, use Actions &amp; Info.</p>
                <div class="dt-buttons" id="pstDataButtons"></div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif


{{-- ══ DATE MODAL ══ --}}
<div class="modal fade" id="pstDateModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog" style="max-width:400px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-calendar-event-line"></i> Stocktaking Date</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div class="date-mode-toggle">
                    <div class="dmc {{ !$isCustom ? 'active-sys' : '' }}" id="pstDmcSystem" onclick="pstSetDateMode('system')">
                        <div class="dmc-label">System date</div>
                        <div class="dmc-val">{{ Carbon::today()->format('d M Y') }}</div>
                    </div>
                    <div class="dmc {{ $isCustom ? 'active-cus' : '' }}" id="pstDmcCustom" onclick="pstSetDateMode('custom')">
                        <div class="dmc-label">Custom date</div>
                        <div class="dmc-val" id="pstDmcCustomVal">{{ $isCustom ? $displayDate : 'Pick a date' }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="pstDateForm">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                    <input type="hidden" name="pst_custom_date" id="pstDateFormValue" value="">
                    <div id="pstCustomDateRow" style="{{ !$isCustom ? 'display:none;' : '' }}">
                        <input type="date" class="form-control" id="pstCustomDateInput" value="{{ $date }}" oninput="pstPreviewDate(this.value)">
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


{{-- ══ INFO MODAL ══ --}}
<div class="modal fade" id="pstInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-information-line"></i> About Stocktaking Data</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;line-height:1.6;">
                <ul class="info-list mb-3" style="padding-left:18px;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <li>Every product counted live from the Stocktaking tab for the selected branch + date, newest activity first.</li>
                    <li><strong>Expected and Found are editable inline</strong> — click into a cell, change the value, tab or click away. The edit is queued offline instantly and syncs later.</li>
                    <li><strong>Expected never changes on its own.</strong> It only moves when you edit it here — nothing in the background (sales, syncing, refreshing, viewing another tab) ever touches it.</li>
                    <li>Editing here never creates a duplicate row for a product — it edits the existing one and moves it back to the top of this list.</li>
                    <li>Tap the cloud icon in the blue bar to sync all queued edits and deletes. Syncing pushes the corrected quantity straight to live stock — it never rewrites Expected.</li>
                    <li>Sales made after a product was counted are netted off and itemised only at rectification — see "Sales Since Count" on the Actions &amp; Info tab.</li>
                    <li>Tap the chart icon in the top bar to view current totals (Expected Value, Found Value, Difference).</li>
                </ul>
                @if($isRectified)
                <div class="alert alert-info border-0 py-2 px-3" style="font-size:12px;border-radius:6px;">
                    <i class="ri-information-line me-1"></i> This date is rectified. Edits are still queued offline — syncing keeps pushing corrected figures to live stock and refreshes the Actions &amp; Info summary automatically.
                </div>
                @endif
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
'use strict';

const PD_BRANCH_ID = '{{ $branchId }}';
const PD_DATE      = '{{ $date }}';
const PD_QUEUE_KEY = 'partialstocktaking_data_queue_' + PD_BRANCH_ID + '_' + PD_DATE;

let pdQueue = [];

function pdUuid() { return 'pd_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 9); }
function pdCsrf() { return document.querySelector('meta[name="csrf-token"]').getAttribute('content'); }

function loadPdQueue() { try { pdQueue = JSON.parse(localStorage.getItem(PD_QUEUE_KEY) || '[]'); } catch (e) { pdQueue = []; } }
function savePdQueue() { localStorage.setItem(PD_QUEUE_KEY, JSON.stringify(pdQueue)); updatePdBadge(); }

function updatePdBadge() {
    const badge = document.getElementById('pdPendingBadge');
    const btn   = document.getElementById('pdSyncBtn');
    if (!badge || !btn) return;
    if (pdQueue.length > 0) { badge.textContent = pdQueue.length; badge.classList.add('show'); }
    else { badge.classList.remove('show'); }
}

function pdQueueUpdate(rowId, expected, found) {
    const idx = pdQueue.findIndex(op => op.type === 'update' && op.id === rowId);
    const op  = { client_uuid: pdUuid(), type: 'update', id: rowId, expected, found };
    if (idx >= 0) { pdQueue[idx] = op; } else { pdQueue.push(op); }
    savePdQueue();
}
function pdQueueDelete(rowId) {
    pdQueue = pdQueue.filter(op => op.id !== rowId);
    pdQueue.push({ client_uuid: pdUuid(), type: 'delete', id: rowId });
    savePdQueue();
}

function pdRefreshDiff(rowId) {
    const inpE = document.getElementById('inpExpected' + rowId);
    const inpF = document.getElementById('inpFound'    + rowId);
    const cell = document.getElementById('cellDiff'    + rowId);
    if (!inpE || !inpF || !cell) return;
    const expected = parseFloat(inpE.value) || 0;
    const found    = parseFloat(inpF.value) || 0;
    const diff     = found - expected;
    cell.textContent = diff.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    cell.className   = diff > 0 ? 'pst-diff-pos' : (diff < 0 ? 'pst-diff-neg' : 'pst-diff-zero');
}

function pdMarkRowDirty(rowId) {
    const deletedTag = document.getElementById('deletedTag' + rowId);
    if (deletedTag && deletedTag.style.display === 'inline') return;
    const tag = document.getElementById('editedTag' + rowId);
    if (tag) tag.style.display = 'inline';
}
function pdMarkRowDeleted(rowId) {
    const editedTag = document.getElementById('editedTag' + rowId);
    if (editedTag) editedTag.style.display = 'none';
    const tag = document.getElementById('deletedTag' + rowId);
    if (tag) tag.style.display = 'inline';
}

$(document).ready(function () {

    loadPdQueue();
    updatePdBadge();

    @if($branchId && $data->isNotEmpty())

    pdQueue.forEach(function (op) {
        if (op.type === 'update') {
            const inpE = document.getElementById('inpExpected' + op.id);
            const inpF = document.getElementById('inpFound'    + op.id);
            if (inpE) { inpE.value = parseFloat(op.expected).toFixed(2); inpE.closest('td').setAttribute('data-order', inpE.value); }
            if (inpF) { inpF.value = parseFloat(op.found).toFixed(2);    inpF.closest('td').setAttribute('data-order', inpF.value); }
            pdRefreshDiff(op.id);
            pdMarkRowDirty(op.id);
        }
        if (op.type === 'delete') { pdMarkRowDeleted(op.id); }
    });

    /* Server orders rows by last_activity_line_id DESC ("latest affected on
       top"). Ordering is enabled so users can sort by any column, but we
       start with no active sort (order: []) so that initial order is kept
       until the user clicks a header. Expected/Found are live <input>
       fields, so their <td> carries a data-order attribute (kept in sync
       on every edit below) for DataTables to sort by instead of the empty
       cell text. Del is an icon-only action column, so it stays excluded
       from sorting. */
    var table = $('#pstDataTable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        pageLength: 100,
        lengthMenu: [[50, 100, 250, -1], [50, 100, 250, 'All']],
        order: [],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start' },
            { targets: -1,     orderable: false },
        ],
        buttons: [
            { extend: 'excelHtml5', title: @json($title), text: 'Excel', exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: @json($title), text: 'CSV',   exportOptions: { columns: ':visible:not(:last-child)' } },
        ],
    });
    table.buttons().container().appendTo('#pstDataButtons');

    // Merge-count hover tooltip on product names (replaces the old Lines column)
    document.querySelectorAll('#pstDataTable [data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    $(document).on('change blur', '.pd-inline-input', function () {
        const rowId = parseInt($(this).data('id'), 10);
        const inpE  = document.getElementById('inpExpected' + rowId);
        const inpF  = document.getElementById('inpFound'    + rowId);
        if (!inpE || !inpF) return;

        let valE = parseFloat(inpE.value);
        let valF = parseFloat(inpF.value);
        if (isNaN(valE) || valE < 0) { valE = 0; inpE.value = '0.00'; }
        if (isNaN(valF) || valF < 0) { valF = 0; inpF.value = '0.00'; }
        inpE.value = valE.toFixed(2);
        inpF.value = valF.toFixed(2);

        inpE.closest('td').setAttribute('data-order', valE);
        inpF.closest('td').setAttribute('data-order', valF);
        table.cell(inpE.closest('td')).invalidate('dom');
        table.cell(inpF.closest('td')).invalidate('dom').draw(false);

        pdQueueUpdate(rowId, valE, valF);
        pdRefreshDiff(rowId);
        pdMarkRowDirty(rowId);
    });

    $(document).on('keydown', '.pd-inline-input', function (e) { if (e.key === 'Enter') { $(this).blur(); } });

    var deleteId = null;
    $(document).on('click', '.deleteBtn', function () {
        deleteId = parseInt($(this).data('id'), 10);
        $('#deleteRowProductLabel').text($(this).data('product'));
        $('#deleteRowModal').modal('show');
    });

    $('#deleteRowConfirmBtn').on('click', function (e) {
        e.preventDefault();
        $('#deleteRowModal').modal('hide');
        pdQueueDelete(deleteId);
        pdMarkRowDeleted(deleteId);
        toastr.info('Removal queued offline. Sync to apply.', 'Queued');
    });

    document.getElementById('pdTotalsBtn')?.addEventListener('click', function (e) { e.preventDefault(); $('#pdTotalsModal').modal('show'); });
    document.getElementById('downloadModalBtn')?.addEventListener('click', function (e) { e.preventDefault(); $('#downloadModal').modal('show'); });

    @endif

    document.getElementById('pdSyncBtn')?.addEventListener('click', function (e) {
        e.preventDefault();
        if (!pdQueue.length) { toastr.info('Nothing to sync — no offline changes pending.'); return; }
        document.getElementById('pdSyncCount').textContent = pdQueue.length;
        $('#pdSyncModal').modal('show');
    });

    document.getElementById('pdSyncConfirmBtn')?.addEventListener('click', function () {
        const btn = this;
        btn.disabled = true;
        fetch('{{ route("retail.operations.partialstocktaking.data.sync") }}', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': pdCsrf() },
            body:    JSON.stringify({ ops: pdQueue }),
        })
        .then(res => res.json().then(data => ({ status: res.status, data })))
        .then(({ status, data }) => {
            btn.disabled = false;
            if (status === 200) {
                toastr.success(data.message, 'Synced');
                pdQueue = [];
                savePdQueue();
                $('#pdSyncModal').modal('hide');
                setTimeout(function () { location.reload(); }, 700);
            } else {
                toastr.error(data.message || 'Sync failed.', 'Error');
            }
        })
        .catch(() => {
            btn.disabled = false;
            toastr.error('Could not reach the server. Your changes remain queued offline.', 'Network Error');
        });
    });

    document.getElementById('pstInfoBtn')?.addEventListener('click', function (e) { e.preventDefault(); $('#pstInfoModal').modal('show'); });

    @if(Session::has('message'))
    toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
    @endif
});

function pstSetDateMode(mode) {
    document.getElementById('pstDmcSystem').classList.toggle('active-sys', mode === 'system');
    document.getElementById('pstDmcCustom').classList.toggle('active-cus', mode === 'custom');
    document.getElementById('pstCustomDateRow').style.display = mode === 'custom' ? '' : 'none';
    document.getElementById('pstDateFormValue').value = mode === 'system' ? '' : document.getElementById('pstCustomDateInput').value;
}
function pstPreviewDate(val) {
    if (!val) return;
    document.getElementById('pstDateFormValue').value = val;
    const d  = new Date(val + 'T00:00:00');
    const mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    document.getElementById('pstDmcCustomVal').textContent = d.getDate() + ' ' + mo[d.getMonth()] + ' ' + d.getFullYear();
}
document.getElementById('pstDateChip')?.addEventListener('click', () => {
    document.getElementById('pstDateFormValue').value = '{{ $isCustom ? $date : "" }}';
    $('#pstDateModal').modal('show');
});
</script>
@endsection