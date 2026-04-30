@php
    use Illuminate\Support\Facades\DB;
    use Carbon\Carbon;

    $now            = Carbon::now();
    $startOfMonth   = $now->copy()->startOfMonth();
    $endOfMonth     = $now->copy()->endOfMonth();
    $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
    $lastMonthEnd   = $now->copy()->subMonth()->endOfMonth();
    $sevenDaysAhead = $now->copy()->addDays(7)->toDateString();
    $today          = $now->toDateString();

    // ── Tenant KPIs ──────────────────────────────────────────────
    $tenantStats = DB::table('tenants')
        ->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Approved' AND put_on_hold = 'No'  THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'Pending'                          THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN put_on_hold = 'Yes'                         THEN 1 ELSE 0 END) as on_hold
        ")
        ->first();

    $tenantStats = $tenantStats ?? (object)['total'=>0,'active'=>0,'pending'=>0,'on_hold'=>0];

    // ── Revenue KPIs ─────────────────────────────────────────────
    $revenueStats = DB::table('tenant_invoices')
        ->selectRaw("
            SUM(CASE WHEN status = 'Paid'                                                    THEN amount ELSE 0 END) as total_collected,
            SUM(CASE WHEN status = 'Paid'   AND created_at BETWEEN ? AND ?                  THEN amount ELSE 0 END) as this_month,
            SUM(CASE WHEN status = 'Paid'   AND created_at BETWEEN ? AND ?                  THEN amount ELSE 0 END) as last_month,
            SUM(CASE WHEN status = 'Overdue'                                                 THEN amount ELSE 0 END) as overdue_amount,
            COUNT(CASE WHEN status = 'Overdue' THEN 1 END)                                                          as overdue_count,
            COUNT(CASE WHEN status = 'Pending' THEN 1 END)                                                          as pending_invoices
        ", [$startOfMonth, $endOfMonth, $lastMonthStart, $lastMonthEnd])
        ->first();

    $revenueStats = $revenueStats ?? (object)[
        'total_collected'=>0,'this_month'=>0,'last_month'=>0,
        'overdue_amount'=>0,'overdue_count'=>0,'pending_invoices'=>0,
    ];

    // ── Upcoming payments – next 7 days ───────────────────────────
    $upcomingPayments = DB::table('tenants')
        ->select('id', 'full_name', 'business_name', 'subscription_plan', 'payment_amount', 'next_payment_date')
        ->whereBetween('next_payment_date', [$today, $sevenDaysAhead])
        ->where('status', 'Approved')
        ->orderBy('next_payment_date')
        ->limit(7)
        ->get();

    // ── Recent tenants ────────────────────────────────────────────
    $recentTenants = DB::table('tenants')
        ->select('id', 'full_name', 'business_name', 'email', 'status', 'subscription_plan', 'created_at')
        ->orderByDesc('created_at')
        ->limit(7)
        ->get();

    // ── Overdue tenants ───────────────────────────────────────────
    $overdueList = DB::table('tenant_invoices as i')
        ->join('tenants as t', 't.id', '=', 'i.tenant_id')
        ->select(
            't.id', 't.business_name', 't.full_name',
            DB::raw('SUM(i.amount) as total_overdue'),
            DB::raw('COUNT(i.id) as invoice_count'),
            DB::raw('MIN(i.due_date) as oldest_due')
        )
        ->where('i.status', 'Overdue')
        ->groupBy('t.id', 't.business_name', 't.full_name')
        ->orderByDesc('total_overdue')
        ->limit(6)
        ->get();

@endphp

<!-- ============================================================== -->
<!-- Start Page Content here -->
<!-- ============================================================== -->

<style>
.quick-action-item {
    transition: background-color .15s ease, border-color .15s ease;
    border-color: var(--bs-border-color) !important;
}
.quick-action-item:hover {
    background-color: var(--bs-light);
    border-color: var(--bs-primary) !important;
}
</style>

<div class="content-page">
    <div class="content">

        <!-- Start Content-->
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box justify-content-between d-flex align-items-lg-center flex-lg-row flex-column">
                        <h4 class="page-title">Dashboard</h4>
                        <p class="text-muted mb-0">{{ $now->format('l, d F Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- ── ROW 1 · 6 KPI WIDGETS (ordered by priority) ── --}}
            <div class="row row-cols-1 row-cols-xxl-6 row-cols-lg-3 row-cols-md-2">

                {{-- 1. Pending Approval --}}
                <div class="col">
                    <div class="card widget-icon-box">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="flex-grow-1 overflow-hidden">
                                    <h5 class="text-muted text-uppercase fs-13 mt-0">Pending Approval</h5>
                                    <h3 class="my-3">{{ number_format($tenantStats->pending) }}</h3>
                                    <p class="mb-0 text-muted text-truncate">
                                        <span class="badge bg-warning me-1">
                                            <i class="ri-time-line"></i> {{ $tenantStats->on_hold }}
                                        </span>
                                        <span>On hold</span>
                                    </p>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title text-bg-warning rounded rounded-3 fs-3 widget-icon-box-avatar shadow">
                                        <i class="ri-hourglass-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. On Hold --}}
                <div class="col">
                    <div class="card widget-icon-box">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="flex-grow-1 overflow-hidden">
                                    <h5 class="text-muted text-uppercase fs-13 mt-0">On Hold</h5>
                                    <h3 class="my-3">{{ number_format($tenantStats->on_hold) }}</h3>
                                    <p class="mb-0 text-muted text-truncate">
                                        <span class="badge bg-secondary me-1">
                                            <i class="ri-pause-circle-line"></i> Paused
                                        </span>
                                        <span>Tenants paused</span>
                                    </p>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title text-bg-secondary rounded rounded-3 fs-3 widget-icon-box-avatar shadow">
                                        <i class="ri-pause-circle-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. Approved / Active --}}
                <div class="col">
                    <div class="card widget-icon-box">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="flex-grow-1 overflow-hidden">
                                    <h5 class="text-muted text-uppercase fs-13 mt-0">Approved</h5>
                                    <h3 class="my-3">{{ number_format($tenantStats->active) }}</h3>
                                    <p class="mb-0 text-muted text-truncate">
                                        <span class="badge bg-success me-1">
                                            <i class="ri-building-line"></i> {{ $tenantStats->total }}
                                        </span>
                                        <span>Total tenants</span>
                                    </p>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title text-bg-success rounded rounded-3 fs-3 widget-icon-box-avatar shadow">
                                        <i class="ri-building-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. Due This Month --}}
                <div class="col">
                    <div class="card widget-icon-box">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="flex-grow-1 overflow-hidden">
                                    <h5 class="text-muted text-uppercase fs-13 mt-0">Due This Month</h5>
                                    <h3 class="my-3">{{ number_format($revenueStats->this_month, 2) }}</h3>
                                    <p class="mb-0 text-muted text-truncate">
                                        <span class="badge bg-info me-1">
                                            <i class="ri-calendar-check-line"></i> {{ $revenueStats->pending_invoices }}
                                        </span>
                                        <span>Pending invoices</span>
                                    </p>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title text-bg-info rounded rounded-3 fs-3 widget-icon-box-avatar shadow">
                                        <i class="ri-calendar-check-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5. Paid Last Month --}}
                <div class="col">
                    <div class="card widget-icon-box">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="flex-grow-1 overflow-hidden">
                                    <h5 class="text-muted text-uppercase fs-13 mt-0">Paid Last Month</h5>
                                    <h3 class="my-3">{{ number_format($revenueStats->last_month, 2) }}</h3>
                                    <p class="mb-0 text-muted text-truncate">
                                        <span class="badge bg-danger me-1">
                                            <i class="ri-error-warning-line"></i> {{ $revenueStats->overdue_count }}
                                        </span>
                                        <span>Overdue invoices</span>
                                    </p>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title text-bg-primary rounded rounded-3 fs-3 widget-icon-box-avatar shadow">
                                        <i class="ri-checkbox-circle-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 6. Total Revenue (all-time collected from approved tenants) --}}
                <div class="col">
                    <div class="card widget-icon-box">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="flex-grow-1 overflow-hidden">
                                    <h5 class="text-muted text-uppercase fs-13 mt-0">Total Revenue</h5>
                                    <h3 class="my-3">{{ number_format($revenueStats->total_collected, 2) }}</h3>
                                    <p class="mb-0 text-muted text-truncate">
                                        <span class="badge bg-success me-1">
                                            <i class="ri-arrow-up-line"></i> All time
                                        </span>
                                        <span>Collected (paid)</span>
                                    </p>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title text-bg-success rounded rounded-3 fs-3 widget-icon-box-avatar shadow">
                                        <i class="ri-money-dollar-circle-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- end row -->

            {{-- ── ROW 2 · RECENT TENANTS + UPCOMING PAYMENTS (equal height) ── --}}
            <div class="row align-items-stretch">

                <div class="col-xl-7 d-flex">
                    <div class="card w-100">
                        <div class="d-flex card-header justify-content-between align-items-center">
                            <h4 class="header-title">Recent Tenants</h4>
                            <a href="{{ route('master.tenants') }}" class="btn btn-sm btn-info">View All <i class="ri-arrow-right-line ms-1"></i></a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-borderless table-hover table-nowrap table-centered m-0">
                                    <thead class="border-top border-bottom bg-light-subtle border-light">
                                        <tr>
                                            <th class="py-1">Business</th>
                                            <th class="py-1">Contact</th>
                                            <th class="py-1">Plan</th>
                                            <th class="py-1">Status</th>
                                            <th class="py-1">Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentTenants as $tenant)
                                            <tr>
                                                <td>
                                                    <strong>{{ Str::limit($tenant->business_name, 20) }}</strong><br>
                                                    <small class="text-muted">{{ $tenant->full_name }}</small>
                                                </td>
                                                <td>{{ Str::limit($tenant->email, 22) }}</td>
                                                <td>{{ $tenant->subscription_plan ?? '—' }}</td>
                                                <td>
                                                    @php
                                                        $sc = match(strtolower($tenant->status)) {
                                                            'approved'  => 'success',
                                                            'pending'   => 'warning',
                                                            'rejected'  => 'danger',
                                                            'suspended' => 'secondary',
                                                            default     => 'info',
                                                        };
                                                    @endphp
                                                    <span class="badge bg-{{ $sc }}">{{ $tenant->status }}</span>
                                                </td>
                                                <td>{{ $tenant->created_at ? \Carbon\Carbon::parse($tenant->created_at)->format('d M Y') : '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-3">No tenants found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5 d-flex">
                    <div class="card w-100">
                        <div class="d-flex card-header justify-content-between align-items-center">
                            <h4 class="header-title">Upcoming Payments <span class="badge bg-warning ms-1">7 days</span></h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-borderless table-hover table-nowrap table-centered m-0">
                                    <thead class="border-top border-bottom bg-light-subtle border-light">
                                        <tr>
                                            <th class="py-1">Business</th>
                                            <th class="py-1">Plan</th>
                                            <th class="py-1">Amount</th>
                                            <th class="py-1">Due</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($upcomingPayments as $payment)
                                            <tr>
                                                <td>{{ Str::limit($payment->business_name, 18) }}</td>
                                                <td>{{ $payment->subscription_plan ?? '—' }}</td>
                                                <td><strong>{{ number_format($payment->payment_amount ?? 0, 2) }}</strong></td>
                                                <td>
                                                    @php
                                                        $dueDate  = \Carbon\Carbon::parse($payment->next_payment_date);
                                                        $daysLeft = (int) now()->diffInDays($dueDate, false);
                                                        $dueBadge = $daysLeft <= 2 ? 'danger' : ($daysLeft <= 5 ? 'warning' : 'success');
                                                    @endphp
                                                    <span class="badge bg-{{ $dueBadge }}">{{ $dueDate->format('d M') }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-muted py-3">No payments due in 7 days.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- end row -->

            {{-- ── ROW 3 · OVERDUE TENANTS + QUICK ACTIONS ── --}}
            <div class="row align-items-stretch">

                {{-- Overdue Tenants --}}
                <div class="col-xl-7 d-flex">
                    <div class="card w-100">
                        <div class="d-flex card-header justify-content-between align-items-center">
                            <h4 class="header-title">
                                Overdue Tenants
                                @if($overdueList->isNotEmpty())
                                    <span class="badge bg-danger ms-1">{{ $revenueStats->overdue_count }}</span>
                                @endif
                            </h4>
                            <span class="text-muted fs-13">Outstanding: <strong>{{ number_format($revenueStats->overdue_amount, 2) }}</strong></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-borderless table-hover table-nowrap table-centered m-0">
                                    <thead class="border-top border-bottom bg-light-subtle border-light">
                                        <tr>
                                            <th class="py-1">Business</th>
                                            <th class="py-1">Contact</th>
                                            <th class="py-1">Invoices</th>
                                            <th class="py-1">Total Owed</th>
                                            <th class="py-1">Oldest Due</th>
                                            <th class="py-1">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($overdueList as $overdue)
                                            <tr>
                                                <td>
                                                    <strong>{{ Str::limit($overdue->business_name, 20) }}</strong><br>
                                                    <small class="text-muted">{{ $overdue->full_name }}</small>
                                                </td>
                                                <td>
                                                    @php
                                                        $daysOverdue = $overdue->oldest_due
                                                            ? (int) \Carbon\Carbon::parse($overdue->oldest_due)->diffInDays(now())
                                                            : 0;
                                                    @endphp
                                                    <span class="badge bg-{{ $daysOverdue > 30 ? 'danger' : 'warning' }}">
                                                        {{ $daysOverdue }}d overdue
                                                    </span>
                                                </td>
                                                <td><span class="badge bg-light text-dark">{{ $overdue->invoice_count }}</span></td>
                                                <td><strong class="text-danger">{{ number_format($overdue->total_overdue, 2) }}</strong></td>
                                                <td>{{ $overdue->oldest_due ? \Carbon\Carbon::parse($overdue->oldest_due)->format('d M Y') : '—' }}</td>
                                                <td>
                                                    <a href="javascript:void(0);" class="btn btn-xs btn-soft-danger">
                                                        <i class="ri-mail-send-line me-1"></i> Remind
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="ri-checkbox-circle-line text-success fs-3 d-block mb-1"></i>
                                                    <span class="text-muted">No overdue tenants — all clear!</span>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="col-xl-5 d-flex">
                    <div class="card w-100">
                        <div class="card-header">
                            <h4 class="header-title">Quick Actions</h4>
                        </div>
                        <div class="card-body d-flex flex-column gap-2">

                            {{-- Approve pending --}}
                            <a href="{{ route('master.tenants') }}?status=Pending" class="d-flex align-items-center gap-3 p-3 rounded border text-decoration-none quick-action-item">
                                <span class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title text-bg-warning rounded rounded-3 fs-4 shadow-sm">
                                        <i class="ri-user-follow-line"></i>
                                    </span>
                                </span>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Approve Pending Tenants</h6>
                                    <small class="text-muted">{{ $tenantStats->pending }} awaiting review</small>
                                </div>
                                <i class="ri-arrow-right-s-line text-muted fs-5"></i>
                            </a>

                            {{-- Send payment reminders --}}
                            <a href="{{ route('master.tenant.invoices') }}?status=Overdue" class="d-flex align-items-center gap-3 p-3 rounded border text-decoration-none quick-action-item">
                                <span class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title text-bg-danger rounded rounded-3 fs-4 shadow-sm">
                                        <i class="ri-mail-send-line"></i>
                                    </span>
                                </span>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Send Payment Reminders</h6>
                                    <small class="text-muted">{{ $revenueStats->overdue_count }} overdue invoices</small>
                                </div>
                                <i class="ri-arrow-right-s-line text-muted fs-5"></i>
                            </a>

                            {{-- Review on-hold --}}
                            <a href="{{ route('master.tenants') }}?hold=Yes" class="d-flex align-items-center gap-3 p-3 rounded border text-decoration-none quick-action-item">
                                <span class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title text-bg-secondary rounded rounded-3 fs-4 shadow-sm">
                                        <i class="ri-pause-circle-line"></i>
                                    </span>
                                </span>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Review On-Hold Tenants</h6>
                                    <small class="text-muted">{{ $tenantStats->on_hold }} currently paused</small>
                                </div>
                                <i class="ri-arrow-right-s-line text-muted fs-5"></i>
                            </a>

                            {{-- Add new tenant --}}
                            <a href="{{ route('master.tenants') }}" class="d-flex align-items-center gap-3 p-3 rounded border text-decoration-none quick-action-item">
                                <span class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title text-bg-success rounded rounded-3 fs-4 shadow-sm">
                                        <i class="ri-user-add-line"></i>
                                    </span>
                                </span>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Add New Tenant</h6>
                                    <small class="text-muted">Register a new business</small>
                                </div>
                                <i class="ri-arrow-right-s-line text-muted fs-5"></i>
                            </a>

                            {{-- Generate invoice --}}
                            <a href="{{ route('master.tenant.invoices') }}" class="d-flex align-items-center gap-3 p-3 rounded border text-decoration-none quick-action-item">
                                <span class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title text-bg-info rounded rounded-3 fs-4 shadow-sm">
                                        <i class="ri-file-add-line"></i>
                                    </span>
                                </span>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Generate Invoice</h6>
                                    <small class="text-muted">{{ $revenueStats->pending_invoices }} pending unpaid</small>
                                </div>
                                <i class="ri-arrow-right-s-line text-muted fs-5"></i>
                            </a>

                        </div>
                    </div>
                </div>

            </div>
            <!-- end row -->

        </div>
        <!-- container -->

    </div>
    <!-- content -->

</div>