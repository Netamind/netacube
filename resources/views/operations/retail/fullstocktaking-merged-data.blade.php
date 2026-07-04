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
        : null;

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

    $title = ($branchName ?? 'Branch') . ' Full Stocktaking ' . $displayDate;
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ══════════════════════════════════════════════════════════════
   MERGED DATA
══════════════════════════════════════════════════════════════ */

.content-page > .content > .container-fluid { padding-top: 16px; }

.fst-card {
    border: none; box-shadow: none; border-radius: 0; overflow: hidden;
    display: flex; flex-direction: column; background-color: transparent;
    height: auto;
}

/* ── Silver header bar ── */
.fst-card-header {
    padding: 4px 10px !important;
    background-color: silver;
    color: #666666;
    display: flex; align-items: center; justify-content: space-between;
    flex: 0 0 auto; gap: 8px;
}

#fstDateChip {
    height: 28px; padding: 0 8px; border-radius: 4px;
    background: none; color: #666666; border: none;
    font-weight: bold; font-size: 14px;
    display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
    white-space: nowrap;
}
#fstDateChip:hover { color: #333333; }
#fstDateChip .fst-mode-tag {
    font-size: 9px; font-weight: 700; letter-spacing: .3px;
    padding: 2px 6px; border-radius: 8px;
    background: rgba(255,255,255,.35); color: #555555; text-transform: uppercase;
}
#fstDateChip.custom-mode .fst-mode-tag { background: #fcd34d; color: #7c4a03; }
#fstDateChip .fst-edit-pencil { font-size: 11px; opacity: .65; }

.fst-hdr-left { display: flex; align-items: center; gap: 8px; min-width: 0; }

#fstActionsBtn {
    height: 28px; padding: 0 10px; border-radius: 4px;
    border: 1px solid rgba(102,102,102,.35); background: rgba(255,255,255,.35);
    color: #555555; font-size: 12px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 5px; cursor: pointer;
    white-space: nowrap; text-decoration: none; flex-shrink: 0;
}
#fstActionsBtn i { font-size: 14px; }
#fstActionsBtn:hover { background: rgba(255,255,255,.6); color: #333333; }

.fst-hdr-actions { display: flex; align-items: center; gap: 2px; flex-shrink: 0; }
.fst-hdr-btn {
    height: 24px; width: 24px; border: none; background: none;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 0; color: #666666; font-size: 16px;
    cursor: pointer; position: relative; padding: 1px; text-decoration: none;
}
.fst-hdr-btn:hover { color: #333333; }
.fst-hdr-divider { width: 1px; height: 16px; background: #8a8a8a; margin: 0 6px; opacity: .6; }

/* ── Blue branch bar ── */
.fst-branch-row {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 7px 10px; flex: 0 0 auto;
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; flex-wrap: nowrap;
}
.fst-branch-left {
    display: flex; flex-direction: row; align-items: center;
    gap: 10px; min-width: 0; flex: 1 1 auto;
}
#fstBranchForm { margin: 0; display: inline-flex; align-items: center; min-width: 0; }
#fstBranchSelect {
    border: none; background: transparent; color: silver;
    font-size: 16px; font-weight: 600; cursor: pointer;
    padding: 0 0 0 2px; outline: none; max-width: 280px;
}
#fstBranchSelect option { color: #1e293b; background: #fff; font-size: 14px; }
#fstBranchSelect:disabled {
    opacity: 1; color: silver; -webkit-text-fill-color: silver; cursor: not-allowed;
}

.fst-rectified-tag {
    font-size: 10px; font-weight: 700; background: #d1fae5; color: #065f46;
    border-radius: 5px; padding: 3px 8px; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;
}

