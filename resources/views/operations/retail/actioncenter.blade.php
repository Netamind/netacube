@extends('operations.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    $pref       = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();
    $categories = DB::connection('tenant')->table('categories')->orderBy('category')->get();

    $selectedCategory = null;
    $branches         = collect();

    // Date — system date by default, user can override via session/filter
    $date        = $pref->action_date ?? Carbon::today()->toDateString();
    $displayDate = Carbon::parse($date)->format('d F Y');

    // Selected product
    $productId = $pref->action_product_id ?? null;
    $product   = null;

    if ($selectedCategory = ($pref && $pref->category_id
        ? DB::connection('tenant')->table('categories')->where('id', $pref->category_id)->first()
        : null)) {

        $branches = DB::connection('tenant')
            ->table('branches')
            ->where('sector',   'Retail')
            ->where('category', (string) $selectedCategory->id)
            ->where('status',   'active')
            ->orderBy('name')
            ->get();
    }

    // All retail base products for the search
    $baseProducts = DB::connection('tenant')
        ->table('retail_base_products')
        ->where('is_product', 1)
        ->orderBy('name')
        ->get(['id', 'name', 'unit', 'selling_price']);

    if ($productId) {
        $product = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $productId)
            ->first(['id', 'name', 'unit', 'selling_price']);
    }

    // Branch data for JS — keyed by branch id
    $branchData = [];
    foreach ($branches as $b) {
        $stock = $product
            ? (DB::connection('tenant')->table('retail_branch_products')
                ->where('branch_id', $b->id)->where('base_product_id', $product->id)
                ->value('stock_quantity') ?? 0)
            : 0;
        $sdnote = $product
            ? (DB::connection('tenant')->table('retail_deliverynotes')
                ->where('delivery_date', $date)->where('branch_id', $b->id)
                ->where('base_product_id', $product->id)->where('submitted', true)
                ->value('quantity') ?? 0)
            : 0;
        $pending = $product
            ? DB::connection('tenant')->table('retail_deliverynotes')
                ->where('delivery_date', $date)->where('branch_id', $b->id)
                ->where('base_product_id', $product->id)->where('submitted', false)
                ->value('quantity')
            : null;
        $branchData[$b->id] = [
            'name'    => $b->name,
            'stock'   => $stock,
            'sdnote'  => $sdnote,
            'pending' => $pending,
        ];
    }
@endphp

<style>
/* ── Progress bar ──────────────────────────────────────────────────────── */
#progressBar { height: 8px; display: none; transform: rotate(180deg); }

