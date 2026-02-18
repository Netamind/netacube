@extends('website.homepage')

@section('title', 'Netacube Help Center - Guides & Video Tutorials')

@section('styles')
  
@endsection

@section('content')

    <!-- Hero Section -->
    <section class="bg-half-260 bg-primary d-table w-100" style="background: url('website/assets/images/software/bg.png') center center;">
        <div class="bg-overlay"></div>
        <div class="container">
            <div class="row align-items-center position-relative mt-5" style="z-index: 1;">
                <div class="col-lg-8 col-md-12 text-center text-lg-start">
                    <div class="title-heading mt-4">
                        <h1 class="heading mb-3 text-white">Help Center</h1>
                        <p class="para-desc text-white-50 mx-auto mx-lg-0">
                            Step-by-step guides and official video tutorials to help you get the most out of Netacube — 
                            from first login to advanced features and daily operations.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Most Popular Topics (replaces Quick Access) -->
    <section class="section-uniform bg-white">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-12">
                    <h4 class="title mb-4">Most Popular Topics</h4>
                </div>
            </div>

            <div class="row g-4">
                <!-- Topic 1 -->
                <div class="col-lg-4 col-md-6">
                    <a href="/help/getting-started" class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light d-block text-decoration-none">
                        <i class="uil uil-rocket h2 text-primary mb-3 d-block"></i>
                        <h5 class="fw-bold mb-3">Getting Started</h5>
                        <p class="text-muted mb-0">Account creation, first login, initial setup and adding your first products</p>
                    </a>
                </div>

                <!-- Topic 2 -->
                <div class="col-lg-4 col-md-6">
                    <a href="/help/pos" class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light d-block text-decoration-none">
                        <i class="uil uil-shopping-cart-alt h2 text-primary mb-3 d-block"></i>
                        <h5 class="fw-bold mb-3">Point of Sale</h5>
                        <p class="text-muted mb-0">Making sales, barcode scanning, handling payments, refunds & daily closing</p>
                    </a>
                </div>

                <!-- Topic 3 -->
                <div class="col-lg-4 col-md-6">
                    <a href="/help/inventory" class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light d-block text-decoration-none">
                        <i class="uil uil-box h2 text-primary mb-3 d-block"></i>
                        <h5 class="fw-bold mb-3">Inventory Management</h5>
                        <p class="text-muted mb-0">Adding products, stock levels, low-stock alerts, transfers between branches</p>
                    </a>
                </div>

                <!-- Topic 4 -->
                <div class="col-lg-4 col-md-6">
                    <a href="/help/employees" class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light d-block text-decoration-none">
                        <i class="uil uil-users-alt h2 text-primary mb-3 d-block"></i>
                        <h5 class="fw-bold mb-3">Employees & Attendance</h5>
                        <p class="text-muted mb-0">Adding staff, clock in/out, leave requests and shift management</p>
                    </a>
                </div>

                <!-- Topic 5 -->
                <div class="col-lg-4 col-md-6">
                    <a href="/help/payroll" class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light d-block text-decoration-none">
                        <i class="uil uil-moneybag h2 text-primary mb-3 d-block"></i>
                        <h5 class="fw-bold mb-3">Payroll & Payslips</h5>
                        <p class="text-muted mb-0">Salary calculation, deductions, generating payslips and payment records</p>
                    </a>
                </div>

                <!-- Topic 6 -->
                <div class="col-lg-4 col-md-6">
                    <a href="/help/offline" class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light d-block text-decoration-none">
                        <i class="uil uil-wifi-slash h2 text-primary mb-3 d-block"></i>
                        <h5 class="fw-bold mb-3">Offline Mode</h5>
                        <p class="text-muted mb-0">How to keep selling when internet is down + how data syncs back</p>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Video Tutorials Section -->
    <section class="section-uniform bg-light">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-12">
                    <h4 class="title mb-3">Official Video Tutorials</h4>
                    <p class="text-muted para-desc mx-auto" style="max-width: 720px;">
                        Watch these step-by-step videos to learn Netacube quickly and effectively.  
                        New tutorials are added regularly.
                    </p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Video 1 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift bg-white overflow-hidden">
                        <div class="ratio ratio-16x9 bg-dark">
                            <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" 
                                    title="Netacube Quick Start Guide" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen></iframe>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-2">Quick Start Guide (5 min)</h5>
                            <p class="text-muted mb-3 small">Register, login, add products & make your first sale</p>
                            <a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ" target="_blank" class="btn btn-sm btn-outline-primary">Watch on YouTube →</a>
                        </div>
                    </div>
                </div>

                <!-- Video 2 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift bg-white overflow-hidden">
                        <div class="ratio ratio-16x9 bg-dark">
                            <iframe src="https://www.youtube.com/embed/VIDEO_ID_POS" 
                                    title="Mastering Point of Sale" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen></iframe>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-2">Mastering the POS (12 min)</h5>
                            <p class="text-muted mb-3 small">Barcode scanning, discounts, payments, receipts & refunds</p>
                            <a href="https://www.youtube.com/watch?v=VIDEO_ID_POS" target="_blank" class="btn btn-sm btn-outline-primary">Watch on YouTube →</a>
                        </div>
                    </div>
                </div>

                <!-- Video 3 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift bg-white overflow-hidden">
                        <div class="ratio ratio-16x9 bg-dark">
                            <iframe src="https://www.youtube.com/embed/VIDEO_ID_INVENTORY" 
                                    title="Inventory Management" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen></iframe>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-2">Inventory & Stock (10 min)</h5>
                            <p class="text-muted mb-3 small">Products, variants, stock levels, transfers & alerts</p>
                            <a href="https://www.youtube.com/watch?v=VIDEO_ID_INVENTORY" target="_blank" class="btn btn-sm btn-outline-primary">Watch on YouTube →</a>
                        </div>
                    </div>
                </div>

                <!-- Video 4 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift bg-white overflow-hidden">
                        <div class="ratio ratio-16x9 bg-dark">
                            <iframe src="https://www.youtube.com/embed/VIDEO_ID_PAYROLL" 
                                    title="Employee & Payroll Setup" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen></iframe>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-2">Employees & Payroll (14 min)</h5>
                            <p class="text-muted mb-3 small">Add staff, track attendance, process payroll & generate payslips</p>
                            <a href="https://www.youtube.com/watch?v=VIDEO_ID_PAYROLL" target="_blank" class="btn btn-sm btn-outline-primary">Watch on YouTube →</a>
                        </div>
                    </div>
                </div>

                <!-- Video 5 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift bg-white overflow-hidden">
                        <div class="ratio ratio-16x9 bg-dark">
                            <iframe src="https://www.youtube.com/embed/VIDEO_ID_OFFLINE" 
                                    title="Working Offline with Netacube" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen></iframe>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-2">Offline Mode Explained (8 min)</h5>
                            <p class="text-muted mb-3 small">How offline functionality works + sync troubleshooting</p>
                            <a href="https://www.youtube.com/watch?v=VIDEO_ID_OFFLINE" target="_blank" class="btn btn-sm btn-outline-primary">Watch on YouTube →</a>
                        </div>
                    </div>
                </div>

                <!-- Video 6 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100 hover-lift bg-white overflow-hidden">
                        <div class="ratio ratio-16x9 bg-dark">
                            <iframe src="https://www.youtube.com/embed/VIDEO_ID_REPORTS" 
                                    title="Reports & Business Insights" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen></iframe>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-2">Reports & Analytics (11 min)</h5>
                            <p class="text-muted mb-3 small">Sales reports, profit analysis, inventory trends & exports</p>
                            <a href="https://www.youtube.com/watch?v=VIDEO_ID_REPORTS" target="_blank" class="btn btn-sm btn-outline-primary">Watch on YouTube →</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Download User Manual & More Tutorials -->
            <div class="row justify-content-center mt-5 pt-4">
                <div class="col-auto text-center">
                    <a href="/downloads/netacube-user-manual-v1.2.pdf" 
                       class="btn btn-primary btn-lg px-5 py-3 mb-3" 
                       download>
                        <i class="uil uil-file-download me-2"></i> 
                        Download Complete User Manual (PDF)
                    </a>
                    
                    <p class="text-muted mt-3 mb-0">
                        Version 1.2 • Updated January 2026 • 68 pages
                    </p>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="https://www.youtube.com/@NetacubeOfficial" target="_blank" class="btn btn-outline-primary">
                    View All Tutorials on YouTube →
                </a>
            </div>
        </div>
    </section>

@endsection