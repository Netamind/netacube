<!DOCTYPE html>
<html lang="en" data-menu-color="brand">
<head>
    <meta charset="utf-8" />
    <title>Netacube - The ultimate business management system</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />
    <meta content="Coderthemes" name="author" />

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('dashboard/images/icon.png') }}" type="image/x-icon">

    <!-- Daterangepicker css -->
    <link rel="stylesheet" href="{{ asset('dashboard/assets/vendor/daterangepicker/daterangepicker.css') }}">

    <!-- Vector Map css -->
    <link rel="stylesheet" href="{{ asset('dashboard/assets/vendor/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css') }}">

    <!-- Theme Config Js -->
    <script src="{{ asset('dashboard/assets/js/config.js') }}"></script>

    <!-- App css -->
    <link href="{{ asset('dashboard/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />

    <!-- Remixicons -->
    <link href="{{ asset('dashboard/assets/remixicons/remixicon.css') }}" rel="stylesheet" type="text/css" />

    <!-- Datatables css -->
    <link href="{{ asset('dashboard/assets/vendor/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dashboard/assets/vendor/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dashboard/assets/vendor/datatables.net-fixedheader-bs5/css/fixedHeader.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dashboard/assets/vendor/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dashboard/assets/vendor/datatables.net-select-bs5/css/select.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dashboard/assets/vendor/datatables.net-fixedcolumns-bs5/css/fixedColumns4.2.0.css') }}" rel="stylesheet" type="text/css" />

    <!-- Toastr -->
    <link href="{{ asset('library/toastr/toastr.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Fullcalendar css -->
    <link href="{{ asset('dashboard/assets/vendor/fullcalendar/main.min.css') }}" rel="stylesheet" type="text/css" />

    <style>
        /* ── Modal headers — same classes/values as operations/retail/branchproducts.blade.php ── */
        .mh-danger { background:linear-gradient(135deg,#c0392b,#e74c3c); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
        .mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
        .mh-close  { filter:brightness(0) invert(1); opacity:.8; }
        .mh-close:hover { opacity:1; }
    </style>
</head>
<body>

    <!-- Pre-loader -->
    <div id="preloader">
        <div id="status">
            <div class="bouncing-loader"><div></div><div></div><div></div></div>
        </div>
    </div>

    <!-- Begin page -->
    <div class="wrapper">

        <!-- ========== Topbar Start ========== -->
        <div class="navbar-custom">
            <div class="topbar container-fluid">
                <div class="d-flex align-items-center gap-lg-2 gap-1">

                    <div class="logo-topbar">
                        <a href="#" class="logo-light">
                            <span class="logo-lg"><img src="{{ asset('tenants/operations/images/icon.png') }}" alt=""></span>
                            <span class="logo-sm"><img src="{{ asset('tenants/operations/images/icon.png') }}" alt=""></span>
                        </a>
                        <a href="#" class="logo-dark">
                            <span class="logo-lg"><img src="{{ asset('tenants/operations/images/icon.png') }}" alt=""></span>
                            <span class="logo-sm"><img src="{{ asset('tenants/operations/images/icon.png') }}" alt=""></span>
                        </a>
                    </div>

                    <!-- Sidebar toggle (hamburger) -->
                    <button class="button-toggle-menu">
                        <i class="ri-menu-2-fill"></i>
                    </button>

                    <!-- Mobile navbar toggle -->
                    <button class="navbar-toggle" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                        <div class="lines"><span></span><span></span><span></span></div>
                    </button>

                    <!-- Desktop global search -->
                    <div class="app-search dropdown d-none d-lg-block">
                        <form>
                            <div class="input-group">
                                <input type="search" class="form-control dropdown-toggle" placeholder="Search..." id="top-search">
                                <span class="ri-search-line search-icon"></span>
                            </div>
                        </form>
                        <div class="dropdown-menu dropdown-menu-animated dropdown-lg" id="search-dropdown"></div>
                    </div>
                </div>

                <ul class="topbar-menu d-flex align-items-center gap-3">

                    <!-- Mobile search (collapsed under sm) -->
                    <li class="dropdown d-lg-none">
                        <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button">
                            <i class="ri-search-line fs-22"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated dropdown-lg p-0">
                            <form class="p-3">
                                <input type="search" class="form-control" placeholder="Search ..." aria-label="Search">
                            </form>
                        </div>
                    </li>

                    <!-- App launcher -->
                    <li class="dropdown d-none d-sm-inline-block">
                        <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button">
                            <i class="ri-apps-2-fill fs-22"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated dropdown-lg p-0">
                            <div class="p-2"><div class="row g-0"></div></div>
                        </div>
                    </li>

                    <!-- Theme settings panel trigger -->
                    <li class="d-none d-sm-inline-block">
                        <a class="nav-link" data-bs-toggle="offcanvas" href="#theme-settings-offcanvas">
                            <i class="ri-settings-3-fill fs-22"></i>
                        </a>
                    </li>

                    <!-- Fullscreen toggle -->
                    <li class="d-none d-md-inline-block">
                        <a class="nav-link" href="#" data-toggle="fullscreen">
                            <i class="ri-fullscreen-line fs-22"></i>
                        </a>
                    </li>

                    <!-- User account dropdown -->
                    <li class="dropdown me-md-2">
                        <a class="nav-link dropdown-toggle arrow-none nav-user px-2" data-bs-toggle="dropdown" href="#" role="button">
                            <span class="account-user-avatar">
                                <i class="ri-user-fill align-middle" style="color:gray"></i>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated profile-dropdown">
                            <a href="#" class="dropdown-item">
                                <i class="ri-account-circle-fill align-middle me-1"></i>
                                <span>Profile</span>
                            </a>
                            <a href="#" class="dropdown-item" onclick="document.getElementById('logout-form').submit();">
                                <i class="ri-logout-box-r-fill align-middle me-1"></i>
                                <span>Sign Out</span>
                            </a>
                            <form id="logout-form" action="{{ route('tenant.logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <!-- ========== Topbar End ========== -->

        <!-- ========== Left Sidebar Start ========== -->
        <div class="leftside-menu">

            <!-- Sidebar brand logo (full & icon-only variants) -->
            <a href="#" class="logo logo-light" style="text-align:left;padding-left:20px;">
                <span class="logo-lg"><img src="{{ asset('tenants/operations/images/operations.png') }}" alt="" style="height:50px"></span>
                <span class="logo-sm"><img src="{{ asset('tenants/operations/images/icon.png') }}" alt=""></span>
            </a>
            <a href="#" class="logo logo-dark" style="text-align:left;">
                <span class="logo-lg"><img src="{{ asset('tenants/operations/images/operations.png') }}" alt="" style="height:50px"></span>
                <span class="logo-sm"><img src="{{ asset('tenants/operations/images/icon.png') }}" alt=""></span>
            </a>

            <!-- Toggle buttons for condensed / full sidebar -->
            <div class="button-sm-hover" data-bs-toggle="tooltip" data-bs-placement="right" title="Show Full Sidebar">
                <i class="ri-checkbox-blank-circle-line align-middle"></i>
            </div>
            <div class="button-close-fullsidebar">
                <i class="ri-close-fill align-middle"></i>
            </div>

            <div class="h-100" id="leftside-menu-container" data-simplebar>

                <!-- Authenticated user identity block -->
                <div class="leftbar-user p-3 text-white">
                    <a href="#" class="d-flex align-items-center text-reset">
                        <div class="flex-shrink-0">
                            <i class="ri-user-fill align-middle" style="color:#f2f2f2"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <span class="fw-semibold fs-15 d-block">{{ Auth::user()->name }}</span>
                            <span class="fs-13">{{DB::connection('tenant')->table('branches')->where('id',Auth::user()->branch) ->value('name') }}</span>
                        </div>
                        <div class="ms-auto">
                            <i class="ri-arrow-right-s-fill fs-20" style="color:#f2f2f2"></i>
                        </div>
                    </a>
                </div>

                <!--- Sidemenu -->
                <ul class="side-nav">

                    {{--
                    ============================================================
                    SIDEBAR STRUCTURE OVERVIEW — WHOLESALE OPERATIONS
                    ============================================================
                    This is the Wholesale sector's own dashboard, mirroring the
                    Retail sidebar's structure/markup so both stay visually and
                    behaviorally consistent.

                    IMPLEMENTED & WIRED:   Products (Base Products CRUD),
                                           Inventory (Branch Products CRUD),
                                           Price Changes (read-only log).
                    ROUTE RESERVED ONLY:   Warehouses, Suppliers, Wholesale
                                           Customers, Audit Logs, All
                                           Deliverynotes — these link to a
                                           real named route so they don't
                                           404, but the controller behind
                                           each is currently a stub (or, for
                                           Warehouses/Suppliers, still needs
                                           its method added to
                                           WholesaleOperationsController).
                    STILL "#" PLACEHOLDER: everything else below — full menu
                                           kept visible so it's easy to build
                                           against next.

                    Deliberately excluded: a Sales section. Wholesale sales
                    (orders, invoicing, customer-facing POS/portal) lives on
                    its own separate dashboard — this one is scoped to
                    Wholesale INVENTORY MANAGEMENT only.

                    GENERAL              — Dashboard, Inventory, Actioncenter, Products (flat);
                                           Directory: Warehouses, Suppliers, Wholesale Customers (dropdown)
                    STOCK MANAGEMENT     — Transactions, Orders, Stocktaking (dropdowns)
                    DOCUMENTS            — Quotations, Deliverynotes, Invoices (dropdowns) — supplier
                                           paperwork trail, not customer-facing sales docs
                    EXPENDITURES         — Expenses, Payables (dropdowns)
                    REPORTS              — Stock, Finances (dropdowns) — no Sales report here
                    SETTINGS             — Dashboard (flat), Logout (flat)
                    ============================================================
                    --}}


                    <!-- ==================== GENERAL ==================== -->
                    <!-- Top-level utility links: dashboard overview + org-wide reference data -->
                    <li class="side-nav-title mt-1">General</li>

                    <!-- Main dashboard / home screen -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-dashboard-3-line"></i>
                            <span>Wholesale</span>
                        </a>
                    </li>

                    <?php
                    $role = Auth::user()->role;
                    ?>

                    @if($role=="Admin")
                      <li class="side-nav-item">
                        <a href="{{ route('tenant.admin.dashboard') }}" class="side-nav-link">
                            <i class="ri-settings-3-line"></i>
                            <span>Admin</span>
                        </a>
                    </li>
                    @elseif($role=="Operations")
                      <li class="side-nav-item">
                        <a href="{{ route('tenant.operations.hub.dashboard', ['hub' => 1]) }}" class="side-nav-link">
                            <i class="ri-settings-3-line"></i>
                            <span>Operations</span>
                        </a>
                    </li>
                    @endif



                    <!-- Inventory: per-branch stock (Branch Products) — real-time warehouse stock levels -->
                    <li class="side-nav-item">
                        <a href="{{ route('wholesale.operations.branchproducts') }}" class="side-nav-link">
                            <i class="ri-stack-line"></i>
                            <span>Inventory</span>
                        </a>
                    </li>

                    <!-- Action Center: GRN discrepancies & items awaiting review, promoted from Deliverynotes -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-list-check-3"></i>
                            <span>Actioncenter</span>
                        </a>
                    </li>


                    <!-- Directory: warehouse locations + supplier/customer registry, grouped as org reference data -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarWholesaleDirectory" aria-expanded="false" class="side-nav-link">
                            <i class="ri-building-4-line"></i>
                            <span>Directory</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarWholesaleDirectory">
                            <ul class="side-nav-second-level">
                                <li><a href="{{ route('wholesale.operations.branches') }}">Warehouses</a></li>          {{-- Warehouse / depot locations — route reserved, WholesaleOperationsController@showBranchesView not yet built --}}
                                <li><a href="{{ route('wholesale.operations.suppliers') }}">Suppliers</a></li>           {{-- Vendor registry — route reserved, WholesaleOperationsController@showSuppliersView not yet built --}}
                                <li><a href="{{ route('wholesale.operations.customers') }}">Wholesale Customers</a></li> {{-- Bulk-buyer accounts — NOT implemented yet (WholesaleCustomersController stub) --}}
                            </ul>
                        </div>
                    </li>

                    <!-- Master product catalogue (SKU / base item definitions) -->
                    <li class="side-nav-item">
                        <a href="{{ route('wholesale.operations.baseproducts') }}" class="side-nav-link">
                            <i class="ri-price-tag-3-line"></i>
                            <span>Products</span>
                        </a>
                    </li>

                    <!-- Price Changes: read-only log fed by Base + Branch Products edits -->
                    <li class="side-nav-item">
                        <a href="{{ route('wholesale.operations.pricechanges') }}" class="side-nav-link">
                            <i class="ri-price-tag-2-line"></i>
                            <span>Price Changes</span>
                        </a>
                    </li>

                    <!-- Product category hierarchy management -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-list-check-3"></i>
                            <span>Categories</span>
                        </a>
                    </li>


                    <!-- ==================== STOCK MANAGEMENT ==================== -->
                    {{--
                        Covers the full physical stock lifecycle across warehouses:
                          Transactions — damages, expiries, usages, transfers
                          Orders      — purchase order creation and processing
                          Stocktaking — periodic partial / full counts
                    --}}
                    <li class="side-nav-title mt-2">Stock Management</li>

                    <!-- Transactions: stock movement types -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarWholesaleTransactions" aria-expanded="false" class="side-nav-link">
                            <i class="ri-exchange-line"></i>
                            <span>Transactions</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarWholesaleTransactions">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Damages</a></li>
                                <li><a href="#">Expiries</a></li>
                                <li><a href="#">Usages</a></li>
                                <li><a href="#">Transfers</a></li>
                            </ul>
                        </div>
                    </li>

                    <!-- Orders: purchase orders, split by category -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarWholesaleOrders" aria-expanded="false" class="side-nav-link">
                            <i class="ri-shopping-cart-2-line"></i>
                            <span>Orders</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarWholesaleOrders">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Regular</a></li>
                                <li><a href="#">Emergency</a></li>
                                <li><a href="#">Bulk</a></li>
                                <li><a href="#">Rare</a></li>
                            </ul>
                        </div>
                    </li>

                    <!-- Stocktaking: scheduled partial or full physical counts and historical results -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarWholesaleStocktaking" aria-expanded="false" class="side-nav-link">
                            <i class="ri-scales-3-line"></i>
                            <span>Stocktaking</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarWholesaleStocktaking">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Partial Stocktaking</a></li>
                                <li><a href="#">Full Stocktaking</a></li>
                            </ul>
                        </div>
                    </li>


                    <!-- ==================== DOCUMENTS ==================== -->
                    {{--
                        Procurement paperwork trail — supplier-facing, not the
                        customer sales interface: Quotations (pre-PO pricing),
                        Purchase Orders (already under Stock Management ▸
                        Orders), Deliverynotes (goods received from
                        suppliers), Invoices (supplier billing tied to POs).
                    --}}
                    <li class="side-nav-title mt-2">Documents</li>

                    <!-- Quotations: supplier price quotes received before raising a PO -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarWholesaleQuotations" aria-expanded="false" class="side-nav-link">
                            <i class="ri-file-list-3-line"></i>
                            <span>Quotations</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarWholesaleQuotations">
                            <ul class="side-nav-second-level">
                                <li><a href="#">All Quotations</a></li>
                                <li><a href="#">Pending Approval</a></li>
                                <li><a href="#">Expired</a></li>
                            </ul>
                        </div>
                    </li>

                    <!-- Deliverynotes: goods-received confirmations against purchase orders -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarWholesaleDeliverynotes" aria-expanded="false" class="side-nav-link">
                            <i class="ri-truck-line"></i>
                            <span>Deliverynotes</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarWholesaleDeliverynotes">
                            <ul class="side-nav-second-level">
                                <li><a href="{{ route('wholesale.operations.deliverynotes') }}">All Deliverynotes</a></li> {{-- NOT implemented yet — route reserved --}}
                                <li><a href="#">Pending Reconciliation</a></li>
                                <li><a href="#">Discrepancies</a></li>
                            </ul>
                        </div>
                    </li>

                    <!-- Invoices: supplier billing documents tied to purchase orders/delivery notes -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarWholesaleInvoices" aria-expanded="false" class="side-nav-link">
                            <i class="ri-bill-line"></i>
                            <span>Invoices</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarWholesaleInvoices">
                            <ul class="side-nav-second-level">
                                <li><a href="#">All Invoices</a></li>
                                <li><a href="#">Unpaid</a></li>
                                <li><a href="#">Paid</a></li>
                                <li><a href="#">Overdue</a></li>
                            </ul>
                        </div>
                    </li>


                    <!-- ==================== EXPENDITURES ==================== -->
                    {{--
                        Expenses  — categories, direct purchases, recurring costs, petty cash
                        Payables  — outstanding supplier invoices and payment history
                    --}}
                    <li class="side-nav-title mt-2">Expenditures</li>

                    <!-- Expenses: all outgoing business costs, including petty cash -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarWholesaleExpenses" aria-expanded="false" class="side-nav-link">
                            <i class="ri-bill-line"></i>
                            <span>Expenses</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarWholesaleExpenses">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Types</a></li>
                                <li><a href="#">Expenditures</a></li>
                            </ul>
                        </div>
                    </li>

                    <!-- Payables: amounts owed to suppliers — outstanding balances and payment history -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarWholesalePayables" aria-expanded="false" class="side-nav-link">
                            <i class="ri-refund-2-line"></i>
                            <span>Payables</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarWholesalePayables">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Outstanding Balances</a></li>  {{-- Unpaid supplier invoices, by amount owed --}}
                                <li><a href="#">Payment History</a></li>      {{-- Settled payment records --}}
                            </ul>
                        </div>
                    </li>


                    <!-- ==================== REPORTS ==================== -->
                    {{--
                        Stock     — warehouse values, audit logs, valuation, movement, shrinkage, expiry, reorder triggers
                        Finances  — P&L, margin, expenditure summary, debtors, payables
                        (No Sales report section — sales lives on the separate Wholesale Sales dashboard)
                    --}}
                    <li class="side-nav-title mt-2">Reports</li>

                    <!-- Stock reports: physical inventory health and reorder intelligence -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarWholesaleStockReports" aria-expanded="false" class="side-nav-link">
                            <i class="ri-archive-2-line"></i>
                            <span>Stock</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarWholesaleStockReports">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Warehouse Values</a></li>
                                <li><a href="{{ route('wholesale.operations.auditlogs') }}">Audit Logs</a></li> {{-- NOT implemented yet — route reserved, lands on a "coming soon" notice for now --}}
                                <li><a href="#">Valuation</a></li>    {{-- Current stock value at cost & wholesale price --}}
                                <li><a href="#">Movement</a></li>     {{-- Stock flow in/out over time --}}
                                <li><a href="#">Shrinkage</a></li>    {{-- Losses: theft, damage, waste --}}
                                <li><a href="#">Expiry</a></li>       {{-- Items approaching or past expiry date --}}
                                <li><a href="#">Reordering</a></li>   {{-- SKUs below reorder threshold --}}
                                <li><a href="#">Batch Report</a></li> {{-- Batch / lot traceability --}}
                            </ul>
                        </div>
                    </li>

                    <!-- Financial reports: profitability, margins, outstanding balances -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarWholesaleFinancialReports" aria-expanded="false" class="side-nav-link">
                            <i class="ri-line-chart-line"></i>
                            <span>Finances</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarWholesaleFinancialReports">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Profit & Loss</a></li>       {{-- Revenue minus costs for a period --}}
                                <li><a href="#">Margin Analysis</a></li>     {{-- Gross margin by product / category --}}
                                <li><a href="#">Expenditure Summary</a></li> {{-- Total spend breakdown --}}
                                <li><a href="#">Debtors</a></li>             {{-- Amounts owed to the business --}}
                                <li><a href="#">Payables</a></li>            {{-- Amounts the business owes --}}
                            </ul>
                        </div>
                    </li>


                    <!-- ==================== SETTINGS ==================== -->
                    <!-- Application configuration and session management -->
                    <li class="side-nav-title mt-2">Settings</li>

                    <!-- Dashboard: idle timeout / session lifetime / landing sector -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-settings-4-line"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <!-- Logout: end the authenticated session -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link" onclick="document.getElementById('logout-form').submit();">
                            <i class="ri-logout-box-r-line"></i>
                            <span>Logout</span>
                        </a>
                    </li>

                </ul>
                <div class="clearfix"></div>
            </div>
        </div>
        <!-- ========== Left Sidebar End ========== -->

        @yield('content', View::make('operations.wholesale.default'))

    </div>
    <!-- END wrapper -->

    <!-- Theme Settings Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="theme-settings-offcanvas">
        <div class="d-flex align-items-center bg-primary p-3 offcanvas-header">
            <h5 class="text-white m-0">Theme Settings</h5>
            <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body p-0">
            <div data-simplebar class="h-100">
                <div class="card mb-0 p-3">
                    <div class="alert alert-warning" role="alert">
                        <strong>Customize </strong> the overall color scheme as per your wish.
                    </div>

                    <h5 class="my-3 fs-16 fw-bold">Color Scheme</h5>
                    <div class="d-flex flex-column gap-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="data-bs-theme" id="layout-color-light" value="light">
                            <label class="form-check-label" for="layout-color-light">Light</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="data-bs-theme" id="layout-color-dark" value="dark">
                            <label class="form-check-label" for="layout-color-dark">Dark</label>
                        </div>
                    </div>

                    <h5 class="my-3 fs-16 fw-bold">Topbar Color</h5>
                    <div class="d-flex flex-column gap-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="data-topbar-color" id="topbar-color-light" value="light">
                            <label class="form-check-label" for="topbar-color-light">Light</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="data-topbar-color" id="topbar-color-dark" value="dark">
                            <label class="form-check-label" for="topbar-color-dark">Dark</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="data-topbar-color" id="topbar-color-brand" value="brand">
                            <label class="form-check-label" for="topbar-color-brand">Brand</label>
                        </div>
                    </div>

                    <div>
                        <h5 class="my-3 fs-16 fw-bold">Sidebar Color</h5>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="data-menu-color" id="leftbar-color-dark" value="dark">
                                <label class="form-check-label" for="leftbar-color-dark">Dark</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="data-menu-color" id="leftbar-color-brand" value="brand">
                                <label class="form-check-label" for="leftbar-color-brand">Brand</label>
                            </div>
                        </div>
                    </div>

                    <div id="sidebar-size">
                        <h5 class="my-3 fs-16 fw-bold">Sidebar Size</h5>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="data-sidenav-size" id="leftbar-size-default" value="default">
                                <label class="form-check-label" for="leftbar-size-default">Default</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="data-sidenav-size" id="leftbar-size-small" value="condensed">
                                <label class="form-check-label" for="leftbar-size-small">Condensed</label>
                            </div>
                        </div>
                    </div>

                    <div id="sidebar-user">
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <label class="fs-16 fw-bold m-0" for="sidebaruser-check">Sidebar User Info</label>
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="sidebar-user" id="sidebaruser-check">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer border-top p-3 text-center">
            <div class="row">
                <div class="col-6">
                    <button type="button" class="btn btn-light w-100" id="reset-layout">Reset</button>
                </div>
                <div class="col-6">
                    <a href="#" class="btn btn-primary w-100" onclick="document.getElementById('logout-form').submit();">Sign Out</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('library/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('library/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('library/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('library/papaparse/papaparse.min.js') }}"></script>
    <script src="{{ asset('library/cropper/cropper.js') }}"></script>
    <script src="{{ asset('dashboard/assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/daterangepicker/moment.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/admin-resources/jquery.vectormap/maps/jquery-jvectormap-world-mill-en.js') }}"></script>
    <script src="{{ asset('dashboard/assets/js/app.min.js') }}"></script>

    <!-- Datatables -->
    <script src="{{ asset('dashboard/assets/vendor/datatables.net/js/jszip.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net/js/pdfmake.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net/js/vfs_fonts.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net-buttons/js/buttons.flash.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net-keytable/js/dataTables.keyTable.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net-select/js/dataTables.select.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/vendor/datatables.net-fixedcolumns-bs5/js/fixedColumns4.2.0.js') }}"></script>

    <!-- Fullcalendar -->
    <script src="{{ asset('dashboard/assets/vendor/fullcalendar/main.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/js/pages/demo.calendar.js') }}"></script>

    {{--
        ══ IDLE TIMEOUT WARNING MODAL ══
        Lives in this shared layout so every Retail Operations page that
        @extends this file gets it for free.

        This is a CLIENT-SIDE MIRROR of EnforceIdleTimeout: that middleware
        stamps session('last_activity_at') = time() on every request (not
        on mouse/key activity), and logs the user out once idle_timeout_minutes
        has passed since the last request. So the countdown below is timed
        from page load (this request), not from mouse movement — it matches
        what the server is actually counting, and is intentionally reset on
        every refresh in lockstep with the server-side timestamp.

        "Stay Signed In" does a same-origin $.ajax GET of the current URL,
        which passes back through EnforceIdleTimeout and refreshes
        last_activity_at server-side too, then reloads on a clean timer.
        Doing nothing until 0 just reloads the page, which lets the
        middleware perform the real redirect + "logged out due to
        inactivity" flash message.
    --}}
    @php
        // Same source as the "Dashboard" settings link above (retail.operations.dashboard.settings
        // → OperationsDashboardSettingsController::showRetailSettingsView): Admin gets their own
        // table, everyone else (Operations, and any other retail role) reads Operations' table.
        $__role = Auth::user()->role;

        if ($__role === 'Admin') {
            $__idleSettings = \Illuminate\Support\Facades\DB::connection('tenant')
                ->table('admin_dashboard_settings')
                ->where('user_id', \Illuminate\Support\Facades\Auth::id())
                ->first();

            if (!$__idleSettings) {
                $__idleSettings = \App\Http\Controllers\Tenant\AdminDashboardSettingsController::defaultsObject();
            }
        } else {
            $__idleSettings = \Illuminate\Support\Facades\DB::connection('tenant')
                ->table('operations_dashboard_settings')
                ->where('user_id', \Illuminate\Support\Facades\Auth::id())
                ->first();

            if (!$__idleSettings) {
                $__idleSettings = \App\Http\Controllers\Tenant\OperationsDashboardSettingsController::defaultsObject();
            }
        }

        // ── Session-lifetime clock ──
        // Unlike idle timeout (mirrored from EnforceIdleTimeout's per-request
        // last_activity_at), there's no server-side enforcement of
        // session_lifetime_minutes yet — this is a client-side-only mirror
        // for now. It needs a fixed starting point that does NOT reset on
        // every page load (otherwise it would never actually expire), so we
        // stamp it once per login into the session itself.
        // ?resetSessionClock=1 is what the modal's "Stay Signed In" button
        // hits (same-origin ajax GET) to explicitly restart this clock.
        if (request()->boolean('resetSessionClock') || !session()->has('retail_session_started_at')) {
            session()->put('retail_session_started_at', time());
        }

        $__sessionStartedAt = session('retail_session_started_at');
    @endphp

    <div class="modal fade" id="idleTimeoutWarningModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
        <div class="modal-header mh-danger"><h5 class="modal-title mh-title"><i class="ri-time-line"></i> Session About to Expire</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body text-center py-4">
          <i class="ri-time-line text-danger" style="font-size:60px"></i>
          <h5 class="mt-2 mb-1">Logging out in <span id="idleCountdownDisplay" class="text-danger">60</span>s</h5>
          <p style="font-size:13px;color:#6c757d;margin-bottom:0;">You've been inactive for a while. For your security, your session is about to end.</p>
        </div>
        <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;"><button type="button" class="btn btn-secondary btn-sm px-4" id="idleStayLoggedInBtn">Stay Signed In</button><button type="button" class="btn btn-danger btn-sm px-4" onclick="document.getElementById('logout-form').submit();">Log Out Now</button></div>
      </div></div>
    </div>

    {{--
        ══ SESSION LIFETIME WARNING MODAL ══
        Same pattern/markup as the idle-timeout modal above, but timed off
        session_lifetime_minutes (a hard cap on total session age) instead of
        idle_timeout_minutes (inactivity). Countdown starts from
        $__sessionStartedAt (stamped once per login into the session — see
        the @php block above), not from this page load, so it keeps counting
        down across page views instead of resetting on every request.
        "Stay Signed In" hits ?resetSessionClock=1, which restarts that
        stored timestamp server-side, then reloads on a clean timer — same
        ajax-then-reload shape as the idle modal's button.
    --}}
    <div class="modal fade" id="sessionTimeoutWarningModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
        <div class="modal-header mh-danger"><h5 class="modal-title mh-title"><i class="ri-time-line"></i> Session Time Limit Reached</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body text-center py-4">
          <i class="ri-time-line text-danger" style="font-size:60px"></i>
          <h5 class="mt-2 mb-1">Logging out in <span id="sessionCountdownDisplay" class="text-danger">60</span>s</h5>
          <p style="font-size:13px;color:#6c757d;margin-bottom:0;">You've reached your session's time limit. For your security, you'll be signed out shortly.</p>
        </div>
        <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;"><button type="button" class="btn btn-secondary btn-sm px-4" id="sessionStayLoggedInBtn">Stay Signed In</button><button type="button" class="btn btn-danger btn-sm px-4" onclick="document.getElementById('logout-form').submit();">Log Out Now</button></div>
      </div></div>
    </div>

    <script>
    (function () {
        var idleEnabled     = @json((bool) $__idleSettings->idle_timeout_enabled);
        var idleTimeoutSecs = {{ (float) $__idleSettings->idle_timeout_minutes }} * 60;
        var warnThreshold   = Math.min(60, idleTimeoutSecs); // show the modal once <=60s (or the whole budget, if shorter) remain

        if (!idleEnabled || !idleTimeoutSecs) return;

        var pageLoadedAt = Date.now();
        var $modal       = $('#idleTimeoutWarningModal');
        var $countdown   = $('#idleCountdownDisplay');
        var $stayBtn     = $('#idleStayLoggedInBtn');
        var warningShown = false;
        var refreshing   = false;

        function tick() {
            if (refreshing) return;

            var elapsedSecs   = Math.floor((Date.now() - pageLoadedAt) / 1000);
            var remainingSecs = idleTimeoutSecs - elapsedSecs;

            if (remainingSecs <= 0) {
                // Next request hits EnforceIdleTimeout and gets redirected
                // to login with the "logged out due to inactivity" message.
                window.location.reload();
                return;
            }

            if (remainingSecs <= warnThreshold) {
                $countdown.text(Math.ceil(remainingSecs));
                if (!warningShown) {
                    warningShown = true;
                    // Same trigger pattern as every other modal in this app
                    // (e.g. #overviewModal, #bulkActionsModal) — jQuery's
                    // .modal('show'), not vanilla bootstrap.Modal().
                    $modal.modal('show');
                }
            }
        }

        $stayBtn.on('click', function () {
            if (refreshing) return;
            refreshing = true;
            $stayBtn.prop('disabled', true);
            $stayBtn.html('<i class="ri-loader-4-line me-1"></i> Refreshing session...');

            $.ajax({
                url: window.location.href,
                method: 'GET',
                timeout: 15000,
                complete: function () { window.location.reload(); }
            });
        });

        setInterval(tick, 1000);
    })();
    </script>

    <script>
    (function () {
        var sessionLifetimeSecs = {{ (float) $__idleSettings->session_lifetime_minutes }} * 60;
        var warnThreshold       = Math.min(60, sessionLifetimeSecs); // show the modal once <=60s (or the whole budget, if shorter) remain

        if (!sessionLifetimeSecs) return;

        // Elapsed since actual login (server clock, at the time this page
        // rendered), not since this page load — that's what makes it a
        // session-lifetime cap rather than another idle timer.
        var elapsedAtLoadSecs = {{ (float) (time() - $__sessionStartedAt) }};
        var pageLoadedAt      = Date.now();
        var $modal            = $('#sessionTimeoutWarningModal');
        var $countdown        = $('#sessionCountdownDisplay');
        var $stayBtn          = $('#sessionStayLoggedInBtn');
        var warningShown      = false;
        var refreshing        = false;
        var tickInterval;

        var expired = false;

        function tick() {
            if (refreshing || expired) return;

            var clientElapsedSecs = Math.floor((Date.now() - pageLoadedAt) / 1000);
            var remainingSecs     = sessionLifetimeSecs - elapsedAtLoadSecs - clientElapsedSecs;

            if (remainingSecs <= 0) {
                // NOTE: there's no server-side enforcement of session
                // lifetime yet (unlike idle timeout's real
                // EnforceIdleTimeout middleware) — nothing actually ends
                // the session, so reloading here would just find the same
                // "expired" state again and loop forever, once per second.
                // Until that middleware exists, stop and pin the modal open
                // instead — "Log Out Now" still works, "Stay Signed In"
                // still resets the clock if they want more time.
                expired = true;
                clearInterval(tickInterval);
                $countdown.text(0);
                $modal.modal('show');
                return;
            }

            if (remainingSecs <= warnThreshold) {
                $countdown.text(Math.ceil(remainingSecs));
                if (!warningShown) {
                    warningShown = true;
                    $modal.modal('show');
                }
            }
        }

        $stayBtn.on('click', function () {
            if (refreshing) return;
            refreshing = true;
            $stayBtn.prop('disabled', true);
            $stayBtn.html('<i class="ri-loader-4-line me-1"></i> Refreshing session...');

            var originalHref = window.location.href;
            var resetUrl = originalHref
                + (originalHref.indexOf('?') === -1 ? '?' : '&')
                + 'resetSessionClock=1';

            $.ajax({
                url: resetUrl,
                method: 'GET',
                timeout: 15000,
                complete: function () { window.location.href = originalHref; }
            });
        });

        tickInterval = setInterval(tick, 1000);
    })();
    </script>

    <script>
        var Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 12000
        });
    </script>

    @yield('scripts')
</body>
</html>