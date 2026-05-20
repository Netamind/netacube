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

    $products       = collect();
    $alreadyCounted = collect();

    if ($branchId && ! $isRectified) {
        $products = DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
            ->where('rbp.branch_id', $branchId)
            ->where('rbp.is_active', 1)
            ->select(
                'rbp.base_product_id as id',
                'bp.name as product',
                'bp.unit',
                'rbp.stock_quantity',
                DB::raw('COALESCE(rbp.selling_price, bp.selling_price) as selling_price')
            )
            ->orderBy('bp.name')
            ->get();
    }

    if ($branchId) {
        $alreadyCounted = DB::connection('tenant')
            ->table('retail_fullstocktaking')
            ->where('branch_id', $branchId)
            ->where('date', $date)
            ->pluck('found', 'base_product_id');
    }
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
.card-body  { padding: 0 !important; display: flex; flex-direction: column; }
.card       { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; gap: 6px; }

/* ── Header action buttons — same style, both identical ─────────────── */
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

/* ── Action bar — same gradient as search row and cart bar ───────────── */
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
.fst-left  { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; }
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

/* ── Refresh button — same look as action bar elements ───────────────── */
.fst-refresh-btn {
    height: 32px;
    padding: 0 12px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(255,255,255,0.12);
    border: 1.5px solid rgba(255,255,255,0.3);
    border-radius: 7px;
    color: #dde0e8;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
}
.fst-refresh-btn:hover { background: rgba(255,255,255,0.22); }

