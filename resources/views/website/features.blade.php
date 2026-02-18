@extends('website.homepage')

@section('title', 'Netacube Features')

@section('styles')
  
@endsection

@section('content')

    <!-- Hero Section - Features focused -->
    <section class="bg-half-260 bg-primary d-table w-100" style="background: url('website/assets/images/software/bg.png') center center;">
        <div class="bg-overlay"></div>
        <div class="container">
            <div class="row align-items-center position-relative mt-5" style="z-index: 1;">
                <div class="col-lg-8 col-md-12 text-center text-lg-start">
                    <div class="title-heading mt-4">
                        <h1 class="heading mb-3 text-white">Powerful Features</h1>
                        <p class="para-desc text-white-50 mx-auto mx-lg-0">
                            Netacube delivers a complete, integrated business management solution — combining robust POS, inventory control, 
                            employee management, payroll, reporting, and more into one secure and intuitive platform.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Features Grid -->
    <section class="section-uniform bg-white">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-12">
                    <h4 class="title mb-5">Core Features That Drive Your Business Forward</h4>
                </div>
            </div>

            <div class="row g-4">
                <!-- POS -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-shopping-cart-alt h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Point of Sale (POS)</h5>
                        <p class="text-muted mb-0">
                            Fast, reliable checkout with barcode scanning, multiple payment methods, receipt customization, 
                            and real-time sales tracking. Works smoothly even during peak hours.
                        </p>
                    </div>
                </div>

                <!-- Inventory -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-box h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Inventory Management</h5>
                        <p class="text-muted mb-0">
                            Real-time stock tracking, low-stock alerts, batch/lot management, multi-location support, 
                            product variants, and automatic reordering suggestions.
                        </p>
                    </div>
                </div>

                <!-- Offline -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-wifi-slash h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Full Offline Functionality</h5>
                        <p class="text-muted mb-0">
                            Continue sales, inventory updates, and daily operations without internet — 
                            everything automatically syncs when connection returns.
                        </p>
                    </div>
                </div>

                <!-- Employee / HR -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-users-alt h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Employee Management</h5>
                        <p class="text-muted mb-0">
                            Complete HR tools including attendance tracking, leave management, shift scheduling, 
                            performance reviews, and role-based access permissions.
                        </p>
                    </div>
                </div>

                <!-- Payroll -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-moneybag h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Payroll & Deductions</h5>
                        <p class="text-muted mb-0">
                            Automated payroll processing, salary calculations, statutory deductions, payslip generation, 
                            and detailed payment history tracking.
                        </p>
                    </div>
                </div>

                <!-- Documents -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-file-alt h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Document Generation</h5>
                        <p class="text-muted mb-0">
                            Professional, customizable invoices, quotations, delivery notes, purchase orders, 
                            and receipts — all branded with your logo and details.
                        </p>
                    </div>
                </div>

                <!-- Reporting -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-chart-bar h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Advanced Reporting & Analytics</h5>
                        <p class="text-muted mb-0">
                            Powerful dashboards and reports for sales, profit margins, inventory trends, 
                            employee performance, and business insights — exportable in multiple formats.
                        </p>
                    </div>
                </div>

                <!-- Security -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-shield-check h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Enterprise-Grade Security</h5>
                        <p class="text-muted mb-0">
                            Role-based access control, activity audit logs, data encryption, daily backups, 
                            and secure user authentication to protect your business information.
                        </p>
                    </div>
                </div>

                <!-- Multi-branch -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-building h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Multi-Branch / Multi-Location</h5>
                        <p class="text-muted mb-0">
                            Centralized control of multiple stores or branches with inter-location transfers, 
                            consolidated reporting, and location-specific settings.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Expanded final section: Integration & Philosophy -->
    <section class="section-uniform bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h4 class="title mb-4">A Truly Unified Business Platform</h4>
                    <p class="text-muted para-desc mx-auto mb-4" style="max-width: 780px;">
                        Netacube is designed as a fully integrated system where every feature works in harmony to streamline your operations. 
                        For instance, a sale completed through the POS automatically adjusts inventory levels in real-time, updates financial records, 
                        and contributes to analytical dashboards. Employee shifts and attendance data seamlessly flow into payroll calculations, 
                        while security measures ensure that all data remains protected across modules. This integration eliminates redundant data entry, 
                        reduces errors, and provides a holistic view of your business performance — allowing you to make informed decisions quickly and efficiently.
                    </p>
                    <p class="text-muted para-desc mx-auto mb-5" style="max-width: 780px;">
                        Beyond technical integration, Netacube emphasizes usability and reliability, ensuring that even non-technical users can leverage 
                        its full potential with minimal training. Whether managing a single location or multiple branches, the platform scales with your 
                        business while maintaining consistent performance and data accuracy.
                    </p>
                </div>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-10">
                    <div class="row">
                        <div class="col-md-4 mt-4">
                            <div class="d-flex align-items-start">
                                <div class="icon-box me-3">
                                    <i class="uil uil-link h2 text-primary mb-0"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2">Seamless Integration</h5>
                                    <p class="text-muted mb-0">All modules communicate in real-time, ensuring data consistency across your entire operation.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mt-4">
                            <div class="d-flex align-items-start">
                                <div class="icon-box me-3">
                                    <i class="uil uil-shield-check h2 text-primary mb-0"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2">Built for Reliability</h5>
                                    <p class="text-muted mb-0">Offline capabilities and robust security keep your business running smoothly under any conditions.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mt-4">
                            <div class="d-flex align-items-start">
                                <div class="icon-box me-3">
                                    <i class="uil uil-rocket h2 text-primary mb-0"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2">Scalable Growth</h5>
                                    <p class="text-muted mb-0">From startup to enterprise, Netacube adapts to your needs with flexible, expandable features.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection