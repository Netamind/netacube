@extends('operations.retail.dashboard')
@section('content')
@php
    $branches = DB::connection('tenant')->table('branches')->orderBy('name')->get();
    $pref     = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();

    $branchProducts = collect();
    $selectedBranch = null;
    $shopValue      = 0;
    $branchCategory = null;

    if ($pref && $pref->branch_id) {
        $selectedBranch = DB::connection('tenant')->table('branches')->find($pref->branch_id);

        if ($selectedBranch) {
            $branchCategory = DB::connection('tenant')
                ->table('categories')
                ->where('id', $selectedBranch->category)
                ->first();
        }

        $branchProducts = DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
            ->where('rbp.branch_id', $pref->branch_id)
            ->select('rbp.*', 'bp.name', 'bp.code', 'bp.unit', 'bp.supplier',
                     'bp.selling_price as bp_sell', 'bp.cost_price as bp_cost')
            ->get();

        foreach ($branchProducts as $bp) {
            $shopValue += (float)$bp->bp_sell * (float)$bp->stock_quantity;
        }
    }

    $baseProducts = collect();
    if ($selectedBranch) {
        $alreadyIn    = $branchProducts->pluck('base_product_id')->toArray();
        $baseProducts = DB::connection('tenant')
            ->table('retail_base_products')
            ->whereNotIn('id', $alreadyIn)
            ->where('is_product', 1)
            ->get();
    }

    $supplierRows = collect();
    if ($selectedBranch && $selectedBranch->category) {
        $supplierRows = DB::connection('tenant')->table('suppliers')
            ->where('status', 'active')
            ->where('category', $selectedBranch->category)
            ->orderBy('name')
            ->get(['id', 'name', 'category']);
    }

    $maintableTitle  = 'Branch Products — ' . ($selectedBranch->name ?? 'All');
    $activeCount     = $branchProducts->where('is_active', 1)->count();
    $lowStockCount   = $branchProducts->filter(fn($p) => (float)$p->stock_quantity <= (float)$p->reorder_point && (float)$p->stock_quantity > 0)->count();
    $zeroCount       = $branchProducts->filter(fn($p) => (float)$p->stock_quantity <= 0)->count();
@endphp