.fst-branch-right {
    display: flex; align-items: center; gap: 8px; flex-shrink: 0;
}
.fst-page-label {
    font-size: 12px; font-weight: 600; color: silver;
    white-space: nowrap; letter-spacing: .2px;
}
.fst-bar-icon-btn {
    height: 30px; width: 30px; border: none; background: none;
    display: inline-flex; align-items: center; justify-content: center;
    color: silver; font-size: 17px; cursor: pointer; flex-shrink: 0;
    border-radius: 4px; padding: 0; position: relative; text-decoration: none;
}
.fst-bar-icon-btn:hover { background: rgba(255,255,255,.12); }
.fst-pending-badge {
    position: absolute; top: -5px; right: -5px;
    background: #dc2626; color: #fff; font-size: 9px; font-weight: 700;
    min-width: 16px; height: 16px; border-radius: 8px;
    display: none; align-items: center; justify-content: center;
    padding: 0 4px; border: 1px solid #fff;
}
.fst-pending-badge.show { display: flex; }

/* ── Card body ── */
.fst-card-body {
    flex: 0 1 auto; min-height: 0; display: flex;
    flex-direction: column; padding: 0 !important; overflow: hidden;
    background-color: #fff;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.fst-placeholder-wrap { padding: 48px 20px; text-align: center; color: #94a3b8; }
.fst-placeholder-wrap i { font-size: 52px; display: block; margin-bottom: 12px; color: #c8d0ed; }
.fst-placeholder-wrap h5 { color: #64748b; font-weight: 600; }

/* ── Table area ── */
.fst-table-wrap {
    flex: 0 1 auto; min-height: 0; max-height: calc(100vh - 230px);
    overflow-y: auto; overflow-x: auto;
    padding: 0 1.5rem 1.5rem 1.5rem;
}
.fst-table-wrap table.dataTable { margin-top: 0 !important; }

.dt-buttons .btn {
    background: transparent !important; background-image: none !important;
    box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

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

/* ── Inline edit inputs (silver border, matches Branch Products neutral input look) ── */
.md-inline-input {
    width: 90px;
    border: 1px solid silver;
    border-radius: 4px;
    padding: 3px 6px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    color: #1a1a1a;
    background: #fff;
    outline: none;
    transition: border-color .15s;
}
.md-inline-input:focus {
    border-color: silver;
    background: #fff;
    outline: none;
    box-shadow: none;
}

/* ── Edited / Deleted badges next to product name ── */
.fst-row-edited-badge {
    font-size: 9px; font-weight: 700; background: #fde68a; color: #92400e;
    border-radius: 5px; padding: 2px 7px; margin-left: 6px; display: none;
}
.fst-row-deleted-badge {
    font-size: 9px; font-weight: 700; background: #fecaca; color: #991b1b;
    border-radius: 5px; padding: 2px 7px; margin-left: 6px; display: none;
}

.action-icon { cursor: pointer; padding: 4px 6px; border-radius: 6px; }
.action-icon:hover { background: #f0f0f0; }

/* ── Modal headers ── */
.mh-pos {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 10px 16px !important; border-bottom: none;
}
.mh-pos-title { color: #fff; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.mh-amber { background: linear-gradient(135deg,#d97706,#f59e0b); padding: 12px 18px !important; border-bottom: none; }
.mh-title { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close-w { filter: brightness(0) invert(1); opacity: .8; }
.mh-close-w:hover { opacity: 1; }
.info-list li { margin-bottom: 8px; }

/* ── Nav actions modal links ── */
.fst-action-link {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border-radius: 8px;
    background: #f8f9fa; border: 1px solid #e2e8f0;
    text-decoration: none; color: #1e293b; transition: background .15s;
}
.fst-action-link:hover { background: #f1f5ff; color: #1e293b; }
.fst-action-link .fal-icon { font-size: 20px; flex-shrink: 0; }
.fst-action-link .fal-title { font-size: 13px; font-weight: 600; }
.fst-action-link .fal-sub   { font-size: 11px; color: #64748b; }
.fst-action-link .fal-arrow { margin-left: auto; color: #94a3b8; font-size: 18px; flex-shrink: 0; }

/* ── Totals modal ── */
.totals-stat-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
.totals-stat-row:last-child { border-bottom: none; }
.totals-stat-row .lbl { color: #64748b; }
.totals-stat-row .val { font-weight: 800; color: #1e293b; font-size: 14px; }
.totals-stat-row .val.diff-pos { color: #059669; }
.totals-stat-row .val.diff-neg { color: #dc2626; }
.totals-stat-row .val.accent { color: #3b4fa0; }

/* ── Date modal ── */
.date-mode-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.dmc { padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer; }
.dmc.active-sys { border-color: #4B5EBD; background: #eff3ff; }
.dmc.active-cus { border-color: #d97706; background: #fffbeb; }
.dmc-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.dmc-val { font-size: 13px; font-weight: 600; color: #64748b; }
.dmc.active-sys .dmc-val { color: #4B5EBD; }
.dmc.active-cus .dmc-val { color: #d97706; }

/* ── Spinner removal ── */
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }

/* ── MOBILE ── */
@media (max-width: 768px) {
    .fst-card { height: auto !important; margin: 8px; }
    .content-page { padding: 0 !important; }
    .content { padding: 0 !important; }
    .content-page > .content > .container-fluid { padding-top: 0 !important; padding-left: 0 !important; padding-right: 0 !important; }
    .fst-table-wrap { padding: 0 10px 12px; max-height: calc(100vh - 210px); }
    .modal-dialog { margin: 1.25rem auto !important; max-width: calc(100% - 24px) !important; }
    .modal-content { border-radius: 10px !important; }
    #fstActionsBtn { font-size: 11px; padding: 0 8px; height: 26px; }
    .fst-card-header { gap: 4px; }
    .fst-hdr-actions { gap: 0; }
    .fst-hdr-btn { width: 24px; height: 24px; font-size: 15px; padding: 0; }
    .fst-hdr-divider { margin: 0 3px; }
    .fst-branch-row { gap: 6px; }
    .fst-page-label { font-size: 11px; }
    .fst-bar-icon-btn { height: 26px; width: 26px; font-size: 15px; }
    .md-inline-input { width: 72px; font-size: 12px; }
}
</style>

{{-- Progress bar --}}
<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">

<div class="fst-card">

    {{-- ── Silver header ── --}}
    <div class="fst-card-header">
        <div class="fst-hdr-left">
            <button type="button" id="fstDateChip"
                    class="{{ $isCustom ? 'custom-mode' : '' }}"
                    title="Change stocktaking date">
                <i class="ri-calendar-line"></i> {{ $displayDate }}
                <span class="fst-mode-tag">{{ $isCustom ? 'Custom' : 'Today' }}</span>
                <i class="ri-pencil-line fst-edit-pencil"></i>
            </button>

            <button type="button" id="fstActionsBtn"
                    onclick="$('#fstNavActionsModal').modal('show')"
                    title="Quick navigation">
                <i class="ri-layout-grid-line"></i> <span class="fab-label">FS Actions</span>
            </button>
        </div>

        <div class="fst-hdr-actions">
            @if($branchId)
                <button type="button" class="fst-hdr-btn" id="mdTotalsBtn" title="Merged Data Totals">
                    <i class="ri-bar-chart-box-line"></i>
                </button>
                <span class="fst-hdr-divider"></span>
                <button type="button" class="fst-hdr-btn" id="downloadModalBtn" title="Download">
                    <i class="ri-download-line"></i>
                </button>
                <span class="fst-hdr-divider"></span>
            @endif
            <button type="button" class="fst-hdr-btn" id="fstInfoBtn" title="About Merged Data">
                <i class="ri-information-line"></i>
            </button>
        </div>
    </div>

    {{-- ── Blue branch bar ── --}}
    <div class="fst-branch-row">
        <div class="fst-branch-left">
            <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="fstBranchForm">
                @csrf
                <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                <select name="branch_id" id="fstBranchSelect" disabled
                        onchange="document.getElementById('fstBranchForm').submit()">
                    <option value="" hidden>{{ $branchName ?? '— Select Branch —' }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </form>
            @if($isRectified)
                <span class="fst-rectified-tag"><i class="ri-lock-line"></i> Rectified</span>
            @endif
        </div>

        <div class="fst-branch-right">
            <span class="fst-page-label">Merged Data</span>
            <a href="#" class="fst-bar-icon-btn" id="mdSyncBtn" title="Sync offline changes">
                <i class="ri-upload-cloud-2-line"></i>
                <span class="fst-pending-badge" id="mdPendingBadge"></span>
            </a>
        </div>
    </div>

    {{-- ── Body ── --}}
    <div class="fst-card-body">
        @if(!$branchId)
            <div class="fst-placeholder-wrap">
                <i class="ri-store-2-line"></i>
                <h5>No Branch Selected</h5>
                <p style="font-size:13px;">Select a branch from the bar above to view merged data.</p>
            </div>
        @elseif($data->isEmpty())
            <div class="fst-placeholder-wrap">
                <i class="ri-stack-line"></i>
                <h5>No Merged Data Yet</h5>
                <p style="font-size:13px;">No counted lines have been merged for {{ $branchName }} on {{ $displayDate }}.</p>
            </div>
        @else
        <div class="fst-table-wrap">
            <table id="mergedDataTable" class="table table-sm table-striped row-border order-column w-100">
                <thead style="background-color:#e2e2e9">
                    <tr>
                        <th>Product</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Expected</th>
                        <th>Found</th>
                        <th>Difference</th>
                        <th>Merges</th>
                        <th>Del</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $d)
                    @php $diff = $d->found - $d->expected_at_count; @endphp
                    <tr id="row{{ $d->id }}" data-id="{{ $d->id }}">
                        <td>
                            {{ $d->product_name }}
                            <span class="fst-row-edited-badge" id="editedTag{{ $d->id }}">Edited</span>
                            <span class="fst-row-deleted-badge" id="deletedTag{{ $d->id }}">Deleted</span>
                        </td>
                        <td>{{ $d->unit }}</td>
                        <td>{{ number_format($d->price, 2) }}</td>
                        <td>
                            <input type="number"
                                   class="md-inline-input"
                                   id="inpExpected{{ $d->id }}"
                                   data-id="{{ $d->id }}"
                                   data-field="expected"
                                   value="{{ number_format($d->expected_at_count, 2, '.', '') }}"
                                   step="0.01" min="0">
                        </td>
                        <td>
                            <input type="number"
                                   class="md-inline-input"
                                   id="inpFound{{ $d->id }}"
                                   data-id="{{ $d->id }}"
                                   data-field="found"
                                   value="{{ number_format($d->found, 2, '.', '') }}"
                                   step="0.01" min="0">
                        </td>
                        <td class="{{ $diff > 0 ? 'fst-diff-pos' : ($diff < 0 ? 'fst-diff-neg' : 'fst-diff-zero') }}"
                            id="cellDiff{{ $d->id }}">
                            {{ number_format($diff, 2) }}
                        </td>
                        <td>{{ $d->merge_count }}</td>
                        <td>
                            <i class="ri-delete-bin-line action-icon text-danger deleteBtn"
                               data-id="{{ $d->id }}"
                               data-product="{{ $d->product_name }}"></i>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>{{-- /.fst-card --}}
</div></div></div>


{{-- ══ NAV ACTIONS MODAL ══ --}}
<div class="modal fade" id="fstNavActionsModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:360px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-layout-grid-line"></i> Quick Actions
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
                <a href="{{ route('retail.operations.fullstocktaking') }}" class="fst-action-link">
                    <i class="ri-scales-3-line fal-icon" style="color:#4B5EBD;"></i>
                    <div><div class="fal-title">Stocktaking</div><div class="fal-sub">Count products for this branch and date</div></div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
                <a href="{{ route('retail.operations.fullstocktaking.merged-data') }}" class="fst-action-link">
                    <i class="ri-stack-line fal-icon" style="color:#4B5EBD;"></i>
                    <div><div class="fal-title">Merged Data</div><div class="fal-sub">View all merged stocktake records</div></div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
                <a href="{{ route('retail.operations.fullstocktaking.missing-products') }}" class="fst-action-link">
                    <i class="ri-error-warning-line fal-icon" style="color:#d97706;"></i>
                    <div><div class="fal-title">Missing Products</div><div class="fal-sub">Products not yet counted on this date</div></div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
                <a href="{{ route('retail.operations.fullstocktaking.actions-and-info') }}" class="fst-action-link">
                    <i class="ri-flashlight-line fal-icon" style="color:#059669;"></i>
                    <div><div class="fal-title">Actions &amp; Info</div><div class="fal-sub">Rectify, export and manage stocktake</div></div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
            </div>
        </div>
    </div>
</div>


@if($branchId)
{{-- ══ TOTALS MODAL ══ --}}
<div class="modal fade" id="mdTotalsModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:380px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-bar-chart-box-line"></i> Merged Data Totals
                </h5>
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
                <h5 style="font-size:13px;color:#64748b;font-weight:400;">Queued offline — this count will reappear under Missing Products once synced.</h5>
                <a href="#" class="btn btn-danger me-2 mt-3" id="deleteRowConfirmBtn">Yes, Queue Delete</a>
                <a href="#" class="btn btn-info mt-3" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>


{{-- ══ SYNC CONFIRM MODAL ══ --}}
<div class="modal fade" id="mdSyncModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-amber">
                <h5 class="modal-title mh-title">
                    <i class="ri-upload-cloud-2-line"></i> Sync Offline Changes
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
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
                <button type="button" class="btn btn-primary btn-sm" id="mdSyncConfirmBtn">
                    <i class="ri-upload-cloud-2-line"></i> Sync Now
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ══ DOWNLOAD MODAL ══ --}}
<div class="modal fade" id="downloadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-download-line"></i> Download Merged Data
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <p class="mb-2" style="font-size:13px;color:#475569;">Download merged data as a spreadsheet or PDF report.</p>
                <div class="dt-buttons" id="mergedDataButtons"></div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif


{{-- ══ DATE MODAL ══ --}}
<div class="modal fade" id="fstDateModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog" style="max-width:400px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-calendar-event-line"></i> Stocktaking Date
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div class="date-mode-toggle">
                    <div class="dmc {{ !$isCustom ? 'active-sys' : '' }}" id="fstDmcSystem"
                         onclick="fstSetDateMode('system')">
                        <div class="dmc-label">System date</div>
                        <div class="dmc-val">{{ Carbon::today()->format('d M Y') }}</div>
                    </div>
                    <div class="dmc {{ $isCustom ? 'active-cus' : '' }}" id="fstDmcCustom"
                         onclick="fstSetDateMode('custom')">
                        <div class="dmc-label">Custom date</div>
                        <div class="dmc-val" id="fstDmcCustomVal">{{ $isCustom ? $displayDate : 'Pick a date' }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="fstDateForm">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                    <input type="hidden" name="fst_custom_date" id="fstDateFormValue" value="">
                    <div id="fstCustomDateRow" style="{{ !$isCustom ? 'display:none;' : '' }}">
                        <input type="date" class="form-control" id="fstCustomDateInput"
                               value="{{ $date }}" oninput="fstPreviewDate(this.value)">
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ri-check-line"></i> Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


{{-- ══ INFO MODAL ══ --}}
<div class="modal fade" id="fstInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-information-line"></i> About Merged Data
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;line-height:1.6;">
                <ul class="info-list mb-3" style="padding-left:18px;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <li>This is every product line merged from the Stocktaking tab for the selected branch + date.</li>
                    <li><strong>Expected and Found are editable inline</strong> — click directly into a cell, change the value, and tab or click away. The edit is queued offline instantly.</li>
                    <li>Tap the cloud icon in the blue bar to sync all queued edits and deletes to the server.</li>
                    <li>A deleted row reappears under Missing Products the next time that tab refreshes.</li>
                    <li>Tap the chart icon in the top bar to view current totals (Expected Value, Found Value, Difference).</li>
                </ul>
                @if($isRectified)
                <div class="alert alert-info border-0 py-2 px-3" style="font-size:12px;border-radius:6px;">
                    <i class="ri-information-line me-1"></i> This date is rectified. Edits are still queued offline — syncing will re-run the sales-netting calculation and update the History record automatically.
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

const MD_BRANCH_ID = '{{ $branchId }}';
const MD_DATE      = '{{ $date }}';
const MD_QUEUE_KEY = 'fullstocktaking_merged_data_queue_' + MD_BRANCH_ID + '_' + MD_DATE;

let mdQueue = [];

function mdUuid() { return 'md_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 9); }
function mdCsrf() { return document.querySelector('meta[name="csrf-token"]').getAttribute('content'); }

function loadMdQueue() {
    try { mdQueue = JSON.parse(localStorage.getItem(MD_QUEUE_KEY) || '[]'); } catch (e) { mdQueue = []; }
}
function saveMdQueue() {
    localStorage.setItem(MD_QUEUE_KEY, JSON.stringify(mdQueue));
    updateMdBadge();
}

function updateMdBadge() {
    const badge = document.getElementById('mdPendingBadge');
    const btn   = document.getElementById('mdSyncBtn');
    if (!badge || !btn) return;
    if (mdQueue.length > 0) {
        badge.textContent = mdQueue.length;
        badge.classList.add('show');
    } else {
        badge.classList.remove('show');
    }
}

/* Queue an update op — one per row (replace if already queued) */
function mdQueueUpdate(rowId, expected, found) {
    const idx = mdQueue.findIndex(op => op.type === 'update' && op.id === rowId);
    const op  = { client_uuid: mdUuid(), type: 'update', id: rowId, expected, found };
    if (idx >= 0) { mdQueue[idx] = op; } else { mdQueue.push(op); }
    saveMdQueue();
}

/* Queue a delete op — remove any pending update for same row first */
function mdQueueDelete(rowId) {
    mdQueue = mdQueue.filter(op => op.id !== rowId);
    mdQueue.push({ client_uuid: mdUuid(), type: 'delete', id: rowId });
    saveMdQueue();
}

/* Recalculate and refresh the diff cell for a row */
function mdRefreshDiff(rowId) {
    const inpE = document.getElementById('inpExpected' + rowId);
    const inpF = document.getElementById('inpFound'    + rowId);
    const cell = document.getElementById('cellDiff'    + rowId);
    if (!inpE || !inpF || !cell) return;
    const expected = parseFloat(inpE.value) || 0;
    const found    = parseFloat(inpF.value) || 0;
    const diff     = found - expected;
    cell.textContent = diff.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    cell.className   = diff > 0 ? 'fst-diff-pos' : (diff < 0 ? 'fst-diff-neg' : 'fst-diff-zero');
}

/* Mark a row as having unsync'd edits — orange "Edited" badge only.
   If the row is already queued for delete, the delete badge wins and
   the edited badge is suppressed (red replaces orange). */
function mdMarkRowDirty(rowId) {
    const deletedTag = document.getElementById('deletedTag' + rowId);
    if (deletedTag && deletedTag.style.display === 'inline') return;
    const tag = document.getElementById('editedTag' + rowId);
    if (tag) tag.style.display = 'inline';
}

/* Mark a row as queued for delete — red "Deleted" badge only.
   Replaces the edited badge if present. Inputs stay fully editable;
   no disabling, no opacity fade, no background/border changes. */
function mdMarkRowDeleted(rowId) {
    const editedTag = document.getElementById('editedTag' + rowId);
    if (editedTag) editedTag.style.display = 'none';
    const tag = document.getElementById('deletedTag' + rowId);
    if (tag) tag.style.display = 'inline';
}

$(document).ready(function () {

    loadMdQueue();
    updateMdBadge();

    @if($branchId && $data->isNotEmpty())

    /* ── Re-apply any offline-queued state on page load ── */
    mdQueue.forEach(function (op) {
        if (op.type === 'update') {
            const inpE = document.getElementById('inpExpected' + op.id);
            const inpF = document.getElementById('inpFound'    + op.id);
            if (inpE) inpE.value = parseFloat(op.expected).toFixed(2);
            if (inpF) inpF.value = parseFloat(op.found).toFixed(2);
            mdRefreshDiff(op.id);
            mdMarkRowDirty(op.id);
        }
        if (op.type === 'delete') {
            mdMarkRowDeleted(op.id);
        }
    });

    /* ── DataTable ── */
    var table = $('#mergedDataTable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        pageLength: 100,
        lengthMenu: [[50, 100, 250, -1], [50, 100, 250, 'All']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center', orderable: true },
            { targets: 0,      className: 'text-start' },
            /* Disable sorting on inline-input columns so clicks don't sort */
            { targets: [3, 4], orderable: false },
        ],
        buttons: [
            { extend: 'excelHtml5', title: @json($title), text: 'Excel', exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: @json($title), text: 'CSV',   exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: @json($title), text: 'PDF',   exportOptions: { columns: ':visible:not(:last-child)' } },
        ],
    });
    table.buttons().container().appendTo('#mergedDataButtons');

    /* ── Inline editing: queue on blur / Enter ── */
    $(document).on('change blur', '.md-inline-input', function () {
        const rowId   = parseInt($(this).data('id'), 10);
        const inpE    = document.getElementById('inpExpected' + rowId);
        const inpF    = document.getElementById('inpFound'    + rowId);
        if (!inpE || !inpF) return;

        /* Clamp to 2dp and minimum 0 */
        let valE = parseFloat(inpE.value);
        let valF = parseFloat(inpF.value);
        if (isNaN(valE) || valE < 0) { valE = 0; inpE.value = '0.00'; }
        if (isNaN(valF) || valF < 0) { valF = 0; inpF.value = '0.00'; }
        inpE.value = valE.toFixed(2);
        inpF.value = valF.toFixed(2);

        mdQueueUpdate(rowId, valE, valF);
        mdRefreshDiff(rowId);
        mdMarkRowDirty(rowId);
    });

    /* Allow Enter to commit inline edit */
    $(document).on('keydown', '.md-inline-input', function (e) {
        if (e.key === 'Enter') { $(this).blur(); }
    });

    /* ── Delete ── */
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
        mdMarkRowDeleted(deleteId);
        toastr.info('Removal queued offline. Sync to apply.', 'Queued');
    });

    /* ── Totals / Download buttons ── */
    document.getElementById('mdTotalsBtn')?.addEventListener('click', function (e) {
        e.preventDefault(); $('#mdTotalsModal').modal('show');
    });
    document.getElementById('downloadModalBtn')?.addEventListener('click', function (e) {
        e.preventDefault(); $('#downloadModal').modal('show');
    });

    @endif

    /* ── Sync button ── */
    document.getElementById('mdSyncBtn')?.addEventListener('click', function (e) {
        e.preventDefault();
        if (!mdQueue.length) { toastr.info('Nothing to sync — no offline changes pending.'); return; }
        document.getElementById('mdSyncCount').textContent = mdQueue.length;
        $('#mdSyncModal').modal('show');
    });

    document.getElementById('mdSyncConfirmBtn')?.addEventListener('click', function () {
        const btn = this;
        btn.disabled = true;
        fetch('{{ route("retail.operations.fullstocktaking.merged-data.sync") }}', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': mdCsrf() },
            body:    JSON.stringify({ ops: mdQueue }),
        })
        .then(res => res.json().then(data => ({ status: res.status, data })))
        .then(({ status, data }) => {
            btn.disabled = false;
            if (status === 200) {
                toastr.success(data.message, 'Synced');
                mdQueue = [];
                saveMdQueue();
                $('#mdSyncModal').modal('hide');
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

    document.getElementById('fstInfoBtn')?.addEventListener('click', function (e) {
        e.preventDefault(); $('#fstInfoModal').modal('show');
    });

    @if(Session::has('message'))
    toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
    @endif
});

/* ── Date modal helpers ── */
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
    const d  = new Date(val + 'T00:00:00');
    const mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    document.getElementById('fstDmcCustomVal').textContent = d.getDate() + ' ' + mo[d.getMonth()] + ' ' + d.getFullYear();
}
document.getElementById('fstDateChip')?.addEventListener('click', () => {
    document.getElementById('fstDateFormValue').value = '{{ $isCustom ? $date : "" }}';
    $('#fstDateModal').modal('show');
});
</script>
@endsection