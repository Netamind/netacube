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

    $missingProducts = collect();
    $missingValue    = 0;

    if ($branchId) {
        $alreadySeeded = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
            ->where('branch_id', $branchId)->where('date', $date)->exists();

        if (! $alreadySeeded) {
            $countedIds = DB::connection('tenant')->table('retail_fullstocktaking')
                ->where('branch_id', $branchId)->where('date', $date)
                ->pluck('base_product_id');

            $missing = DB::connection('tenant')
                ->table('retail_branch_products as rbp')
                ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
                ->where('rbp.branch_id', $branchId)
                ->whereNotIn('rbp.base_product_id', $countedIds)
                ->select(
                    'rbp.base_product_id', 'bp.name as product_name', 'bp.unit',
                    DB::raw('COALESCE(rbp.selling_price, bp.selling_price) as price'),
                    'rbp.stock_quantity as quantity',
                    'rbp.batch_number', 'rbp.expiry_date'
                )
                ->get();

            if ($missing->isNotEmpty()) {
                $now  = now();
                $rows = $missing->map(fn ($m) => [
                    'date'            => $date,
                    'branch_id'       => $branchId,
                    'base_product_id' => $m->base_product_id,
                    'product_name'    => $m->product_name,
                    'unit'            => $m->unit,
                    'price'           => $m->price ?? 0,
                    'quantity'        => $m->quantity ?? 0,
                    'rate'            => 1.00,
                    'batch_number'    => $m->batch_number,
                    'expiry_date'     => $m->expiry_date,
                    'product_status'  => 'Active',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ])->toArray();

                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::connection('tenant')->table('retail_fullstocktaking_missing_products')->insertOrIgnore($chunk);
                }
            }
        }

        $missingProducts = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
            ->where('branch_id', $branchId)
            ->where('date', $date)
            ->orderBy('product_name')
            ->get();

        $missingValue = $missingProducts->sum(fn ($m) => $m->quantity * $m->price);
    }

    $title = $branchName . ' Missing Products ' . $displayDate;
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
.card-header { padding: 0.5rem 1.5rem !important; background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; border-top-left-radius: 10px; border-top-right-radius: 10px; }
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
.fst-left { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; flex-wrap: wrap; }
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
.fst-pending-badge { position: absolute; top: -6px; right: -6px; background: #dc2626; color: #fff; font-size: 9px; font-weight: 700; min-width: 16px; height: 16px; border-radius: 8px; display: none; align-items: center; justify-content: center; padding: 0 4px; border: 1px solid #fff; }
.fst-pending-badge.show { display: flex; }

.tab-header-container { background: #cccccc; border-bottom: 1px solid #b3b3b3; }
.nav-pills .nav-link { border-radius: 0 !important; padding: .65rem 1rem; font-weight: 500; color: #495057; border-bottom: none; transition: all .2s; font-size: 12.5px; }
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

#missingProductsTable thead th,
table.dataTable thead th { text-align: center !important; vertical-align: middle !important; }
#missingProductsTable thead th:first-child,
table.dataTable thead th:first-child { text-align: left !important; }
#missingProductsTable tbody td,
table.dataTable tbody td { text-align: center !important; vertical-align: middle !important; }
#missingProductsTable tbody td:first-child,
table.dataTable tbody td:first-child { text-align: left !important; }

.mp-qty-input { width: 90px; text-align: center; border: 1px solid #c5caec; border-radius: 5px; padding: 4px 6px; font-weight: 700; color: #1d4ed8; }
.mp-qty-input:focus { outline: 2px solid #4B5EBD; border-color: #4B5EBD; }
.mp-dirty { background: #fffbeb !important; border-color: #f59e0b !important; }
.action-icon { cursor: pointer; padding: 4px 6px; border-radius: 6px; }
.action-icon:hover { background: #f0f0f0; }

.mh-blue  { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; }
.mh-amber { background: linear-gradient(135deg,#d97706,#f59e0b); padding: 12px 18px !important; border-bottom: none; }
.mh-title { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close { filter: brightness(0) invert(1); opacity: .8; } .mh-close:hover { opacity: 1; }
.info-list li { margin-bottom: 8px; }

/* ── Stats rows inside modal ─────────────────────────────────────────── */
.mp-stat-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
.mp-stat-row:last-child { border-bottom: none; }
.mp-stat-row .lbl { color: #64748b; }
.mp-stat-row .val { font-weight: 700; color: #1e293b; }
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
            <a href="#" class="fst-icon-btn" id="mpStatsBtn" title="View missing stats"><i class="ri-bar-chart-2-line"></i></a>
            <a href="#" class="fst-icon-btn" id="mpDownloadBtn" title="Download"><i class="ri-download-line"></i></a>
            @endif
            <a href="#" class="fst-icon-btn" id="mpSyncBtn" title="Sync offline changes">
                <i class="ri-upload-cloud-2-line"></i>
                <span class="fst-pending-badge" id="mpPendingBadge"></span>
            </a>
        </div>
    </div>

    <div class="tab-header-container">
        <ul class="nav nav-pills nav-justified mb-0">
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking') }}" class="nav-link"><i class="ri-scales-3-line"></i> Stocktaking</a></li>
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking.merged-data') }}" class="nav-link"><i class="ri-stack-line"></i> Merged Data</a></li>
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking.missing-products') }}" class="nav-link active"><i class="ri-error-warning-line"></i> Missing Products</a></li>
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking.actions-and-info') }}" class="nav-link"><i class="ri-flashlight-line"></i> Actions &amp; Info</a></li>
        </ul>
    </div>

    <div class="card-body">

        @if(!$branchId)
            <div style="padding:40px;text-align:center;color:#94a3b8;"><i class="ri-store-2-line" style="font-size:40px;display:block;margin-bottom:10px;"></i>Select a branch above.</div>
        @else

        <div class="table-wrapper" style="margin-top:14px;">
            <table id="missingProductsTable" class="table table-sm table-striped row-border order-column w-100">
                <thead style="background-color:#e2e2e9">
                    <tr>
                        <th>Product</th><th>Unit</th><th>Price</th><th>Quantity</th><th>Action</th>
                    </tr>
                </thead>
                <tbody id="mpTbody">
                    @foreach($missingProducts as $m)
                    <tr id="mprow{{ $m->id }}" data-id="{{ $m->id }}">
                        <td>{{ $m->product_name }}</td>
                        <td>{{ $m->unit }}</td>
                        <td>{{ number_format($m->price, 2) }}</td>
                        <td>
                            <input type="number" class="mp-qty-input" id="mpqty{{ $m->id }}" value="{{ number_format($m->quantity, 2) }}" data-original="{{ number_format($m->quantity, 2) }}">
                        </td>
                        <td>
                            <i class="ri-delete-bin-line action-icon text-danger mpDeleteBtn" data-id="{{ $m->id }}" data-product="{{ $m->product_name }}"></i>
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

{{-- ══ STATS MODAL ═════════════════════════════════════════════════════ --}}
<div class="modal fade" id="mpStatsModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:380px;">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-bar-chart-2-line"></i> Missing Products Stats</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div class="mp-stat-row"><span class="lbl">Branch</span><span class="val">{{ $branchName }}</span></div>
                <div class="mp-stat-row"><span class="lbl">Date</span><span class="val">{{ $displayDate }}</span></div>
                <div class="mp-stat-row"><span class="lbl">Missing products</span><span class="val">{{ $missingProducts->count() }}</span></div>
                <div class="mp-stat-row"><span class="lbl">Missing value</span><span class="val">MWK {{ number_format($missingValue, 2) }}</span></div>
            </div>
        </div>
    </div>
</div>

{{-- ══ DELETE CONFIRM MODAL ════════════════════════════════════════════ --}}
<div class="modal fade" id="mpDeleteModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:380px;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
                <h5 class="mt-2">Remove <span id="mpDeleteProductLabel" class="text-danger"></span>?</h5>
                <p class="text-muted" style="font-size:12px;">This deletes the missing-product entry only — it does not affect live branch stock.</p>
                <a href="#" class="btn btn-danger me-2 mt-2" id="mpDeleteConfirmBtn">Yes, Remove</a>
                <a href="#" class="btn btn-secondary mt-2" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>

{{-- ══ SYNC CONFIRM MODAL ══════════════════════════════════════════════ --}}
<div class="modal fade" id="mpSyncModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header mh-amber">
                <h5 class="modal-title mh-title"><i class="ri-upload-cloud-2-line"></i> Sync Offline Changes</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;">
                <p>You have <strong id="mpSyncCount">0</strong> unsynced change(s) on this device. Sync now to upload them and reload with the latest data?</p>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Not Yet</button>
                <button type="button" class="btn btn-primary btn-sm" id="mpSyncConfirmBtn"><i class="ri-upload-cloud-2-line"></i> Sync Now</button>
            </div>
        </div>
    </div>
</div>

{{-- ══ DOWNLOAD MODAL ══════════════════════════════════════════════════ --}}
<div class="modal fade" id="mpDownloadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-download-line"></i> Download Missing Products</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" style="font-size:13px;">Click a button to download missing-product data in spreadsheet/table form, or generate a PDF report.</p>
                <div class="dt-buttons" id="missingProductsButtons"></div>
                <hr style="margin:14px 0;">
                <form action="{{ route('retail.operations.fullstocktaking.report.missing-products') }}" method="POST" target="_blank">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">
                    <input type="hidden" name="date" value="{{ $date }}">
                    <button type="submit" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;border:1px solid #d8ddf0;background:#fff;cursor:pointer;width:100%;text-align:left;">
                        <i class="ri-file-pdf-2-line" style="font-size:20px;color:#4B5EBD;flex-shrink:0;"></i>
                        <span style="flex:1;">
                            <span style="font-size:12.5px;font-weight:600;color:#1e293b;display:block;line-height:1.2;">Missing Products PDF Report</span>
                            <span style="font-size:11px;color:#64748b;display:block;margin-top:2px;">All missing products for this branch and date</span>
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
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;">
                    <div style="padding:10px 12px;border-radius:8px;border:1px solid {{ !$isCustom ? '#4B5EBD' : '#e2e8f0' }};background:{{ !$isCustom ? '#eff3ff' : '#fff' }};cursor:pointer;" id="fstDmcSystem" onclick="fstSetDateMode('system')">
                        <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">System date</div>
                        <div style="font-size:13px;font-weight:600;color:#4B5EBD;">{{ Carbon::today()->format('d M Y') }}</div>
                    </div>
                    <div style="padding:10px 12px;border-radius:8px;border:1px solid {{ $isCustom ? '#d97706' : '#e2e8f0' }};background:{{ $isCustom ? '#fffbeb' : '#fff' }};cursor:pointer;" id="fstDmcCustom" onclick="fstSetDateMode('custom')">
                        <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Custom date</div>
                        <div style="font-size:13px;font-weight:600;color:#d97706;" id="fstDmcCustomVal">{{ $isCustom ? $displayDate : 'Pick a date' }}</div>
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
                <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Missing Products</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;line-height:1.6;">
                <ul class="info-list mb-3">
                    <li>Products at this branch that were <strong>never counted</strong> during this stocktake appear here automatically, seeded from current branch stock the first time this tab is opened for the date.</li>
                    <li>Edits and deletes here work <strong>offline</strong> — change a quantity, it's queued on this device, then tap the cloud icon above to sync.</li>
                    <li>Removing an entry here only deletes the missing-product record — it does not affect live branch stock.</li>
                    <li>Tap the stats icon in the top bar to view the current missing count and its value at present quantities.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
'use strict';

const MP_BRANCH_ID = '{{ $branchId }}';
const MP_DATE       = '{{ $date }}';
const MP_QUEUE_KEY  = 'fullstocktaking_missing_products_queue_' + MP_BRANCH_ID + '_' + MP_DATE;

let mpQueue = [];

function mpUuid() {
    return 'mp_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 9);
}
function mpCsrf() { return document.querySelector('meta[name="csrf-token"]').getAttribute('content'); }
function loadMpQueue() { try { mpQueue = JSON.parse(localStorage.getItem(MP_QUEUE_KEY) || '[]'); } catch (e) { mpQueue = []; } }
function saveMpQueue() { localStorage.setItem(MP_QUEUE_KEY, JSON.stringify(mpQueue)); updateMpBadge(); }

function updateMpBadge() {
    const badge = document.getElementById('mpPendingBadge');
    const btn   = document.getElementById('mpSyncBtn');
    if (!badge || !btn) return;
    if (mpQueue.length > 0) {
        badge.textContent = mpQueue.length; badge.classList.add('show'); btn.classList.add('has-pending');
    } else {
        badge.classList.remove('show'); btn.classList.remove('has-pending');
    }
}

function mpQueueUpdate(rowId, quantity) {
    const idx = mpQueue.findIndex(op => op.type === 'update' && op.id === rowId);
    const op = { client_uuid: mpUuid(), type: 'update', id: rowId, quantity: quantity };
    if (idx >= 0) { mpQueue[idx] = op; } else { mpQueue.push(op); }
    saveMpQueue();
}
function mpQueueDelete(rowId) {
    mpQueue = mpQueue.filter(op => op.id !== rowId);
    mpQueue.push({ client_uuid: mpUuid(), type: 'delete', id: rowId });
    saveMpQueue();
}

$(document).ready(function () {
    loadMpQueue(); updateMpBadge();

    var table = $('#missingProductsTable').DataTable({
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
            { extend: 'excelHtml5', title: @json($title ?? 'Missing Products'), text: 'Excel', exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: @json($title ?? 'Missing Products'), text: 'CSV',   exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: @json($title ?? 'Missing Products'), text: 'PDF',    exportOptions: { columns: ':visible:not(:last-child)' } },
        ]
    });
    table.buttons().container().appendTo('#missingProductsButtons');

    $('#mpDownloadBtn').on('click', function (e) { e.preventDefault(); $('#mpDownloadModal').modal('show'); });

    mpQueue.forEach(function (op) {
        if (op.type === 'update') {
            var $input = $('#mpqty' + op.id);
            if ($input.length) { $input.val(parseFloat(op.quantity).toFixed(2)).addClass('mp-dirty'); }
        }
    });

    $(document).on('change', '.mp-qty-input', function () {
        var $input = $(this);
        var rowId  = parseInt($input.closest('tr').data('id'), 10);
        var qty    = parseFloat($input.val());
        if (isNaN(qty) || qty < 0) { toastr.warning('Quantity must be 0 or greater.'); $input.val($input.data('original')); return; }
        $input.val(qty.toFixed(2));
        $input.addClass('mp-dirty');
        mpQueueUpdate(rowId, qty);
    });

    var deleteRowId = null;
    $(document).on('click', '.mpDeleteBtn', function () {
        deleteRowId = parseInt($(this).data('id'), 10);
        $('#mpDeleteProductLabel').text($(this).data('product'));
        $('#mpDeleteModal').modal('show');
    });

    $('#mpDeleteConfirmBtn').on('click', function (e) {
        e.preventDefault();
        $('#mpDeleteModal').modal('hide');
        $('#mprow' + deleteRowId).fadeOut(200, function () { $(this).remove(); });
        mpQueueDelete(deleteRowId);
        toastr.info('Removal queued offline. Sync to apply.', 'Queued');
    });

    document.getElementById('mpSyncBtn')?.addEventListener('click', function (e) {
        e.preventDefault();
        if (!mpQueue.length) { toastr.info('Nothing to sync — no offline changes pending.'); return; }
        document.getElementById('mpSyncCount').textContent = mpQueue.length;
        $('#mpSyncModal').modal('show');
    });

    document.getElementById('mpSyncConfirmBtn')?.addEventListener('click', function () {
        var btn = this; btn.disabled = true;
        fetch('{{ route("retail.operations.fullstocktaking.missing-products.sync") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': mpCsrf() },
            body: JSON.stringify({ ops: mpQueue }),
        })
        .then(res => res.json().then(data => ({ status: res.status, data })))
        .then(({ status, data }) => {
            btn.disabled = false;
            if (status === 200) {
                toastr.success(data.message, 'Synced');
                mpQueue = []; saveMpQueue();
                $('#mpSyncModal').modal('hide');
                setTimeout(function () { location.reload(); }, 700);
            } else {
                toastr.error(data.message || 'Sync failed.', 'Error');
            }
        })
        .catch(() => { btn.disabled = false; toastr.error('Could not reach the server. Your changes remain queued offline.', 'Network Error'); });
    });

    document.getElementById('fstInfoBtn')?.addEventListener('click', function (e) { e.preventDefault(); $('#fstInfoModal').modal('show'); });
    document.getElementById('mpStatsBtn')?.addEventListener('click', function (e) { e.preventDefault(); $('#mpStatsModal').modal('show'); });

    @if(Session::has('message'))
    toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
    @endif
});

function fstSetDateMode(mode) {
    document.getElementById('fstDmcSystem').style.borderColor = mode === 'system' ? '#4B5EBD' : '#e2e8f0';
    document.getElementById('fstDmcSystem').style.background  = mode === 'system' ? '#eff3ff' : '#fff';
    document.getElementById('fstDmcCustom').style.borderColor = mode === 'custom' ? '#d97706' : '#e2e8f0';
    document.getElementById('fstDmcCustom').style.background  = mode === 'custom' ? '#fffbeb' : '#fff';
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