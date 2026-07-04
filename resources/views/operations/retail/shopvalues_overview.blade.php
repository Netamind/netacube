@extends('operations.retail.dashboard')
@section('content')
@php
    $pref       = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();
    $categories = DB::connection('tenant')->table('categories')->orderBy('category')->get();

    $selectedCategory = null;
    if ($pref && $pref->category_id) {
        $selectedCategory = DB::connection('tenant')
            ->table('categories')
            ->where('id', $pref->category_id)
            ->first();
    }

    // Branches: sector = Retail always. Category filter only applied if one is selected.
    $branchesQuery = DB::connection('tenant')
        ->table('branches')
        ->where('sector', 'Retail')
        ->where('status', 'active');

    if ($selectedCategory) {
        $branchesQuery->where('category', (string) $selectedCategory->id);
    }

    $branches        = $branchesQuery->orderBy('name')->get();
    $branchValueRows = collect();
    $totalShopValue  = 0;
    $totalProducts   = 0;

    foreach ($branches as $branch) {
        $products = DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
            ->where('rbp.branch_id', $branch->id)
            ->select('rbp.selling_price', 'bp.selling_price as bp_sell', 'rbp.stock_quantity', 'rbp.is_active')
            ->get();

        $shopValue = $products->sum(function ($p) {
            $price = $p->selling_price !== null ? (float) $p->selling_price : (float) $p->bp_sell;
            return $price * (float) $p->stock_quantity;
        });

        $totalShopValue += $shopValue;
        $totalProducts  += $products->count();

        $lowStock  = $products->filter(fn($p) => (float) $p->stock_quantity > 0 && (float) $p->stock_quantity <= 5)->count();
        $zeroStock = $products->filter(fn($p) => (float) $p->stock_quantity <= 0)->count();

        $branchValueRows->push((object)[
            'id'         => $branch->id,
            'name'       => $branch->name,
            'shop_value' => $shopValue,
            'products'   => $products->count(),
            'active'     => $products->where('is_active', 1)->count(),
            'low_stock'  => $lowStock,
            'zero_stock' => $zeroStock,
        ]);
    }
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

/* ── Category select in header ──────────────────────────────────────────── */
#categorySelectHeader {
  border: none; background: transparent; color: #fff;
  font-size: 18px; font-weight: 600; cursor: pointer;
  padding: 0; outline: none; max-width: 340px;
}
#categorySelectHeader option { color: #1e293b; background: #fff; font-size: 14px; }

