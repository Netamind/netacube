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

                    <button class="button-toggle-menu">
                        <i class="ri-menu-2-fill"></i>
                    </button>

                    <button class="navbar-toggle" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                        <div class="lines"><span></span><span></span><span></span></div>
                    </button>

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

                    <li class="dropdown d-none d-sm-inline-block">
                        <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button">
                            <i class="ri-apps-2-fill fs-22"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated dropdown-lg p-0">
                            <div class="p-2"><div class="row g-0"></div></div>
                        </div>
                    </li>

                    <li class="d-none d-sm-inline-block">
                        <a class="nav-link" data-bs-toggle="offcanvas" href="#theme-settings-offcanvas">
                            <i class="ri-settings-3-fill fs-22"></i>
                        </a>
                    </li>

                    <li class="d-none d-md-inline-block">
                        <a class="nav-link" href="#" data-toggle="fullscreen">
                            <i class="ri-fullscreen-line fs-22"></i>
                        </a>
                    </li>

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
                                <i class="ri-logout-box-fill align-middle me-1"></i>
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

                    <!-- ==================== GENERAL ==================== -->

                       <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-dashboard-2-fill"></i>
                            <span> Finance </span>
                        </a>
                    </li>

                     <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-calendar-event-fill"></i>
                            <span> Events </span>
                        </a>
                    </li>
                    {{--
                        Landing area for all finance staff — officers and managers alike.
                        "Finance Dashboard" gives a live overview of the loan book:
                        total disbursed, repayments due today, overdue accounts, and
                        interest income for the current period.
                        "Announcements" surfaces policy updates, rate changes, and
                        regulatory notices to all staff immediately.
                        "My Tasks" shows each officer's pending actions — applications
                        to review, calls to make, documents to verify.
                    --}}
                    <li class="side-nav-title mt-1">General</li>
 

                    <!-- ==================== FRONT DESK ==================== -->
                    {{--
                        Everything a front desk officer handles when a client walks in
                        or calls. This is the daily transactional interface — fast,
                        focused, and client-facing.

                        "Client Walk-ins"     — log and manage walk-in client visits,
                                               capture purpose of visit and assign to officer
                        "New Enquiry"         — record an initial loan enquiry before any
                                               formal application is submitted
                        "Appointment Book"    — schedule client meetings with loan officers
                        "Document Checklist"  — per-client tracker of required documents
                                               (ID, payslips, collateral docs, guarantor forms)
                        "Loan Calculator"     — quick repayment and interest calculator
                                               used at the counter to give clients indicative figures
                        "Announcements Board" — display notices relevant to clients
                                               waiting in the branch (rates, products, hours)
                    --}}
                    <li class="side-nav-title mt-2">Front Desk</li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-walk-fill"></i>
                            <span> Client Walk-ins </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-questionnaire-fill"></i>
                            <span> New Enquiry </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-calendar-check-fill"></i>
                            <span> Appointment Book </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-file-check-fill"></i>
                            <span> Document Checklist </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-calculator-fill"></i>
                            <span> Loan Calculator </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-megaphone-fill"></i>
                            <span> Announcements Board </span>
                        </a>
                    </li>

                    <!-- ==================== CLIENT MANAGEMENT ==================== -->
                    {{--
                        The complete client registry and relationship management module.
                        Every borrower starts here before any loan is processed.

                        "Client Registry"     — master list of all registered borrowers
                                               with personal details, employment, and contacts
                        "KYC Verification"    — Know Your Customer checks: ID verification,
                                               address proof, and AML screening per client
                        "Guarantors"          — manage guarantor profiles linked to loans;
                                               track their own financial position and commitments
                        "Blacklist"           — clients barred from borrowing due to default,
                                               fraud, or policy violations
                        "Client Statements"   — full transaction history per client across
                                               all their loans — disbursements, repayments, fees
                        "Next of Kin"         — emergency contact and beneficiary records
                                               required for insurance and recovery purposes
                    --}}
                    <li class="side-nav-title mt-2">Client Management</li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-group-fill"></i>
                            <span> Client Registry </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-shield-check-fill"></i>
                            <span> KYC Verification </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-contacts-fill"></i>
                            <span> Guarantors </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-forbid-2-fill"></i>
                            <span> Blacklist </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-file-user-fill"></i>
                            <span> Client Statements </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-parent-fill"></i>
                            <span> Next of Kin </span>
                        </a>
                    </li>

                    <!-- ==================== LOAN APPLICATIONS ==================== -->
                    {{--
                        The end-to-end loan origination pipeline — from first submission
                        through credit assessment to final approval or rejection.

                        "New Application"     — capture a fresh loan application with
                                               all required borrower and loan details
                        "Under Review"        — applications currently being assessed
                                               by credit officers; shows assigned officer and age
                        "Credit Assessment"   — formal scoring and risk evaluation module;
                                               integrates employment, collateral, and history data
                        "Pending Approval"    — applications that have passed assessment
                                               and are awaiting manager sign-off
                        "Approved Loans"      — fully approved loans ready for disbursement
                        "Rejected Applications" — declined applications with rejection reason
                                               logged for audit and client feedback purposes
                        "Application Archive"  — complete searchable history of all past
                                               applications regardless of outcome
                    --}}
                    <li class="side-nav-title mt-2">Loan Applications</li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-file-add-fill"></i>
                            <span> New Application </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-eye-fill"></i>
                            <span> Under Review </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-bar-chart-box-fill"></i>
                            <span> Credit Assessment </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-time-fill"></i>
                            <span> Pending Approval </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-checkbox-circle-fill"></i>
                            <span> Approved Loans </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-close-circle-fill"></i>
                            <span> Rejected Applications </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-archive-fill"></i>
                            <span> Application Archive </span>
                        </a>
                    </li>

                    <!-- ==================== LOAN MANAGEMENT ==================== -->
                    {{--
                        The active loan book — everything related to loans that have
                        been disbursed and are currently running.

                        "Active Loans"        — all currently running loans with balance,
                                               next due date, and repayment status
                        "Disbursements"       — record and track funds paid out to borrowers;
                                               links to payment method and disbursement date
                        "Repayment Schedules" — auto-generated amortisation tables per loan
                                               showing principal, interest, and total per period
                        "Interest Accruals"   — daily/monthly interest earned but not yet
                                               collected; critical for income recognition
                        "Loan Restructuring"  — modify terms of a struggling loan: extend
                                               tenure, reduce instalment, or capitalise arrears
                        "Early Settlements"   — process full or partial early payoff with
                                               correct interest rebate calculation
                        "Loan Closures"       — formally close fully repaid loans and
                                               release any held collateral
                        "Rolled Over Loans"   — loans whose term has been extended or
                                               renewed at the end of the original period
                    --}}
                    <li class="side-nav-title mt-2">Loan Management</li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-money-dollar-circle-fill"></i>
                            <span> Active Loans </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-send-plane-fill"></i>
                            <span> Disbursements </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-calendar-fill"></i>
                            <span> Repayment Schedules </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-percent-fill"></i>
                            <span> Interest Accruals </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-tools-fill"></i>
                            <span> Loan Restructuring </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-hand-coin-fill"></i>
                            <span> Early Settlements </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-lock-fill"></i>
                            <span> Loan Closures </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-refresh-fill"></i>
                            <span> Rolled Over Loans </span>
                        </a>
                    </li>

                    <!-- ==================== REPAYMENTS ==================== -->
                    {{--
                        All inbound payment processing — recording money coming back
                        in from borrowers against their outstanding loan balances.

                        "Receive Payment"     — capture a repayment at the counter or
                                               via bank transfer; auto-allocates to principal
                                               and interest per the schedule
                        "Payment History"     — full chronological repayment log per loan
                                               or per client across all their loans
                        "Missed Payments"     — payments that were due but not received;
                                               triggers penalty interest and follow-up workflow
                        "Penalty Charges"     — manage late payment fees applied to
                                               overdue instalments per the lending policy
                        "Payment Receipts"    — generate and reprint official receipts
                                               for every repayment transaction
                        "Waiver Requests"     — process manager-approved waivers of
                                               penalties or interest for hardship cases
                    --}}
                    <li class="side-nav-title mt-2">Repayments</li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-cash-fill"></i>
                            <span> Receive Payment </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-history-fill"></i>
                            <span> Payment History </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-alarm-fill"></i>
                            <span> Missed Payments </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-error-warning-fill"></i>
                            <span> Penalty Charges </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-receipt-fill"></i>
                            <span> Payment Receipts </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-hand-heart-fill"></i>
                            <span> Waiver Requests </span>
                        </a>
                    </li>

                    <!-- ==================== COLLECTIONS & RECOVERY ==================== -->
                    {{--
                        Managing non-performing and delinquent loans — the debt
                        recovery arm of the lending operation.

                        "Overdue Accounts"    — loans past their due date sorted by
                                               days past due (DPD); priority collection queue
                        "Collection Calls"    — log every follow-up call made to a
                                               delinquent borrower with outcome and next action
                        "Field Visits"        — assign and record physical visits to
                                               borrowers who are unresponsive to calls
                        "Demand Notices"      — generate and track formal written demands
                                               issued to defaulters and their guarantors
                        "Legal Actions"       — log cases referred to legal counsel or courts;
                                               track court dates and judgement outcomes
                        "Collateral Recovery" — manage seizure and disposal of pledged
                                               assets when a loan defaults beyond recovery
                        "Write-offs"          — formally write off irrecoverable debts with
                                               full audit trail and management approval
                        "Recovery Log"        — record any amounts recovered from
                                               previously written-off accounts
                    --}}
                    <li class="side-nav-title mt-2">Collections & Recovery</li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-alert-fill"></i>
                            <span> Overdue Accounts </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-phone-fill"></i>
                            <span> Collection Calls </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-map-pin-user-fill"></i>
                            <span> Field Visits </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-mail-send-fill"></i>
                            <span> Demand Notices </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-scales-fill"></i>
                            <span> Legal Actions </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-home-gear-fill"></i>
                            <span> Collateral Recovery </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-delete-bin-fill"></i>
                            <span> Write-offs </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-shield-flash-fill"></i>
                            <span> Recovery Log </span>
                        </a>
                    </li>

                    <!-- ==================== COLLATERAL ==================== -->
                    {{--
                        Asset management for all security pledged against loans.

                        "Collateral Register" — master register of all assets pledged
                                               as security: property, vehicles, equipment
                        "Valuations"          — record and track professional valuations
                                               of pledged assets over time
                        "Insurance Policies"  — track insurance cover on pledged assets;
                                               flag policies nearing expiry
                        "Release of Security" — process the formal release of collateral
                                               when a loan is fully repaid and closed
                    --}}
                    <li class="side-nav-title mt-2">Collateral</li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-safe-fill"></i>
                            <span> Collateral Register </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-survey-fill"></i>
                            <span> Valuations </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-umbrella-fill"></i>
                            <span> Insurance Policies </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-lock-unlock-fill"></i>
                            <span> Release of Security </span>
                        </a>
                    </li>

                    <!-- ==================== LOAN PRODUCTS ==================== -->
                    {{--
                        Configuration and management of the lending product suite.

                        "Product Catalogue"   — all loan products offered: personal,
                                               business, asset finance, salary advance, etc.
                        "Interest Rate Setup" — configure flat, reducing balance, or
                                               compound interest rates per product
                        "Fees & Charges"      — define processing fees, insurance premiums,
                                               legal fees, and other upfront charges
                        "Eligibility Rules"   — set minimum/maximum loan amounts, tenure
                                               limits, and borrower qualification criteria
                        "Loan Terms Library"  — standard terms and conditions templates
                                               used in loan agreements per product type
                    --}}
                    <li class="side-nav-title mt-2">Loan Products</li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-stack-fill"></i>
                            <span> Product Catalogue </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-percent-fill"></i>
                            <span> Interest Rate Setup </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-price-tag-3-fill"></i>
                            <span> Fees & Charges </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-list-check-fill"></i>
                            <span> Eligibility Rules </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-file-text-fill"></i>
                            <span> Loan Terms Library </span>
                        </a>
                    </li>

                    <!-- ==================== ACCOUNTING ==================== -->
                    {{--
                        Internal financial accounting for the lending company itself.

                        "Chart of Accounts"   — ledger structure for the business
                        "Journal Entries"     — manual accounting entries and adjustments
                        "Income & Expenses"   — track operational income vs running costs
                        "Bank Reconciliation" — match internal records against bank statements
                        "Trial Balance"       — period-end debit/credit balance summary
                        "Petty Cash"          — manage and reconcile branch petty cash float
                    --}}
                    <li class="side-nav-title mt-2">Accounting</li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-book-fill"></i>
                            <span> Chart of Accounts </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-edit-fill"></i>
                            <span> Journal Entries </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-funds-fill"></i>
                            <span> Income & Expenses </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-bank-card-fill"></i>
                            <span> Bank Reconciliation </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-file-chart-fill"></i>
                            <span> Trial Balance </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-wallet-fill"></i>
                            <span> Petty Cash </span>
                        </a>
                    </li>

                    <!-- ==================== REPORTS ==================== -->
                    {{--
                        Management and regulatory reporting suite for the lending business.

                        "Loan Book Summary"    — snapshot of the entire portfolio: total
                                                outstanding, interest due, and PAR ratios
                        "Portfolio at Risk"    — PAR30/PAR60/PAR90 breakdown showing
                                                loans delinquent by 30, 60, or 90+ days
                        "Interest Income"      — interest earned and collected per period
                                                broken down by product and branch
                        "Disbursement Report"  — total funds disbursed per period with
                                                breakdown by officer, product, and branch
                        "Repayment Report"     — collections received vs expected per period
                        "Defaulters List"      — all clients in default with days past due,
                                                outstanding balance, and assigned collector
                        "Write-off Report"     — debts formally written off with approvals
                        "Collection Report"    — performance of the collections team by officer
                        "Fee Income Report"    — processing fees, penalties, and other charges
                                                collected in the period
                        "Regulatory Report"    — formatted output for submission to the
                                                financial regulator (Reserve Bank, RBM, etc.)
                    --}}
                    <li class="side-nav-title mt-2">Reports</li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-bar-chart-fill"></i>
                            <span> Loan Book Summary </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-alert-fill"></i>
                            <span> Portfolio at Risk </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-money-dollar-box-fill"></i>
                            <span> Interest Income </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-send-plane-fill"></i>
                            <span> Disbursement Report </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-cash-fill"></i>
                            <span> Repayment Report </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-user-unfollow-fill"></i>
                            <span> Defaulters List </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-delete-bin-fill"></i>
                            <span> Write-off Report </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-phone-fill"></i>
                            <span> Collection Report </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-price-tag-3-fill"></i>
                            <span> Fee Income Report </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-government-fill"></i>
                            <span> Regulatory Report </span>
                        </a>
                    </li>

                    <!-- ==================== SYSTEM ==================== -->
                    <li class="side-nav-title mt-2">System</li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-settings-4-fill"></i>
                            <span> Settings </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link">
                            <i class="ri-question-answer-fill"></i>
                            <span> Support </span>
                        </a>
                    </li>

                    <li class="side-nav-item">
                        <a href="#" class="side-nav-link" onclick="document.getElementById('logout-form').submit();">
                            <i class="ri-logout-box-r-line"></i>
                            <span> Logout </span>
                        </a>
                    </li>

                </ul>
                <div class="clearfix"></div>
            </div>
        </div>
        <!-- ========== Left Sidebar End ========== -->

        @yield('content', View::make('tenants.admin.default'))

    </div>
    <!-- END wrapper -->

    <!-- Theme Settings -->
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