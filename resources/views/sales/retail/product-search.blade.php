@extends('sales.retail.dashboard')
@section('content')

@php
    // ── Resolve the logged-in user's branch, sector & category ─────────────
    // Peer branches must match BOTH sector and category — branches.sector +
    // branches.category + branches.name form the actual uniqueness key
    // (see the branches migration's b_s_c_unique index), so category alone
    // isn't a safe grouping: two branches can share a category but sit in
    // different sectors (e.g. Retail vs Wholesale), and that's not the same
    // "peer group" a Sales user should see stock/pricing for.
    $myBranchId = Auth::user()->branch;
    $myBranch   = $myBranchId ? DB::connection('tenant')->table('branches')->find($myBranchId) : null;

    $mySector     = $myBranch->sector ?? null;
    $categoryId   = $myBranch->category ?? null;
    $categoryName = null;

    $categoryBranches = collect();
    $availabilityData = collect(); // payload sent to the front end, built once per page load
    $lastSyncedAt     = now(); // moment this payload was generated — shown to the user as "last sync"

    if ($mySector && $categoryId) {
        $categoryName = optional(
            DB::connection('tenant')->table('categories')->where('id', $categoryId)->first()
        )->category;

        $categoryBranches = DB::connection('tenant')
            ->table('branches')
            ->where('sector', $mySector)
            ->where('category', $categoryId)
            ->orderBy('name')
            ->get();

        $branchIds = $categoryBranches->pluck('id');

        // ── Pull every product in this sector+category's branches once, with
        //    all branch-level stock rows nested in, so the front end never
        //    needs to call the server again — searching/filtering happens
        //    entirely in JS against this single payload. ────────────────────
        $baseProducts = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('is_product', 1)
            ->orderBy('name')
            ->get();

        $branchRowsByProduct = DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('branches as br', 'br.id', '=', 'rbp.branch_id')
            ->whereIn('rbp.branch_id', $branchIds)
            ->select(
                'rbp.base_product_id', 'rbp.branch_id', 'br.name as branch_name',
                'rbp.stock_quantity', 'rbp.reorder_point',
                'rbp.selling_price', 'rbp.is_active'
            )
            ->orderBy('br.name')
            ->get()
            ->groupBy('base_product_id');

        $availabilityData = $baseProducts
            ->filter(function ($bp) use ($branchRowsByProduct) {
                // Only keep products actually stocked at at least one branch
                // in this sector+category — keeps the payload relevant and small.
                return $branchRowsByProduct->has($bp->id);
            })
            ->map(function ($bp) use ($branchRowsByProduct) {
                return [
                    'id'       => $bp->id,
                    'name'     => $bp->name,
                    'code'     => $bp->code,
                    'unit'     => $bp->unit,
                    'supplier' => $bp->supplier,
                    'bp_sell'  => $bp->selling_price,
                    'branches' => $branchRowsByProduct->get($bp->id, collect())->values(),
                ];
            })
            ->values();
    }
@endphp

<style>
/* ── Card chrome ─────────────────────────────────────────────────────────── */
.card       { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; }
.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff;
  border-radius: 0 !important; /* flat blue bar — no rounded corners */
}
.card-body  { padding: 1.25rem 1.5rem 1.5rem 1.5rem !important; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header h4 i { margin-right: 0.25rem; }
.card-header .btn-light {
  height:28px; padding:0 10px;
  display:flex; align-items:center; justify-content:center; line-height:1;
}
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }
.card-header .btn-search {
  height:34px; padding:0 16px; font-weight:600; font-size:13px;
  display:flex; align-items:center; gap:6px; line-height:1;
  background:#fff; color:#4B5EBD; border:none; border-radius:8px;
}
.card-header .btn-search:hover { background:#eef1ff; }

.stock-ok   { color: #16a34a; font-weight: 700; }
.stock-low  { color: #d97706; font-weight: 700; }
.stock-zero { color: #dc2626; font-weight: 700; }
.price-cell { font-size:12px; font-weight:600; color:#1e293b; }
.price-branch { color: #1d4ed8; font-weight: 700; }
.price-base   { color: #059669; font-weight: 600; }

.no-branch-wrap { padding: 48px 20px; text-align: center; color: #94a3b8; }
.no-branch-wrap i { font-size: 52px; display: block; margin-bottom: 12px; color: #c8d0ed; }
.no-branch-wrap h5 { color: #64748b; font-weight: 600; }

/* ── Card body prompt (search now lives in the modal) ───────────────────── */
.search-prompt { text-align:center; padding: 40px 20px 32px; }
.search-prompt i.prompt-icon { font-size:52px; color:#c8d0ed; display:block; margin-bottom:14px; }
.search-prompt h5 { color:#334155; font-weight:700; margin-bottom:6px; }
.search-prompt p { color:#94a3b8; font-size:13px; margin-bottom:20px; }
.prompt-meta { display:flex; align-items:center; justify-content:center; gap:10px; flex-wrap:wrap; margin-top:22px; }

/* ── Modal header helpers ───────────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── Search bar (now inside the modal) ───────────────────────────────────── */
.avail-search-wrap { position: relative; width: 100%; }
.avail-search-wrap input {
  box-sizing: border-box;
  width: 100%;
  font-size: 15px;
  line-height: 1.4;
  padding: 0.75em 2.6em 0.75em 1em;   /* right padding clears the clear-button addon */
  border-radius: 8px;
  border: 1.5px solid #dde1f0;
  background: #fff; color: #1e293b;
  box-shadow: 0 1px 2px rgba(16,24,40,0.04);
  transition: border-color .15s, box-shadow .15s;
}
.avail-search-wrap input::placeholder { color: #9ca3af; }
.avail-search-wrap input:focus {
  border-color: #4B5EBD; box-shadow: 0 0 0 3px rgba(75,94,189,0.12); outline: none;
}
/* ── Clear (✕) addon button, sits inside the input on the right ────────── */
.avail-search-wrap .clear-btn {
  position: absolute; right: 0.6em; top: 50%; transform: translateY(-50%);
  width: 26px; height: 26px; border-radius: 6px; border: none; background: transparent;
  color: #9ca3af; font-size: 1.15em; display: none;
  align-items: center; justify-content: center; cursor: pointer;
  transition: background-color .15s, color .15s;
}
.avail-search-wrap .clear-btn.show { display: flex; }
.avail-search-wrap .clear-btn:hover { background: #f1f3fa; color: #4B5EBD; }

/* ── "Last synced" chip + scope chips ────────────────────────────────────── */
.avail-sync-chip {
  display: inline-flex; align-items: center; gap: 0.4em;
  font-size: 12px; color: #94a3b8; white-space: nowrap;
}
.avail-sync-chip i { font-size: 1.05em; color: #b6bdcc; }
.avail-search-meta { display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:center; }
.avail-meta-chip {
  display: inline-flex; align-items: center; gap: 0.4em;
  background: #fff; border: 1.5px solid #dde1f0; color: #4B5EBD;
  font-size: 12.5px; font-weight: 600;
  padding: 0.55em 0.9em; line-height: 1.4; border-radius: 8px; white-space: nowrap;
}

/* ── Suggestion list (matches the .search-result-list pattern) ──────────── */
.avail-suggest-box {
  position: relative;
  z-index: 50;
  margin-top: 8px;
  width: 100%;
  max-height: 420px;
  overflow-y: auto;
  background: #fff;
  border: 1.5px solid #dde1f0;
  border-radius: 10px;
  display: none;
}
.avail-suggest-box::-webkit-scrollbar { width: 6px; }
.avail-suggest-box::-webkit-scrollbar-thumb { background: #c8d0ed; border-radius: 6px; }
.avail-suggest-box.show { display: block; }
.avail-suggest-item {
  padding: 10px 14px;
  cursor: pointer;
  border-bottom: 1px solid #f5f6f9;
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
}
.avail-suggest-item:last-child { border-bottom: none; }
.avail-suggest-item:hover, .avail-suggest-item.active { background: #f5f7ff; }
.avail-suggest-name { font-size: 13.5px; font-weight: 600; color: #1e293b; }
.avail-suggest-badge {
  font-size: 10.5px; font-weight: 600; color: #4B5EBD; background: #eef2ff;
  padding: 2px 8px; border-radius: 20px; white-space: nowrap; flex-shrink: 0;
}
.avail-suggest-empty { padding: 16px; text-align: center; color: #94a3b8; font-size: 12.5px; }

/* ── Result / distribution card (now rendered in the main card body) ────── */
#mainResultsWrap { margin-top: 4px; }
.result-card {
  border: 1px solid #e9ecef; border-radius: 10px; overflow: hidden;
}
.result-card-header {
  background: #f8f9fb; padding: 10px 16px; display: flex;
  align-items: center; justify-content: space-between; border-bottom: 1px solid #eef0f5;
}
.result-card-title { font-size: 14px; font-weight: 700; color: #1e293b; }
.avail-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.avail-table th {
  text-align: center; padding: 8px 16px; background: #fcfcfd; color: #94a3b8;
  font-weight: 600; text-transform: uppercase; font-size: 10.5px; letter-spacing: .4px;
  border-bottom: 1px solid #eef0f5;
}
.avail-table th:first-child { text-align: left; }
.avail-table td { padding: 8px 16px; border-bottom: 1px solid #f5f6f9; vertical-align: middle; text-align: center; }
.avail-table td:first-child { text-align: left; }
.avail-table tr:last-child td { border-bottom: none; }
.avail-table tr.is-my-branch td { background: #f5f7ff; }
.branch-name-cell { font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 6px; }
.branch-name-pill {
  display: inline-flex; align-items: center;
  font-size: 11.5px; font-weight: 700; color: #fff; background: #4B5EBD;
  padding: 3px 11px; border-radius: 20px; letter-spacing: .2px;
}
.no-stock-row td { color: #cbd5e1; font-style: italic; }

</style>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      Product Availability
    </h4>

    <div class="d-flex align-items-center" style="gap:8px;">
      @if($myBranch && $mySector && $categoryId)
        <button type="button" class="btn btn-search" id="openSearchBtn">
          <i class="ri-search-line"></i> Search
        </button>
      @endif
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="About Availability Search">
        <i class="ri-information-line"></i>
      </a>
    </div>
  </div>

  <div class="card-body">

    @if(!$myBranch || !$mySector || !$categoryId)
      <div class="no-branch-wrap">
        <i class="ri-store-line"></i>
        <h5>No Branch / Sector / Category Found</h5>
        <p style="font-size:13px;">Your account isn't assigned to a branch with both a sector and a category, so availability search isn't available.</p>
      </div>
    @else

      <div class="search-prompt" id="searchPromptState">
        <i class="ri-search-eye-line prompt-icon"></i>
        <h5>Find where a product is stocked</h5>
        <p>Search by name or code to see stock, price, and status across your branches.</p>
        <button type="button" class="btn btn-primary" id="openSearchBtnBody">
          <i class="ri-search-line me-1"></i> Search Products
        </button>
        <div class="prompt-meta">
          <span class="avail-meta-chip"><i class="ri-store-2-line"></i> {{ $mySector }}</span>
          <span class="avail-meta-chip"><i class="ri-price-tag-3-line"></i> {{ $categoryName ?? 'Category' }}</span>
          <span class="avail-meta-chip"><i class="ri-git-branch-line"></i> {{ $categoryBranches->count() }} branch(es)</span>
          <span class="avail-sync-chip" title="Data was loaded from the server at this time; re-open or reload the page to refresh it.">
            <i class="ri-refresh-line"></i> Last synced: {{ $lastSyncedAt->format('H:i:s') }}
          </span>
        </div>
      </div>

      <div id="mainResultsWrap" style="display:none;"></div>

    @endif

  </div>
</div>
</div></div></div>

{{-- ══════════════════════════════════════════════════════════════════════
     SEARCH MODAL — search input + live suggestions + distribution result
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content"
       style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title">Search Products</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:18px 20px;">

      <div class="avail-search-meta mb-3">
        <span class="avail-meta-chip"><i class="ri-store-2-line"></i> {{ $mySector }}</span>
        <span class="avail-meta-chip"><i class="ri-price-tag-3-line"></i> {{ $categoryName ?? 'Category' }}</span>
        <span class="avail-meta-chip"><i class="ri-git-branch-line"></i> {{ $categoryBranches->count() }} branch(es)</span>
      </div>

      <div class="avail-search-wrap">
        <input type="text" id="availSearchInput" placeholder="Search product name or code…" autocomplete="off" />
        <button type="button" class="clear-btn" id="availClearBtn" title="Clear search">
          <i class="ri-close-line"></i>
        </button>
      </div>
      <div id="availSuggestBox" class="avail-suggest-box"></div>

    </div>
  </div></div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     INFO MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content"
       style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Product Availability</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:18px 20px;">
      <p class="mb-2"><strong>What is this?</strong><br>
      Search for a product to see which branches in your sector &amp; category currently stock it, along with their stock level, selling price, and status.</p>
      <p class="mb-2" style="font-size:12.5px;color:#6c757d;">
        Results are scoped to branches sharing both the <strong>{{ $mySector ?? '—' }}</strong> sector and the <strong>{{ $categoryName ?? '—' }}</strong> category as your branch
        (your branch: <strong>{{ $myBranch->name ?? '—' }}</strong>).
      </p>
      <hr class="my-3">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <tbody>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;width:140px;border-bottom:1px solid #f1f5f9">Scope</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Only branches sharing both your sector and your category are shown.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Stock Qty</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9"><span style="color:#dc2626;font-weight:600">Red = zero</span>, <span style="color:#d97706;font-weight:600">amber = at/below reorder point</span>, <span style="color:#16a34a;font-weight:600">green = healthy</span>.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Sell Price</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9"><span style="color:#1d4ed8;font-weight:700">Blue</span> = that branch has set its own price override. <span style="color:#059669;font-weight:600">Green</span> = the branch has no override and is using the base catalogue price.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Search</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Type multiple words in any order — every word just needs to appear somewhere in the name or code, spaces and all, so "para ce" and "parace" both find "Paracetamol".</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569">Your Branch</td><td style="padding:8px 12px">Your own branch's name is shown as a highlighted pill for quick reference.</td></tr>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    $('#infoBtn').on('click', function (e) { e.preventDefault(); $('#infoModal').modal('show'); });

    @if($myBranch && $mySector && $categoryId)

    var myBranchId = {{ $myBranchId }};

    // ── Entire dataset for this sector+category, fetched once on page load.
    //    All searching/filtering below happens against this in-memory
    //    array — no further requests to the server. ─────────────────────
    var allProducts   = @json($availabilityData);
    var lastSyncedAt  = @json($lastSyncedAt->format('H:i:s'));

    var suggestBox  = $('#availSuggestBox');
    var searchInput = $('#availSearchInput');
    var clearBtn    = $('#availClearBtn');
    var activeIndex = -1; // currently keyboard-highlighted suggestion

    function resetModalState() {
        clearBtn.removeClass('show');
        hideSuggestions();
    }

    // ── Open the search modal from either header or body button ─────────
    $('#openSearchBtn, #openSearchBtnBody').on('click', function () {
        $('#searchModal').modal('show');
    });

    $('#searchModal').on('shown.bs.modal', function () {
        searchInput.trigger('focus');
    });

    $('#searchModal').on('hidden.bs.modal', function () {
        resetModalState();
    });

    // ── Clicking the search input clears it, ready for a fresh search ────
    searchInput.on('click', function () {
        if ($(this).val() !== '') {
            $(this).val('');
            clearBtn.removeClass('show');
            hideSuggestions();
        }
    });

    function fmtNum(val, dec) {
        dec = dec === undefined ? 2 : dec;
        if (val === null || val === '' || val === undefined) return '—';
        var n = parseFloat(val);
        return isNaN(n) ? '—' : n.toLocaleString('en-US', {minimumFractionDigits:dec, maximumFractionDigits:dec});
    }

    function stockClass(sq, rp) {
        sq = parseFloat(sq || 0); rp = parseFloat(rp || 0);
        return sq <= 0 ? 'stock-zero' : (sq <= rp ? 'stock-low' : 'stock-ok');
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function normalize(str) {
        return (str == null ? '' : String(str)).toLowerCase();
    }

    // ── Token-based smart matching ───────────────────────────────────────
    // The query is split into whitespace-separated tokens; a product
    // matches only if EVERY token appears somewhere in its searchable text
    // (name + code + supplier), regardless of order or where in the text
    // it falls. This is what makes both spacing variants of the same
    // search find the same product:
    //   "parace"  → one token "parace", a direct substring of "paracetamol"
    //   "para ce" → two tokens "para" + "ce", both substrings of
    //               "paracetamol" independently — still matches, even
    //               though "para ce" itself never appears verbatim.
    // Results are then ranked so a name that STARTS WITH the first token
    // sorts above one that merely contains it further in.
    function filterProducts(q) {
        var tokens = normalize(q).split(/\s+/).filter(Boolean);
        if (!tokens.length) return [];

        var matches = allProducts.filter(function (p) {
            var haystack = normalize([p.name, p.code, p.supplier].filter(Boolean).join(' '));
            return tokens.every(function (t) { return haystack.indexOf(t) !== -1; });
        });

        var firstToken = tokens[0];
        matches.sort(function (a, b) {
            var an = normalize(a.name), bn = normalize(b.name);
            var aStarts = an.indexOf(firstToken) === 0 ? 0 : 1;
            var bStarts = bn.indexOf(firstToken) === 0 ? 0 : 1;
            if (aStarts !== bStarts) return aStarts - bStarts;
            return an.localeCompare(bn);
        });

        return matches.slice(0, 30); // cap suggestion list length
    }

    // ── Render the dropdown list of matching products as the user types ──
    function renderSuggestions(products, q) {
        activeIndex = -1;

        if (!products.length) {
            suggestBox.html(`<div class="avail-suggest-empty">No products matched "<strong>${escapeHtml(q)}</strong>".</div>`).addClass('show');
            return;
        }

        var html = '';
        products.forEach(function (p, idx) {
            html += `
                <div class="avail-suggest-item" data-index="${idx}">
                    <div>
                        <div class="avail-suggest-name">${escapeHtml(p.name)}</div>
                    </div>
                    <span class="avail-suggest-badge">${p.branches ? p.branches.length : 0} branch(es)</span>
                </div>`;
        });

        suggestBox.html(html).addClass('show').data('current', products);
    }

    function hideSuggestions() {
        suggestBox.removeClass('show').empty();
        activeIndex = -1;
    }

    // ── Render the full branch-availability card for ONE selected product,
    //    into the MAIN page card (not the modal) ──────────────────────────
    function renderProductResult(p) {
        var wrap = $('#mainResultsWrap');
        var branchRows = '';
        var unit = p.unit || '';

        if (!p.branches || !p.branches.length) {
            branchRows = `<tr class="no-stock-row"><td colspan="4">Not stocked at any branch in your sector &amp; category.</td></tr>`;
        } else {
            p.branches.forEach(function (b) {
                var isMine = parseInt(b.branch_id) === myBranchId;

                // A branch is on its OWN price only when it has a non-null
                // override; otherwise it's inheriting the base catalogue
                // price — colour + source label follow that distinction.
                var hasOverride = (b.selling_price !== null && b.selling_price !== undefined && b.selling_price !== '');
                var priceVal    = hasOverride ? b.selling_price : p.bp_sell;
                var priceClass  = hasOverride ? 'price-branch' : 'price-base';
                var priceText   = fmtNum(priceVal) + (unit ? ('/' + escapeHtml(unit)) : '');

                branchRows += `
                    <tr class="${isMine ? 'is-my-branch' : ''}">
                        <td class="branch-name-cell">
                            ${isMine ? '<span class="branch-name-pill">' + escapeHtml(b.branch_name) + '</span>' : escapeHtml(b.branch_name)}
                        </td>
                        <td><span class="${stockClass(b.stock_quantity, b.reorder_point)}">${fmtNum(b.stock_quantity, 0)}</span></td>
                        <td><span class="price-cell ${priceClass}">${priceText}</span></td>
                        <td>${b.is_active == 1
                            ? '<span class="badge bg-success" style="font-size:10.5px;">Active</span>'
                            : '<span class="badge bg-secondary" style="font-size:10.5px;">Inactive</span>'}</td>
                    </tr>`;
            });
        }

        var html = `
        <div class="result-card">
            <div class="result-card-header">
                <div class="result-card-title">${escapeHtml(p.name)}</div>
                <span class="avail-sync-chip" title="Data was loaded from the server at this time; re-open or reload the page to refresh it.">
                    <i class="ri-refresh-line"></i> Last synced: ${lastSyncedAt}
                </span>
            </div>
            <table class="avail-table">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Stock</th>
                        <th>Sell Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>${branchRows}</tbody>
            </table>
        </div>`;

        wrap.html(html);
        $('#searchPromptState').hide();
        wrap.show();
    }


    // ── Typing: show live suggestions only, no result card yet ──────────
    searchInput.on('input', function () {
        var q = $(this).val().trim();

        clearBtn.toggleClass('show', q.length > 0);

        if (q.length === 0) {
            hideSuggestions();
            return;
        }

        if (q.length < 2) {
            hideSuggestions();
            return;
        }

        renderSuggestions(filterProducts(q), q);
    });

    // ── Clear (✕) button: wipe input and suggestions ─────────────────────
    clearBtn.on('click', function () {
        searchInput.val('').trigger('focus');
        clearBtn.removeClass('show');
        hideSuggestions();
    });

    // ── Click a suggestion → render its result in the main card, close modal ──
    suggestBox.on('click', '.avail-suggest-item', function () {
        var idx = $(this).data('index');
        var products = suggestBox.data('current') || [];
        var chosen = products[idx];
        if (!chosen) return;

        renderProductResult(chosen);
        hideSuggestions();
        searchInput.val(chosen.name);
        clearBtn.addClass('show');
        $('#searchModal').modal('hide');
    });

    // ── Keyboard navigation through the dropdown (optional convenience) ──
    searchInput.on('keydown', function (e) {
        var items = suggestBox.find('.avail-suggest-item');
        if (!items.length || !suggestBox.hasClass('show')) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            items.removeClass('active').eq(activeIndex).addClass('active');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            items.removeClass('active').eq(activeIndex).addClass('active');
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0) {
                items.eq(activeIndex).trigger('click');
            }
        } else if (e.key === 'Escape') {
            hideSuggestions();
        }
    });

    // ── Click outside the search box/dropdown closes the suggestion list ──
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.avail-search-wrap, .avail-suggest-box').length) {
            hideSuggestions();
        }
    });

    @endif

});
</script>
@endsection