/* ── Tab navigation ─────────────────────────────────────────────────────── */
.tab-header-container {
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}
.tab-header-container .nav { justify-content: flex-start !important; }
.nav-pills .nav-link {
  border-radius: 0 !important; padding: .6rem 1.1rem;
  font-weight: 500; font-size: 13px; color: #495057;
  border-bottom: 3px solid transparent; transition: all .2s; white-space: nowrap;
}
.nav-pills .nav-link:hover { background: #e9ecef; color: #4B5EBD; }
.nav-pills .nav-link.active {
  background: transparent !important; color: #4B5EBD !important;
  border-bottom-color: #4B5EBD; font-weight: 600;
}
.nav-pills .nav-link i { font-size: 1rem; margin-right: .3rem; }

/* ── Metric strip ───────────────────────────────────────────────────────── */
.metric-strip {
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 10px; padding: 14px 1.5rem 12px;
  background: #f8f9fc; border-bottom: 1px solid #e4e7f0;
}
.metric-card {
  background: #fff; border: 0.5px solid #e4e7f0; border-radius: 8px; padding: 10px 14px;
}
.metric-card .mc-label { font-size:11px; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:3px; }
.metric-card .mc-value { font-size:21px; font-weight:700; color:#1e293b; line-height:1.1; }
.metric-card .mc-sub   { font-size:11px; color:#94a3b8; margin-top:2px; }

/* ── Value colours ──────────────────────────────────────────────────────── */
.val-green  { color:#059669; font-weight:700; }
.val-orange { color:#ea580c; font-weight:700; }
.val-red    { color:#dc2626; font-weight:700; }
.val-blue   { color:#4B5EBD; font-weight:700; }
.val-grey   { color:#94a3b8; font-weight:600; }

/* ── Progress bar ───────────────────────────────────────────────────────── */
.pct-bar-wrap  { display:flex; align-items:center; gap:8px; justify-content:center; }
.pct-bar-track { width:70px; height:6px; background:#e9ecef; border-radius:3px; overflow:hidden; flex-shrink:0; }
.pct-bar-fill  { height:100%; background:linear-gradient(to right,#4B5EBD,#576CC0); border-radius:3px; }
.pct-label     { font-size:12px; color:#64748b; font-weight:600; min-width:36px; text-align:right; }

/* ── Action icon button ─────────────────────────────────────────────────── */
.mv-btn {
  font-size:14px; padding:4px 8px;
  background: linear-gradient(to right,#4B5EBD,#576CC0);
  color:#fff; border-radius:5px; border:none;
  text-decoration:none; display:inline-flex; align-items:center; justify-content:center;
  transition: opacity .15s;
}
.mv-btn:hover { opacity:.85; color:#fff; }

/* ── Table column alignment ─────────────────────────────────────────────── */
#branchValueTable thead th { text-align: center !important; vertical-align: middle !important; }
#branchValueTable thead th:first-child,
.dataTables_scrollHead table thead th:first-child,
.dataTables_scrollHeadInner table thead th:first-child { text-align: left !important; }
#branchValueTable tbody td { text-align: center !important; vertical-align: middle !important; }
#branchValueTable tbody td:first-child { text-align: left !important; }

/* ── tfoot matches thead — beats DataTables scrollX clone + striping ────── */
#branchValueTable tfoot tr td,
table.dataTable tfoot tr td,
.dataTables_scrollFoot table tfoot tr td,
.dataTables_scrollFoot table tbody tr td {
  background-color: #e2e2e9 !important;
  font-weight: 700;
  text-align: center;
}
#branchValueTable tfoot tr td:first-child,
.dataTables_scrollFoot table tfoot tr td:first-child {
  text-align: left !important;
}

/* ── Download modal ─────────────────────────────────────────────────────── */
.mh-blue { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }
.download-section { margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:1px solid #eee; }
.download-section:last-child { border-bottom:none; margin-bottom:0; padding-bottom:0; }
.download-section h6 { color:#4B5EBD; font-weight:600; margin-bottom:.75rem; }
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none;">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- ── Card header ─────────────────────────────────────────────────────── --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <i class="ri-store-2-line"></i>&nbsp;
      <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
            id="headerCategoryForm" style="margin:0;display:inline;">
        @csrf
        <input type="hidden" name="user_id" value="{{ Auth::id() }}">
        <select name="category_id" id="categorySelectHeader"
                onchange="document.getElementById('headerCategoryForm').submit()">
          <option value="" hidden>{{ $selectedCategory ? $selectedCategory->category : '— Select Category —' }}</option>
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
      <a href="#" class="btn btn-light text-primary fs-16" id="downloadModalBtn" title="Download">
        <i class="ri-download-line"></i>
      </a>
      <a href="#" class="btn btn-light text-primary fs-16" id="infoBtn" title="About Shop Values">
        <i class="ri-information-line"></i>
      </a>
    </div>
  </div>

  {{-- ── Tabs ────────────────────────────────────────────────────────────── --}}
  <div class="tab-header-container">
    <ul class="nav nav-pills mb-0">
      <li class="nav-item">
        <a href="{{ route('retail.operations.shopvalues.overview') }}" class="nav-link active">
          <i class="ri-store-2-line"></i> Branch Overview
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('retail.operations.shopvalues.movement') }}" class="nav-link">
          <i class="ri-line-chart-line"></i> Stock Value Movement
        </a>
      </li>
    </ul>
  </div>

  {{-- ── Metric strip ─────────────────────────────────────────────────────── --}}
  @if($selectedCategory)
  <div class="metric-strip">
    <div class="metric-card">
      <div class="mc-label">Branches</div>
      <div class="mc-value" style="color:#4B5EBD;">{{ $branches->count() }}</div>
      <div class="mc-sub">Active in category</div>
    </div>
    <div class="metric-card">
      <div class="mc-label">Total Shop Value</div>
      <div class="mc-value" style="color:#059669;font-size:17px;">MWK {{ number_format($totalShopValue, 0) }}</div>
      <div class="mc-sub">Sell price × stock qty</div>
    </div>
    <div class="metric-card">
      <div class="mc-label">Avg per Branch</div>
      <div class="mc-value" style="color:#0ea5e9;font-size:17px;">
        MWK {{ $branches->count() > 0 ? number_format($totalShopValue / $branches->count(), 0) : '0' }}
      </div>
      <div class="mc-sub">Category average</div>
    </div>
    <div class="metric-card">
      <div class="mc-label">Total Products</div>
      <div class="mc-value" style="color:#64748b;">{{ number_format($totalProducts) }}</div>
      <div class="mc-sub">Across all branches</div>
    </div>
  </div>
  @endif

  {{-- ── Card body ───────────────────────────────────────────────────────── --}}
  <div class="card-body">

    <table id="branchValueTable"
           class="table table-sm table-striped row-border order-column w-100 mt-3">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Branch Name</th>
          <th>Products</th>
          <th>Active</th>
          <th>Low Stock</th>
          <th>Zero Stock</th>
          <th>Shop Value (MWK)</th>
          <th>% of Total</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($branchValueRows as $bvr)
          @php
            $pct = $totalShopValue > 0
                ? round(($bvr->shop_value / $totalShopValue) * 100, 1)
                : 0;
          @endphp
          <tr>
            <td style="text-align:left !important;">{{ $bvr->name }}</td>
            <td>{{ $bvr->products }}</td>
            <td><span class="val-green">{{ $bvr->active }}</span></td>
            <td>
              @if($bvr->low_stock > 0)
                <span class="val-orange">{{ $bvr->low_stock }}</span>
              @else
                <span class="val-grey">0</span>
              @endif
            </td>
            <td>
              @if($bvr->zero_stock > 0)
                <span class="val-red">{{ $bvr->zero_stock }}</span>
              @else
                <span class="val-grey">0</span>
              @endif
            </td>
            <td>
              <span class="{{ $bvr->shop_value > 0 ? 'val-blue' : 'val-grey' }}">
                {{ number_format($bvr->shop_value, 0) }}
              </span>
            </td>
            <td>
              <div class="pct-bar-wrap">
                <div class="pct-bar-track">
                  <div class="pct-bar-fill" style="width:{{ $pct }}%"></div>
                </div>
                <span class="pct-label">{{ $pct }}%</span>
              </div>
            </td>
            <td>
              <a href="{{ route('retail.operations.shopvalues.movement') }}?branch_id={{ $bvr->id }}"
                 class="mv-btn" title="View Movement">
                <i class="ri-line-chart-line"></i>
              </a>
            </td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td style="text-align:left !important;"><i class="ri-bar-chart-2-line me-1"></i>Totals</td>
          <td>{{ $branchValueRows->sum('products') }}</td>
          <td><span class="val-green">{{ $branchValueRows->sum('active') }}</span></td>
          <td><span class="val-orange">{{ $branchValueRows->sum('low_stock') }}</span></td>
          <td><span class="val-red">{{ $branchValueRows->sum('zero_stock') }}</span></td>
          <td><span class="val-blue">{{ number_format($totalShopValue, 0) }}</span></td>
          <td>100%</td>
          <td></td>
        </tr>
      </tfoot>
    </table>

  </div>
</div>
</div></div></div>

{{-- ══ Download Modal ════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="downloadModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-download-line"></i> Download Branch Overview</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <div class="download-section">
        <h6>Branch Shop Values</h6>
        <div class="dt-buttons" id="overviewButtons"></div>
      </div>
    </div>
  </div></div>
</div>

{{-- ══ Info Modal ════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Shop Values</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:18px 20px;">
      <p><strong>Branch Overview</strong> shows the total retail value of stock held at each branch in the selected category.</p>
      <hr class="my-3">
      <table style="width:100%;font-size:13px;border-collapse:collapse;">
        <tbody>
          <tr>
            <td style="padding:8px 12px;font-weight:700;color:#475569;width:160px;border-bottom:1px solid #f1f5f9;">Shop Value</td>
            <td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">
              The sum of (selling price × stock quantity) for every product at the branch — i.e. the total retail value of all stock currently held. The row total is the summation across all branches in the selected category.
            </td>
          </tr>
          <tr>
            <td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9;">Low Stock</td>
            <td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">Products with stock quantity between 1 and 5 (inclusive). Shown in <span style="color:#ea580c;font-weight:700;">orange</span>.</td>
          </tr>
          <tr>
            <td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9;">Zero Stock</td>
            <td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">Products with no stock remaining. Shown in <span style="color:#dc2626;font-weight:700;">red</span>.</td>
          </tr>
          <tr>
            <td style="padding:8px 12px;font-weight:700;color:#475569;">Movement tab</td>
            <td style="padding:8px 12px;">Click the chart icon <i class="ri-line-chart-line"></i> in the Action column to see a day-by-day value breakdown for that branch.</td>
          </tr>
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

    var table = $('#branchValueTable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthMenu: [[25, 50, 100, -1],[25, 50, 100,'All']],
        order: [[5,'desc']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start dt-head-left' }  // ← fix: added dt-head-left
        ],
        buttons: [
            { extend:'excelHtml5', title:'Branch Shop Values — {{ addslashes($selectedCategory->category ?? "") }}', exportOptions:{ columns:':not(:last-child)' } },
            { extend:'csvHtml5',   title:'Branch Shop Values — {{ addslashes($selectedCategory->category ?? "") }}', exportOptions:{ columns:':not(:last-child)' } },
            { extend:'pdfHtml5',   title:'Branch Shop Values — {{ addslashes($selectedCategory->category ?? "") }}', exportOptions:{ columns:':not(:last-child)' },
              customize: function(doc){ doc.content[1].table.widths = Array(doc.content[1].table.body[0].length+1).join('*').split(''); } }
        ]
    });
    table.buttons().container().appendTo('#overviewButtons');

    $('#downloadModalBtn').on('click', function(e){ e.preventDefault(); $('#downloadModal').modal('show'); });
    $('#infoBtn').on('click',          function(e){ e.preventDefault(); $('#infoModal').modal('show'); });
});
</script>
@endsection