/* ── Card chrome (mirrors shopvalues_overview) ─────────────────────────── */
.card-header {
    padding: 0.5rem 1.5rem !important;
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    color: #fff;
    border-radius: 10px 10px 0 0 !important;
}
.card-body { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card      { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 {
    color: #fff; font-weight: 600; margin-bottom: 0;
    display: flex; align-items: center;
}
.card-header .btn-light {
    height: 28px; padding: 0 10px;
    display: flex; align-items: center; justify-content: center; line-height: 1;
}

/* ── Category select in header ─────────────────────────────────────────── */
#categorySelectHeader {
    border: none; background: transparent; color: #fff;
    font-size: 18px; font-weight: 600; cursor: pointer;
    padding: 0; outline: none; max-width: 340px;
}
#categorySelectHeader option { color: #1e293b; background: #fff; font-size: 14px; }

/* ── Tab navigation ────────────────────────────────────────────────────── */
.tab-header-container { background: #f8f9fa; border-bottom: 1px solid #dee2e6; }
.tab-header-container .nav { justify-content: flex-start !important; }
.nav-pills .nav-link {
    border-radius: 0 !important; padding: .6rem 1.1rem;
    font-weight: 500; font-size: 13px; color: #495057;
    border-bottom: 3px solid transparent; transition: all .2s; white-space: nowrap;
}
.nav-pills .nav-link:hover  { background: #e9ecef; color: #4B5EBD; }
.nav-pills .nav-link.active {
    background: transparent !important; color: #4B5EBD !important;
    border-bottom-color: #4B5EBD; font-weight: 600;
}
.nav-pills .nav-link i { font-size: 1rem; margin-right: .3rem; }

/* ── Action-bar strip below tabs ───────────────────────────────────────── */
.action-bar {
    display: flex; align-items: center; flex-wrap: wrap;
    gap: 8px;
    padding: 10px 1.5rem;
    background: #f0f2fa;
    border-bottom: 1px solid #dde1f0;
}
.action-bar .ab-date {
    font-size: 13px; font-weight: 600; color: #4B5EBD;
    display: flex; align-items: center; gap: 5px;
    cursor: pointer; border: 1px solid #c5caec;
    border-radius: 6px; padding: 4px 10px;
    background: #fff; transition: background .15s;
    user-select: none;
}
.action-bar .ab-date:hover { background: #eef0fa; }
.action-bar .ab-spacer { flex: 1; }
.action-bar .ab-btn {
    height: 30px; padding: 0 10px; font-size: 13px; font-weight: 500;
    border-radius: 6px; display: inline-flex; align-items: center; gap: 5px;
    cursor: pointer; border: none; transition: all .15s;
}
.ab-btn-submit  { background: #059669; color: #fff; }
.ab-btn-submit:hover  { background: #047857; color: #fff; }
.ab-btn-all     { background: #4B5EBD; color: #fff; }
.ab-btn-all:hover     { background: #3b4fa0; color: #fff; }
.ab-btn-cancel  { background: #ea580c; color: #fff; }
.ab-btn-cancel:hover  { background: #c2410c; color: #fff; }
.ab-btn-delete  { background: #dc2626; color: #fff; }
.ab-btn-delete:hover  { background: #b91c1c; color: #fff; }

/* ── Product search ────────────────────────────────────────────────────── */
.product-search-wrap {
    position: relative; flex: 1; min-width: 200px; max-width: 340px;
}
#productSearch {
    width: 100%; border: 1px solid #c5caec; border-radius: 6px;
    padding: 4px 10px; font-size: 13px; outline: none;
}
#productSearch:focus { border-color: #4B5EBD; box-shadow: 0 0 0 2px rgba(75,94,189,0.15); }
#productDropdown {
    display: none;
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: #fff; border: 1px solid #dde1f0; border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12); z-index: 2000;
    max-height: 280px; overflow-y: auto;
}
#productDropdown .pd-item {
    padding: 8px 12px; font-size: 13px; cursor: pointer;
    border-bottom: 1px solid #f1f5f9; transition: background .1s;
}
#productDropdown .pd-item:last-child { border-bottom: none; }
#productDropdown .pd-item:hover { background: #f0f2fa; }
#productDropdown .pd-item .pd-name { font-weight: 600; color: #1e293b; }
#productDropdown .pd-item .pd-meta { font-size: 11px; color: #94a3b8; margin-top: 1px; }

/* ── Selected product bar ──────────────────────────────────────────────── */
.selected-product-bar {
    padding: 10px 1.5rem;
    background: #fff;
    border-bottom: 1px solid #e9ecef;
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.spb-label  { font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; }
.spb-name   { font-size: 15px; font-weight: 700; color: #4B5EBD; }
.spb-price  { font-size: 13px; color: #059669; font-weight: 600; }
.spb-unit   { font-size: 12px; color: #94a3b8; }
.spb-spacer { flex: 1; }
.spb-empty  { font-size: 13px; color: #94a3b8; font-style: italic; }

/* ── Branch grid ───────────────────────────────────────────────────────── */
.branch-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 14px;
    padding: 1.5rem;
}
.branch-card {
    background: #fff;
    border: 1px solid #e4e7f0;
    border-radius: 10px;
    padding: 14px;
    transition: box-shadow .2s, border-color .2s;
}
.branch-card:hover { box-shadow: 0 4px 12px rgba(75,94,189,0.1); border-color: #c5caec; }
.bc-name  { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
.bc-meta  { font-size: 11px; color: #94a3b8; margin-bottom: 10px; }
.bc-meta span { margin-right: 8px; }
.bc-meta .bc-stock  { color: #059669; font-weight: 600; }
.bc-meta .bc-sdnote { color: #4B5EBD; font-weight: 600; }
.bc-input-wrap { position: relative; }
.bc-input {
    width: 100%; text-align: center; font-size: 16px; font-weight: 600;
    border: 1.5px solid #dde1f0; border-radius: 7px; padding: 7px 8px;
    outline: none; transition: border-color .15s, box-shadow .15s;
    color: #1e293b; background: #fafafa;
}
.bc-input:focus {
    border-color: #4B5EBD;
    box-shadow: 0 0 0 3px rgba(75,94,189,0.12);
    background: #fff;
}
.bc-input.saved   { border-color: #059669; background: #f0fdf4; }
.bc-input.error   { border-color: #dc2626; background: #fef2f2; }
.bc-input.saving  { border-color: #f59e0b; }

/* ── No-product placeholder ────────────────────────────────────────────── */
.no-product-placeholder {
    padding: 60px 1.5rem; text-align: center; color: #94a3b8;
}
.no-product-placeholder i { font-size: 48px; color: #dde1f0; margin-bottom: 14px; }
.no-product-placeholder p { font-size: 14px; margin: 0; }

/* ── Modal header ──────────────────────────────────────────────────────── */
.mh-blue  { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 14px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-title { color: #fff; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }

/* ── No-category placeholder ───────────────────────────────────────────── */
.no-category-wrap {
    padding: 80px 1.5rem; text-align: center;
}
.no-category-wrap i  { font-size: 52px; color: #dde1f0; margin-bottom: 16px; }
.no-category-wrap p  { color: #94a3b8; font-size: 14px; }
</style>

<div class="progress" id="progressBar" role="progressbar">
    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

    {{-- ── Card header ──────────────────────────────────────────────────── --}}
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="header-title mb-0">
            <i class="ri-store-2-line me-1"></i>
            <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
                  id="headerCategoryForm" style="margin:0;display:inline;">
                @csrf
                <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                <select name="category_id" id="categorySelectHeader"
                        onchange="document.getElementById('headerCategoryForm').submit()">
                    <option value="" hidden>
                        {{ $selectedCategory ? $selectedCategory->category : '— Select Category —' }}
                    </option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}"
                            {{ ($pref && $pref->category_id == $cat->id) ? 'selected' : '' }}>
                            {{ $cat->category }}
                        </option>
                    @endforeach
                </select>
            </form>
        </h4>
        <div class="d-flex align-items-center" style="gap:4px;">
            <a href="#" class="btn btn-light text-primary fs-16" id="dateModalBtn" title="Change date">
                <i class="ri-calendar-line"></i>
            </a>
            <a href="#" class="btn btn-light text-primary fs-16" id="infoBtn" title="About Action Centre">
                <i class="ri-information-line"></i>
            </a>
        </div>
    </div>

    {{-- ── Tabs ─────────────────────────────────────────────────────────── --}}
    <div class="tab-header-container">
        <ul class="nav nav-pills mb-0">
            <li class="nav-item">
                <a href="{{ route('retail.operations.actioncenter') }}" class="nav-link active">
                    <i class="ri-send-plane-line"></i> Action Centre
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="ri-file-list-3-line"></i> Delivery Notes
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="ri-price-tag-3-line"></i> Price Changes
                </a>
            </li>
        </ul>
    </div>

    @if(!$selectedCategory)
        {{-- ── No category selected ────────────────────────────────────── --}}
        <div class="no-category-wrap">
            <i class="ri-store-2-line d-block mx-auto"></i>
            <p>Please select a category above to get started.</p>
        </div>
    @else

        {{-- ── Action bar ──────────────────────────────────────────────── --}}
        <div class="action-bar">

            {{-- Date pill --}}
            <span class="ab-date" id="datePill">
                <i class="ri-calendar-check-line"></i>
                <span id="datePillText">{{ $displayDate }}</span>
            </span>

            {{-- Product search --}}
            <div class="product-search-wrap">
                <input type="text" id="productSearch"
                       placeholder="Search product…"
                       autocomplete="off"
                       value="{{ $product ? $product->name : '' }}">
                <div id="productDropdown">
                    @foreach($baseProducts as $bp)
                        <div class="pd-item"
                             data-id="{{ $bp->id }}"
                             data-name="{{ $bp->name }}"
                             data-unit="{{ $bp->unit }}"
                             data-price="{{ $bp->selling_price }}">
                            <div class="pd-name">{{ $bp->name }}</div>
                            <div class="pd-meta">{{ number_format($bp->selling_price, 2) }} / {{ $bp->unit }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <span class="ab-spacer"></span>

            {{-- Action buttons --}}
            <button class="ab-btn ab-btn-submit" id="submitBtn" title="Add distributed quantities to branches">
                <i class="ri-check-line"></i> Submit
            </button>
            <button class="ab-btn ab-btn-all" id="submitAllBtn" title="Submit all products distributed on this date">
                <i class="ri-check-double-line"></i> Submit (ALL)
            </button>
            <button class="ab-btn ab-btn-cancel" id="cancelBtn" title="Delete this distribution">
                <i class="ri-close-line"></i> Cancel
            </button>
            <button class="ab-btn ab-btn-delete" id="deleteBtn" title="Delete product">
                <i class="ri-delete-bin-line"></i> Delete
            </button>
        </div>

        {{-- ── Selected product summary ─────────────────────────────────── --}}
        <div class="selected-product-bar">
            @if($product)
                <span class="spb-label">Selected:</span>
                <span class="spb-name" id="spbName">{{ $product->name }}</span>
                <span class="spb-price" id="spbPrice">MWK {{ number_format($product->selling_price, 2) }}</span>
                <span class="spb-unit" id="spbUnit">/ {{ $product->unit }}</span>
            @else
                <span class="spb-empty" id="spbEmpty">No product selected — use the search above.</span>
                <span class="spb-name" id="spbName" style="display:none"></span>
                <span class="spb-price" id="spbPrice" style="display:none"></span>
                <span class="spb-unit"  id="spbUnit"  style="display:none"></span>
            @endif
        </div>

        {{-- ── Branch grid ──────────────────────────────────────────────── --}}
        @if($product)
            <div class="branch-grid" id="branchGrid">
                @foreach($branches as $branch)
                    @php
                        $stock = DB::connection('tenant')
                            ->table('retail_branch_products')
                            ->where('branch_id',       $branch->id)
                            ->where('base_product_id', $product->id)
                            ->value('stock_quantity') ?? 0;

                        $sdnote = DB::connection('tenant')
                            ->table('retail_deliverynotes')
                            ->where('delivery_date',   $date)
                            ->where('branch_id',       $branch->id)
                            ->where('base_product_id', $product->id)
                            ->where('submitted',       true)
                            ->value('quantity') ?? 0;

                        $pending = DB::connection('tenant')
                            ->table('retail_deliverynotes')
                            ->where('delivery_date',   $date)
                            ->where('branch_id',       $branch->id)
                            ->where('base_product_id', $product->id)
                            ->where('submitted',       false)
                            ->value('quantity');
                    @endphp
                    <div class="branch-card"
                         data-branch-id="{{ $branch->id }}"
                         data-product-id="{{ $product->id }}">
                        <div class="bc-name">{{ $branch->name }}</div>
                        <div class="bc-meta">
                            <span>stock: <span class="bc-stock">{{ $stock }}</span></span>
                            <span>sdnote: <span class="bc-sdnote">{{ $sdnote }}</span></span>
                        </div>
                        <div class="bc-input-wrap">
                            <input type="number"
                                   class="bc-input"
                                   placeholder="0"
                                   value="{{ $pending !== null ? $pending : '' }}"
                                   data-branch-id="{{ $branch->id }}"
                                   data-product-id="{{ $product->id }}"
                                   data-branch-name="{{ $branch->name }}">
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="no-product-placeholder" id="noProductPlaceholder">
                <i class="ri-search-line d-block mx-auto"></i>
                <p>Search and select a product above to distribute to branches.</p>
            </div>
        @endif

    @endif

</div>
</div></div></div>

{{-- ══ Date Modal ════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="dateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-calendar-line"></i> Change Date</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="dateForm">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                    <label class="form-label fw-semibold">Delivery Date</label>
                    <input type="date" name="action_date" class="form-control" value="{{ $date }}" id="dateInput">
                    <button type="submit" class="btn btn-primary mt-3" style="float:right">
                        <i class="ri-check-line"></i> Apply
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ══ Info Modal ════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Action Centre</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <p><strong>Action Centre</strong> is where you distribute stock to retail branches by creating delivery notes.</p>
                <hr class="my-3">
                <table style="width:100%;font-size:13px;border-collapse:collapse;">
                    <tbody>
                        <tr>
                            <td style="padding:8px 12px;font-weight:700;color:#475569;width:160px;border-bottom:1px solid #f1f5f9;">Select Product</td>
                            <td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">Use the search box to choose a base product. Each branch card will show its current stock and already-submitted delivery notes for the selected date.</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9;">Enter Quantity</td>
                            <td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">Type a quantity in the branch card. On change it is saved automatically as a pending delivery note (not yet added to stock).</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9;">Submit</td>
                            <td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">Adds the pending delivery note quantities for the current product to branch stock. The note is marked as submitted.</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9;">Submit (ALL)</td>
                            <td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">Same as Submit but processes every pending delivery note for the selected date across all products.</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9;">Cancel</td>
                            <td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">Deletes all pending (unsubmitted) delivery notes for the current product on the selected date.</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 12px;font-weight:700;color:#475569;">Date</td>
                            <td style="padding:8px 12px;">Defaults to today. Click the calendar icon in the header or the date pill in the action bar to change it.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
$(document).ready(function () {

    /* ── Toastr defaults ───────────────────────────────────────────────── */
    toastr.options = { timeOut: 5000, progressBar: true, positionClass: 'toast-top-end' };

    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

    /* ── Progress bar helpers ──────────────────────────────────────────── */
    function showProgress() { $('#progressBar').show(); }
    function hideProgress() { $('#progressBar').hide(); }

    /* ── Date modal ────────────────────────────────────────────────────── */
    $('#dateModalBtn, #datePill').on('click', function (e) {
        e.preventDefault();
        $('#dateModal').modal('show');
    });

    /* ── Info modal ────────────────────────────────────────────────────── */
    $('#infoBtn').on('click', function (e) {
        e.preventDefault();
        $('#infoModal').modal('show');
    });

    /* ── Product data (embedded server-side, zero AJAX) ───────────────── */
    /*
     * All products are already in the DOM as .pd-item elements.
     * We also keep a JS map keyed by id so we can look up price/unit
     * instantly when a product is selected — no network round-trip needed.
     */
    var productMap = {};
    $('#productDropdown .pd-item').each(function () {
        var id = String($(this).data('id'));
        productMap[id] = {
            id:    id,
            name:  $(this).data('name'),
            unit:  $(this).data('unit'),
            price: parseFloat($(this).data('price')) || 0,
        };
    });

    var branchData = @json($branchData);

    /* ── Product search — pure client-side filter ──────────────────────── */
    $('#productSearch').on('input', function () {
        var val = $(this).val().toLowerCase().trim();

        if (val.length < 1) {
            $('#productDropdown').hide();
            return;
        }

        var shown = 0;
        $('#productDropdown .pd-item').each(function () {
            var name    = $(this).data('name').toLowerCase();
            var code    = String($(this).data('code') || '').toLowerCase();
            var matches = name.includes(val) || code.includes(val);
            $(this).toggle(matches);
            if (matches) shown++;
        });

        shown > 0 ? $('#productDropdown').show() : $('#productDropdown').hide();
    });

    /* ── Product selected — update UI and branch grid, NO page reload ──── */
    $('#productDropdown').on('click', '.pd-item', function () {
        var p = productMap[String($(this).data('id'))];
        if (!p) return;

        $('#productSearch').val(p.name);
        $('#productDropdown').hide();

        /* Update selected-product bar */
        $('#spbEmpty').hide();
        $('#spbName').text(p.name).show();
        $('#spbPrice').text('MWK ' + p.price.toLocaleString('en-US', { minimumFractionDigits: 2 })).show();
        $('#spbUnit').text('/ ' + p.unit).show();

        /* Track active product for AJAX calls */
        activeProductId = p.id;

        /* Re-render branch grid client-side */
        renderBranchGrid(p);

        /* Persist selection silently in the background (no reload) */
        $.post('{{ route("tenant.admin.update.filters") }}', {
            _token:            csrfToken,
            user_id:           {{ Auth::id() }},
            action_product_id: p.id,
        });
    });

    /* Close dropdown when clicking outside */
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.product-search-wrap').length) {
            $('#productDropdown').hide();
        }
    });

    /* ── Render branch grid from client-side data ──────────────────────── */
    function renderBranchGrid(product) {
        var $grid = $('#branchGrid');

        if (!$grid.length) {
            /* First time: replace the no-product placeholder */
            $('#noProductPlaceholder').replaceWith(
                '<div class="branch-grid" id="branchGrid"></div>'
            );
            $grid = $('#branchGrid');
        }

        $grid.empty();

        $.each(branchData, function (branchId, b) {
            var card = [
                '<div class="branch-card" data-branch-id="' + branchId + '" data-product-id="' + product.id + '">',
                    '<div class="bc-name">' + $('<span>').text(b.name).html() + '</div>',
                    '<div class="bc-meta">',
                        '<span>stock: <span class="bc-stock">' + b.stock + '</span></span>',
                        '<span>sdnote: <span class="bc-sdnote">' + b.sdnote + '</span></span>',
                    '</div>',
                    '<div class="bc-input-wrap">',
                        '<input type="number"',
                        '       class="bc-input"',
                        '       placeholder="0"',
                        '       value="' + (b.pending !== null ? b.pending : '') + '"',
                        '       data-branch-id="' + branchId + '"',
                        '       data-product-id="' + product.id + '"',
                        '       data-branch-name="' + $('<span>').text(b.name).html() + '">',
                    '</div>',
                '</div>',
            ].join('');

            $grid.append(card);
        });
    }

    /* ── Route URLs (defined once, referenced everywhere) ─────────────── */
    var routes = {
        saveDnote:  '{{ route("retail.operations.actioncenter.save.dnote") }}',
        submit:     '{{ route("retail.operations.actioncenter.submit") }}',
        submitAll:  '{{ route("retail.operations.actioncenter.submitall") }}',
        cancel:     '{{ route("retail.operations.actioncenter.cancel") }}',
        deleteNote: '{{ route("retail.operations.actioncenter.delete.note") }}',
        updateNote: '{{ route("retail.operations.actioncenter.update.note") }}',
        notes:      '{{ route("retail.operations.actioncenter.notes") }}',
        dates:      '{{ route("retail.operations.actioncenter.dates") }}',
    };

    /* ── Active product id (tracks the currently selected product) ─────── */
    var activeProductId = '{{ $product->id ?? '' }}';
    var activeDate      = '{{ $date }}';

    /* ── On-change save for branch inputs ──────────────────────────────── */
    $(document).on('change', '.bc-input', function () {
        var $input    = $(this);
        var branchId  = $input.data('branch-id');
        var productId = $input.data('product-id');
        var quantity  = $input.val();

        if (quantity === '' || isNaN(quantity)) return;

        $input.removeClass('saved error').addClass('saving');

        $.ajax({
            type: 'POST',
            url:  routes.saveDnote,
            data: { branch_id: branchId, base_product_id: productId, quantity: quantity, delivery_date: activeDate },
            success: function (res) {
                if (res.status === 200 || res.status === 201) {
                    $input.removeClass('saving').addClass('saved');
                } else {
                    $input.removeClass('saving').addClass('error');
                    toastr.error('Failed to save delivery note.');
                }
            },
            error: function () {
                $input.removeClass('saving').addClass('error');
                toastr.error('An error occurred saving the delivery note.');
            }
        });
    });

    /* ── Submit (current product) ──────────────────────────────────────── */
    $('#submitBtn').on('click', function () {
        if (!activeProductId) { toastr.warning('Please select a product first.'); return; }
        var $btn = $(this).prop('disabled', true);
        showProgress();
        $.ajax({
            type:     'POST',
            url:      routes.submit,
            data:     { base_product_id: activeProductId, delivery_date: activeDate },
            complete: function () { hideProgress(); $btn.prop('disabled', false); },
            success:  function (data) {
                if (data.success) toastr.success(data.success);
                if (data.info)    toastr.info(data.info);
            },
            error: function (xhr) { handleAjaxError(xhr); }
        });
    });

    /* ── Submit ALL products for the date ──────────────────────────────── */
    $('#submitAllBtn').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        showProgress();
        $.ajax({
            type:     'POST',
            url:      routes.submitAll,
            data:     { delivery_date: activeDate },
            complete: function () { hideProgress(); $btn.prop('disabled', false); },
            success:  function (data) {
                if (data.success) toastr.success(data.success);
                if (data.info)    toastr.info(data.info);
            },
            error: function (xhr) { handleAjaxError(xhr); }
        });
    });

    /* ── Cancel pending notes for current product ──────────────────────── */
    $('#cancelBtn').on('click', function () {
        if (!activeProductId) { toastr.warning('Please select a product first.'); return; }
        var $btn = $(this).prop('disabled', true);
        showProgress();
        $.ajax({
            type:     'POST',
            url:      routes.cancel,
            data:     { base_product_id: activeProductId, delivery_date: activeDate },
            complete: function () { hideProgress(); $btn.prop('disabled', false); },
            success:  function (data) {
                if (data.success) {
                    toastr.success(data.success);
                    $('.bc-input').val('').removeClass('saved error saving');
                }
                if (data.info) toastr.info(data.info);
            },
            error: function (xhr) { handleAjaxError(xhr); }
        });
    });

    /* ── Delete note ───────────────────────────────────────────────────── */
    $('#deleteBtn').on('click', function () {
        toastr.info('Delete feature coming soon.');
    });

    /* ── Generic AJAX error handler ────────────────────────────────────── */
    function handleAjaxError(xhr) {
        if (xhr.status === 0 && xhr.readyState === 0) {
            toastr.error('Timeout — check your connection and try again.', 'Timeout');
        } else if (xhr.status === 422) {
            var msgs = '';
            $.each(xhr.responseJSON.errors ?? {}, function (k, v) { msgs += v + '\n'; });
            toastr.error(msgs, 'Validation Error');
        } else if (xhr.status === 500) {
            toastr.error(xhr.responseJSON?.error ?? 'Internal server error.', 'Server Error');
        } else {
            toastr.error('An unspecified error occurred.', 'Error');
        }
    }

    /* ── Session flash messages ────────────────────────────────────────── */
    @if(Session::has('message'))
        var type = "{{ Session::get('alert-type','info') }}";
        toastr[type]("{{ Session::get('message') }}");
    @endif
});
</script>
@endsection