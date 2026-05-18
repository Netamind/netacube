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
                            <form id="logout-form" action="#" method="POST" class="d-none">
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
                            <span class="fs-13">{{ Auth::user()->branch }}</span>
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
                    SIDEBAR STRUCTURE OVERVIEW
                    ============================================================
                    GENERAL              — Dashboard, Events, Categories, Branches,
                                           Suppliers, Baseproducts (flat links)
                    STOCK MANAGEMENT     — Inventory, Allocation, Orders,
                                           Stocktaking (dropdowns)
                    SALES                — Today (flat), History, Summary (dropdowns)
                    ORDERS & DELIVERIES  — (section heading; sub-items live in
                                           Stock Management for now)
                    EXPENDITURES         — Expenses, Payables (dropdowns)
                    REPORTS              — Stock, Finances, Sales, Supplier (dropdowns)
                    SYSTEM               — Settings (dropdown), Support, Logout (flat)
                    ============================================================
                    --}}


                    <!-- ==================== GENERAL ==================== -->
                    <!-- Top-level utility links: dashboard overview + org-wide reference data -->
                    <li class="side-nav-title mt-1">General</li>

                    <!-- Main dashboard / home screen -->
                    <li class="side-nav-item">
                        <a href="{{ route('retail.operations.dashboard') }}" class="side-nav-link">
                            <i class="ri-dashboard-3-line"></i>
                            <span>Retail</span>
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
                    @endif



                    <!-- Business calendar: promotions, deliveries, closures -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-calendar-event-fill"></i>
                            <span>Events</span>
                        </a>
                    </li>


                    <!-- Product category hierarchy management -->
                    <li class="side-nav-item">
                        <a href="{{ route('retail.operations.baseproducts') }}" class="side-nav-link">
                            <i class="ri-list-check-3"></i>
                            <span>Categories</span>
                        </a>
                    </li>

                    <!-- Branch / store location registry -->
                    <li class="side-nav-item">
                        <a href="{{ route('retail.operations.branches') }}" class="side-nav-link">
                            <i class="ri-store-3-line"></i>
                            <span>Branches</span>
                        </a>
                    </li>

                    <!-- Supplier / vendor directory -->
                    <li class="side-nav-item">
                        <a href="{{ route('retail.operations.suppliers') }}" class="side-nav-link">
                            <i class="ri-building-2-line"></i>
                            <span>Suppliers</span>
                        </a>
                    </li>

                    <!-- Master product catalogue (SKU / base item definitions) -->
                    <li class="side-nav-item">
                        <a href="{{ route('retail.operations.baseproducts') }}" class="side-nav-link">
                            <i class="ri-box-3-line"></i>
                            <span>Baseproducts</span>
                        </a>
                    </li>


                    <!-- ==================== STOCK MANAGEMENT ==================== -->
                    {{--
                        Covers the full physical stock lifecycle:
                          Inventory   — levels, transactions, transfers, shop values, audit trail
                          Allocation  — GRN workflow: receive → verify → flag discrepancies
                          Orders      — purchase order creation and processing
                          Stocktaking — periodic partial / full counts
                    --}}
                    <li class="side-nav-title mt-2">Stock Management</li>

                    <!-- Inventory: real-time stock levels, movement history, shop valuations, audit log -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarInventory" aria-expanded="false" class="side-nav-link">
                            <i class="ri-stack-line"></i>
                            <span>Inventory</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarInventory">
                            <ul class="side-nav-second-level">
                                <li><a href="{{ route('retail.operations.branchproducts') }}">Inventory</a></li>   
                                <!--<li><a href="#">Transactions</a></li>  
                                <li><a href="#">Transfers</a></li>  -->  
                                <li><a href="{{ route('retail.operations.shopvalues.overview') }}">Shopvalues</a></li> 
                                <li><a  href="{{ route('retail.operations.auditlogs') }}">Audit Logs</a></li>     
                            </ul>
                        </div>

                    <!-- Allocation: live GRN workflow — receive delivery, verify quantities, log discrepancies & price changes -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarDeliveryNotes" aria-expanded="false" class="side-nav-link">
                            <i class="ri-truck-line"></i>
                            <span>Deliverynotes</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarDeliveryNotes">
                            <ul class="side-nav-second-level">
                                <li><a  href="{{ route('retail.operations.actioncenter') }}">Actioncenter</a></li>     
                              <!--  <li><a  href="#">Pricechanges</a></li>   
                                <li><a  href="#">Summary</a></li>         
                                <li><a  href="#">History</a></li> -->       
                            </ul>
                        </div>
                    </li>

                    <!-- Orders: raise and process purchase orders, viewed by supplier or category -->
                   <!-- <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarAllOrders" aria-expanded="false" class="side-nav-link">
                            <i class="ri-shopping-cart-2-line"></i>
                            <span>Orders</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarAllOrders">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Supplier wise</a></li>   {{-- Orders grouped by supplier --}}
                                <li><a href="#">Category wise</a></li>   {{-- Orders grouped by product category --}}
                                <li><a href="#">Process Orders</a></li>  {{-- Approve / dispatch pending orders --}}
                            </ul>
                        </div>
                    </li>-->

                    <!-- Stocktaking: scheduled partial or full physical counts and historical results -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarStocktaking" aria-expanded="false" class="side-nav-link">
                            <i class="ri-clipboard-line"></i>
                            <span>Stocktaking</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarStocktaking">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Partial</a></li>  
                                <li><a href="#">Fullcount</a></li>             
                            </ul>
                        </div>
                    </li>


                    <!-- ==================== SALES ==================== -->
                    {{--
                        Today   — live real-time POS view (flat link, no drill-down needed)
                        History — all recorded sales: system, interval, manual, with discrepancies & invoices
                        Summary — aggregated views by day / month / custom range for management review
                    --}}
                    <li class="side-nav-title mt-2">Sales</li>

                    <!-- Today: live sales feed for the current trading day -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-pulse-line"></i>
                            <span>Today</span>
                        </a>
                    </li>

                    <!-- History: full transaction log across all sale types -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarSalesHistory" aria-expanded="false" class="side-nav-link">
                            <i class="ri-history-line"></i>
                            <span>History</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarSalesHistory">
                            <ul class="side-nav-second-level">
                                <li><a href="#">System Sales</a></li>       {{-- POS-recorded transactions --}}
                                <li><a href="#">Interval Sales</a></li>     {{-- Sales captured over defined intervals --}}
                                <li><a href="#">Manual Sales</a></li>       {{-- Manually entered / offline sales --}}
                                <li><a href="#">Discrepancies</a></li>      {{-- Voids, overrides, mismatches --}}
                                <li><a href="#">invoices</a></li>           {{-- Customer-facing sales invoices --}}
                            </ul>
                        </div>
                    </li>

                    <!-- Summary: aggregated sales snapshots for management reporting -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarSalesSummary" aria-expanded="false" class="side-nav-link">
                            <i class="ri-bar-chart-grouped-line"></i>
                            <span>Summary</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarSalesSummary">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Groups</a></li>          {{-- Sales by product group / department --}}
                                <li><a href="#">Daily Summary</a></li>   {{-- Day-by-day sales totals --}}
                                <li><a href="#">Monthly Summary</a></li> {{-- Month-by-month aggregates --}}
                                <li><a href="#">Custom Range</a></li>    {{-- User-defined date range report --}}
                            </ul>
                        </div>
                    </li>


                    <!-- ==================== ORDERS & DELIVERIES ==================== -->
                    {{--
                        Section heading retained for navigation clarity.
                        Operational sub-items (Orders, Allocation) live under Stock Management
                        where the physical stock workflow is managed end-to-end.
                    --}}
                    <li class="side-nav-title mt-2">Orders &amp; Deliveries</li>


                    <!-- ==================== EXPENDITURES ==================== -->
                    {{--
                        Expenses  — categories, direct purchases, recurring costs, petty cash
                        Payables  — outstanding supplier invoices and payment history
                    --}}
                    <li class="side-nav-title mt-2">Expenditures</li>

                    <!-- Expenses: all outgoing business costs, including petty cash -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarExpenses" aria-expanded="false" class="side-nav-link">
                            <i class="ri-secure-payment-line"></i>
                            <span>Expenses</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarExpenses">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Categories</a></li>   {{-- Expense type definitions --}}
                                <li><a href="#">Purchases</a></li>    {{-- One-off expense entries --}}
                                <li><a href="#">Reccuring</a></li>    {{-- Scheduled / standing expenses --}}
                                <li><a href="#">Pettycash</a></li>    {{-- Small cash disbursement log --}}
                            </ul>
                        </div>
                    </li>

                    <!-- Payables: amounts owed to suppliers — open invoices and payment history -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarPayables" aria-expanded="false" class="side-nav-link">
                            <i class="ri-bank-card-line"></i>
                            <span>Payables</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarPayables">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Invoices</a></li>  {{-- Unpaid / outstanding supplier invoices --}}
                                <li><a href="#">History</a></li>   {{-- Settled payment records --}}
                            </ul>
                        </div>
                    </li>


                    <!-- ==================== REPORTS ==================== -->
                    {{--
                        Stock     — valuation, movement, shrinkage, expiry, reorder triggers
                        Finances  — P&L, margin, expenditure summary, debtors, payables
                        Sales     — branch and product performance, customer analysis
                        Supplier  — purchase volumes and invoice reconciliation per supplier
                    --}}
                    <li class="side-nav-title mt-2">Reports</li>

                    <!-- Stock reports: physical inventory health and reorder intelligence -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarStockReports" aria-expanded="false" class="side-nav-link">
                            <i class="ri-archive-drawer-line"></i>
                            <span>Stock</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarStockReports">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Valuation</a></li>   {{-- Current stock value at cost & retail --}}
                                <li><a href="#">Movement</a></li>    {{-- Stock flow in/out over time --}}
                                <li><a href="#">Shrinkage</a></li>   {{-- Losses: theft, damage, waste --}}
                                <li><a href="#">Expiry</a></li>      {{-- Items approaching or past expiry date --}}
                                <li><a href="#">Reordering</a></li>  {{-- SKUs below reorder threshold --}}
                            </ul>
                        </div>
                    </li>

                    <!-- Financial reports: profitability, margins, outstanding balances -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarFinancialReports" aria-expanded="false" class="side-nav-link">
                            <i class="ri-line-chart-line"></i>
                            <span>Finances</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarFinancialReports">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Profit & Loss</a></li>           {{-- Revenue minus costs for a period --}}
                                <li><a href="#">Margin Analysis</a></li>          {{-- Gross margin by product / category --}}
                                <li><a href="#">Expenditure Summary</a></li>      {{-- Total spend breakdown --}}
                                <li><a href="#">Debtors</a></li>                  {{-- Amounts owed to the business --}}
                                <li><a href="#">Payables</a></li>                 {{-- Amounts the business owes --}}
                            </ul>
                        </div>
                    </li>

                    <!-- Sales analytics: branch performance, product rankings, customer trends -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarAnalytics" aria-expanded="false" class="side-nav-link">
                            <i class="ri-bar-chart-2-line"></i>
                            <span>Sales</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarAnalytics">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Branch Performance</a></li>   {{-- Revenue & units sold per branch --}}
                                <li><a href="#">Product Performance</a></li>  {{-- Top / bottom selling SKUs --}}
                                <li><a href="#">Branch Customers</a></li>     {{-- Customer visit and spend patterns --}}
                            </ul>
                        </div>
                    </li>

                    <!-- Supplier reports: procurement spend and invoice reconciliation -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarSupplierReports" aria-expanded="false" class="side-nav-link">
                            <i class="ri-community-line"></i>
                            <span>Supplier</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarSupplierReports">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Purchases</a></li>  {{-- Total ordered / received per supplier --}}
                                <li><a href="#">Invoices</a></li>   {{-- Invoice matching and reconciliation --}}
                            </ul>
                        </div>
                    </li>


                    <!-- ==================== SYSTEM ==================== -->
                    <!-- Application configuration and session management -->
                    <li class="side-nav-title mt-2">System</li>

                    <!-- Settings: general configuration and accessibility preferences -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarSettings" aria-expanded="false" class="side-nav-link">
                            <i class="ri-settings-4-line"></i>
                            <span>Settings</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarSettings">
                            <ul class="side-nav-second-level">
                                <li><a href="#">General</a></li>        {{-- System-wide configuration options --}}
                                <li><a href="#">Accessibility</a></li>  {{-- Display, language, and a11y settings --}}
                            </ul>
                        </div>
                    </li>

                    <!-- Support: raise a ticket or access help documentation -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-customer-service-2-line"></i>
                            <span>Support</span>
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

        @yield('content', View::make('operations.retail.default'))

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