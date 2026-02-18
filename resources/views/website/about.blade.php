@extends('website.homepage')

@section('title', 'About Netacube')

@section('styles')
  
@endsection

@section('content')
    <!-- Hero Section -->
    <section class="bg-half-260 bg-primary d-table w-100" style="background: url('website/assets/images/software/bg.png') center center;">
        <div class="bg-overlay"></div>
        <div class="container">
            <div class="row align-items-center position-relative mt-5" style="z-index: 1;">
                <div class="col-lg-7 col-md-12 text-center text-lg-start">
                    <div class="title-heading mt-4">
                        <h1 class="heading mb-3 text-white">About Netacube</h1>
                        <p class="para-desc text-white-50 mx-auto mx-lg-0">
                            Developed by Netamind Technology — a purpose-built, enterprise-grade business management platform 
                            designed to meet the real operational needs of retail, wholesale, and service enterprises.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="section-uniform bg-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 order-2 order-lg-1 mt-4 mt-lg-0">
                    <h4 class="title mb-4">Our Story</h4>
                    <p class="text-muted">
                        Netacube was established to address the genuine challenges encountered by modern businesses. 
                        Founded by <strong>Netamind Technology</strong>, we identified a critical gap: many retail, wholesale, 
                        and service-based enterprises were hindered by fragmented systems, unreliable connectivity, 
                        inadequate data protection, and software solutions ill-suited to real-world operational requirements.
                    </p>
                    <p class="text-muted">
                        Beginning as a dedicated team of software developers and business professionals, we created a comprehensive, 
                        unified platform that integrates inventory management, point-of-sale operations, human resources, payroll, 
                        document generation, and advanced analytics — all engineered to perform reliably both online and offline.
                    </p>
                    <p class="text-muted">
                        We take pride in developing a solution that actively supports the digital transformation and sustainable 
                        growth of businesses.
                    </p>
                </div>
                <div class="col-lg-6 order-1 order-lg-2">
                    <img src="https://img.freepik.com/premium-photo/multi-ethnic-team-young-software-developers-using-computers-modern-office-with-focus-african-american-woman-instructing-colleague-copy-space_236854-29514.jpg" class="img-fluid rounded shadow" alt="Netamind Technology Team">
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision Section -->
    <section class="section-uniform bg-light">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-12">
                    <h4 class="title mb-5">Our Mission & Vision</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mt-4">
                    <div class="d-flex align-items-start shadow-sm rounded p-4 bg-white hover-lift h-100">
                        <div class="icon-box me-4">
                            <i class="uil uil-rocket h2 text-white mb-0"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold">Our Mission</h5>
                            <p class="text-muted mb-0">
                                To deliver a secure, affordable, and highly practical business management solution that empowers 
                                enterprises to streamline operations, safeguard critical data, and achieve sustainable growth — 
                                even in environments with limited connectivity.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mt-4">
                    <div class="d-flex align-items-start shadow-sm rounded p-4 bg-white hover-lift h-100">
                        <div class="icon-box me-4">
                            <i class="uil uil-eye h2 text-white mb-0"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold">Our Vision</h5>
                            <p class="text-muted mb-0">
                                To become the most trusted and widely adopted business management platform, setting the standard 
                                for innovative, reliable, and practical enterprise technology.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- What Sets Us Apart Section -->
    <section class="section-uniform bg-white">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-12">
                    <h4 class="title mb-4">What Sets Netacube Apart</h4>
                    <p class="text-muted para-desc mx-auto mb-5">Key differentiators that make Netacube the preferred choice for businesses</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mt-4">
                    <div class="text-center">
                        <div class="icon-box mx-auto mb-3">
                            <i class="uil uil-wifi-slash h1 text-primary mb-0"></i>
                        </div>
                        <h5>True Offline Capability</h5>
                        <p class="text-muted">Continue all operations seamlessly during internet outages — with automatic synchronization when connectivity returns.</p>
                    </div>
                </div>
                <div class="col-md-4 mt-4">
                    <div class="text-center">
                        <div class="icon-box mx-auto mb-3">
                            <i class="uil uil-shield-check h1 text-primary mb-0"></i>
                        </div>
                        <h5>Enterprise-Grade Security</h5>
                        <p class="text-muted">Built-in role-based access, audit logs, encryption, and daily backups — designed with data protection as a core priority.</p>
                    </div>
                </div>
                <div class="col-md-4 mt-4">
                    <div class="text-center">
                        <div class="icon-box mx-auto mb-3">
                            <i class="uil uil-users-alt h1 text-primary mb-0"></i>
                        </div>
                        <h5>Practical Design, Professional Standards</h5>
                        <p class="text-muted">Engineered for real business needs while maintaining international best practices in usability and functionality.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="section-uniform bg-light">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-12">
                    <h4 class="title mb-4">Our Core Values</h4>
                    <p class="text-muted para-desc mx-auto mb-5">The principles that guide our development, decisions, and commitment to our customers</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mt-4">
                    <div class="text-center">
                        <div class="icon-box mx-auto mb-3">
                            <i class="uil uil-shield-check h1 text-primary mb-0"></i>
                        </div>
                        <h5>Security First</h5>
                        <p class="text-muted">Enterprise-grade protection underpins every aspect of our platform, ensuring the confidentiality, integrity, and availability of your business data.</p>
                    </div>
                </div>
                <div class="col-md-4 mt-4">
                    <div class="text-center">
                        <div class="icon-box mx-auto mb-3">
                            <i class="uil uil-users-alt h1 text-primary mb-0"></i>
                        </div>
                        <h5>Customer-Centric Excellence</h5>
                        <p class="text-muted">Your success is our highest priority. We provide dedicated 24/7 support and continuously evolve the platform based on real customer needs and feedback.</p>
                    </div>
                </div>
                <div class="col-md-4 mt-4">
                    <div class="text-center">
                        <div class="icon-box mx-auto mb-3">
                            <i class="uil uil-lightbulb-alt h1 text-primary mb-0"></i>
                        </div>
                        <h5>Innovation with Simplicity</h5>
                        <p class="text-muted">We deliver powerful, forward-thinking features through an intuitive interface that requires minimal training and respects users' time and operational context.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection