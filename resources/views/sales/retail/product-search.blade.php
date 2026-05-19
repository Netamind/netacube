@extends('sales.retail.dashboard')
@section('content')

@php
    // ── Resolve the logged-in user's branch & category ─────────────────────
    $myBranchId = Auth::user()->branch;
    $myBranch   = $myBranchId ? DB::connection('tenant')->table('branches')->find($myBranchId) : null;

    $categoryId   = $myBranch->category ?? null;
    $categoryName = null;

    $categoryBranches = collect();
    $availabilityData = collect(); // payload sent to the front end, built once per page load
    $lastSyncedAt     = now(); // moment this payload was generated — shown to the user as "last sync"

    if ($categoryId) {
        $categoryName = optional(
            DB::connection('tenant')->table('categories')->where('id', $categoryId)->first()
        )->category;

        $categoryBranches = DB::connection('tenant')
            ->table('branches')
            ->where('category', $categoryId)
            ->orderBy('name')
            ->get();

        $branchIds = $categoryBranches->pluck('id');

        // ── Pull every product in this category's branches once, with all
        //    branch-level stock rows nested in, so the front end never needs
        //    to call the server again — searching/filtering happens entirely
        //    in JS against this single payload. ─────────────────────────────
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
                // in this category — keeps the payload relevant and small.
                return $branchRowsByProduct->has($bp->id);
            })
            ->map(function ($bp) use ($branchRowsByProduct) {
                return [
                    'id'       => $bp->id,
                    'name'     => $bp->name,
                    'code'     => $bp->code,
                    'unit'     => $bp->unit,
                    'supplier' => $bp->supplier,
                    'branches' => $branchRowsByProduct->get($bp->id, collect())->values(),
                ];
            })
            ->values();
    }
@endphp

<style>
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