<style>
/* ── Reset & Base ─────────────────────────────────────────────────── */
.dt-buttons .btn { background:transparent !important; background-image:none !important; box-shadow:none !important; border-color:#5bc0de; color:#5bc0de; }
.dt-buttons .btn:hover { background:#5bc0de !important; color:#fff; }

/* ── Card chrome ─────────────────────────────────────────────────── */
.card-header { padding:0.5rem 1.5rem !important; background:linear-gradient(to right,#4B5EBD,#576CC0); color:#fff; border-radius:10px 10px 0 0 !important; flex-wrap:wrap; gap:8px; }
.card-body   { padding:0 1.5rem 1.5rem 1.5rem !important; }
.card        { border:none; box-shadow:0 4px 8px rgba(0,0,0,0.1); border-radius:10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header .btn-light { height:28px; padding:0 10px; display:flex; align-items:center; justify-content:center; line-height:1; }
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

/* ── Select all checkbox ────────────────────────────────────────── */
.header-select-all { width:16px; height:16px; cursor:pointer; accent-color:#4B5EBD; background:#d1d5db; border-radius:3px; margin-right:10px; flex-shrink:0; vertical-align:middle; }
.header-title-block { display:flex; flex-direction:column; line-height:1.25; min-width:0; }
.card-header-actions { display:flex; align-items:center; gap:4px; flex-wrap:wrap; justify-content:flex-end; }

@media (max-width:576px) {
  .card-header { padding:10px 14px !important; }
  .header-title-block { max-width:55vw; }
  #branchSelectHeader { font-size:15px; max-width:100%; }
  .card-header-actions { width:100%; justify-content:flex-start; }
  .card-header .btn-light { height:32px; width:32px; padding:0; font-size:15px; }
}

/* ── Remove spinners ─────────────────────────────────────────────── */
input[type=number]::-webkit-outer-spin-button, input[type=number]::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
input[type=number] { -moz-appearance:textfield; appearance:textfield; }

/* ── Bulk button ─────────────────────────────────────────────────── */
#bulkActionsHeaderBtn { position:relative; opacity:.5; pointer-events:none; cursor:not-allowed; transition:opacity .15s; }
#bulkActionsHeaderBtn.enabled { opacity:1; pointer-events:auto; cursor:pointer; }
#bulkActionsHeaderBtn .bah-count { position:absolute; top:-5px; right:-5px; background:#dc2626; color:#fff; border-radius:50%; font-size:10px; font-weight:700; min-width:16px; height:16px; line-height:16px; text-align:center; padding:0 3px; display:none; box-shadow:0 0 0 1.5px #fff; }
#bulkActionsHeaderBtn .bah-count.show { display:block; }

/* ── Table alignment ─────────────────────────────────────────────── */
#maintable thead th, table.dataTable thead th { text-align:center !important; vertical-align:middle !important; }
#maintable thead th:first-child, table.dataTable thead th:first-child { text-align:left !important; }
#maintable tbody td, table.dataTable tbody td { text-align:center !important; vertical-align:middle !important; }
#maintable tbody td:first-child, table.dataTable tbody td:first-child { text-align:left !important; }

/* ── Stock & price badges ────────────────────────────────────────── */
.stock-ok   { color:#16a34a; font-weight:700; }
.stock-low  { color:#d97706; font-weight:700; }
.stock-zero { color:#dc2626; font-weight:700; }
.price-branch { color:#1d4ed8; font-weight:700; font-size:12px; }
.price-base   { color:#059669; font-weight:600; font-size:12px; }

/* ── No branch state ─────────────────────────────────────────────── */
.no-branch-wrap { padding:48px 20px; text-align:center; color:#94a3b8; }
.no-branch-wrap i { font-size:52px; display:block; margin-bottom:12px; color:#c8d0ed; }
.no-branch-wrap h5 { color:#64748b; font-weight:600; }

/* ── Modal headers ───────────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-danger { background:linear-gradient(135deg,#c0392b,#e74c3c); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── Branch select ───────────────────────────────────────────────── */
#branchSelectHeader { border:none; background:transparent; color:#fff; font-size:18px; font-weight:600; cursor:pointer; padding:0; outline:none; max-width:300px; }
#branchSelectHeader option { color:#1e293b; background:#fff; font-size:14px; }

/* ── Overview modal tabs ─────────────────────────────────────────── */
.overview-tab-btn { flex:1; padding:8px 0; font-size:12px; font-weight:600; border:none; background:transparent; color:#94a3b8; border-bottom:2px solid transparent; cursor:pointer; transition:color .15s; }
.overview-tab-btn.active { color:#4B5EBD; border-bottom-color:#4B5EBD; }
.sv-metric { background:#eef0f7; border-radius:8px; padding:10px 12px; text-align:center; }
.sv-metric .sv-label { font-size:11px; color:#6c757d; margin-bottom:4px; }
.sv-metric .sv-value { font-size:20px; font-weight:600; }
.pricing-swatch { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:8px; margin-bottom:8px; width:100%; }
.pricing-swatch-br { background:#eff6ff; border:1px solid #bfdbfe; }
.pricing-swatch-bp { background:#ecfdf5; border:1px solid #a7f3d0; }
.pricing-swatch .swatch-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
.swatch-dot-br { background:#1d4ed8; }
.swatch-dot-bp { background:#059669; }
.pricing-swatch .swatch-label { font-size:13px; font-weight:600; }
.pricing-swatch .swatch-desc  { font-size:12px; color:#64748b; margin-top:1px; }

/* ── Confirm modal ───────────────────────────────────────────────── */
.confirm-icon-wrap { width:56px; height:56px; border-radius:50%; background:#fffbeb; display:flex; align-items:center; justify-content:56px; margin:0 auto 14px; }
.confirm-icon-wrap { width:56px; height:56px; border-radius:50%; background:#fffbeb; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
.confirm-icon-wrap i { font-size:28px; color:#d97706; }

/* ════════════════════════════════════════════════════════════════════
   SEARCH RESULT LIST
   ════════════════════════════════════════════════════════════════════ */
.search-result-list { max-height:420px; overflow-y:auto; border:1px solid #e2e6f0; border-radius:10px; background:#fff; display:none; box-shadow:0 4px 18px rgba(0,0,0,0.10); margin-top:6px; }
.search-result-list::-webkit-scrollbar { width:6px; }
.search-result-list::-webkit-scrollbar-thumb { background:#c8d0ed; border-radius:6px; }

.sri-item { border-bottom:1px solid #f1f5f9; transition:background .1s; background:#fff; }
.sri-item:nth-child(even) { background:#fafafa; }
.sri-item:last-child { border-bottom:none; }

.sri-main { display:flex; align-items:center; gap:10px; padding:10px 14px 6px; }
.sri-name-wrap { flex:1; min-width:0; }
.sri-name  { font-size:13px; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sri-code  { font-size:11px; color:#94a3b8; font-weight:400; }

.sri-meta-row { display:flex; align-items:center; gap:10px; padding:0 14px 10px; flex-wrap:wrap; }
.sri-stock-pill { font-size:11px; font-weight:700; padding:3px 9px; border-radius:10px; white-space:nowrap; background:transparent; color:#94a3b8; }
.sri-stock-ok   { color:#94a3b8; }
.sri-stock-low  { color:#94a3b8; }
.sri-stock-zero { color:#94a3b8; }

.sri-price-tag  { font-size:12px; font-weight:600; padding:3px 9px; border-radius:10px; white-space:nowrap; background:transparent; color:#94a3b8; }
.sri-price-branch { background:transparent; color:#94a3b8; }
.sri-price-base   { background:transparent; color:#94a3b8; }

.sri-override-wrap { display:flex; align-items:center; gap:6px; margin-left:auto; }
.sri-override-label { font-size:11px; color:#94a3b8; }
.sri-override-check { width:14px; height:14px; accent-color:#94a3b8; cursor:pointer; }
.sri-price-override-input {
    width:100px; border:1px solid #e2e6f0; border-radius:6px; height:28px;
    font-size:12px; font-weight:600; padding:0 8px; color:#475569;
    background:#fafafa; outline:none; text-align:center; display:none;
}
.sri-price-override-input:focus { outline:none; box-shadow:none; border-color:#e2e6f0; }

.sri-controls { display:flex; align-items:center; gap:8px; padding:0 14px 12px; flex-wrap:wrap; }
.sri-qty-wrap { display:flex; align-items:center; gap:6px; }
.sri-qty-label { font-size:11px; color:#94a3b8; font-weight:600; white-space:nowrap; }
.sri-qty-input {
    width:120px; border:1px solid #e2e6f0; border-radius:6px; height:30px;
    font-size:13px; font-weight:600; padding:0 10px; color:#475569;
    background:#fff; outline:none; text-align:center;
}
.sri-qty-input:focus { outline:none; box-shadow:none; border-color:#e2e6f0; }
.sri-qty-input:disabled { background:#f0f0f0; color:#aaa; }

.sri-add-btn {
    height:30px; padding:0 16px; font-size:12px; font-weight:700;
    border:none; border-radius:6px; cursor:pointer;
    background:#e9ecef; color:#60a5fa;
    display:flex; align-items:center; gap:4px;
    box-shadow:none; transition:background .15s; white-space:nowrap; flex-shrink:0;
}
.sri-add-btn:hover:not(:disabled) { background:#dee2e6; }
.sri-add-btn:disabled { background:#eef0f2 !important; color:#b0b7c3 !important; cursor:default; }
.sri-added-msg { font-size:11px; font-weight:600; color:#94a3b8; display:none; align-items:center; gap:4px; flex-shrink:0; }
.sri-already-badge { font-size:10px; background:#f1f3f9; color:#94a3b8; padding:2px 8px; border-radius:10px; font-weight:600; flex-shrink:0; }
.sri-na { color:#cbd5e1; font-weight:400; }

/* ════════════════════════════════════════════════════════════════════
   EDIT & ADD MODAL TABS — text color + underline only, same bg
   ════════════════════════════════════════════════════════════════════ */
.edit-modal-tabs { display:flex; background:#f1f3f9; border-bottom:2px solid #dde1f0; margin:0; list-style:none; padding:0 18px; gap:2px; }
.edit-modal-tab-btn {
    position:relative; display:flex; align-items:center; gap:6px;
    font-size:12px; font-weight:500; color:#94a3b8;
    padding:10px 16px; border:none; background:#f1f3f9; cursor:pointer;
    border-bottom:3px solid transparent; margin-bottom:-2px;
    transition:color .15s;
    text-decoration:none; white-space:nowrap;
}
.edit-modal-tab-btn:hover:not(.em-active) { color:#4B5EBD; }
.edit-modal-tab-btn.em-active {
    color:#4B5EBD;
    font-weight:700;
    background:#f1f3f9;
    border-bottom:3px solid #4B5EBD;
}
.edit-tab-pane { display:none; }
.edit-tab-pane.em-show { display:block; }

/* Add modal nav tabs — same colour-only active style */
#addProductModal .nav-link { font-size:12px; color:#94a3b8; background:transparent !important; border:none !important; border-bottom:3px solid transparent !important; padding:9px 14px; font-weight:500; transition:color .15s; }
#addProductModal .nav-link.active { color:#4B5EBD !important; font-weight:700; border-bottom-color:#4B5EBD !important; background:transparent !important; }
#addProductModal .nav-link:hover:not(.active) { color:#4B5EBD; }
#addProductModal .nav-tabs { background:#f1f3f9; border-bottom:2px solid #dde1f0 !important; padding:0 4px; gap:2px; }

/* ── Read-only field ─────────────────────────────────────────────── */
.edit-ro { background:#f1f3f9 !important; color:#64748b !important; border-color:#e2e6f0 !important; cursor:default !important; font-weight:600; }

/* ════════════════════════════════════════════════════════════════════
   PRICE SOURCE CARDS — silver bg, green=base, blue=branch, compact
   ════════════════════════════════════════════════════════════════════ */
.price-source-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }

.psc {
    border:1.5px solid #e2e6f0;
    border-radius:8px;
    padding:9px 12px;
    cursor:pointer;
    transition:border-color .15s, background .15s;
    user-select:none;
    background:#f4f5f7;
}
.psc:hover:not(.psc-active-base):not(.psc-active-branch) { border-color:#c8d0ed; }

/* Active states — border colour only, keep silver bg */
.psc-active-base   { border-color:#059669 !important; background:#f4f5f7; }
.psc-active-branch { border-color:#1d4ed8 !important; background:#f4f5f7; }

.psc-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:5px; flex-shrink:0; }
.psc-dot-base   { background:#059669; }
.psc-dot-branch { background:#1d4ed8; opacity:.35; }
.psc-active-branch .psc-dot-branch { opacity:1; }

.psc-label { font-size:11px; font-weight:700; display:flex; align-items:center; margin-bottom:3px; }
.psc-label-base   { color:#059669; }
.psc-label-branch { color:#9ca3af; }
.psc-active-branch .psc-label-branch { color:#1d4ed8; }

.psc-val   { font-size:14px; font-weight:700; color:#9ca3af; }
.psc-active-base   .psc-val-base   { color:#059669; }
.psc-active-branch .psc-val-branch { color:#1d4ed8; }

.psc-sub { font-size:10px; color:#9ca3af; margin-top:1px; }

/* ── Section titles inside edit ──────────────────────────────────── */
.edit-section { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.8px; color:#94a3b8; margin:16px 0 8px; display:flex; align-items:center; gap:6px; }
.edit-section::after { content:''; flex:1; height:1px; background:#e9ecef; }

/* ── Reason textarea ─────────────────────────────────────────────── */
.edit-reason-label { font-size:11px; color:#6c757d; font-weight:600; margin-bottom:4px; display:flex; align-items:center; gap:4px; }
.edit-reason-opt { font-size:10px; color:#94a3b8; font-weight:400; }

/* ════════════════════════════════════════════════════════════════════
   NEW PRODUCT — price source buttons, same silver/colour scheme
   ════════════════════════════════════════════════════════════════════ */
.np-price-source-row { display:flex; align-items:center; gap:8px; background:#f4f5f7; border-radius:8px; padding:8px 12px; margin-bottom:10px; }
.np-ps-btn {
    flex:1; padding:6px 0; border-radius:6px;
    border:1.5px solid #e2e6f0; background:#f4f5f7;
    font-size:12px; font-weight:600; color:#6c757d;
    cursor:pointer; transition:all .15s; text-align:center;
}
.np-ps-btn.np-ps-active-base   { border-color:#059669; color:#059669; background:#f4f5f7; }
.np-ps-btn.np-ps-active-branch { border-color:#1d4ed8; color:#1d4ed8; background:#f4f5f7; }

/* Cost source buttons — same scheme */
.np-cost-source-row { display:flex; align-items:center; gap:8px; background:#f4f5f7; border-radius:8px; padding:8px 12px; margin-bottom:10px; }
.np-cost-btn {
    flex:1; padding:6px 0; border-radius:6px;
    border:1.5px solid #e2e6f0; background:#f4f5f7;
    font-size:12px; font-weight:600; color:#6c757d;
    cursor:pointer; transition:all .15s; text-align:center;
}
.np-cost-btn.np-cost-active-base { border-color:#059669; color:#059669; background:#f4f5f7; }

/* ── CSV wizard ──────────────────────────────────────────────────── */
.csv-step { display:none; }
.csv-step.active { display:block; }
.csv-step-indicator { display:flex; align-items:center; gap:0; margin-bottom:18px; }
.csi-step { display:flex; align-items:center; gap:5px; font-size:11px; font-weight:600; color:#94a3b8; }
.csi-step.active { color:#4B5EBD; }
.csi-step.done   { color:#059669; }
.csi-num { width:22px; height:22px; border-radius:50%; border:2px solid currentColor; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; flex-shrink:0; }
.csi-line { flex:1; height:1px; background:#dee2e6; margin:0 6px; }
.csv-preview-scroll { max-height:200px; overflow-y:auto; border:1px solid #dee2e6; border-radius:8px; }
.csv-preview-row { padding:6px 10px; border-bottom:1px solid #f1f5f9; font-size:12px; display:flex; justify-content:space-between; align-items:center; }
.csv-preview-row:last-child { border-bottom:none; }
.import-skipped-list { max-height:140px; overflow-y:auto; background:#fff8f8; border:1px solid #fecaca; border-radius:6px; padding:8px 12px; margin-top:8px; }
.import-skipped-list li { font-size:12px; color:#7f1d1d; padding:1px 0; }
.csv-chunk-progress-track { background:#e9ecef; border-radius:6px; height:8px; overflow:hidden; margin:14px 0 6px; }
.csv-chunk-progress-fill { background:linear-gradient(to right,#4B5EBD,#576CC0); height:100%; width:0%; transition:width .25s ease; }
.csv-chunk-progress-label { font-size:11px; color:#6c757d; text-align:center; }

/* ── Set branch prices modal ─────────────────────────────────────── */
.sbp-table-wrap { max-height:440px; overflow-y:auto; overflow-x:auto; border:1px solid #e2e6f0; border-radius:8px; }
table.sbp-table { width:100%; min-width:380px; border-collapse:collapse; font-size:13px; }
table.sbp-table thead th { position:sticky; top:0; background:#eef0f7; color:#4B5EBD; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; padding:9px 14px; text-align:left; border-bottom:1.5px solid #dde1f0; z-index:1; }
table.sbp-table thead th.tc { text-align:center; }
table.sbp-table tbody td { padding:8px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
table.sbp-table tbody tr:last-child td { border-bottom:none; }
table.sbp-table tbody tr:hover { background:#fafbff; }
.sbp-input { width:140px; max-width:100%; border:1.5px solid #c8d0ed; border-radius:6px; height:32px; font-size:13px; font-weight:600; padding:0 8px; color:#1d4ed8; background:#f8f9ff; outline:none; text-align:center; }
.sbp-input:focus { border-color:#1d4ed8; box-shadow:0 0 0 3px rgba(29,78,216,0.10); background:#fff; }
.sbp-tool-btn { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; border:1.5px solid #dde1f0; background:#fff; color:#4B5EBD; border-radius:6px; padding:6px 12px; cursor:pointer; transition:background .15s,border-color .15s; }
.sbp-tool-btn:hover { background:#f0f3ff; border-color:#c8d0ed; }
.sbp-tool-btn.sbp-tool-clear { color:#dc2626; }
.sbp-tool-btn.sbp-tool-clear:hover { background:#fef2f2; border-color:#fecaca; }

/* ── Bulk action cards ───────────────────────────────────────────── */
.bulk-option-card { display:flex; align-items:center; gap:12px; padding:14px 16px; border-radius:10px; border:1.5px solid #e9ecef; cursor:pointer; transition:border-color .15s,background .15s; margin-bottom:10px; }
.bulk-option-card:last-child { margin-bottom:0; }
.bulk-option-card:hover { border-color:#c8d0ed; background:#f8f9ff; }
.bulk-option-card .boc-icon { width:40px; height:40px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:19px; flex-shrink:0; }
.boc-title { font-size:14px; font-weight:700; color:#1e293b; }
.boc-desc  { font-size:12px; color:#6c757d; margin-top:1px; }
.boc-icon-base   { background:#ecfdf5; color:#059669; }
.boc-icon-branch { background:#eff6ff; color:#1d4ed8; }
.boc-icon-delete { background:#fef2f2; color:#dc2626; }

/* ── Spinner ─────────────────────────────────────────────────────── */
@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }

/* ── base-info warning banner ────────────────────────────────────── */
.bp-edit-warning { background:#fffbeb; border-left:2px solid #f59e0b; border-radius:0 5px 5px 0; padding:8px 12px; font-size:11px; color:#92400e; margin-bottom:14px; display:flex; align-items:flex-start; gap:6px; }

/* ════════════════════════════════════════════════════════════════════
   ADD PRODUCT MODAL — muted text, silver chrome on non-action elements
   ════════════════════════════════════════════════════════════════════ */
#addProductModal .form-label,
#addProductModal .form-text,
#addProductModal .csi-step,
#addProductModal label,
#addProductModal .edit-section { color:#94a3b8; }

#addProductModal .btn-secondary,
#addProductModal .sbp-tool-btn,
#addProductModal .np-ps-btn:not(.np-ps-active-base):not(.np-ps-active-branch) {
    background:#e9ecef; color:#94a3b8; border-color:#e9ecef;
}
</style>

<div class="progress" id="progressBar" role="progressbar" style="height:8px;transform:rotate(180deg);display:none;border-radius:0">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%;border-radius:0"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      @if($selectedBranch)
        <input type="checkbox" id="selectAll" class="header-select-all">
      @endif
      <form method="POST" action="{{ route('retail.operations.update.filters') }}" id="headerBranchForm" style="margin:0;display:inline;">
        @csrf
        <input type="hidden" name="user_id" value="{{ Auth::id() }}">
        <div class="header-title-block">
          <select name="branch_id" id="branchSelectHeader" onchange="document.getElementById('headerBranchForm').submit()">
            <option value="" hidden>{{ $selectedBranch ? $selectedBranch->name : '— Select Branch —' }}</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ ($pref && $pref->branch_id == $b->id) ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
          </select>
        </div>
      </form>
    </h4>
    <div class="card-header-actions">
      @if($selectedBranch)
      <button type="button" class="btn btn-light text-primary fs-16 mx-1" id="bulkActionsHeaderBtn" disabled title="Select rows to enable bulk actions">
        <i class="ri-stack-line"></i>
        <span class="bah-count" id="bulkActionsHeaderCount"></span>
      </button>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="overviewBtn" title="Overview"><i class="ri-dashboard-line"></i></a>
      @endif
      <a href="#" class="btn btn-light text-success fs-16 mx-1" id="addProductBtn" title="Add product" @if(!$selectedBranch) style="pointer-events:none;opacity:.5" @endif><i class="ri-add-circle-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="About Branch Products"><i class="ri-information-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download"><i class="ri-download-line"></i></a>
    </div>
  </div>

  <div class="card-body">
    @if(!$selectedBranch)
      <div class="no-branch-wrap">
        <i class="ri-store-line"></i>
        <h5>No Branch Selected</h5>
        <p style="font-size:13px;">Select a branch from the header above.</p>
      </div>
    @else
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100 mt-3">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Product Name</th>
          <th>Code</th>
          <th>Unit</th>
          <th>Stock</th>
          <th>Sell Price</th>
          <th>Batch</th>
          <th>Expiry</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($branchProducts as $bp)
          @php
            $row          = 'row' . $bp->id;
            $sq           = (float)$bp->stock_quantity;
            $rp           = (float)$bp->reorder_point;
            $stockClass   = $sq <= 0 ? 'stock-zero' : ($sq <= $rp ? 'stock-low' : 'stock-ok');
            $sellIsBranch = ($bp->selling_price !== null);
            $displayPrice = $sellIsBranch ? $bp->selling_price : $bp->bp_sell;
          @endphp
          <tr id="{{ $row }}">
            <td>
              <input type="checkbox" class="selectRow" value="{{ $bp->id }}" data-row-id="{{ $row }}"
                     data-name="{{ $bp->name }}" data-unit="{{ $bp->unit }}"
                     data-stock="{{ $bp->stock_quantity }}" data-bp-sell="{{ $bp->bp_sell }}"
                     data-sell="{{ $bp->selling_price }}" data-sell-is-branch="{{ $sellIsBranch ? 1 : 0 }}">
              &nbsp;{{ $bp->name }}
            </td>
            <td>{{ $bp->code ?? '—' }}</td>
            <td>{{ $bp->unit }}</td>
            <td><span class="{{ $stockClass }}">{{ number_format($sq, 2) }}</span></td>
            <td>
              <span class="{{ $sellIsBranch ? 'price-branch' : 'price-base' }}">
                {{ number_format($displayPrice, 2) }}
              </span>
            </td>
            <td>{{ $bp->batch_number ?? '—' }}</td>
            <td>{{ $bp->expiry_date  ?? '—' }}</td>
            <td>
              <a href="#" class="viewDataBtn"
                 data-id="{{ $bp->id }}" data-name="{{ $bp->name }}" data-code="{{ $bp->code }}"
                 data-unit="{{ $bp->unit }}" data-supplier="{{ $bp->supplier }}"
                 data-barcode="{{ $bp->primary_barcode }}" data-batch="{{ $bp->batch_number }}"
                 data-expiry="{{ $bp->expiry_date }}" data-cost="{{ $bp->cost_price }}"
                 data-sell="{{ $bp->selling_price }}" data-stock="{{ $bp->stock_quantity }}"
                 data-reorder="{{ $bp->reorder_point }}" data-reorder-qty="{{ $bp->reorder_quantity }}"
                 data-max="{{ $bp->max_stock }}" data-active="{{ $bp->is_active }}"
                 data-track="{{ $bp->track_stock }}" data-neg="{{ $bp->allow_negative_stock }}"
                 data-sell-is-branch="{{ $sellIsBranch ? 1 : 0 }}"
                 data-cost-is-branch="{{ ($bp->cost_price !== null) ? 1 : 0 }}"
                 data-bp-sell="{{ $bp->bp_sell }}" data-bp-cost="{{ $bp->bp_cost }}">
                <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
              </a>
              <a href="#" class="editDataBtn"
                 data-id="{{ $bp->id }}" data-row="{{ $row }}"
                 data-name="{{ $bp->name }}" data-code="{{ $bp->code }}"
                 data-unit="{{ $bp->unit }}" data-supplier="{{ $bp->supplier }}"
                 data-barcode="{{ $bp->primary_barcode }}" data-batch="{{ $bp->batch_number }}"
                 data-expiry="{{ $bp->expiry_date }}" data-cost="{{ $bp->cost_price }}"
                 data-sell="{{ $bp->selling_price }}" data-stock="{{ $bp->stock_quantity }}"
                 data-reorder="{{ $bp->reorder_point }}" data-reorder-qty="{{ $bp->reorder_quantity }}"
                 data-max="{{ $bp->max_stock }}" data-active="{{ $bp->is_active }}"
                 data-track="{{ $bp->track_stock }}" data-neg="{{ $bp->allow_negative_stock }}"
                 data-sell-is-branch="{{ $sellIsBranch ? 1 : 0 }}"
                 data-cost-is-branch="{{ ($bp->cost_price !== null) ? 1 : 0 }}"
                 data-bp-sell="{{ $bp->bp_sell }}" data-bp-cost="{{ $bp->bp_cost }}"
                 data-base-product-id="{{ $bp->base_product_id }}">
                <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
              </a>
              <a href="#" class="deleteDataBtn" data-label="{{ $bp->name }}" data-id="{{ $bp->id }}" data-row="{{ $row }}">
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
</div></div></div>

{{-- ══ BULK ACTIONS MODAL ══ --}}
<div class="modal fade" id="bulkActionsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-stack-line"></i> Bulk Actions <span style="font-size:12px;font-weight:400;opacity:.85" id="bulkActionsModalCountText">— 0 selected</span></h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:18px 20px !important;">
      <div class="bulk-option-card" id="boUseBasePrices"><div class="boc-icon boc-icon-base"><i class="ri-arrow-go-back-line"></i></div><div><div class="boc-title">Use Base Prices</div><div class="boc-desc">Clear branch overrides — revert to catalogue price.</div></div></div>
      <div class="bulk-option-card" id="boSetBranchPrices"><div class="boc-icon boc-icon-branch"><i class="ri-price-tag-3-line"></i></div><div><div class="boc-title">Set Branch Prices</div><div class="boc-desc">Enter a price override for each selected product.</div></div></div>
      <div class="bulk-option-card" id="boBulkDelete"><div class="boc-icon boc-icon-delete"><i class="ri-delete-bin-line"></i></div><div><div class="boc-title">Delete from Branch</div><div class="boc-desc">Remove selected products from this branch only.</div></div></div>
    </div>
    <div class="modal-footer" style="padding:10px 20px 14px;"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
  </div></div>
</div>

{{-- ══ USE BASE PRICES CONFIRM ══ --}}
<div class="modal fade" id="confirmUseBaseModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-danger"><h5 class="modal-title mh-title"><i class="ri-error-warning-line"></i> Confirm Action</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-center py-4">
      <div class="confirm-icon-wrap"><i class="ri-question-line"></i></div>
      <h5 class="mb-2">Are you sure?</h5>
      <p style="font-size:13px;color:#6c757d;max-width:380px;margin:0 auto;">Branch prices for <strong id="confirmUseBaseCount">0</strong> product(s) will be cleared and pricing reverts to the base catalogue.</p>
    </div>
    <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;"><button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger btn-sm px-4" id="confirmUseBaseSubmitBtn"><i class="ri-arrow-go-back-line me-1"></i> Yes, Use Base Prices</button></div>
  </div></div>
</div>

{{-- ══ BULK DELETE CONFIRM ══ --}}
<div class="modal fade" id="confirmBulkDeleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-danger"><h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Remove from Branch</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-center py-4">
      <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
      <h5 class="mt-2 mb-1">Remove <span id="confirmBulkDeleteCount" class="text-danger">0</span> product(s)?</h5>
      <p style="font-size:13px;color:#6c757d;margin-bottom:0;">Removes from this branch only. Base products remain in the catalogue.</p>
    </div>
    <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;"><button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Keep</button><button type="button" class="btn btn-danger btn-sm px-4" id="confirmBulkDeleteSubmitBtn">Remove</button></div>
  </div></div>
</div>

{{-- ══ OVERVIEW MODAL ══ --}}
<div class="modal fade" id="overviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-dashboard-line"></i> Branch Overview</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div style="display:flex;border-bottom:1.5px solid #dde1f0;background:#f8f9fb;padding:0 18px;">
      <button class="overview-tab-btn active" id="ovTabShopBtn" onclick="switchOverviewTab('shop')"><i class="ri-store-2-line me-1"></i>Shop Value</button>
      <button class="overview-tab-btn" id="ovTabPriceBtn" onclick="switchOverviewTab('price')"><i class="ri-price-tag-3-line me-1"></i>Price Guide</button>
    </div>
    <div class="modal-body" style="padding:18px 20px !important;">
      <div id="ovTabShop">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:18px;">
          <div class="sv-metric"><div class="sv-label">Products</div><div class="sv-value" style="color:#4B5EBD;">{{ $branchProducts->count() }}</div></div>
          <div class="sv-metric"><div class="sv-label">Active</div><div class="sv-value" style="color:#198754;">{{ $activeCount }}</div></div>
          <div class="sv-metric"><div class="sv-label">Low / Zero</div><div class="sv-value" style="color:#d97706;">{{ $lowStockCount + $zeroCount }}</div></div>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
          <tbody>
            <tr style="border-bottom:1px solid #e9ecef;"><td style="padding:8px 0;color:#6c757d;font-weight:600;width:140px;">Branch</td><td style="padding:8px 0;font-weight:600;color:#1e293b;">{{ $selectedBranch->name ?? '—' }}</td></tr>
            <tr style="border-bottom:1px solid #e9ecef;"><td style="padding:8px 0;color:#6c757d;font-weight:600;">Category</td><td style="padding:8px 0;color:#1e293b;">{{ $branchCategory->category ?? '—' }}</td></tr>
            <tr style="border-bottom:1px solid #e9ecef;"><td style="padding:8px 0;color:#6c757d;font-weight:600;">Zero stock</td><td style="padding:8px 0;color:#dc2626;font-weight:600;">{{ $zeroCount }}</td></tr>
            <tr style="border-bottom:1px solid #e9ecef;"><td style="padding:8px 0;color:#6c757d;font-weight:600;">Low stock</td><td style="padding:8px 0;color:#d97706;font-weight:600;">{{ $lowStockCount }}</td></tr>
            <tr><td style="padding:12px 0 4px;color:#6c757d;font-weight:600;">Total shop value</td><td style="padding:12px 0 4px;font-size:22px;font-weight:700;color:#4B5EBD;">MWK {{ number_format($shopValue, 0) }}</td></tr>
            <tr><td style="padding:4px 0;color:#6c757d;font-weight:600;">Valuation date</td><td style="padding:4px 0;color:#94a3b8;font-size:12px;">{{ now()->toDateString() }}</td></tr>
          </tbody>
        </table>
      </div>
      <div id="ovTabPrice" style="display:none;">
        <div class="pricing-swatch pricing-swatch-br"><span class="swatch-dot swatch-dot-br"></span><div class="flex-fill"><div class="swatch-label" style="color:#1d4ed8;">Branch Override</div><div class="swatch-desc">Price set specifically for this branch via Edit.</div></div><div style="text-align:right;font-weight:700;font-size:13px;color:#1d4ed8;">Blue</div></div>
        <div class="pricing-swatch pricing-swatch-bp"><span class="swatch-dot swatch-dot-bp"></span><div class="flex-fill"><div class="swatch-label" style="color:#059669;">Base Catalogue Default</div><div class="swatch-desc">No branch override — using the base catalogue price.</div></div><div style="text-align:right;font-weight:700;font-size:13px;color:#059669;">Green</div></div>
        <div style="background:#f8fafc;border-radius:8px;padding:10px 14px;font-size:12px;color:#475569;margin-top:8px;"><i class="ri-lightbulb-line me-1 text-warning"></i>Branch prices are set via the <strong>Edit</strong> modal or during product search. Adding always defaults to the base price.</div>
      </div>
    </div>
    <div class="modal-footer" style="padding:10px 20px 14px;"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
  </div></div>
</div>

{{-- ══ DOWNLOAD MODAL ══ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-download-line"></i> Download</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="buttons"></div></div>
  </div></div>
</div>

{{-- ══ INFO MODAL ══ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Branch Products</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" style="padding:18px 20px;">
      <p class="mb-2"><strong>Branch Products</strong> are base catalogue items assigned to a specific branch with their own stock, prices, and reorder levels.</p>
      <hr class="my-3">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <tbody>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;width:140px;border-bottom:1px solid #f1f5f9">Selling Price</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Branch override shown in <span class="price-branch">blue</span>; base catalogue price shown in <span class="price-base">green</span>.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Stock Qty</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9"><span class="stock-zero">Red=zero</span>, <span class="stock-low">amber=low</span>, <span class="stock-ok">green=healthy</span>.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Reorder Point</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Low-stock alert triggers when stock reaches this level.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569">Track Stock</td><td style="padding:8px 12px">When enabled, sales decrement the stock quantity.</td></tr>
        </tbody>
      </table>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
  </div></div>
</div>

{{-- ══ ADD PRODUCT MODAL ══ --}}
<div class="modal fade" id="addProductModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-add-circle-line"></i> Add Product
          @if($selectedBranch)<span style="font-size:12px;font-weight:400;opacity:.85">— {{ $selectedBranch->name }}</span>@endif
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>

      <ul class="nav nav-tabs border-bottom px-2 pt-2" role="tablist" style="flex-wrap:nowrap;">
        <li class="nav-item"><button class="nav-link active px-3 py-1" data-bs-toggle="tab" data-bs-target="#at1" type="button"><i class="ri-search-line me-1"></i>Search</button></li>
        <li class="nav-item"><button class="nav-link px-3 py-1" data-bs-toggle="tab" data-bs-target="#at2" type="button"><i class="ri-add-line me-1"></i>New Product</button></li>
        <li class="nav-item"><button class="nav-link px-3 py-1" data-bs-toggle="tab" data-bs-target="#at3" type="button"><i class="ri-upload-2-line me-1"></i>Import CSV</button></li>
      </ul>

      <div class="modal-body" style="padding:14px 18px 10px !important;">
        <div class="tab-content">

          {{-- ── Tab 1: Search ── --}}
          <div class="tab-pane fade show active" id="at1" role="tabpanel">
            <div class="mb-1">
              <input type="text" class="form-control" id="baseProductSearch" placeholder="Type product name or code…" autocomplete="off" />
            </div>
            <div id="searchResultList" class="search-result-list"></div>
          </div>

          {{-- ── Tab 2: New Product ── --}}
          <div class="tab-pane fade" id="at2" role="tabpanel">
            <div class="alert" style="background:#eff6ff;border-left:3px solid #4B5EBD;border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;color:#1e40af;margin-bottom:12px;">
              <i class="ri-information-line me-1"></i>
              Products added here go into the base catalogue first, then automatically assigned to this branch.
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Name <span class="text-danger">*</span></label><input class="form-control form-control-sm" type="text" id="new-name" autocomplete="off" /></div>
              <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Code</label><input class="form-control form-control-sm" type="text" id="new-code" autocomplete="off" /></div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Unit</label><input class="form-control form-control-sm" type="text" id="new-unit" value="Each" autocomplete="off" /></div>
              <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Quantity</label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="new-stock-qty" value="0" autocomplete="off" /></div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-12"><label class="form-label fw-semibold" style="font-size:12px">Supplier</label>
                <select class="form-select form-select-sm" id="new-supplier" autocomplete="off">
                  <option value="">Select supplier</option>
                  @foreach($supplierRows as $sup)<option value="{{ $sup->id }}" data-id="{{ $sup->id }}">{{ $sup->name }}</option>@endforeach
                </select>
              </div>
            </div>

            {{-- Selling Price source toggle --}}
            <div class="edit-section"><i class="ri-coin-line"></i>Selling Price</div>
            <div class="np-price-source-row" id="npPriceSourceRow">
              <span style="font-size:11px;color:#6c757d;font-weight:600;white-space:nowrap;">Source:</span>
              <button type="button" class="np-ps-btn np-ps-active-base" id="npBtnBase" onclick="setNpPriceSource('base')">
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#059669;margin-right:4px;vertical-align:middle;"></span> Base Catalogue
              </button>
              <button type="button" class="np-ps-btn" id="npBtnBranch" onclick="setNpPriceSource('branch')">
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#1d4ed8;margin-right:4px;vertical-align:middle;"></span> Branch Override
              </button>
            </div>
            {{-- Base sell price — shown when source = base --}}
            <div id="npBasePriceArea" class="row g-2 mb-2">
              <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:12px">Selling Price <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" type="text" inputmode="decimal" id="new-selling-price" placeholder="0.00" autocomplete="off" />
              </div>
            </div>
            {{-- Branch sell price — shown when source = branch --}}
            <div id="npBranchPriceArea" style="display:none;" class="row g-2 mb-2">
              <div class="col-12">
                <div style="background:#eff6ff;border-left:3px solid #1d4ed8;border-radius:0 5px 5px 0;padding:7px 12px;font-size:11px;color:#1e40af;margin-bottom:8px;">
                  <i class="ri-information-line me-1"></i>Enter the selling price. It will be stored as the base catalogue price <strong>and</strong> set as a branch override for <strong>{{ $selectedBranch->name ?? 'this branch' }}</strong>.
                </div>
                <label class="form-label fw-semibold" style="font-size:12px">Branch Selling Price <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" type="text" inputmode="decimal" id="new-branch-price" placeholder="0.00" autocomplete="off" />
              </div>
            </div>

            {{-- Cost Price --}}
            <div class="edit-section"><i class="ri-money-dollar-circle-line"></i>Cost Price <span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0;color:#b0b7c3;">(base catalogue only)</span></div>
            <div class="row g-2 mb-3">
              <div class="col-12"><input class="form-control form-control-sm" type="text" inputmode="decimal" id="new-cost-price" placeholder="0.00" autocomplete="off" /></div>
            </div>

            <div class="d-flex justify-content-end mt-1">
              <a href="#" class="btn btn-success btn-sm" id="submitAddBtn"><i class="ri-check-line"></i> Save to Catalogue &amp; Branch</a>
            </div>
            <div id="addSuccessNotice" class="mt-2" style="font-size:12px;color:#198754;display:none;"><i class="ri-check-double-line me-1"></i><span id="addSuccessText"></span></div>
          </div>

          {{-- ── Tab 3: CSV Import ── --}}
          <div class="tab-pane fade" id="at3" role="tabpanel">
            <div class="csv-step-indicator">
              <div class="csi-step active" id="csi1"><span class="csi-num">1</span>Guide</div>
              <div class="csi-line"></div>
              <div class="csi-step" id="csi2"><span class="csi-num">2</span>Supplier</div>
              <div class="csi-line"></div>
              <div class="csi-step" id="csi3"><span class="csi-num">3</span>Upload</div>
              <div class="csi-line"></div>
              <div class="csi-step" id="csi4"><span class="csi-num">4</span>Import</div>
            </div>
            <div class="csv-step active" id="csvStep1">
              <div style="font-size:13px;color:#374151;margin-bottom:12px;">Prepare a CSV file with these columns:</div>
              <div style="background:#f8f9fa;border-radius:8px;padding:12px 14px;margin-bottom:14px;font-family:monospace;font-size:12px;color:#374151;overflow-x:auto;white-space:nowrap;">name, code, unit, selling_price, cost_price, quantity</div>
              <table style="width:100%;border-collapse:collapse;font-size:11px;margin-bottom:14px;">
                <thead><tr style="background:#eef0f7;"><th style="padding:6px 8px;text-align:left;border-bottom:1px solid #dee2e6;">Column</th><th style="padding:6px 8px;text-align:left;border-bottom:1px solid #dee2e6;">Required</th><th style="padding:6px 8px;text-align:left;border-bottom:1px solid #dee2e6;">Notes</th></tr></thead>
                <tbody>
                  <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">name</td><td style="padding:5px 8px;color:#dc2626;">Yes</td><td style="padding:5px 8px;color:#6c757d;">Product name</td></tr>
                  <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">code</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">SKU / product code</td></tr>
                  <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">unit</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">Each, kg, g, Litre, Box…</td></tr>
                  <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">selling_price</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">Base price — 2,000 or 2000</td></tr>
                  <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">cost_price</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">Base cost — 2,000 or 2000</td></tr>
                  <tr><td style="padding:5px 8px;font-weight:600;">quantity</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">Opening stock (default 0)</td></tr>
                </tbody>
              </table>
              <div class="d-flex justify-content-between align-items-center">
                <a href="#" id="csvDownloadSample" style="font-size:12px;color:#4B5EBD;"><i class="ri-download-line me-1"></i>Download sample</a>
                <button type="button" class="btn btn-primary btn-sm" onclick="csvGoToStep(2)">Next <i class="ri-arrow-right-s-line"></i></button>
              </div>
            </div>
            <div class="csv-step" id="csvStep2">
              <label class="form-label fw-semibold" style="font-size:12px">Supplier <span class="text-danger">*</span></label>
              <select class="form-select form-select-sm mb-3" id="csv-supplier" autocomplete="off">
                <option value="">Select supplier</option>
                @foreach($supplierRows as $sup)<option value="{{ $sup->id }}">{{ $sup->name }}</option>@endforeach
              </select>
              <div style="font-size:11px;color:#6c757d;margin-bottom:14px;">Only suppliers in this branch's category are listed.</div>
              <div class="d-flex justify-content-between"><button type="button" class="btn btn-secondary btn-sm" onclick="csvGoToStep(1)"><i class="ri-arrow-left-s-line"></i> Back</button><button type="button" class="btn btn-primary btn-sm" onclick="csvStep2Next()">Next <i class="ri-arrow-right-s-line"></i></button></div>
            </div>
            <div class="csv-step" id="csvStep3">
              <label class="form-label fw-semibold" style="font-size:12px">CSV File <span class="text-danger">*</span></label>
              <input class="form-control form-control-sm mb-2" type="file" id="csv-file" accept=".csv,.txt" />
              <div id="csvFilePreviewWrap" style="display:none;"><div style="font-size:11px;color:#6c757d;margin-bottom:6px;" id="csvFilePreviewLabel"></div><div class="csv-preview-scroll" id="csvFilePreviewScroll"></div></div>
              <div style="font-size:11px;color:#6c757d;margin:10px 0 14px;">Existing branch products will only have their quantity updated.</div>
              <div class="d-flex justify-content-between"><button type="button" class="btn btn-secondary btn-sm" onclick="csvGoToStep(2)"><i class="ri-arrow-left-s-line"></i> Back</button><button type="button" class="btn btn-success btn-sm" id="csvImportBtn"><i class="ri-upload-2-line"></i> Import CSV</button></div>
              <div class="text-center mt-3 pt-2" style="border-top:1px solid #f1f5f9;">
                <a href="#" id="csvClearCacheBtn" style="font-size:11px;color:#94a3b8;"><i class="ri-delete-bin-line me-1"></i>Clear loaded data in local storage</a>
              </div>
            </div>
            <div class="csv-step" id="csvStep4">
              <div id="csvImportProgress" style="font-size:13px;color:#475569;margin-bottom:14px;text-align:center;padding:20px 0;"></div>
              <div id="csvChunkProgressWrap" style="display:none;">
                <div class="csv-chunk-progress-track"><div class="csv-chunk-progress-fill" id="csvChunkProgressFill"></div></div>
                <div class="csv-chunk-progress-label" id="csvChunkProgressLabel"></div>
              </div>
              <div class="d-flex justify-content-end"><button type="button" class="btn btn-primary btn-sm" id="csvDoneBtn"><i class="ri-check-line"></i> Done</button></div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

{{-- ══ VIEW MODAL ══ --}}
<div class="modal fade" id="viewProductModal" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-eye-line"></i> Branch Product Details</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" style="padding:16px 20px !important;">
      <div class="mb-3 pb-2 border-bottom d-flex align-items-start justify-content-between">
        <div><div style="font-size:17px;font-weight:700;color:#1e293b" id="vw-name"></div><div style="font-size:12px;color:#6c757d" id="vw-meta-line"></div></div>
        <div id="vw-badges" class="d-flex gap-2 flex-wrap justify-content-end"></div>
      </div>
      <div id="vw-price-notice" style="background:#f0f3ff;border-left:3px solid #4B5EBD;border-radius:0 5px 5px 0;padding:7px 12px;font-size:11px;color:#3a4a9a;display:none;margin-bottom:12px;"><i class="ri-information-line me-1"></i><span id="vw-price-notice-text"></span></div>
      <ul class="nav nav-tabs nav-sm mb-3" role="tablist" style="font-size:12px;">
        <li class="nav-item"><button class="nav-link active py-1 px-2" data-bs-toggle="tab" data-bs-target="#vw-t1"><i class="ri-money-dollar-circle-line me-1"></i>Pricing</button></li>
        <li class="nav-item"><button class="nav-link py-1 px-2" data-bs-toggle="tab" data-bs-target="#vw-t2"><i class="ri-stack-line me-1"></i>Stock</button></li>
        <li class="nav-item"><button class="nav-link py-1 px-2" data-bs-toggle="tab" data-bs-target="#vw-t3"><i class="ri-settings-3-line me-1"></i>Settings</button></li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="vw-t1">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 14px;">
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Selling Price (MWK)</label><div id="vw-sell" style="font-size:13px;font-weight:600;"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Cost Price (MWK)</label><div id="vw-cost" style="font-size:13px;"></div></div>
          </div>
        </div>
        <div class="tab-pane fade" id="vw-t2">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 14px;">
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Stock on Hand</label><div id="vw-stock"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Reorder Point</label><div id="vw-reorder"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Reorder Qty</label><div id="vw-reorder-qty"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Max Stock</label><div id="vw-max"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Barcode</label><div id="vw-barcode"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Batch</label><div id="vw-batch"></div></div>
            <div style="grid-column:1/-1;"><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Expiry Date</label><div id="vw-expiry"></div></div>
          </div>
        </div>
        <div class="tab-pane fade" id="vw-t3">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 14px;">
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Track Stock</label><div id="vw-track"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Allow Negative Stock</label><div id="vw-neg"></div></div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer" style="padding:10px 20px 14px;justify-content:space-between;">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><i class="ri-close-line me-1"></i> Close</button>
      <a href="#" class="btn btn-primary btn-sm" id="vwEditBtn"><i class="ri-edit-box-line me-1"></i> Edit</a>
    </div>
  </div></div>
</div>

{{-- ══ EDIT MODAL ══ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-edit-box-line"></i> <span id="editModalName"></span></h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>

      {{-- Tab bar: same bg throughout, active = coloured text + underline --}}
      <div class="edit-modal-tabs" id="editTabBar">
        <button type="button" class="edit-modal-tab-btn em-active" id="emTab1Btn" onclick="switchEditTab(1)">
          <i class="ri-edit-line" style="font-size:13px;"></i> Edit
        </button>
        <button type="button" class="edit-modal-tab-btn" id="emTab2Btn" onclick="switchEditTab(2)">
          <i class="ri-settings-3-line" style="font-size:13px;"></i> Settings
        </button>
        <button type="button" class="edit-modal-tab-btn" id="emTab3Btn" onclick="switchEditTab(3)">
          <i class="ri-database-2-line" style="font-size:13px;"></i> Base Info
        </button>
      </div>

      <div class="modal-body" style="padding:16px 18px 10px !important;">
        <input type="hidden" id="editId">
        <input type="hidden" id="editRow">
        <input type="hidden" id="editBaseProductId">

        {{-- ── TAB 1: EDIT ── --}}
        <div class="edit-tab-pane em-show" id="emTab1">

          {{-- Product name (read-only) --}}
          <div class="mb-2">
            <label class="form-label fw-semibold" style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;">Product</label>
            <input type="text" class="form-control form-control-sm edit-ro" id="edit-ro-name" readonly tabindex="-1" autocomplete="off" />
          </div>

          {{-- Unit + link --}}
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;">Unit</label>
              <input type="text" class="form-control form-control-sm edit-ro" id="edit-ro-unit" readonly tabindex="-1" autocomplete="off" />
            </div>
              <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:12px">Quantity</label>
              <input class="form-control form-control-sm" type="text" inputmode="decimal" id="editStockQty" autocomplete="off" />
            </div>
          </div>

          {{-- Quantity (col-6) --}}
          <div class="row g-2 mb-2">
            
          </div>

          {{-- Reason for quantity change --}}
          <div class="mb-3">
            <div class="edit-reason-label">
              Reason for quantity change <span class="edit-reason-opt">(optional)</span>
            </div>
            <textarea class="form-control form-control-sm" id="editStockReason" rows="2" placeholder="e.g. Stock count correction, received delivery…" style="resize:vertical;font-size:12px;" autocomplete="off"></textarea>
          </div>

          {{-- Selling Price source ──────────────────── --}}
          <div class="edit-section"><i class="ri-coin-line"></i>Selling Price Source</div>

          {{-- Two compact cards: base (green) and branch (blue) --}}
          <div class="price-source-grid mb-2">
            <div class="psc psc-active-base" id="editPscBase" onclick="setEditPriceSource('base')">
              <div class="psc-label psc-label-base"><span class="psc-dot psc-dot-base"></span>Base catalogue</div>
              <div class="psc-val psc-val-base" id="editPscBaseVal">—</div>
              <div class="psc-sub">Inherited · all branches</div>
            </div>
            <div class="psc" id="editPscBranch" onclick="setEditPriceSource('branch')">
              <div class="psc-label psc-label-branch"><span class="psc-dot psc-dot-branch"></span>This branch only</div>
              <div class="psc-val psc-val-branch" id="editPscBranchVal">—</div>
              <div class="psc-sub">Override for this branch</div>
            </div>
          </div>

          {{-- Branch price input — shown only when branch is active --}}
          <div id="editBranchPriceFields" style="display:none;">
            <div class="row g-2 mb-1">
              <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:12px">Branch Selling Price <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" type="text" inputmode="decimal" id="editSellPrice" placeholder="0.00" autocomplete="off" />
              </div>
            </div>
          </div>

        </div>

        {{-- ── TAB 2: SETTINGS ── --}}
        <div class="edit-tab-pane" id="emTab2">
          <div class="edit-section" style="margin-top:0;"><i class="ri-coin-line"></i>Cost Price <span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0;color:#b0b7c3;">(base catalogue only)</span></div>
          <div class="row g-2 mb-2">
            <div class="col-12">
              <label class="form-label fw-semibold" style="font-size:12px">Cost Price (MWK)</label>
              <input class="form-control form-control-sm" type="text" inputmode="decimal" id="editCostPrice" placeholder="0.00" autocomplete="off" />
            </div>
          </div>
          <div class="edit-section"><i class="ri-stack-line"></i>Reorder &amp; Limits</div>
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Reorder Point</label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="editReorderPoint" autocomplete="off" /></div>
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Reorder Qty</label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="editReorderQty" autocomplete="off" /></div>
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Max Stock</label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="editMaxStock" autocomplete="off" /></div>
          </div>
          <div class="edit-section"><i class="ri-qr-code-line"></i>Barcode &amp; Batch</div>
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Barcode</label><input class="form-control form-control-sm" type="text" id="editBarcode" autocomplete="off" /></div>
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Batch Number</label><input class="form-control form-control-sm" type="text" id="editBatch" autocomplete="off" /></div>
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Expiry Date</label><input class="form-control form-control-sm" type="date" id="editExpiry" autocomplete="off" /></div>
          </div>
          <div class="edit-section"><i class="ri-toggle-line"></i>Status &amp; Behaviour</div>
          <div class="row g-2">
            <div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" id="editTrackStock"><label class="form-check-label" for="editTrackStock" style="font-size:12px">Track stock</label></div></div>
            <div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" id="editAllowNeg"><label class="form-check-label" for="editAllowNeg" style="font-size:12px">Allow negative</label></div></div>
            <div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" id="editIsActive"><label class="form-check-label" for="editIsActive" style="font-size:12px">Active</label></div></div>
          </div>
        </div>

        {{-- ── TAB 3: BASE INFO ── --}}
        <div class="edit-tab-pane" id="emTab3">
          <div class="bp-edit-warning">
            <i class="ri-alert-line" style="font-size:14px;flex-shrink:0;margin-top:1px;"></i>
            Changes here update the <strong>base catalogue</strong> and affect all branches using this product.
          </div>
          <input type="hidden" id="bpEditId">
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Name <span class="text-danger">*</span></label><input class="form-control form-control-sm" type="text" id="bpEditName" autocomplete="off" /></div>
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Code</label><input class="form-control form-control-sm" type="text" id="bpEditCode" autocomplete="off" /></div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Unit</label><input class="form-control form-control-sm" type="text" id="bpEditUnit" autocomplete="off" /></div>
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Base Sell Price <span class="text-danger">*</span></label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="bpEditSellPrice" placeholder="0.00" autocomplete="off" /></div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Base Cost Price</label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="bpEditCostPrice" placeholder="0.00" autocomplete="off" /></div>
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:12px">Supplier</label>
              <select class="form-select form-select-sm" id="bpEditSupplier" autocomplete="off">
                <option value="">Select supplier</option>
                @foreach($supplierRows as $sup)<option value="{{ $sup->id }}">{{ $sup->name }}</option>@endforeach
              </select>
            </div>
          </div>
          <div class="alert border-0 py-2 px-3 mb-0" style="background:#ecfdf5;border-left:2px solid #059669 !important;border-radius:0 5px 5px 0;font-size:11px;color:#065f46;">
            <i class="ri-information-line me-1"></i>These are the catalogue defaults shown in <span style="color:#059669;font-weight:700;">green</span> for branches without an override.
          </div>
        </div>

      </div>

      <div class="modal-footer" style="padding:10px 18px 14px;justify-content:flex-end;gap:8px;">
        <a href="#" class="btn btn-secondary btn-sm" id="cancelEditBtn">Cancel</a>
        <a href="#" class="btn btn-primary btn-sm" id="submitEditBtn"><i class="ri-check-line me-1"></i> Update Branch Product</a>
        <a href="#" class="btn btn-success btn-sm" id="submitBaseProductBtn" style="display:none;"><i class="ri-check-line me-1"></i> Update Base Product</a>
      </div>
    </div>
  </div>
</div>

{{-- ══ DELETE MODAL ══ --}}
<div class="modal fade" id="deleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-danger"><h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Remove from Branch</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-center py-4">
      <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
      <h5 class="mt-2 mb-1">Remove <span id="deleteLabel" class="text-danger"></span>?</h5>
      <p style="font-size:13px;color:#6c757d;margin-bottom:0;">Removes from <strong>{{ $selectedBranch->name ?? 'this branch' }}</strong> only. Base product is kept.</p>
      <input type="hidden" id="deleteId"><input type="hidden" id="deleteRow">
    </div>
    <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;"><a href="#" class="btn btn-secondary btn-sm px-4" id="keepBtn">Keep</a><a href="#" class="btn btn-danger btn-sm px-4" id="submitDeleteBtn">Remove</a></div>
  </div></div>
</div>

{{-- ══ SET BRANCH PRICES MODAL ══ --}}
<div class="modal fade" id="setBranchPricesModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-price-tag-3-line"></i> Set Branch Prices — <span id="sbpCount">0</span> product(s)</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" style="padding:16px 18px !important;">
      <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;margin-bottom:10px;">
        <div style="font-size:11px;color:#6c757d;">Leave blank to keep current price unchanged.</div>
        <div style="display:flex;gap:8px;">
          <button type="button" class="sbp-tool-btn" id="sbpFillBaseBtn"><i class="ri-arrow-go-back-line"></i> Use Base Prices</button>
          <button type="button" class="sbp-tool-btn sbp-tool-clear" id="sbpClearAllBtn"><i class="ri-close-line"></i> Clear All</button>
        </div>
      </div>
      <div class="sbp-table-wrap"><table class="sbp-table"><thead><tr><th>Product</th><th class="tc">Branch Price (MWK)</th></tr></thead><tbody id="sbpProductList"></tbody></table></div>
    </div>
    <div class="modal-footer" style="padding:10px 18px 14px;gap:8px;"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary btn-sm" id="sbpSubmitBtn"><i class="ri-check-line me-1"></i> Save Branch Prices</button></div>
  </div></div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000, allowHtml:true };

    var BRANCH_ID = {{ $selectedBranch->id ?? 'null' }};

    // ── Number formatting ──────────────────────────────────────────────────
    function purifyFloat(raw) {
        var s = String(raw || '').replace(/[^0-9.\-]/g, '');
        var parts = s.split('.');
        if (parts.length > 2) s = parts[0] + '.' + parts.slice(1).join('');
        return s;
    }

    function bindNumFmt(selector) {
        $(document).on('blur', selector, function () {
            var raw = purifyFloat($(this).val());
            if (raw === '' || raw === '-') return;
            var n = parseFloat(raw);
            if (!isNaN(n)) $(this).val(n.toLocaleString('en-US', { minimumFractionDigits:0, maximumFractionDigits:4 }));
        });
        $(document).on('focus', selector, function () {
            var raw = purifyFloat($(this).val());
            if (raw !== '') $(this).val(raw);
        });
        $(document).on('paste', selector, function (e) {
            e.preventDefault();
            var pasted = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
            $(this).val(purifyFloat(pasted));
        });
    }

    bindNumFmt([
        '#new-selling-price','#new-cost-price','#new-stock-qty','#new-branch-price',
        '#editSellPrice','#editCostPrice','#editStockQty',
        '#editReorderPoint','#editReorderQty','#editMaxStock',
        '#bpEditSellPrice','#bpEditCostPrice',
        '.sbp-input'
    ].join(', '));

    // NOTE: '.sri-qty-input' and '.sri-price-override-input' (the search-result
    // qty + override inputs) are intentionally EXCLUDED from bindNumFmt — they
    // keep plain default browser focus/blur behaviour with no extra styling,
    // and they no longer auto-grab focus or carry placeholder/default values.

    function normaliseNumericInputs(selector) {
        $(selector).each(function () { $(this).val(purifyFloat($(this).val())); });
    }

    // ── Utilities ──────────────────────────────────────────────────────────
    function handleAjaxError(xhr, status) {
        if (status === 'timeout') { toastr.error('Request timed out.', 'Timeout'); return; }
        if (xhr.status === 422) {
            var e = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
            var m = ''; $.each(e, function(k,v) { m += v + '\n'; });
            toastr.error(m || (xhr.responseJSON && xhr.responseJSON.error) || 'Validation failed.', 'Error');
        } else if (xhr.status === 500) {
            toastr.error('Server error.', 'Error');
        } else {
            toastr.error('Unspecified error.', 'Error');
        }
    }

    function fmtNum(val, dec) {
        dec = dec === undefined ? 2 : dec;
        if (val === null || val === '' || val === undefined) return '—';
        var n = parseFloat(val);
        return isNaN(n) ? '—' : n.toLocaleString('en-US', { minimumFractionDigits:dec, maximumFractionDigits:dec });
    }

    function yn(val) {
        return parseInt(val) === 1
            ? '<span class="badge bg-success" style="font-size:11px">Yes</span>'
            : '<span class="badge bg-secondary" style="font-size:11px">No</span>';
    }

    function buildRow(p) {
        var sq = parseFloat(p.stock_quantity || 0);
        var rp = parseFloat(p.reorder_point  || 0);
        var sc = sq <= 0 ? 'stock-zero' : (sq <= rp ? 'stock-low' : 'stock-ok');
        var d  = function(v){ return (v || '').toString().replace(/"/g, '&quot;'); };
        var sellClass = p.sell_is_branch ? 'price-branch' : 'price-base';
        var displayPrice = (p.selling_price !== null && p.selling_price !== undefined && p.selling_price !== '') ? p.selling_price : p.bp_sell;

        return `<tr id="${p.row}">
            <td>
                <input type="checkbox" class="selectRow" value="${p.id}" data-row-id="${p.row}"
                       data-name="${d(p.name)}" data-unit="${d(p.unit)}"
                       data-stock="${p.stock_quantity}" data-bp-sell="${p.bp_sell || ''}"
                       data-sell="${(p.selling_price !== null && p.selling_price !== undefined) ? p.selling_price : ''}"
                       data-sell-is-branch="${p.sell_is_branch ? 1 : 0}">
                &nbsp;${p.name || ''}
            </td>
            <td>${p.code || '—'}</td>
            <td>${p.unit || '—'}</td>
            <td><span class="${sc}">${fmtNum(sq, 2)}</span></td>
            <td><span class="${sellClass}">${fmtNum(displayPrice, 2)}</span></td>
            <td>${p.batch_number || '—'}</td>
            <td>${p.expiry_date  || '—'}</td>
            <td>
                <a href="#" class="viewDataBtn"
                   data-id="${p.id}" data-name="${d(p.name)}" data-code="${d(p.code)}"
                   data-unit="${d(p.unit)}" data-supplier="${d(p.supplier)}"
                   data-barcode="${d(p.primary_barcode)}" data-batch="${d(p.batch_number)}"
                   data-expiry="${d(p.expiry_date)}"
                   data-cost="${(p.cost_price !== null && p.cost_price !== undefined) ? p.cost_price : ''}"
                   data-sell="${(p.selling_price !== null && p.selling_price !== undefined) ? p.selling_price : ''}"
                   data-stock="${p.stock_quantity}" data-reorder="${p.reorder_point}"
                   data-reorder-qty="${(p.reorder_quantity !== null && p.reorder_quantity !== undefined) ? p.reorder_quantity : ''}"
                   data-max="${(p.max_stock !== null && p.max_stock !== undefined) ? p.max_stock : ''}"
                   data-active="${p.is_active}" data-track="${p.track_stock}" data-neg="${p.allow_negative_stock}"
                   data-sell-is-branch="${p.sell_is_branch ? 1 : 0}"
                   data-cost-is-branch="${p.cost_is_branch ? 1 : 0}"
                   data-bp-sell="${p.bp_sell || ''}" data-bp-cost="${p.bp_cost || ''}">
                   <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="editDataBtn"
                   data-id="${p.id}" data-row="${p.row}" data-name="${d(p.name)}"
                   data-unit="${d(p.unit)}" data-code="${d(p.code)}" data-supplier="${d(p.supplier)}"
                   data-sell="${(p.selling_price !== null && p.selling_price !== undefined) ? p.selling_price : ''}"
                   data-cost="${(p.cost_price !== null && p.cost_price !== undefined) ? p.cost_price : ''}"
                   data-stock="${p.stock_quantity}" data-reorder="${p.reorder_point}"
                   data-reorder-qty="${(p.reorder_quantity !== null && p.reorder_quantity !== undefined) ? p.reorder_quantity : ''}"
                   data-max="${(p.max_stock !== null && p.max_stock !== undefined) ? p.max_stock : ''}"
                   data-barcode="${d(p.primary_barcode)}" data-batch="${d(p.batch_number)}"
                   data-expiry="${d(p.expiry_date)}" data-active="${p.is_active}"
                   data-track="${p.track_stock}" data-neg="${p.allow_negative_stock}"
                   data-sell-is-branch="${p.sell_is_branch ? 1 : 0}"
                   data-cost-is-branch="${p.cost_is_branch ? 1 : 0}"
                   data-bp-sell="${p.bp_sell || ''}" data-bp-cost="${p.bp_cost || ''}"
                   data-base-product-id="${p.base_product_id || ''}">
                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="deleteDataBtn"
                   data-label="${d(p.name)}" data-id="${p.id}" data-row="${p.row}">
                   <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
                </a>
            </td>
        </tr>`;
    }

    function updateSelectedCount() {
        var total = $('.selectRow').length;
        var count = $('.selectRow:checked').length;
        var badge = $('#bulkActionsHeaderCount');
        if (count > 0) {
            badge.text(count).addClass('show');
            $('#bulkActionsHeaderBtn').addClass('enabled').prop('disabled', false).attr('title', count + ' selected — click for bulk actions');
        } else {
            badge.text('').removeClass('show');
            $('#bulkActionsHeaderBtn').removeClass('enabled').prop('disabled', true).attr('title', 'Select rows to enable bulk actions');
        }
        $('#selectAll').prop('checked', total > 0 && count === total);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  DataTable
    // ════════════════════════════════════════════════════════════════════════
    @if($selectedBranch)

    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, 'All']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  }
        ],
        buttons: [
            { extend: 'excelHtml5', title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' },
              customize: function(doc) { doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split(''); }
            }
        ]
    });
    window._dt = table;
    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    $('#overviewBtn').on('click', function(e) { e.preventDefault(); $('#overviewModal').modal('show'); });

    // ════════════════════════════════════════════════════════════════════════
    //  ADD PRODUCT — Search tab
    // ════════════════════════════════════════════════════════════════════════
    var allBaseProducts = [];

    function loadBaseProducts() {
        if (allBaseProducts.length) return;
        $.ajax({ type:'GET', url:'{{ route("retail.operations.baseproducts.search") }}', data:{ branch_id: BRANCH_ID },
            success: function(data) { allBaseProducts = data.products || []; }
        });
    }

    var branchProductBaseIds = {};
    @foreach($branchProducts as $bp)
        branchProductBaseIds[{{ $bp->base_product_id }}] = {
            stock: {{ (float)$bp->stock_quantity }},
            sell:  {{ $bp->selling_price !== null ? $bp->selling_price : 'null' }},
            bpSell:{{ $bp->bp_sell !== null ? $bp->bp_sell : 'null' }},
            sellIsBranch: {{ ($bp->selling_price !== null) ? 'true' : 'false' }}
        };
    @endforeach

    function softResetAddModal() {
        $('#baseProductSearch').val('');
        $('#searchResultList').hide();
        $('#addSuccessNotice').hide();
        $('#new-name,#new-selling-price,#new-cost-price,#new-code,#new-branch-price').val('');
        $('#new-stock-qty').val('0');
        $('#new-unit').val('Each');
        $('#new-supplier').val('');
        $('#csv-supplier').val('');
        $('#csv-file').val('');
        $('#csvFilePreviewWrap').hide();
        $('#csvFilePreviewScroll').html('');
        $('#csvImportProgress').html('');
        $('#csvChunkProgressWrap').hide();
        $('#csvChunkProgressFill').css('width', '0%');
        $('#csvChunkProgressLabel').text('');
        setNpPriceSource('base');
        csvGoToStep(1);
    }

    $('#addProductBtn').on('click', function(e) {
        e.preventDefault();
        softResetAddModal();
        loadBaseProducts();
        $('#addProductModal').modal('show');
        setTimeout(function() { $('#baseProductSearch').focus(); }, 400);
    });
    $('#addProductModal').on('hidden.bs.modal', softResetAddModal);

    // Fuzzy subsequence match: true if every character of `needle` appears
    // in `haystack`, in order, but not necessarily next to each other.
    // e.g. "pra" matches "paracetamol" (p...r...a...) even though "pra"
    // isn't a literal substring of it.
    function bpIsSubsequence(needle, haystack) {
        var hi = 0;
        for (var i = 0; i < haystack.length && hi < needle.length; i++) {
            if (haystack[i] === needle[hi]) hi++;
        }
        return hi === needle.length;
    }

    // Does this token match the product's searchable text? Checked in order:
    // (1) literal substring within a single word — fast, covers most typing,
    //     e.g. "500" in "500mg" or "ceta" in "paracetamol";
    // (2) fuzzy subsequence within a single word — for partial/skipped-letter
    //     typing, e.g. "pra" in "paracetamol";
    // (3) fuzzy subsequence across the whole run-together text — for queries
    //     typed with no space, e.g. "para500" spanning "paracetamol" and
    //     "500mg" as separate words.
    function bpTokenMatchesWords(token, words, joined) {
        return words.some(function (w) {
            return w.indexOf(token) !== -1 || bpIsSubsequence(token, w);
        }) || bpIsSubsequence(token, joined);
    }

    // ── Search box: independent of any result-row input. Typing here never
    //    shifts focus to qty/override fields and never gets re-focused away. ──
    // Token-based ("smart") search: split the query into whitespace-separated
    // tokens and require every token to match somewhere in the product's
    // searchable words, in any order — e.g. "para 500" or "500 para" both
    // match "Paracetamol 500mg".
    $('#baseProductSearch').on('input', function() {
        var q = $(this).val().trim().toLowerCase();
        if (!q) { $('#searchResultList').hide(); return; }
        var tokens = q.split(/\s+/).filter(Boolean);
        var results = allBaseProducts.filter(function(p) {
            var words = ((p.name||'') + ' ' + (p.code||'') + ' ' + (p.unit||'') + ' ' + (p.supplier||'')).toLowerCase().split(/\s+/).filter(Boolean);
            var joined = words.join('');
            return tokens.every(function(t) { return bpTokenMatchesWords(t, words, joined); });
        }).slice(0, 30);
        renderSearchResults(results, q);
    });

    function renderSearchResults(results, q) {
        var list = $('#searchResultList');
        if (!results.length) {
            list.html('<div style="padding:16px;text-align:center;color:#94a3b8;font-size:12px;"><i class="ri-search-line me-1"></i>No products found</div>').show();
            return;
        }
        var html = '';
        results.forEach(function(p) {
            var re     = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            var nameHl = p.name.replace(re, '<strong>$1</strong>');
            var codeHtml = p.code ? ' <span class="sri-code">· ' + p.code + '</span>' : '';
            var inBranch = branchProductBaseIds[p.id] || null;
            var unitLbl = p.unit || 'Each';

            var priceClass, priceLbl, priceVal;
            if (inBranch && inBranch.sellIsBranch && inBranch.sell !== null) {
                priceClass = 'sri-price-branch'; priceLbl = 'branch';
                priceVal   = parseFloat(inBranch.sell).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
            } else {
                var basePrice = (inBranch && inBranch.bpSell !== null) ? inBranch.bpSell : p.selling_price;
                priceClass = 'sri-price-base'; priceLbl = 'base';
                priceVal   = basePrice ? parseFloat(basePrice).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}) : '<span class="sri-na">PNA</span>';
            }

            var stockClass, stockLbl;
            if (inBranch) {
                var sq = parseFloat(inBranch.stock);
                stockClass = sq <= 0 ? 'sri-stock-zero' : (sq <= 5 ? 'sri-stock-low' : 'sri-stock-ok');
                stockLbl   = 'Stock: ' + sq.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
            } else {
                stockClass = 'sri-stock-zero'; stockLbl = 'Not in branch';
            }

            // Override label flips based on current price source for this row:
            //  - currently on base price  -> "Override base price"
            //  - currently on branch price -> "Use base price"
            var overrideLabel = (priceLbl === 'branch') ? 'Use base price' : 'Override base price';
            var priceDisplay = (priceVal.indexOf('sri-na') >= 0) ? priceVal : (priceVal + '/' + unitLbl);

            var isAdded = !!_addedMap[p.id];
            var addedMsg = _addedMap[p.id] || '';
            var btnDis  = isAdded ? 'disabled' : '';
            var msgStyle = isAdded ? 'display:flex;' : 'display:none;';
            var ockId = 'sri_ovck_' + p.id;
            var ovinId = 'sri_ovin_' + p.id;

            html += `
            <div class="sri-item" data-id="${p.id}" data-bp-sell="${p.selling_price || ''}">
                <div class="sri-main">
                    <div class="sri-name-wrap">
                        <div class="sri-name">${nameHl}${codeHtml}</div>
                    </div>
                    ${inBranch ? '<span class="sri-already-badge">In branch</span>' : ''}
                </div>
                <div class="sri-meta-row">
                    <span class="sri-stock-pill ${stockClass}">${stockLbl}</span>
                    <span class="sri-price-tag ${priceClass}">${priceDisplay}</span>
                    <div class="sri-override-wrap">
                        <span class="sri-override-label">${overrideLabel}</span>
                        <input type="checkbox" class="sri-override-check" id="${ockId}" ${btnDis}
                               onchange="toggleSriPriceOverride(${p.id},this.checked)" />
                        <input type="text" inputmode="decimal" class="sri-price-override-input" id="${ovinId}"
                               autocomplete="off" ${btnDis} />
                    </div>
                </div>
                <div class="sri-controls">
                    <div class="sri-qty-wrap">
                        <span class="sri-qty-label">Qty</span>
                        <input type="text" inputmode="decimal" class="sri-qty-input" id="sri_qty_${p.id}"
                               value="" autocomplete="off" ${btnDis}
                               onkeydown="if(event.key==='Enter'){event.preventDefault();addProductFromSearch(${p.id});}" />
                    </div>
                    <button type="button" class="sri-add-btn" id="sri_btn_${p.id}"
                            onclick="addProductFromSearch(${p.id})" ${btnDis}>
                        Add
                    </button>
                    <span class="sri-added-msg" id="sri_msg_${p.id}" style="${msgStyle}">
                        <span id="sri_msg_text_${p.id}">${addedMsg}</span>
                    </span>
                </div>
            </div>`;
        });
        list.html(html).show();
        // No auto-focus here on purpose — typing in the search box must stay
        // independent and never get redirected into a result-row input.
    }

    window.toggleSriPriceOverride = function(pid, checked) {
        var inp = document.getElementById('sri_ovin_' + pid);
        if (!inp) return;
        inp.style.display = checked ? 'block' : 'none';
        if (checked) { inp.focus(); }
    };

    var _addedMap = {};

    window.addProductFromSearch = function(pid) {
        var qtyRaw = purifyFloat($('#sri_qty_' + pid).val());
        $('#sri_qty_' + pid).val(qtyRaw);
        var ovRawNorm = purifyFloat($('#sri_ovin_' + pid).val());
        $('#sri_ovin_' + pid).val(ovRawNorm);

        var qtyVal = $('#sri_qty_' + pid).val().trim();
        if (qtyVal === '') { toastr.warning('Enter a quantity.', 'Required'); $('#sri_qty_' + pid).focus(); return; }
        var qty    = parseFloat(qtyVal);
        if (isNaN(qty) || qty < 0) { toastr.warning('Enter a valid quantity.', 'Required'); $('#sri_qty_' + pid).focus(); return; }

        var overrideChecked = $('#sri_ovck_' + pid).prop('checked');
        var overridePrice   = null;
        if (overrideChecked) {
            var ovRaw = purifyFloat($('#sri_ovin_' + pid).val());
            overridePrice = ovRaw !== '' ? parseFloat(ovRaw) : null;
            if (overridePrice !== null && isNaN(overridePrice)) { toastr.warning('Enter a valid override price.', 'Required'); return; }
        }

        var btn = $('#sri_btn_' + pid), qtyInput = $('#sri_qty_' + pid);
        btn.prop('disabled', true); qtyInput.prop('disabled', true);
        $('#sri_ovck_' + pid).prop('disabled', true);
        $('#sri_ovin_' + pid).prop('disabled', true);

        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST', url: '{{ route("retail.operations.branchproducts.upsert") }}', timeout: 60000,
            data: { branch_id: BRANCH_ID, base_product_id: pid, stock_quantity: qty, track_stock: 1, is_active: 1, allow_negative_stock: 0, _token: '{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); },
            success: function(data) {
                if (data.status === 201) {
                    if (overridePrice !== null && overridePrice > 0) {
                        $.ajax({
                            type: 'POST', url: '{{ route("retail.operations.branchproducts.update") }}', timeout: 60000,
                            data: { id: data.product.id, selling_price: overridePrice, stock_quantity: data.product.stock_quantity, reorder_point: data.product.reorder_point || 0, track_stock: data.product.track_stock, allow_negative_stock: data.product.allow_negative_stock, is_active: data.product.is_active, _token: '{{ csrf_token() }}' },
                            success: function(upd) {
                                var finalProduct = (upd.status === 201) ? upd.product : data.product;
                                applySearchAddResult(pid, qty, finalProduct, overridePrice);
                            },
                            error: function() { applySearchAddResult(pid, qty, data.product, null); }
                        });
                    } else {
                        applySearchAddResult(pid, qty, data.product, null);
                    }
                } else {
                    btn.prop('disabled', false); qtyInput.prop('disabled', false);
                    $('#sri_ovck_' + pid).prop('disabled', false);
                    toastr.error(data.error || 'Error.', 'Error');
                }
            },
            error: function() {
                btn.prop('disabled', false); qtyInput.prop('disabled', false);
                $('#sri_ovck_' + pid).prop('disabled', false);
                handleAjaxError.apply(this, arguments);
            }
        });
    };

    function applySearchAddResult(pid, qty, product, overridePrice) {
        toastr.success(product.name + ' saved.', 'Success');
        if (window._dt) {
            if (table.row('#' + product.row).length) table.row('#' + product.row).remove();
            table.row.add($(buildRow(product))).draw(false);
        }
        branchProductBaseIds[pid] = {
            stock: parseFloat(product.stock_quantity),
            sell:  product.selling_price,
            bpSell: product.bp_sell,
            sellIsBranch: (product.sell_is_branch == 1 || product.sell_is_branch === true)
        };
        var msg = qty > 0 ? fmtNum(qty, 2) + ' added' : 'saved';
        if (overridePrice) msg += ' · price MWK ' + fmtNum(overridePrice, 2);
        _addedMap[pid] = msg;
        $('#sri_msg_text_' + pid).text(msg);
        $('#sri_msg_' + pid).show();
        $('#sri_qty_' + pid).val('').prop('disabled', false);
        var btn2 = $('#sri_btn_' + pid);
        btn2.prop('disabled', false);
        btn2.text('Add');
        $('#sri_ovck_' + pid).prop('checked', false).prop('disabled', false);
        $('#sri_ovin_' + pid).val('').hide().prop('disabled', false);
    }

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#baseProductSearch, #searchResultList').length) $('#searchResultList').hide();
    });

    // ════════════════════════════════════════════════════════════════════════
    //  NEW PRODUCT — price source toggle
    //  One price only: base mode shows base sell input, branch mode hides it
    //  and shows a single branch price input instead.
    // ════════════════════════════════════════════════════════════════════════
    var _npPriceSource = 'base';

    window.setNpPriceSource = function(src) {
        _npPriceSource = src;
        if (src === 'base') {
            $('#npBtnBase').addClass('np-ps-active-base').removeClass('np-ps-active-branch');
            $('#npBtnBranch').removeClass('np-ps-active-base np-ps-active-branch');
            $('#npBasePriceArea').show();
            $('#npBranchPriceArea').hide();
        } else {
            $('#npBtnBranch').addClass('np-ps-active-branch').removeClass('np-ps-active-base');
            $('#npBtnBase').removeClass('np-ps-active-base np-ps-active-branch');
            $('#npBasePriceArea').hide();
            $('#npBranchPriceArea').show();
        }
    };

    // ── New product save ───────────────────────────────────────────────────
    $('#submitAddBtn').on('click', function(e) {
        e.preventDefault();
        normaliseNumericInputs('#new-selling-price,#new-cost-price,#new-stock-qty,#new-branch-price');
        var name = $('#new-name').val().trim();
        if (!name) { toastr.warning('Product name is required.', 'Required'); $('#new-name').focus(); return; }

        var baseSell, branchSell = null;
        if (_npPriceSource === 'branch') {
            var bp = $('#new-branch-price').val();
            if (!bp || parseFloat(bp) < 0) { toastr.warning('Branch selling price is required.', 'Required'); $('#new-branch-price').focus(); return; }
            // Use the branch price as both the base catalogue price and the branch override
            baseSell   = bp;
            branchSell = bp;
        } else {
            baseSell = $('#new-selling-price').val();
            if (!baseSell || parseFloat(baseSell) < 0) { toastr.warning('Selling price is required.', 'Required'); $('#new-selling-price').focus(); return; }
        }

        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST', url: '{{ route("retail.operations.baseproducts.insert") }}', timeout: 60000,
            data: { name:name, selling_price:baseSell, cost_price:$('#new-cost-price').val(), unit:$('#new-unit').val()||'Each', code:$('#new-code').val(), supplier:$('#new-supplier').val(), is_product:1, _token:'{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            success: function(bpData) {
                if (bpData.status === 201) {
                    $.ajax({
                        type: 'POST', url: '{{ route("retail.operations.branchproducts.upsert") }}', timeout: 60000,
                        data: { branch_id:BRANCH_ID, base_product_id:bpData.product.id, stock_quantity:$('#new-stock-qty').val()||0, reorder_point:0, track_stock:1, allow_negative_stock:0, is_active:1, _token:'{{ csrf_token() }}' },
                        complete: function() { $('#progressBar').hide(); self.prop('disabled', false); },
                        success: function(data) {
                            if (data.status === 201) {
                                if (branchSell !== null) {
                                    // Set branch price override
                                    $.ajax({
                                        type: 'POST', url: '{{ route("retail.operations.branchproducts.update") }}', timeout: 60000,
                                        data: { id:data.product.id, selling_price:branchSell, cost_price:null, stock_quantity:data.product.stock_quantity, reorder_point:0, track_stock:1, allow_negative_stock:0, is_active:1, _token:'{{ csrf_token() }}' },
                                        success: function(upd) {
                                            finaliseNewProduct((upd.status === 201) ? upd.product : data.product, name, baseSell);
                                        },
                                        error: function() { finaliseNewProduct(data.product, name, baseSell); }
                                    });
                                } else {
                                    finaliseNewProduct(data.product, name, baseSell);
                                }
                            } else { toastr.error(data.error || 'Failed to assign to branch.', 'Error'); }
                        },
                        error: function() { $('#progressBar').hide(); self.prop('disabled', false); handleAjaxError.apply(this, arguments); }
                    });
                } else { $('#progressBar').hide(); self.prop('disabled', false); toastr.error(bpData.error || 'Failed to create product.', 'Error'); }
            },
            error: function() { $('#progressBar').hide(); self.prop('disabled', false); handleAjaxError.apply(this, arguments); }
        });
    });

    function finaliseNewProduct(product, name, sell) {
        toastr.success('Product created and added to branch.', 'Success');
        if (window._dt) {
            if (table.row('#' + product.row).length) table.row('#' + product.row).remove();
            table.row.add($(buildRow(product))).draw(false);
        }
        allBaseProducts = []; loadBaseProducts();
        $('#new-name,#new-selling-price,#new-cost-price,#new-code,#new-branch-price').val('');
        $('#new-stock-qty').val('0'); $('#new-unit').val('Each');
        $('#addSuccessText').text('"' + name + '" added (MWK ' + parseFloat(sell).toLocaleString('en-US',{minimumFractionDigits:2}) + ').');
        $('#addSuccessNotice').show();
        $('#new-name').focus();
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CSV WIZARD
    // ════════════════════════════════════════════════════════════════════════
    window.csvGoToStep = function(step) {
        $('.csv-step').removeClass('active');
        $('#csvStep' + step).addClass('active');
        for (var i = 1; i <= 4; i++) {
            var el = document.getElementById('csi' + i);
            el.className = 'csi-step' + (i < step ? ' done' : (i === step ? ' active' : ''));
        }
    };

    window.csvStep2Next = function() {
        if (!$('#csv-supplier').val()) { toastr.warning('Select a supplier.', 'Required'); return; }
        csvGoToStep(3);
    };

    $('#csvDownloadSample').on('click', function(e) {
        e.preventDefault();
        var csv = 'name,code,unit,selling_price,cost_price,quantity\nSample Product,SKU001,Each,1500.00,1000.00,50\nAnother Product,SKU002,kg,"2,500.00","1,800.00",20\n';
        var blob = new Blob([csv], {type:'text/csv'});
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a'); a.href = url; a.download = 'branch_products_sample.csv'; a.click();
        URL.revokeObjectURL(url);
    });

    // Decodes a file as text, auto-detecting encoding: tries strict UTF-8 first
    // (handles the common case, including a BOM) and falls back to Windows-1252
    // (what Excel writes by default on "CSV (Comma delimited)" export) if the
    // bytes aren't valid UTF-8. Without this, non-ASCII bytes from an
    // Excel-exported CSV render as replacement-character "boxes" in the preview.
    function csvReadFileSmart(file, onDone) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var buf = e.target.result;
            var text;
            try {
                text = new TextDecoder('utf-8', { fatal: true }).decode(buf);
            } catch (err) {
                text = new TextDecoder('windows-1252').decode(buf);
            }
            onDone(text);
        };
        reader.onerror = function() { onDone(null); };
        reader.readAsArrayBuffer(file);
    }

    var CSV_PREVIEW_LS_KEY = 'branchproducts_csv_preview_' + BRANCH_ID;
    var CSV_UPLOAD_CHUNK_SIZE = 150; // rows per network request — keeps every POST small and lets progress advance visibly

    function csvRenderPreviewFromText(text) {
        var lines = text.split(/\r\n|\r|\n/).filter(function(l) { return l.trim() !== ''; });
        var previewLines = lines.slice(0, 50);
        var html = previewLines.map(function(line, idx) {
            var cls = idx === 0 ? 'font-weight:700;color:#4B5EBD;' : 'color:#374151;';
            return '<div class="csv-preview-row"><span style="' + cls + '">' + $('<div>').text(line).html() + '</span></div>';
        }).join('');
        $('#csvFilePreviewLabel').text((lines.length - 1) + ' row(s) detected');
        $('#csvFilePreviewScroll').html(html);
        $('#csvFilePreviewWrap').show();
    }

    // Minimal RFC4180-ish CSV parser (quoted fields, embedded commas, escaped
    // quotes, CRLF/CR/LF) — turns the raw text into row objects matching the
    // columns the server expects, so the import can be sent as small JSON
    // chunks instead of the whole file in one request.
    function csvParseRowsFromText(text) {
        text = text.replace(/^\uFEFF/, ''); // strip BOM
        var table = [];
        var row = []; var field = ''; var inQuotes = false; var i = 0, len = text.length;
        while (i < len) {
            var ch = text[i];
            if (inQuotes) {
                if (ch === '"') { if (text[i + 1] === '"') { field += '"'; i += 2; continue; } inQuotes = false; i++; continue; }
                field += ch; i++; continue;
            }
            if (ch === '"') { inQuotes = true; i++; continue; }
            if (ch === ',') { row.push(field); field = ''; i++; continue; }
            if (ch === '\r') { i++; continue; }
            if (ch === '\n') { row.push(field); field = ''; if (row.length > 1 || row[0] !== '') table.push(row); row = []; i++; continue; }
            field += ch; i++;
        }
        if (field !== '' || row.length) { row.push(field); if (row.length > 1 || row[0] !== '') table.push(row); }

        if (table.length < 2) return { error: 'CSV is empty or has no data rows.' };

        var header = table[0].map(function(h) { return h.trim().toLowerCase(); });
        var map = { name:null, code:null, unit:null, selling_price:null, cost_price:null, quantity:null };
        header.forEach(function(col, idx) {
            if (['name','product','product_name'].indexOf(col) !== -1)             map.name          = idx;
            if (['code','sku'].indexOf(col) !== -1)                                map.code          = idx;
            if (col === 'unit')                                                    map.unit          = idx;
            if (['selling_price','price','sell_price'].indexOf(col) !== -1)        map.selling_price = idx;
            if (['cost_price','cost'].indexOf(col) !== -1)                         map.cost_price    = idx;
            if (['quantity','qty','stock','stock_quantity'].indexOf(col) !== -1)   map.quantity      = idx;
        });
        if (map.name === null) return { error: 'CSV must contain a "name" column.' };

        var rows = [];
        for (var r = 1; r < table.length; r++) {
            var cols = table[r];
            var name = (cols[map.name] || '').trim();
            if (name === '') continue;
            rows.push({
                name:          name,
                code:          map.code          !== null ? (cols[map.code]          || '').trim() : '',
                unit:          map.unit          !== null ? (cols[map.unit]          || '').trim() : '',
                selling_price: map.selling_price !== null ? (cols[map.selling_price] || '').trim() : '',
                cost_price:    map.cost_price    !== null ? (cols[map.cost_price]    || '').trim() : '',
                quantity:      map.quantity      !== null ? (cols[map.quantity]      || '').trim() : ''
            });
        }
        if (!rows.length) return { error: 'No valid rows found in CSV.' };
        return { rows: rows };
    }

    var csvParsedRows = []; // in-memory rows ready to upload, built when the file is selected
    var CSV_ROWS_LS_KEY = 'branchproducts_csv_rows_' + BRANCH_ID;

    $('#csv-file').on('change', function() {
        var file = this.files[0];
        csvParsedRows = [];
        $('#csvImportBtn').prop('disabled', true);
        if (!file) { $('#csvFilePreviewWrap').hide(); return; }
        csvReadFileSmart(file, function(text) {
            if (text === null) { toastr.error('Could not read that file.', 'Error'); return; }
            csvRenderPreviewFromText(text);
            var parsed = csvParseRowsFromText(text);
            if (parsed.error) {
                toastr.error(parsed.error, 'Error');
                return;
            }
            csvParsedRows = parsed.rows;
            $('#csvImportBtn').prop('disabled', false);
            try {
                localStorage.setItem(CSV_PREVIEW_LS_KEY, text);
                localStorage.setItem(CSV_ROWS_LS_KEY, JSON.stringify(csvParsedRows));
            } catch (e) { /* storage full/unavailable — proceed in-memory only */ }
        });
    });

    // Manually wipes the cached preview text for this branch's CSV wizard.
    $('#csvClearCacheBtn').on('click', function(e) {
        e.preventDefault();
        try {
            localStorage.removeItem(CSV_PREVIEW_LS_KEY);
            localStorage.removeItem(CSV_ROWS_LS_KEY);
        } catch (e2) {}
        csvParsedRows = [];
        $('#csvFilePreviewWrap').hide();
        $('#csvFilePreviewScroll').html('');
        $('#csv-file').val('');
        $('#csvImportBtn').prop('disabled', true);
        toastr.success('Cleared locally cached CSV data.', 'Done');
    });

    /**
     * Uploads parsed rows to the server in sequential chunks of
     * CSV_UPLOAD_CHUNK_SIZE, each its own small JSON POST — mirrors the base
     * products import so large files never risk a single request timing out,
     * and the progress bar can advance after every chunk instead of sitting
     * on a spinner for the whole import.
     */
    function csvUploadRowsChunked(rows, supplierId) {
        var chunks = [];
        for (var i = 0; i < rows.length; i += CSV_UPLOAD_CHUNK_SIZE) {
            chunks.push(rows.slice(i, i + CSV_UPLOAD_CHUNK_SIZE));
        }
        var totalChunks = chunks.length;
        var aggregate = { created: 0, updated: 0, skipped: 0, skippedNames: [], failedRows: [], chunkErrors: 0 };

        $('#csvChunkProgressWrap').show();
        $('#csvChunkProgressLabel').text('Preparing ' + totalChunks + ' batch(es)…');

        function uploadOne(index) {
            if (index >= totalChunks) return Promise.resolve();

            var chunkRows = chunks[index];
            var pct = Math.round((index / totalChunks) * 100);
            $('#csvChunkProgressFill').css('width', pct + '%');
            $('#csvChunkProgressLabel').text(
                'Uploading batch ' + (index + 1) + ' of ' + totalChunks +
                ' — ' + (index * CSV_UPLOAD_CHUNK_SIZE) + '/' + rows.length + ' rows sent'
            );

            return new Promise(function(resolve) {
                $.ajax({
                    type: 'POST',
                    url:  '{{ route("retail.operations.branchproducts.csv.upload") }}',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        rows:         chunkRows,
                        branch_id:    BRANCH_ID,
                        supplier_id:  supplierId,
                        chunk_index:  index + 1,
                        total_chunks: totalChunks,
                        _token:       '{{ csrf_token() }}'
                    }),
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    timeout: 60000,
                    success: function(data) {
                        if (data.status === 200) {
                            aggregate.created += (data.created_count || 0);
                            aggregate.updated += (data.updated_count || 0);
                            aggregate.skipped += (data.skipped_count || 0);
                            if (data.skipped_names && data.skipped_names.length) {
                                aggregate.skippedNames = aggregate.skippedNames.concat(data.skipped_names);
                                // These rows were NOT saved — keep the full row (not just the
                                // name) so they can be downloaded or resubmitted, not just
                                // reported as a count.
                                var skippedSet = {};
                                data.skipped_names.forEach(function(n) { skippedSet[(n || '').toLowerCase().trim()] = true; });
                                chunkRows.forEach(function(r) {
                                    if (skippedSet[r.name.toLowerCase().trim()]) {
                                        aggregate.failedRows.push($.extend({}, r, { error: 'Could not be resolved to a base product in this category.' }));
                                    }
                                });
                            }
                        } else {
                            aggregate.chunkErrors++;
                            var errMsg = data.error || ('Batch ' + (index + 1) + ' failed.');
                            toastr.error(errMsg, 'Error');
                            // Whole batch failed server-side — none of these rows were saved.
                            chunkRows.forEach(function(r) {
                                aggregate.failedRows.push($.extend({}, r, { error: errMsg }));
                            });
                        }
                        resolve();
                    },
                    error: function(xhr, status) {
                        aggregate.chunkErrors++;
                        var msg = status === 'timeout'
                            ? 'Request timed out.'
                            : ('Network/server error (HTTP ' + (xhr && xhr.status ? xhr.status : '?') + ') for batch ' + (index + 1) + '.');
                        toastr.error(msg, 'Error');
                        chunkRows.forEach(function(r) {
                            aggregate.failedRows.push($.extend({}, r, { error: msg }));
                        });
                        resolve(); // keep going with remaining chunks regardless
                    }
                });
            }).then(function() { return uploadOne(index + 1); });
        }

        return uploadOne(0).then(function() {
            $('#csvChunkProgressFill').css('width', '100%');
            $('#csvChunkProgressLabel').text('All batches sent — ' + totalChunks + ' of ' + totalChunks);
            return aggregate;
        });
    }

    // Loads xlsx.js on demand (only needed when there are failed rows to export).
    var _csvXlsxLoading = null;
    function csvLoadXlsxLib() {
        if (window.XLSX) return Promise.resolve();
        if (_csvXlsxLoading) return _csvXlsxLoading;
        _csvXlsxLoading = new Promise(function(resolve, reject) {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
        return _csvXlsxLoading;
    }

    function csvDownloadFailedRowsAsExcel(rows) {
        csvLoadXlsxLib().then(function() {
            var sheetData = rows.map(function(r) {
                return {
                    name: r.name, code: r.code, unit: r.unit,
                    selling_price: r.selling_price, cost_price: r.cost_price, quantity: r.quantity,
                    error: r.error || 'Failed to save'
                };
            });
            var ws = XLSX.utils.json_to_sheet(sheetData);
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Failed Rows');
            XLSX.writeFile(wb, 'branch_products_failed_rows.xlsx');
        }).catch(function() {
            toastr.error('Could not load the Excel export library — check your connection.', 'Error');
        });
    }

    var csvFailedRows = []; // rows that didn't save on the last attempt — downloadable/resubmittable

    function csvRunImport(rows, supId) {
        var self = $('#csvImportBtn');
        self.prop('disabled', true);
        csvGoToStep(4);
        $('#csvImportProgress').html('<i class="ri-loader-4-line" style="font-size:32px;animation:spin 1s linear infinite;display:inline-block;"></i><div class="mt-2">Importing — please wait…</div>');
        $('#csvChunkProgressWrap').hide();
        $('#csvChunkProgressFill').css('width', '0%');
        $('#progressBar').show();

        csvUploadRowsChunked(rows, supId).then(function(aggregate) {
            $('#progressBar').hide();
            self.prop('disabled', false);

            csvFailedRows = aggregate.failedRows;

            // Only the rows that actually saved get dropped from the cache —
            // whatever's left (skipped/errored) stays cached so it survives
            // an accidental close and can be downloaded or resubmitted.
            try {
                if (csvFailedRows.length) {
                    localStorage.setItem(CSV_ROWS_LS_KEY, JSON.stringify(csvFailedRows));
                } else {
                    localStorage.removeItem(CSV_ROWS_LS_KEY);
                    localStorage.removeItem(CSV_PREVIEW_LS_KEY);
                }
            } catch (e) { /* storage full/unavailable */ }

            var savedCount = aggregate.created + aggregate.updated;
            var html = '<i class="ri-checkbox-circle-line text-success" style="font-size:38px;"></i>' +
                       '<div class="mt-2" style="font-weight:600;color:#1e293b;">' +
                       savedCount + ' of ' + rows.length + ' row(s) saved.</div>';
            if (aggregate.updated > 0) {
                html += '<div class="mt-1" style="font-size:12px;color:#6c757d;">' + aggregate.created + ' new &nbsp;·&nbsp; ' + aggregate.updated + ' updated</div>';
            }
            if (csvFailedRows.length) {
                html += '<div class="mt-2" style="font-size:12px;color:#6c757d;text-align:left;"><strong>' + csvFailedRows.length + '</strong> row(s) were not saved:</div><div class="import-skipped-list"><ul class="mb-0 ps-3">';
                $.each(csvFailedRows.slice(0, 50), function(i, r) { html += '<li>' + $('<div>').text(r.name + ' — ' + r.error).html() + '</li>'; });
                if (csvFailedRows.length > 50) html += '<li style="color:#94a3b8;">…and ' + (csvFailedRows.length - 50) + ' more</li>';
                html += '</ul></div>';
                html += '<div class="d-flex justify-content-center gap-2 mt-3">' +
                        '<button type="button" class="btn btn-outline-danger btn-sm" id="csvDownloadFailedBtn"><i class="ri-file-excel-2-line me-1"></i>Download Failed Rows (.xlsx)</button>' +
                        '<button type="button" class="btn btn-outline-primary btn-sm" id="csvResubmitFailedBtn"><i class="ri-refresh-line me-1"></i>Resubmit Failed Rows</button>' +
                        '</div>';
            }
            $('#csvImportProgress').html(html);

            if (!csvFailedRows.length) {
                toastr.success('Import complete.', 'Done');
            } else {
                toastr.warning(csvFailedRows.length + ' row(s) were not saved — download or resubmit them from this screen.', 'Import finished with issues');
            }
        });
    }

    // Delegated because these buttons are rebuilt into #csvImportProgress on every import.
    $('#csvImportProgress').on('click', '#csvDownloadFailedBtn', function(e) {
        e.preventDefault();
        if (!csvFailedRows.length) { toastr.info('No failed rows to download.', 'Info'); return; }
        csvDownloadFailedRowsAsExcel(csvFailedRows);
    });
    $('#csvImportProgress').on('click', '#csvResubmitFailedBtn', function(e) {
        e.preventDefault();
        var supId = $('#csv-supplier').val();
        if (!supId) { toastr.warning('Select a supplier first.', 'Required'); csvGoToStep(2); return; }
        if (!csvFailedRows.length) { toastr.info('Nothing to resubmit.', 'Info'); return; }
        csvRunImport(csvFailedRows, supId);
    });

    $('#csvImportBtn').on('click', function() {
        var supId = $('#csv-supplier').val();
        if (!supId)                { toastr.warning('Select a supplier first.', 'Required'); csvGoToStep(2); return; }
        if (!csvParsedRows.length) { toastr.warning('Choose a CSV file.', 'Required'); return; }
        csvRunImport(csvParsedRows, supId);
    });

    $('#csvDoneBtn').on('click', function() { $('#addProductModal').modal('hide'); setTimeout(function() { location.reload(); }, 200); });

    // ════════════════════════════════════════════════════════════════════════
    //  VIEW MODAL
    // ════════════════════════════════════════════════════════════════════════
    var _viewData = {};

    $('#tbody').on('click', '.viewDataBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        _viewData = {
            id:b.data('id'), name:b.data('name'), code:b.data('code'), unit:b.data('unit'), supplier:b.data('supplier'),
            barcode:b.data('barcode'), batch:b.data('batch'), expiry:b.data('expiry'),
            cost:b.data('cost'), sell:b.data('sell'), stock:b.data('stock'), reorder:b.data('reorder'),
            reorderQty:b.data('reorder-qty'), max:b.data('max'), active:b.data('active'),
            track:b.data('track'), neg:b.data('neg'),
            sellIsBranch:b.data('sell-is-branch'), costIsBranch:b.data('cost-is-branch'),
            bpSell:b.data('bp-sell'), bpCost:b.data('bp-cost'),
            editRow:b.closest('tr').attr('id')
        };
        function mv(v) { return (v===''||v===null||v===undefined)?'<span class="text-muted fst-italic">—</span>':v; }
        $('#vw-name').text(_viewData.name);
        $('#vw-meta-line').text([_viewData.code?'Code: '+_viewData.code:'', _viewData.unit, _viewData.supplier].filter(Boolean).join(' · '));
        $('#vw-badges').html(parseInt(_viewData.active)===1?'<span class="badge bg-success">Active</span>':'<span class="badge bg-danger">Inactive</span>');
        var ib = parseInt(_viewData.sellIsBranch)===1;
        var noticeText = ib
            ? 'Selling price is a <strong>branch override</strong> (blue). Set via Edit modal.'
            : 'Using base catalogue price' + (_viewData.bpSell ? ' (MWK ' + parseFloat(_viewData.bpSell).toLocaleString('en-US',{minimumFractionDigits:2}) + ')' : '') + ' (green).';
        $('#vw-price-notice-text').html(noticeText);
        $('#vw-price-notice').show();
        var sellClass = ib ? 'price-branch' : 'price-base';
        var displaySell = (ib && _viewData.sell !== '' && _viewData.sell !== null) ? _viewData.sell : _viewData.bpSell;
        $('#vw-sell').html('<span class="' + sellClass + '">' + fmtNum(displaySell, 2) + '</span>');
        $('#vw-cost').text(fmtNum(_viewData.cost || _viewData.bpCost, 2));
        var sq = parseFloat(_viewData.stock), rp = parseFloat(_viewData.reorder || 0);
        var sc = sq <= 0 ? 'stock-zero' : (sq <= rp ? 'stock-low' : 'stock-ok');
        $('#vw-stock').html('<span class="fw-bold ' + sc + '" style="font-size:15px">' + fmtNum(sq, 2) + '</span>');
        $('#vw-reorder').text(fmtNum(_viewData.reorder, 2));
        $('#vw-reorder-qty').html(mv(fmtNum(_viewData.reorderQty, 2)));
        $('#vw-max').html(mv(fmtNum(_viewData.max, 2)));
        $('#vw-barcode').html(mv(_viewData.barcode));
        $('#vw-batch').html(mv(_viewData.batch));
        $('#vw-expiry').html(mv(_viewData.expiry));
        $('#vw-track').html(yn(_viewData.track));
        $('#vw-neg').html(yn(_viewData.neg));
        $('button[data-bs-target="#vw-t1"]').tab('show');
        $('#viewProductModal').modal('show');
    });

    $('#vwEditBtn').on('click', function(e) {
        e.preventDefault();
        $('#viewProductModal').modal('hide');
        setTimeout(function() { $('#' + _viewData.editRow).find('.editDataBtn').trigger('click'); }, 350);
    });

    // ════════════════════════════════════════════════════════════════════════
    //  EDIT MODAL — tab switching
    // ════════════════════════════════════════════════════════════════════════
    window.switchEditTab = function(n) {
        $('.edit-tab-pane').removeClass('em-show');
        $('.edit-modal-tab-btn').removeClass('em-active');
        $('#emTab' + n).addClass('em-show');
        $('#emTab' + n + 'Btn').addClass('em-active');
        if (n === 3) { $('#submitBaseProductBtn').show(); $('#submitEditBtn').hide(); }
        else         { $('#submitEditBtn').show(); $('#submitBaseProductBtn').hide(); }
    };

    // ── Price source toggle (edit modal) ──────────────────────────────────
    // One price only: base mode shows no input (card value is informational),
    // branch mode shows a single sell price input.
    window._editPriceSource      = 'base';
    window._editBpSellStored     = '';
    window._editBranchSellStored = '';

    window.setEditPriceSource = function(src) {
        window._editPriceSource = src;

        var cardBase   = document.getElementById('editPscBase');
        var cardBranch = document.getElementById('editPscBranch');
        var dotBranch  = cardBranch.querySelector('.psc-dot-branch');
        var labelBranch = cardBranch.querySelector('.psc-label-branch');
        var valBranch  = cardBranch.querySelector('.psc-val-branch');
        var fields     = document.getElementById('editBranchPriceFields');

        if (src === 'base') {
            // Base active: green border on base card, reset branch card
            cardBase.className   = 'psc psc-active-base';
            cardBranch.className = 'psc';
            dotBranch.style.opacity   = '.35';
            labelBranch.style.color   = '#9ca3af';
            valBranch.style.color     = '#9ca3af';
            fields.style.display      = 'none';
        } else {
            // Branch active: blue border on branch card, reset base card
            cardBranch.className = 'psc psc-active-branch';
            cardBase.className   = 'psc';
            dotBranch.style.opacity   = '1';
            labelBranch.style.color   = '#1d4ed8';
            valBranch.style.color     = '#1d4ed8';
            fields.style.display      = 'block';
            // Auto-fill with existing branch price if available, otherwise base price
            if (!$('#editSellPrice').val()) {
                var prefill = window._editBranchSellStored || window._editBpSellStored || '';
                if (prefill) $('#editSellPrice').val(parseFloat(prefill).toFixed(2));
            }
            setTimeout(function() { $('#editSellPrice').focus(); }, 50);
        }
    };

    $('#tbody').on('click', '.editDataBtn', function(e) {
        e.preventDefault();
        var b        = $(this);
        var nm       = b.data('name');
        var unit     = b.data('unit') || '—';
        var code     = b.data('code') || '';
        var supplier = b.data('supplier') || '';
        var sellIsBr = parseInt(b.data('sell-is-branch')) === 1;
        var bpId     = b.data('base-product-id') || '';
        var bpSell   = b.data('bp-sell') || '';
        var bpCost   = b.data('bp-cost') || '';
        var brSell   = sellIsBr ? (b.data('sell') || '') : '';

        window._editBpSellStored     = bpSell;
        window._editBranchSellStored = brSell;

        $('#editId').val(b.data('id'));
        $('#editRow').val(b.data('row'));
        $('#editBaseProductId').val(bpId);
        $('#bpEditId').val(bpId);
        $('#editModalName').text(nm);
        $('#edit-ro-name').val(nm);
        $('#edit-ro-unit').val(unit !== '—' ? unit : '');

        $('#editStockQty').val(b.data('stock') !== '' && b.data('stock') !== undefined ? parseFloat(b.data('stock')).toFixed(2) : '');
        $('#editStockReason').val('');
        $('#editReorderPoint').val(b.data('reorder') !== '' ? parseFloat(b.data('reorder')).toFixed(2) : '');
        $('#editReorderQty').val(b.data('reorder-qty') !== '' && b.data('reorder-qty') !== null ? parseFloat(b.data('reorder-qty')).toFixed(2) : '');
        $('#editMaxStock').val(b.data('max') !== '' && b.data('max') !== null ? parseFloat(b.data('max')).toFixed(2) : '');
        $('#editCostPrice').val(b.data('cost') !== '' && b.data('cost') !== null ? parseFloat(b.data('cost')).toFixed(2) : '');
        $('#editBarcode').val(b.data('barcode'));
        $('#editBatch').val(b.data('batch'));
        $('#editExpiry').val(b.data('expiry'));
        $('#editTrackStock').prop('checked', parseInt(b.data('track'))  === 1);
        $('#editAllowNeg').prop('checked',   parseInt(b.data('neg'))    === 1);
        $('#editIsActive').prop('checked',   parseInt(b.data('active')) === 1);

        // Card values
        var bpFmt = bpSell ? 'MWK ' + parseFloat(bpSell).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}) : '—';
        document.getElementById('editPscBaseVal').textContent = bpFmt;
        if (sellIsBr && brSell !== '') {
            document.getElementById('editPscBranchVal').textContent = 'MWK ' + parseFloat(brSell).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
        } else {
            document.getElementById('editPscBranchVal').textContent = '—';
        }

        // Pre-fill price input, set source
        $('#editSellPrice').val(sellIsBr && brSell !== '' ? parseFloat(brSell).toFixed(2) : '');
        setEditPriceSource(sellIsBr ? 'branch' : 'base');

        // Base tab
        $('#bpEditName').val(nm);
        $('#bpEditUnit').val(unit !== '—' ? unit : '');
        $('#bpEditCode').val(code);
        $('#bpEditSupplier').val(supplier);
        $('#bpEditSellPrice').val(bpSell);
        $('#bpEditCostPrice').val(bpCost);

        switchEditTab(1);
        $('#editDataModal').modal('show');
    });

    $('#cancelEditBtn').on('click', function(e) { e.preventDefault(); $('#editDataModal').modal('hide'); });

    $('#submitEditBtn').on('click', function(e) {
        e.preventDefault();
        normaliseNumericInputs('#editSellPrice,#editCostPrice,#editStockQty,#editReorderPoint,#editReorderQty,#editMaxStock');

        var useBranch = (window._editPriceSource === 'branch');
        // When branch: send branch sell price. When base: send null so controller stores null.
        var sell = useBranch ? $('#editSellPrice').val() : null;
        // Cost price always goes to base product only — sent separately via base product update.
        // Here we still pass it so the branch product record can store cost_price for margin calcs.
        var cost = $('#editCostPrice').val() || null;

        if (useBranch && (!sell || parseFloat(sell) < 0)) {
            toastr.warning('Selling price is required when using branch price.', 'Required');
            $('#editSellPrice').focus(); return;
        }

        var reason = $('#editStockReason').val().trim() || null;
        var self   = $(this); self.prop('disabled', true);
        var row    = $('#editRow').val();

        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.branchproducts.update") }}', timeout:60000,
            data: {
                id: $('#editId').val(),
                selling_price: sell,        // null = use base; value = branch override
                cost_price: null,           // cost handled separately via base product
                stock_quantity: $('#editStockQty').val(),
                reorder_point: $('#editReorderPoint').val(),
                reorder_quantity: $('#editReorderQty').val(),
                max_stock: $('#editMaxStock').val(),
                primary_barcode: $('#editBarcode').val(),
                batch_number: $('#editBatch').val(),
                expiry_date: $('#editExpiry').val(),
                track_stock: $('#editTrackStock').prop('checked') ? 1 : 0,
                allow_negative_stock: $('#editAllowNeg').prop('checked') ? 1 : 0,
                is_active: $('#editIsActive').prop('checked') ? 1 : 0,
                stock_change_reason: reason,
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); },
            success: function(data) {
                if (data.status === 201) {
                    // If cost price was provided, update the base product cost too
                    if (cost && cost !== '' && parseFloat(cost) >= 0) {
                        var bpId = $('#editBaseProductId').val();
                        if (bpId) {
                            $.ajax({
                                type:'POST', url:'{{ route("retail.operations.baseproducts.update") }}', timeout:30000,
                                data: {
                                    id: bpId,
                                    name: $('#edit-ro-name').val(),
                                    unit: $('#edit-ro-unit').val(),
                                    selling_price: $('#editPscBaseVal').text().replace('MWK ','').replace(/,/g,'') || data.product.bp_sell || 0,
                                    cost_price: cost,
                                    _token: '{{ csrf_token() }}'
                                },
                                complete: function() { self.prop('disabled', false); }
                            });
                        } else { self.prop('disabled', false); }
                    } else { self.prop('disabled', false); }

                    toastr.success(data.success, 'Success');
                    table.row('#' + row).remove();
                    table.row.add($(buildRow(data.product))).draw(false);
                    updateSelectedCount();
                    $('#editDataModal').modal('hide');
                } else { self.prop('disabled', false); toastr.error(data.error || 'Error.', 'Error'); }
            },
            error: function() { self.prop('disabled', false); handleAjaxError.apply(this, arguments); }
        });
    });

    $('#submitBaseProductBtn').on('click', function(e) {
        e.preventDefault();
        normaliseNumericInputs('#bpEditSellPrice,#bpEditCostPrice');
        var name = $('#bpEditName').val().trim();
        if (!name) { toastr.warning('Product name is required.', 'Required'); $('#bpEditName').focus(); return; }
        var sell = $('#bpEditSellPrice').val();
        if (!sell || parseFloat(sell) < 0) { toastr.warning('Selling price is required.', 'Required'); $('#bpEditSellPrice').focus(); return; }
        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.baseproducts.update") }}', timeout:60000,
            data: { id:$('#bpEditId').val(), name:name, unit:$('#bpEditUnit').val(), code:$('#bpEditCode').val(), supplier:$('#bpEditSupplier').val(), selling_price:sell, cost_price:$('#bpEditCostPrice').val(), branch_product_id:$('#editId').val(), _token:'{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success || 'Base product updated.', 'Success');
                    $('#edit-ro-name').val(name); $('#edit-ro-unit').val($('#bpEditUnit').val());
                    $('#editModalName').text(name);
                    window._editBpSellStored = sell;
                    document.getElementById('editPscBaseVal').textContent = 'MWK ' + parseFloat(sell).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
                    if (data.product) {
                        var row = $('#editRow').val();
                        if (table.row('#' + row).length) { table.row('#' + row).remove(); table.row.add($(buildRow(data.product))).draw(false); }
                    }
                    allBaseProducts = []; loadBaseProducts();
                    switchEditTab(1);
                } else { toastr.error(data.error || 'Error.', 'Error'); }
            },
            error: handleAjaxError
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  DELETE
    // ════════════════════════════════════════════════════════════════════════
    $('#tbody').on('click', '.deleteDataBtn', function(e) {
        e.preventDefault();
        $('#deleteLabel').text($(this).data('label'));
        $('#deleteRow').val($(this).data('row'));
        $('#deleteId').val($(this).data('id'));
        $('#deleteModal').modal('show');
    });
    $('#keepBtn').on('click', function(e) { e.preventDefault(); $('#deleteModal').modal('hide'); });
    $('#submitDeleteBtn').on('click', function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row = $('#deleteRow').val(), id = $('#deleteId').val();
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.branchproducts.delete") }}', timeout:60000,
            data:{ id:id, _token:'{{ csrf_token() }}' },
            beforeSend:function(){$('#progressBar').show();},
            complete:function(){$('#progressBar').hide();self.prop('disabled',false);},
            success:function(data){
                if(data.status===201){toastr.success(data.success,'Success');table.row('#'+row).remove().draw(false);updateSelectedCount();$('#deleteModal').modal('hide');}
                else{toastr.error(data.error||'Failed.','Error');}
            },
            error:handleAjaxError
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  BULK SELECTION
    // ════════════════════════════════════════════════════════════════════════
    $('#selectAll').on('change', function() { $('.selectRow').prop('checked', this.checked); updateSelectedCount(); });
    $('#tbody').on('click', '.selectRow', function() { updateSelectedCount(); });
    function getSelectedIds()  { var ids=[]; $('.selectRow:checked').each(function(){ ids.push($(this).val()); }); return ids; }
    function getSelectedRows() { var rows=[]; $('.selectRow:checked').each(function(){ rows.push($(this).data('row-id')); }); return rows; }

    $('#bulkActionsHeaderBtn').on('click', function() {
        if (!$(this).hasClass('enabled')) return;
        $('#bulkActionsModalCountText').text('— ' + $('.selectRow:checked').length + ' selected');
        $('#bulkActionsModal').modal('show');
    });

    $('#boUseBasePrices').on('click', function() {
        var ids = getSelectedIds();
        if (!ids.length) { toastr.warning('No products selected.', 'Warning'); return; }
        $('#bulkActionsModal').modal('hide');
        $('#confirmUseBaseCount').text(ids.length);
        setTimeout(function(){ $('#confirmUseBaseModal').modal('show'); }, 250);
    });
    $('#confirmUseBaseSubmitBtn').on('click', function() {
        var ids = getSelectedIds(); if (!ids.length) { $('#confirmUseBaseModal').modal('hide'); return; }
        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.branchproducts.bulk.usebaseprices") }}', timeout:120000,
            data:{ids:ids,_token:'{{ csrf_token() }}'},
            beforeSend:function(){$('#progressBar').show();},
            complete:function(){$('#progressBar').hide();self.prop('disabled',false);},
            success:function(data){
                if(data.status===201){toastr.success(data.success,'Success');$.each(data.products,function(i,p){table.row('#'+p.row).remove();table.row.add($(buildRow(p)));});table.draw(false);updateSelectedCount();$('#confirmUseBaseModal').modal('hide');}
                else{toastr.error(data.error||'Failed.','Error');}
            },error:handleAjaxError
        });
    });

    $('#boSetBranchPrices').on('click', function() {
        var ids = getSelectedIds();
        if (!ids.length) { toastr.warning('No products selected.', 'Warning'); return; }
        $('#bulkActionsModal').modal('hide');
        var html = '';
        $('.selectRow:checked').each(function() {
            var cb=$(this), id=cb.val(), name=cb.data('name'), unit=cb.data('unit'), bpSell=cb.data('bp-sell'), sellNow=cb.data('sell'), sellIsBranch=cb.data('sell-is-branch');
            var bpFmt = (bpSell!==''&&bpSell!==null&&bpSell!==undefined)?parseFloat(bpSell).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}):null;
            var metaLine = unit || ''; if(bpFmt) metaLine += (metaLine?' &middot; ':'') + 'Base: <span style="color:#059669;font-weight:700;">'+bpFmt+'</span>';
            var hasBranch = (sellIsBranch==1&&sellNow!==''&&sellNow!==null&&sellNow!==undefined);
            var prefill = hasBranch ? parseFloat(sellNow).toFixed(2) : '';
            html += `<tr data-id="${id}" data-bp-sell="${bpSell||''}"><td><div style="font-size:13px;font-weight:600;color:#1e293b;">${name}</div><div style="font-size:11px;color:#6c757d;margin-top:1px;">${metaLine}</div></td><td style="text-align:center;"><input type="text" inputmode="decimal" class="sbp-input" id="sbp_price_${id}" placeholder="0.00" value="${prefill}" data-autofilled="0" autocomplete="off"></td></tr>`;
        });
        $('#sbpProductList').html(html);
        $('#sbpCount').text(ids.length);
        setTimeout(function(){ $('#setBranchPricesModal').modal('show'); setTimeout(function(){ $('#sbpProductList .sbp-input').first().focus(); }, 350); }, 250);
    });

    $('#sbpFillBaseBtn').on('click', function() {
        var filled = 0;
        $('#sbpProductList tr').each(function() {
            var bp=$(this).data('bp-sell'), id=$(this).data('id');
            if(bp!==''&&bp!==null&&bp!==undefined){$('#sbp_price_'+id).val(parseFloat(bp).toFixed(2)).attr('data-autofilled','1');filled++;}
        });
        if(filled>0) toastr.info(filled+' input(s) filled with base prices.','Filled'); else toastr.warning('No base prices available.','Nothing to fill');
    });

    $('#sbpClearAllBtn').on('click', function() { $('#sbpProductList .sbp-input').val('').attr('data-autofilled','0'); });

    $('#sbpSubmitBtn').on('click', function() {
        normaliseNumericInputs('.sbp-input');
        var items=[];
        $('#sbpProductList tr').each(function(){
            var id=$(this).data('id'), input=$('#sbp_price_'+id), val=input.val(), autofilled=input.attr('data-autofilled')==='1', parsed=parseFloat(val), hasValue=(val!==''&&!isNaN(parsed));
            if(!hasValue&&!autofilled) return;
            var price; if(hasValue){price=parsed;}else if(autofilled){var bp=$(this).data('bp-sell');price=(bp!==''&&bp!==null)?parseFloat(bp):null;}
            if(price===null||isNaN(price)) return;
            items.push({id:parseInt(id),price:price});
        });
        if(!items.length){toastr.warning('No prices to save.','Nothing to save');return;}
        var self=$(this); self.prop('disabled',true);
        $.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.branchproducts.bulk.setbranchprices") }}', timeout:120000,
            contentType:'application/json', data:JSON.stringify({items:items,_token:'{{ csrf_token() }}'}),
            beforeSend:function(){$('#progressBar').show();},
            complete:function(){$('#progressBar').hide();self.prop('disabled',false);},
            success:function(data){
                if(data.status===201){toastr.success(data.success,'Success');$.each(data.products,function(i,p){table.row('#'+p.row).remove();table.row.add($(buildRow(p)));});table.draw(false);updateSelectedCount();$('#setBranchPricesModal').modal('hide');}
                else{toastr.error(data.error||'Failed.','Error');}
            },error:handleAjaxError
        });
    });

    $('#boBulkDelete').on('click', function() {
        var ids=getSelectedIds(); if(!ids.length){toastr.warning('No products selected.','Warning');return;}
        $('#bulkActionsModal').modal('hide');
        $('#confirmBulkDeleteCount').text(ids.length);
        setTimeout(function(){$('#confirmBulkDeleteModal').modal('show');},250);
    });
    $('#confirmBulkDeleteSubmitBtn').on('click', function() {
        var ids=getSelectedIds(), rows=getSelectedRows(); if(!ids.length){$('#confirmBulkDeleteModal').modal('hide');return;}
        var self=$(this); self.prop('disabled',true);
        $.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.branchproducts.bulkdelete") }}', timeout:120000,
            data:{ids:ids,_token:'{{ csrf_token() }}'},
            beforeSend:function(){$('#progressBar').show();},
            complete:function(){$('#progressBar').hide();self.prop('disabled',false);},
            success:function(data){
                if(data.status===201){toastr.success(data.success,'Success');rows.forEach(function(r){table.row('#'+r).remove();});table.draw(false);updateSelectedCount();$('#confirmBulkDeleteModal').modal('hide');}
                else{toastr.error(data.error||'Failed.','Error');}
            },error:handleAjaxError
        });
    });

    @endif

    $('#infoBtn').on('click', function(e) { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').on('click', function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });
});

function switchOverviewTab(tab) {
    document.getElementById('ovTabShop').style.display  = tab==='shop' ? '' : 'none';
    document.getElementById('ovTabPrice').style.display = tab==='price'? '' : 'none';
    document.getElementById('ovTabShopBtn').className   = 'overview-tab-btn' + (tab==='shop' ? ' active' : '');
    document.getElementById('ovTabPriceBtn').className  = 'overview-tab-btn' + (tab==='price'? ' active' : '');
}
</script>
@endsection