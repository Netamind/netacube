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

                    <!-- Mobile search -->
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

            <!-- Sidebar brand logo -->
            <a href="#" class="logo logo-light" style="text-align:left;padding-left:20px;">
                <span class="logo-lg"><img src="{{ asset('tenants/operations/images/operations.png') }}" alt="" style="height:50px"></span>
                <span class="logo-sm"><img src="{{ asset('tenants/operations/images/icon.png') }}" alt=""></span>
            </a>
            <a href="#" class="logo logo-dark" style="text-align:left;">
                <span class="logo-lg"><img src="{{ asset('tenants/operations/images/operations.png') }}" alt="" style="height:50px"></span>
                <span class="logo-sm"><img src="{{ asset('tenants/operations/images/icon.png') }}" alt=""></span>
            </a>

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
                            <span class="fw-semibold fs-15 d-block"></span>
                            <span class="fs-13"></span>
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
                    SALES ROLE SIDEBAR
                    ============================================================
                    SALES          — Dashboard, New Sale, Sales History,
                                     Invoices, Daily Summary
                    INVENTORY      — Stock Levels, Low/Zero Stock, Barcode Lookup,
                                     Product Search
                    DELIVERIES     — Receive Delivery, Delivery Notes, History
                    CUSTOMERS      — Customer List, Accounts, Credit Notes
                    CASH           — Cash Register, Petty Cash, Float
                    REPORTS        — My Sales, Branch Summary, Product Performance
                    SYSTEM         — Settings, Support, Logout
                    ============================================================
                    --}}


                    <!-- ==================== SALES ==================== -->
                    {{--
                        Core point-of-sale functions:
                          Dashboard    — today's metrics, quick actions, stock alerts
                          New Sale     — open a new transaction / POS screen
                          History      — all transactions recorded by this user / branch
                          Invoices     — customer-facing invoice list and generation
                          Daily Summary — end-of-day totals and reconciliation
                    --}}
                    <li class="side-nav-title mt-1">Sales</li>

                    <!-- Main sales dashboard — today's overview -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-dashboard-3-line"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <!-- Open a new sale / POS transaction -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-shopping-cart-line"></i>
                            <span>New Sale</span>
                        </a>
                    </li>

                    <!-- Full transaction history for this branch -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarSalesHistory" aria-expanded="false" class="side-nav-link">
                            <i class="ri-history-line"></i>
                            <span>Sales History</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarSalesHistory">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Today</a></li>            {{-- Current trading day transactions --}}
                                <li><a href="#">By Date</a></li>          {{-- Browse historical dates --}}
                                <li><a href="#">Discrepancies</a></li>    {{-- Flagged voids and mismatches --}}
                            </ul>
                        </div>
                    </li>

                    <!-- Customer-facing invoice list -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-file-text-line"></i>
                            <span>Invoices</span>
                        </a>
                    </li>

                    <!-- End-of-day reconciliation and totals -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-bar-chart-grouped-line"></i>
                            <span>Daily Summary</span>
                        </a>
                    </li>


                    <!-- ==================== INVENTORY ==================== -->
                    {{--
                        Read-only inventory visibility for sales staff:
                          Stock Levels  — current quantities for this branch
                          Low/Zero Stock — items needing urgent attention
                          Barcode Lookup — scan or type a barcode to find a product
                          Product Search — search by name, code, or supplier
                    --}}
                    <li class="side-nav-title mt-2">Inventory</li>

                    <!-- Current stock quantities for this branch -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-stack-line"></i>
                            <span>Stock Levels</span>
                        </a>
                    </li>

                    <!-- Items at or below reorder point / zero -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-alarm-warning-line"></i>
                            <span>Low / Zero Stock</span>
                        </a>
                    </li>

                    <!-- Scan or type barcode to instantly retrieve product details -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-barcode-line"></i>
                            <span>Barcode Lookup</span>
                        </a>
                    </li>

                    <!-- Search products by name, code, or supplier -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-search-line"></i>
                            <span>Product Search</span>
                        </a>
                    </li>


                    <!-- ==================== DELIVERIES ==================== -->
                    {{--
                        Goods-in workflow for sales staff:
                          Receive Delivery — log incoming stock against a delivery note
                          Delivery Notes   — open / pending notes awaiting confirmation
                          History          — all past deliveries received at this branch
                    --}}
                    <li class="side-nav-title mt-2">Deliveries</li>

                    <!-- Start receiving a new delivery and update stock -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-truck-line"></i>
                            <span>Receive Delivery</span>
                        </a>
                    </li>

                    <!-- List of open delivery notes awaiting action -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-clipboard-line"></i>
                            <span>Delivery Notes</span>
                        </a>
                    </li>

                    <!-- Past delivery records for this branch -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-archive-line"></i>
                            <span>Delivery History</span>
                        </a>
                    </li>


                    <!-- ==================== CUSTOMERS ==================== -->
                    {{--
                        Customer management for credit and account sales:
                          Customers    — directory of account customers
                          Accounts     — credit balances and transaction history per customer
                          Credit Notes — issued credits and refunds
                    --}}
                    <li class="side-nav-title mt-2">Customers</li>

                    <!-- Master customer directory -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-group-line"></i>
                            <span>Customers</span>
                        </a>
                    </li>

                    <!-- Per-customer credit balances and history -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-bank-card-line"></i>
                            <span>Accounts</span>
                        </a>
                    </li>

                    <!-- Credits, voids, and refund records -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-refund-2-line"></i>
                            <span>Credit Notes</span>
                        </a>
                    </li>


                    <!-- ==================== CASH ==================== -->
                    {{--
                        Cash handling for branch sales staff:
                          Cash Register — open/close register, record cash movements
                          Petty Cash    — small ad-hoc disbursements
                          Float         — opening float and end-of-day count
                    --}}
                    <li class="side-nav-title mt-2">Cash</li>

                    <!-- Cash register open/close and cash movement log -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-money-dollar-circle-line"></i>
                            <span>Cash Register</span>
                        </a>
                    </li>

                    <!-- Small cash disbursements log -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-secure-payment-line"></i>
                            <span>Petty Cash</span>
                        </a>
                    </li>

                    <!-- Opening float and end-of-day count reconciliation -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-wallet-line"></i>
                            <span>Float</span>
                        </a>
                    </li>


                    <!-- ==================== REPORTS ==================== -->
                    {{--
                        Scoped reporting available to sales staff:
                          My Sales         — personal sales performance for the logged-in user
                          Branch Summary   — aggregated branch totals (view-only)
                          Product Performance — top and slow movers at this branch
                    --}}
                    <li class="side-nav-title mt-2">Reports</li>

                    <!-- Personal sales performance — transactions made by this user -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-user-star-line"></i>
                            <span>My Sales</span>
                        </a>
                    </li>

                    <!-- Branch-level aggregated sales summary (read-only) -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-store-2-line"></i>
                            <span>Branch Summary</span>
                        </a>
                    </li>

                    <!-- Top and slow-moving products at this branch -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-line-chart-line"></i>
                            <span>Product Performance</span>
                        </a>
                    </li>


                    <!-- ==================== SYSTEM ==================== -->
                    <!-- Session and preference management for the sales user -->
                    <li class="side-nav-title mt-2">System</li>

                    <!-- Personal account settings and notification preferences -->
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#sidebarSalesSettings" aria-expanded="false" class="side-nav-link">
                            <i class="ri-settings-4-line"></i>
                            <span>Settings</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="sidebarSalesSettings">
                            <ul class="side-nav-second-level">
                                <li><a href="#">Profile</a></li>          {{-- Name, password, avatar --}}
                                <li><a href="#">Notifications</a></li>    {{-- Low-stock and delivery alerts --}}
                                <li><a href="#">Accessibility</a></li>    {{-- Display and a11y preferences --}}
                            </ul>
                        </div>
                    </li>

                    <!-- Raise a support ticket or access help docs -->
                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-customer-service-2-line"></i>
                            <span>Support</span>
                        </a>
                    </li>

                    <!-- End the authenticated session -->
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