.stock-ok   { color: #16a34a; font-weight: 700; }
.stock-low  { color: #d97706; font-weight: 700; }
.stock-zero { color: #dc2626; font-weight: 700; }
.price-cell { font-size:12px; font-weight:600; color:#1e293b; }

.no-branch-wrap { padding: 48px 20px; text-align: center; color: #94a3b8; }
.no-branch-wrap i { font-size: 52px; display: block; margin-bottom: 12px; color: #c8d0ed; }
.no-branch-wrap h5 { color: #64748b; font-weight: 600; }

/* ── Modal header helpers ───────────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── Search bar ──────────────────────────────────────────────────────────── */
.avail-search-wrap { position: relative; max-width: 640px; width: 100%; flex: 1 1 320px; }
.avail-search-wrap input {
  box-sizing: border-box;
  width: 100%;
  font-size: 15px;
  line-height: 1.4;
  padding: 0.75em 2.6em 0.75em 2.6em;   /* right padding mirrors left, to clear the addon button */
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
.avail-search-wrap i.search-icon {
  position: absolute; left: 0.9em; top: 50%; transform: translateY(-50%);
  color: #9ca3af; font-size: 1.2em; pointer-events: none;
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

/* ── "Last synced" chip ──────────────────────────────────────────────────── */
.avail-sync-chip {
  display: inline-flex; align-items: center; gap: 0.4em;
  font-size: 12px; color: #94a3b8; white-space: nowrap;
}
.avail-sync-chip i { font-size: 1.05em; color: #b6bdcc; }
.avail-search-row { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.avail-search-meta { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.avail-meta-chip {
  display: inline-flex; align-items: center; gap: 0.4em;
  background: #fff; border: 1.5px solid #dde1f0; color: #4B5EBD;
  font-size: 15px; font-weight: 600;
  padding: 0.75em 1em; line-height: 1.4; border-radius: 8px; white-space: nowrap;
}

/* ── Search panel (distinct grey background from results) ──────────────── */
.avail-search-panel {
  background: #f4f5f9; border: 1px solid #eceefa; border-radius: 10px;
  padding: 16px; margin-top: 14px;
  position: relative; /* anchors the suggestion dropdown below the input */
}

/* ── Suggestion dropdown (live, as the user types) ──────────────────────── */
.avail-suggest-box {
  position: absolute;
  z-index: 50;
  top: calc(100% - 8px);
  right: 16px;
  width: min(640px, calc(100% - 32px));
  background: #fff;
  border: 1.5px solid #dde1f0;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(16,24,40,0.10);
  max-height: 320px;
  overflow-y: auto;
  display: none;
}
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
.avail-suggest-sub  { font-size: 11px; color: #94a3b8; margin-top: 1px; }
.avail-suggest-badge {
  font-size: 10.5px; font-weight: 600; color: #4B5EBD; background: #eef2ff;
  padding: 2px 8px; border-radius: 20px; white-space: nowrap; flex-shrink: 0;
}
.avail-suggest-empty { padding: 16px; text-align: center; color: #94a3b8; font-size: 12.5px; }

/* ── Result cards ────────────────────────────────────────────────────────── */
#resultsWrap { margin-top: 20px; }
.result-card {
  border: 1px solid #e9ecef; border-radius: 10px; margin-bottom: 14px; overflow: hidden;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
.result-card-header {
  background: #f8f9fb; padding: 10px 16px; display: flex;
  align-items: center; justify-content: space-between; border-bottom: 1px solid #eef0f5;
}
.result-card-title { font-size: 14px; font-weight: 700; color: #1e293b; }
.result-card-sub    { font-size: 11px; color: #6c757d; margin-top: 1px; }
.result-card-badge {
  font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px;
  background: #eef2ff; color: #4B5EBD;
}
.avail-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.avail-table th {
  text-align: left; padding: 8px 16px; background: #fcfcfd; color: #94a3b8;
  font-weight: 600; text-transform: uppercase; font-size: 10.5px; letter-spacing: .4px;
  border-bottom: 1px solid #eef0f5;
}
.avail-table td { padding: 8px 16px; border-bottom: 1px solid #f5f6f9; vertical-align: middle; }
.avail-table tr:last-child td { border-bottom: none; }
.avail-table tr.is-my-branch td { background: #f5f7ff; }
.branch-name-cell { font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 6px; }
.branch-name-pill {
  display: inline-flex; align-items: center;
  font-size: 11.5px; font-weight: 700; color: #fff; background: #4B5EBD;
  padding: 3px 11px; border-radius: 20px; letter-spacing: .2px;
}
.no-stock-row td { color: #cbd5e1; font-style: italic; }

.search-empty, .search-loading {
  text-align: center; padding: 36px 10px; color: #94a3b8; font-size: 13px;
}
.search-empty i, .search-loading i { font-size: 38px; display: block; margin-bottom: 10px; color: #d6daf0; }
.search-loading i { animation: spin 0.8s linear infinite; }
@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      Product Availability
    </h4>

    <div class="d-flex align-items-center" style="gap:4px;">
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="About Availability Search">
        <i class="ri-information-line"></i>
      </a>
    </div>
  </div>

  <div class="card-body">

    @if(!$myBranch || !$categoryId)
      <div class="no-branch-wrap">
        <i class="ri-store-line"></i>
        <h5>No Branch / Category Found</h5>
        <p style="font-size:13px;">Your account isn't assigned to a branch with a category, so availability search isn't available.</p>
      </div>
    @else

      <div class="avail-search-panel">
        <div class="avail-search-row">
          <div class="avail-search-meta">
            <span class="avail-meta-chip">
              <i class="ri-price-tag-3-line"></i> {{ $categoryName ?? 'Category' }}
            </span>
            <span class="avail-meta-chip">
              <i class="ri-git-branch-line"></i> {{ $categoryBranches->count() }} branch(es)
            </span>
            <span class="avail-sync-chip" id="lastSyncChip" title="Data was loaded from the server at this time; re-open or reload the page to refresh it.">
              <i class="ri-refresh-line"></i> Last synced: <span id="lastSyncTime">{{ $lastSyncedAt->format('H:i:s') }}</span>
            </span>
          </div>
          <div class="avail-search-wrap">
            <i class="ri-search-line search-icon"></i>
            <input type="text" id="availSearchInput" placeholder="Search product name or code…" autocomplete="off" />
            <button type="button" class="clear-btn" id="availClearBtn" title="Clear search">
              <i class="ri-close-line"></i>
            </button>
            <div id="availSuggestBox" class="avail-suggest-box"></div>
          </div>
        </div>
      </div>

      <div id="resultsWrap">
        <div class="search-empty">
          <i class="ri-search-eye-line"></i>
          Start typing, then click a product to view its branch availability.
        </div>
      </div>

    @endif

  </div>
</div>
</div></div></div>

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
      Search for a product to see which branches in your category currently stock it, along with their stock level, selling price, and status.</p>
      <p class="mb-2" style="font-size:12.5px;color:#6c757d;">
        Results are scoped to branches in the <strong>{{ $categoryName ?? '—' }}</strong> category
        (your branch: <strong>{{ $myBranch->name ?? '—' }}</strong>).
      </p>
      <hr class="my-3">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <tbody>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;width:140px;border-bottom:1px solid #f1f5f9">Category Scope</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Only branches sharing the same category as your branch are shown.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Stock Qty</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9"><span style="color:#dc2626;font-weight:600">Red = zero</span>, <span style="color:#d97706;font-weight:600">amber = at/below reorder point</span>, <span style="color:#16a34a;font-weight:600">green = healthy</span>.</td></tr>
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

    @if($myBranch && $categoryId)

    var myBranchId = {{ $myBranchId }};

    // ── Entire dataset for this category, fetched once on page load.
    //    All searching/filtering below happens against this in-memory
    //    array — no further requests to the server. ─────────────────────
    var allProducts = @json($availabilityData);

    var suggestBox  = $('#availSuggestBox');
    var searchInput = $('#availSearchInput');
    var clearBtn    = $('#availClearBtn');
    var activeIndex = -1; // currently keyboard-highlighted suggestion

    function showEmptyState() {
        $('#resultsWrap').html(`
            <div class="search-empty">
                <i class="ri-search-eye-line"></i>
                Start typing, then click a product to view its branch availability.
            </div>`);
    }

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

    // ── Client-side filter: matches name or code, case-insensitive ──────
    function filterProducts(q) {
        q = q.toLowerCase();
        return allProducts.filter(function (p) {
            var name = (p.name || '').toLowerCase();
            var code = (p.code || '').toLowerCase();
            return name.indexOf(q) !== -1 || code.indexOf(q) !== -1;
        }).slice(0, 30); // cap suggestion list length
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
                        <div class="avail-suggest-sub">${escapeHtml([p.code ? 'Code: ' + p.code : '', p.unit].filter(Boolean).join(' · '))}</div>
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

    // ── Render the full branch-availability card for ONE selected product ──
    function renderProductResult(p) {
        var wrap = $('#resultsWrap');
        var branchRows = '';

        if (!p.branches || !p.branches.length) {
            branchRows = `<tr class="no-stock-row"><td colspan="4">Not stocked at any branch in this category.</td></tr>`;
        } else {
            p.branches.forEach(function (b) {
                var isMine = parseInt(b.branch_id) === myBranchId;
                branchRows += `
                    <tr class="${isMine ? 'is-my-branch' : ''}">
                        <td class="branch-name-cell">
                            ${isMine ? '<span class="branch-name-pill">' + escapeHtml(b.branch_name) + '</span>' : escapeHtml(b.branch_name)}
                        </td>
                        <td><span class="${stockClass(b.stock_quantity, b.reorder_point)}">${fmtNum(b.stock_quantity, 0)}</span></td>
                        <td><span class="price-cell">${fmtNum(b.selling_price)}</span></td>
                        <td>${b.is_active == 1
                            ? '<span class="badge bg-success" style="font-size:10.5px;">Active</span>'
                            : '<span class="badge bg-secondary" style="font-size:10.5px;">Inactive</span>'}</td>
                    </tr>`;
            });
        }

        var html = `
        <div class="result-card">
            <div class="result-card-header">
                <div>
                    <div class="result-card-title">${escapeHtml(p.name)}</div>
                    <div class="result-card-sub">${escapeHtml([p.code ? 'Code: ' + p.code : '', p.unit, p.supplier].filter(Boolean).join(' · '))}</div>
                </div>
                <span class="result-card-badge">${p.branches ? p.branches.length : 0} branch(es) stocking</span>
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
    }

    // ── Typing: show live suggestions only, no result card yet ──────────
    searchInput.on('input', function () {
        var q = $(this).val().trim();

        clearBtn.toggleClass('show', q.length > 0);

        if (q.length === 0) {
            hideSuggestions();
            showEmptyState();
            return;
        }

        if (q.length < 2) {
            hideSuggestions();
            return;
        }

        renderSuggestions(filterProducts(q), q);
    });

    // ── Clear (✕) button: wipe input, suggestions, and result card ──────
    clearBtn.on('click', function () {
        searchInput.val('').trigger('focus');
        clearBtn.removeClass('show');
        hideSuggestions();
        showEmptyState();
    });

    // ── Click a suggestion → show its result card, close the dropdown ───
    suggestBox.on('click', '.avail-suggest-item', function () {
        var idx = $(this).data('index');
        var products = suggestBox.data('current') || [];
        var chosen = products[idx];
        if (!chosen) return;

        renderProductResult(chosen);
        hideSuggestions();
        searchInput.val(chosen.name);
        clearBtn.addClass('show');
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
        if (!$(e.target).closest('.avail-search-wrap').length) {
            hideSuggestions();
        }
    });

    @endif

});
</script>
@endsection