.rectified-tag {
    font-size: 9px;
    font-weight: 700;
    background: #d1fae5;
    color: #065f46;
    border-radius: 5px;
    padding: 2px 7px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

/* ── Tabs — active = blue text only, no underline, no bg ─────────────── */
.tab-header-container {
    background: #cccccc;
    border-bottom: 1px solid #b3b3b3;
}
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

/* ── Modal headers ──────────────────────────────────────────────────── */
.mh-blue  { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; }
.mh-title { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }
.info-list li { margin-bottom: 8px; }

/* ── POS workspace ──────────────────────────────────────────────────── */
#fst-workspace-row { flex: 1 1 auto; min-height: 0; }
#fst-left-col  { background-color: #e8e8e8; padding: 0; display: flex; flex-direction: column; }
#fst-search-row { background: #9098a8; padding: 8px; flex: 0 0 auto; }
#fst-search-wrap { position: relative; }
#fst-search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 16px; pointer-events: none; }
#fst-search { background-color: #ececec; text-transform: uppercase; font-weight: bold; border: 1px solid rgba(255,255,255,.35); width: 100%; height: 34px; border-radius: 4px; padding: 0 10px 0 32px; outline: none; color: #1a1a1a; }
#fst-search::placeholder { color: #8a8a8a; font-weight: bold; text-transform: none; }
#fst-search:focus { border-color: rgba(255,255,255,.7); }
#fst-product-display { flex: 1 1 auto; overflow-y: auto; }

.fst-row { display: flex; align-items: center; justify-content: space-between; padding: 6px 8px; border-bottom: 1px solid #d0d0d0; color: black; }
.fst-row .fst-link { color: black; text-decoration: none; cursor: pointer; flex: 0 0 66%; max-width: 66%; min-width: 0; }
.fst-name { text-transform: uppercase; font-weight: bold; font-size: 14px; }
.fst-meta { color: gray; font-family: monospace; font-size: 13px; margin-left: 6px; }
.fst-stock-tag { color: #8a8a8a; font-weight: 600; font-size: 14px; font-family: monospace; margin-left: 6px; }
.fst-already { color: #2563eb; font-weight: 700; font-size: 11px; margin-left: 6px; }
.fst-qty-input { text-align: center; flex: 0 0 30%; max-width: 30%; box-sizing: border-box; border-radius: 5px; border: 1px ridge #b3b3b3; background: transparent; font-size: 15px; font-weight: bold; color: #1a1a1a; height: 36px; }
.fst-qty-input:focus { outline: 1px solid #4B5EBD; }

#fst-right-col { padding: 0; border-left: 1px solid #adadad; display: flex; flex-direction: column; }
#fst-cart-bar { background: #9098a8; padding: 8px 10px; display: flex; align-items: center; justify-content: space-between; flex: 0 0 auto; }
.fst-cart-label { border: 2px solid rgba(255,255,255,0.3); font-weight: bold; color: #dde0e8; background: transparent; padding: 4px 10px; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; }
.fst-cart-label #fstCartTotal { color: #dde0e8; font-weight: bold; font-size: 17px; }
#fst-merge-btn { border: 2px solid rgba(255,255,255,0.3); background: transparent; color: #dde0e8; font-weight: bold; padding: 6px 14px; font-size: 13px; border-radius: 3px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
#fst-merge-btn:disabled { opacity: .45; cursor: not-allowed; }
#fst-cart-table-wrap { background-color: #cccccc; flex: 1 1 auto; overflow-y: auto; min-height: 0; }
#fst-cart-table { width: 100%; font-size: 12px; border-collapse: collapse; }
#fst-cart-table thead th { color: #3d5c5c; border-bottom: 2px solid #b0b5c0; padding: 6px 4px; text-align: center; position: sticky; top: 0; background: #cccccc; }
#fst-cart-table thead th:first-child { text-align: left; padding-left: 6px; }
#fst-cart-table tbody td { border-bottom: 1px solid #b0b5c0; padding: 6px 4px; text-align: center; color: black; }
#fst-cart-table tbody td:first-child { text-align: left; padding-left: 6px; }
.fst-cart-remove { color: red; cursor: pointer; font-weight: bold; text-decoration: none; }
#fst-cart-empty { text-align: center; padding: 40px 16px; color: #595959; font-size: 13px; background-color: #cccccc; height: 100%; }

/* ── Locked state ────────────────────────────────────────────────────── */
.fst-locked-wrap {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 28px 24px;
    background: #f8f9fa;
    margin: 18px;
    border-radius: 10px;
    border: 1px solid #dee2e6;
}
.fst-locked-wrap i { font-size: 32px; color: #16a34a; flex-shrink: 0; margin-top: 2px; }
.fst-locked-wrap .lock-title { font-weight: 700; font-size: 15px; color: #1e293b; margin-bottom: 4px; }
.fst-locked-wrap .lock-body  { font-size: 13px; color: #475569; }

/* ── Date modal ──────────────────────────────────────────────────────── */
.date-mode-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.dmc { padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer; }
.dmc.active-sys { border-color: #4B5EBD; background: #eff3ff; }
.dmc.active-cus { border-color: #d97706; background: #fffbeb; }
.dmc-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.dmc-val { font-size: 13px; font-weight: 600; color: #64748b; }
.dmc.active-sys .dmc-val { color: #4B5EBD; }
.dmc.active-cus .dmc-val { color: #d97706; }

@media (max-width: 900px) {
    #fst-workspace-card { height: calc(100vh - 110px) !important; }
    #fst-workspace-row { flex-direction: column; }
    #fst-left-col { flex: 0 1 auto; }
    #fst-product-display { flex: 0 1 auto; max-height: 45vh; }
    #fst-right-col { flex: 1 1 auto; min-height: 0; }
}
</style>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>
<div class="card" id="fst-workspace-card" style="height: calc(100vh - 130px);">

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
                <i class="ri-calendar-line" style="font-size:11px;"></i>
                <span>{{ $displayDate }}</span>
                <span class="mode-badge">{{ $isCustom ? 'Custom' : 'Today' }}</span>
                <i class="ri-pencil-line fst-edit-pencil"></i>
            </div>
            @if($isRectified)<span class="rectified-tag"><i class="ri-lock-line"></i> Rectified</span>@endif
        </div>
        <div class="fst-right">
            <button class="fst-refresh-btn" onclick="location.reload()" title="Refresh">
                <i class="ri-refresh-line"></i>
            </button>
        </div>
    </div>

    <div class="tab-header-container">
        <ul class="nav nav-pills nav-justified mb-0">
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking') }}" class="nav-link active"><i class="ri-scales-3-line"></i> Stocktaking</a></li>
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking.merged-data') }}" class="nav-link"><i class="ri-git-merge-line"></i> Merged Data</a></li>
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking.missing-products') }}" class="nav-link"><i class="ri-error-warning-line"></i> Missing Products</a></li>
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking.actions-and-info') }}" class="nav-link"><i class="ri-flashlight-line"></i> Actions &amp; Info</a></li>
        </ul>
    </div>

    <div class="card-body">
        @if(!$branchId)
            <div style="padding:48px 20px;text-align:center;color:#94a3b8;">
                <i class="ri-store-2-line" style="font-size:48px;display:block;margin-bottom:12px;color:#c8d0ed;"></i>
                <div style="font-size:15px;font-weight:600;color:#64748b;">No Branch Selected</div>
                <div style="font-size:13px;margin-top:4px;">Select a branch above to begin counting.</div>
            </div>
        @elseif($isRectified)
            <div class="fst-locked-wrap">
                <i class="ri-lock-line"></i>
                <div>
                    <div class="lock-title">Counting Locked — {{ $branchName }} · {{ $displayDate }}</div>
                    <div class="lock-body">This date has already been rectified. Counting is closed — pick a different date or view this stocktake in History. Corrections can still be made via the Merged Data tab's offline sync.</div>
                </div>
            </div>
        @else
        <div class="row g-0" id="fst-workspace-row">
            <div class="col-md-5" id="fst-left-col">
                <div id="fst-search-row">
                    <div id="fst-search-wrap">
                        <i class="ri-search-line"></i>
                        <input type="text" style="display:none;" aria-hidden="true" tabindex="-1">
                        <input type="password" style="display:none;" aria-hidden="true" tabindex="-1">
                        <input type="text" id="fst-search"
                               placeholder="Search product name or code"
                               autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                               data-lpignore="true" data-form-type="other" data-1p-ignore="true"
                               aria-autocomplete="none" role="presentation">
                    </div>
                </div>
                <div id="fst-product-display"></div>
                <script type="application/json" id="fst-products-json">{!! json_encode($products->map(fn($p) => [
                    'id' => $p->id, 'name' => $p->product, 'unit' => $p->unit,
                    'price' => $p->selling_price, 'stock' => (float) $p->stock_quantity,
                    'already' => $alreadyCounted[$p->id] ?? null,
                ])) !!}</script>
            </div>
            <div class="col-md-7" id="fst-right-col">
                <div id="fst-cart-bar">
                    <div class="fst-cart-label"><i class="ri-wallet-3-line"></i> Value: MWK <span id="fstCartTotal">0</span></div>
                    <button id="fst-merge-btn" disabled onclick="openMergeModal()"><i class="ri-upload-cloud-2-line"></i> Merge</button>
                </div>
                <div id="fst-cart-table-wrap">
                    <div id="fst-cart-empty"></div>
                    <table id="fst-cart-table" style="display:none;">
                        <thead><tr><th>Item</th><th>Unit</th><th>Qty</th><th>Actn</th></tr></thead>
                        <tbody id="fst-cart-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
</div></div></div>

{{-- ══ MERGE MODAL ══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="fstMergeModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-upload-cloud-2-line"></i> Merge Counted Data</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <p style="font-size:13px;color:#475569;">You are about to merge <strong id="mergeLineCount">0</strong> counted product line(s) into the stocktake for <strong>{{ $branchName }}</strong> on <strong>{{ $displayDate }}</strong>.</p>
                <label class="form-label fw-semibold" style="font-size:12px;">Enter your password to confirm</label>
                <input type="password" class="form-control" id="fstMergePassword" placeholder="Password" autocomplete="off">
                <div class="alert alert-info border-0 mt-3 py-2 px-3" style="font-size:11.5px;border-radius:6px;">
                    <i class="ri-information-line me-1"></i> Counting can keep happening on other devices while sales continue — the system reconciles them safely at rectification time.
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="fstMergeSubmitBtn"><i class="ri-check-line"></i> Merge Now</button>
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
                <div class="date-mode-toggle">
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
                <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Stocktaking</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;line-height:1.6;">
                <ul class="info-list mb-3">
                    <li><strong>Select branch and date</strong> using the controls in the action bar above the tabs. Branch uses your saved preference; the date chip defaults to today — tap it to pick a custom date.</li>
                    <li><strong>Search and count</strong> — find a product, type the quantity found, it's added to the counted list. Counting the same product again adds to the quantity.</li>
                    <li><strong>Works offline</strong> — counts are stored on this device until you tap Merge.</li>
                    <li><strong>Merge</strong> — password-confirmed, safe to run from multiple devices. Counts are additive; each product's stock is snapshotted at first merge time for accurate rectification later.</li>
                    <li><strong>Sales keep working</strong> — you don't need to stop selling. The system records the last sale processed before each product was counted. At rectification, only sales that entered the system <em>after</em> that marker are netted out.</li>
                    <li><strong>Missing Products</strong> — products never counted appear there; edit offline and sync.</li>
                    <li><strong>Merged Data</strong> — review counted lines; edits and deletes there are queued offline and synced in a batch.</li>
                    <li><strong>Actions &amp; Info</strong> — review the summary statistics, then run rectification once all devices have merged.</li>
                </ul>
                <div class="alert alert-warning border-0" style="font-size:12px;">
                    <i class="ri-alert-line me-1"></i> Rectifying locks new counting for that date+branch, but corrections via Merged Data's offline sync are still applied — they automatically re-run the sales-netting math so figures stay consistent.
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
'use strict';

const FST_BRANCH_ID = '{{ $branchId }}';
const FST_DATE      = '{{ $date }}';
const FST_CART_KEY  = 'fullstocktaking_count_cart_' + FST_BRANCH_ID + '_' + FST_DATE;

let fstCart = [];
let fstAllProducts = [];

function fstEsc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function fstFmt(n) { return (n === null || n === undefined || n === '') ? '0' : parseFloat(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); }
function fstFmt2(n) { return (n === null || n === undefined || n === '') ? '0.00' : parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
function fstCsrf() { return document.querySelector('meta[name="csrf-token"]').getAttribute('content'); }
function fstDeviceId() {
    let id = localStorage.getItem('fullstocktaking_device_id');
    if (!id) { id = 'dev_' + Math.random().toString(36).slice(2, 10); localStorage.setItem('fullstocktaking_device_id', id); }
    return id;
}

$(document).ready(function () {
    @if($branchId && !$isRectified)
    try { fstAllProducts = JSON.parse(document.getElementById('fst-products-json').textContent || '[]'); } catch(e) { fstAllProducts = []; }
    loadFstCart(); renderFstCart();

    const display = document.getElementById('fst-product-display');

    function renderRows(products) {
        if (!products.length) { display.innerHTML = '<div style="text-align:center;padding:24px;color:#595959;font-size:13px;">No products matched.</div>'; return; }
        display.innerHTML = products.map(p => `
            <div class="fst-row" data-id="${p.id}">
                <a href="#" class="fst-link" onclick="event.preventDefault();fstRowClick(${p.id})">
                    <span class="fst-name">${fstEsc(p.name)}</span>
                    <span class="fst-meta">${fstFmt(p.price)}/${fstEsc(p.unit)}</span>
                    <span class="fst-stock-tag">[${fstFmt(p.stock)}]</span>
                    ${p.already !== null ? `<span class="fst-already">counted: ${fstFmt(p.already)}</span>` : ''}
                </a>
                <input type="number" class="fst-qty-input" id="fstq_${p.id}" min="0" step="any" autocomplete="off" onchange="fstQtyChange(${p.id})">
            </div>`).join('');
    }

    $('#fst-search').on('keyup', function () {
        const q = $(this).val().trim().toLowerCase();
        if (q.length < 2) { display.innerHTML = ''; return; }
        renderRows(fstAllProducts.filter(p => p.name.toLowerCase().includes(q)));
    });

    const searchInput = document.getElementById('fst-search');
    searchInput.value = '';
    searchInput.addEventListener('focus', function () { this.value = ''; display.innerHTML = ''; });
    searchInput.addEventListener('click', function () { if (this.value) { this.value = ''; display.innerHTML = ''; } });
    setTimeout(() => { searchInput.value = ''; }, 50);
    searchInput.focus();
    @endif
});

function fstFindProduct(id) { return fstAllProducts.find(p => p.id === id); }
function fstRowClick(id) { const input = document.getElementById('fstq_' + id); if (input) input.focus(); }

function fstQtyChange(id) {
    const p = fstFindProduct(id); if (!p) return;
    const input = document.getElementById('fstq_' + id);
    const qty = parseFloat(input.value);
    if (!qty || qty <= 0) { input.value = ''; return; }
    const existing = fstCart.find(c => c.id === id);
    if (existing) { existing.qty += qty; } else { fstCart.push({ id: p.id, name: p.name, unit: p.unit, price: p.price, qty }); }
    saveFstCart(); renderFstCart();
    input.value = '';
    document.getElementById('fst-search').value = '';
    document.getElementById('fst-product-display').innerHTML = '';
    document.getElementById('fst-search').focus();
}

function fstRemoveCartLine(id) { fstCart = fstCart.filter(c => c.id !== id); saveFstCart(); renderFstCart(); }
function saveFstCart() { localStorage.setItem(FST_CART_KEY, JSON.stringify(fstCart)); }
function loadFstCart() { try { fstCart = JSON.parse(localStorage.getItem(FST_CART_KEY) || '[]'); } catch(e) { fstCart = []; } }
function fstCartValue() { return fstCart.reduce((s, c) => s + (c.qty * (c.price || 0)), 0); }

function renderFstCart() {
    const table = document.getElementById('fst-cart-table');
    const tbody = document.getElementById('fst-cart-tbody');
    const empty = document.getElementById('fst-cart-empty');
    const btn   = document.getElementById('fst-merge-btn');
    document.getElementById('fstCartTotal').textContent = fstFmt2(fstCartValue());
    if (!fstCart.length) {
        tbody.innerHTML = ''; table.style.display = 'none'; empty.style.display = 'block';
        if (btn) btn.disabled = true; return;
    }
    empty.style.display = 'none'; table.style.display = 'table';
    tbody.innerHTML = fstCart.map(c =>
        `<tr><td>${fstEsc(c.name)}</td><td>${fstEsc(c.unit)}</td><td>${fstFmt(c.qty)}</td>
         <td><a href="#" class="fst-cart-remove" onclick="event.preventDefault();fstRemoveCartLine(${c.id})">X</a></td></tr>`
    ).join('');
    if (btn) btn.disabled = false;
}

function openMergeModal() {
    if (!fstCart.length) return;
    document.getElementById('mergeLineCount').textContent = fstCart.length;
    document.getElementById('fstMergePassword').value = '';
    $('#fstMergeModal').modal('show');
}

document.getElementById('fstMergeSubmitBtn')?.addEventListener('click', function () {
    const password = document.getElementById('fstMergePassword').value;
    if (!password) { toastr.warning('Please enter your password.'); return; }
    const lines = fstCart.map(c => ({ base_product_id: c.id, quantity: c.qty, product_name: c.name, unit: c.unit }));
    const btn = this; btn.disabled = true;
    fetch('{{ route("retail.operations.fullstocktaking.merge") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': fstCsrf() },
        body: new URLSearchParams({ password, branch_id: FST_BRANCH_ID, date: FST_DATE, device_id: fstDeviceId(), lines: JSON.stringify(lines) }),
    })
    .then(r => r.json().then(d => ({ status: r.status, d })))
    .then(({ status, d }) => {
        btn.disabled = false;
        if (status === 200) { toastr.success(d.message, 'Merged'); fstCart = []; saveFstCart(); renderFstCart(); $('#fstMergeModal').modal('hide'); setTimeout(() => location.reload(), 800); }
        else if (status === 401) { toastr.error(d.message, 'Incorrect Password'); }
        else if (status === 409) { toastr.error(d.message, 'Locked'); }
        else { toastr.error(d.message || 'Merge failed.', 'Error'); }
    })
    .catch(() => { btn.disabled = false; toastr.error('Could not reach the server. Counts remain saved offline.', 'Network Error'); });
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
document.getElementById('fstInfoBtn')?.addEventListener('click', e => { e.preventDefault(); $('#fstInfoModal').modal('show'); });

@if(Session::has('message'))
toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
@endif
</script>
@endsection