@extends('operations.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    $branchId = request('branch_id');
    $date     = request('date');

    if (! $branchId || ! is_numeric($branchId) || ! $date || ! strtotime($date)) {
        abort(404);
    }

    $branchId = (int) $branchId;

    $summary = DB::connection('tenant')->table('retail_fullstocktaking_summary')
        ->where('branch_id', $branchId)->where('date', $date)->first();

    if (! $summary) {
        abort(404, 'No stocktaking history found for this branch and date.');
    }

    $branchName  = DB::connection('tenant')->table('branches')->where('id', $branchId)->value('name');
    $countedRows = DB::connection('tenant')->table('retail_fullstocktaking')
        ->where('branch_id', $branchId)->where('date', $date)->orderBy('product_name')->get();
    $missingRows = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
        ->where('branch_id', $branchId)->where('date', $date)->orderBy('product_name')->get();

    $displayDate = Carbon::parse($date)->format('d F Y');
    $title       = $branchName . ' Full Stocktaking ' . Carbon::parse($date)->format('d M Y');
@endphp

<style>
.card-header { padding: 0.5rem 1.5rem !important; background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; border-top-left-radius: 10px; border-top-right-radius: 10px; }
.card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; }
.hd-summary-strip { display: flex; flex-wrap: wrap; background: #f4f6ff; border-bottom: 1.5px solid #e4e7f5; }
.hd-strip-seg { flex: 1; min-width: 130px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 12px 10px; }
.hd-strip-seg.accent { background: #eff3ff; }
.hd-strip-label { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #94a3b8; margin-bottom: 3px; }
.hd-strip-val { font-size: 15px; font-weight: 800; color: #1e293b; }
.hd-strip-seg.accent .hd-strip-val { color: #3b4fa0; }
.hd-section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #475569; padding: 14px 16px 6px; }
.hd-table-wrap { padding: 0 16px 20px; overflow-x: auto; }
table.hd-table { width: 100%; font-size: 12.5px; border-collapse: collapse; }
table.hd-table th { background: #e2e2e9; padding: 8px 10px; text-align: center; }
table.hd-table th:first-child, table.hd-table td:first-child { text-align: left; }
table.hd-table td { padding: 8px 10px; text-align: center; border-bottom: 1px solid #f1f5f9; }
.hd-diff-pos { color: #059669; font-weight: 700; }
.hd-diff-neg { color: #dc2626; font-weight: 700; }
.hd-diff-zero { color: #64748b; }
</style>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="ri-archive-2-line me-1"></i> {{ $title }}</h4>
        <a href="{{ route('retail.operations.fullstocktaking.history') }}" class="btn btn-light text-primary fs-16" title="Back to History"><i class="ri-arrow-left-line"></i></a>
    </div>

    <div class="hd-summary-strip">
        <div class="hd-strip-seg"><span class="hd-strip-label">Counted</span><span class="hd-strip-val">{{ $summary->products_counted }}</span></div>
        <div class="hd-strip-seg"><span class="hd-strip-label">No Anomaly</span><span class="hd-strip-val">{{ $summary->products_no_anomaly }}</span></div>
        <div class="hd-strip-seg"><span class="hd-strip-label">Overage</span><span class="hd-strip-val">{{ $summary->products_overage }}</span></div>
        <div class="hd-strip-seg"><span class="hd-strip-label">Shortage</span><span class="hd-strip-val">{{ $summary->products_shortage }}</span></div>
        <div class="hd-strip-seg"><span class="hd-strip-label">Expected (EV)</span><span class="hd-strip-val">{{ number_format($summary->expected_value, 2) }}</span></div>
        <div class="hd-strip-seg"><span class="hd-strip-label">Found (FV)</span><span class="hd-strip-val">{{ number_format($summary->found_value, 2) }}</span></div>
        <div class="hd-strip-seg accent"><span class="hd-strip-label">Full Difference</span><span class="hd-strip-val">{{ number_format($summary->full_difference_value, 2) }}</span></div>
    </div>

    <div class="hd-section-title"><i class="ri-table-line"></i> Counted Products</div>
    <div class="hd-table-wrap">
        <table class="hd-table">
            <thead><tr><th>Product</th><th>Unit</th><th>Price</th><th>Expected</th><th>Final Expected</th><th>Found</th><th>Difference</th><th>Merges</th></tr></thead>
            <tbody>
                @forelse($countedRows as $r)
                @php
                    $finalExpected = $r->expected_final ?? $r->expected_at_count;
                    $diff = $r->found - $finalExpected;
                @endphp
                <tr>
                    <td>{{ $r->product_name }}</td>
                    <td>{{ $r->unit }}</td>
                    <td>{{ number_format($r->price, 2) }}</td>
                    <td>{{ number_format($r->expected_at_count, 2) }}</td>
                    <td>{{ number_format($finalExpected, 2) }}</td>
                    <td>{{ number_format($r->found, 2) }}</td>
                    <td class="{{ $diff > 0 ? 'hd-diff-pos' : ($diff < 0 ? 'hd-diff-neg' : 'hd-diff-zero') }}">{{ number_format($diff, 2) }}</td>
                    <td>{{ $r->merge_count }}</td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;color:#94a3b8;">No counted products.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="hd-section-title"><i class="ri-error-warning-line"></i> Missing Products</div>
    <div class="hd-table-wrap">
        <table class="hd-table">
            <thead><tr><th>Product</th><th>Unit</th><th>Price</th><th>Quantity</th><th>Value</th></tr></thead>
            <tbody>
                @forelse($missingRows as $m)
                <tr>
                    <td>{{ $m->product_name }}</td>
                    <td>{{ $m->unit }}</td>
                    <td>{{ number_format($m->price, 2) }}</td>
                    <td>{{ number_format($m->quantity, 2) }}</td>
                    <td>{{ number_format($m->quantity * $m->price, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:#94a3b8;">No missing products for this date.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div></div></div>

@endsection