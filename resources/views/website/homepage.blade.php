<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Netacube - The Ultimate Business Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Netacube is the ultimate business management system, delivering a secure, intuitive, and highly flexible all-in-one platform for retail, wholesale, and service-based enterprises. Protect your business with enterprise-grade security while enjoying ease of use, powerful features, and dedicated 24/7 support.">
    <meta name="keywords" content="Netacube, business management, inventory, POS, HR, payroll, invoicing, reporting, offline, secure">
    <meta name="author" content="Netamind Technology">
    <!-- Favicon -->
    <link rel="shortcut icon" href="dashboard/images/icon.png">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- GLightbox CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    <!-- Remixicon for onboarding icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Icons -->
    <link href="website/assets/libs/@mdi/font/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="website/assets/libs/@iconscout/unicons/css/line.css" rel="stylesheet">
    <link href="website/assets/css/style.min.css" rel="stylesheet">
    <style>
        /* Core Navbar Styles */
        #topnav {
            background-color: #fff !important;
            box-shadow: 0 0 3px rgba(60, 72, 88, .15);
        }
        #topnav .navigation-menu > li > a {
            color: #212529 !important;
            font-weight: 500;
        }
        #topnav .navigation-menu > li:hover > a,
        #topnav .navigation-menu > li > a.active {
            color: #2f55d4 !important;
        }
        @media (max-width: 991px) {
            #topnav .navigation-menu > li > a {
                color: #212529 !important;
            }
            #topnav .navigation-menu > li:hover > a,
            #topnav .navigation-menu > li > a.active {
                color: #2f55d4 !important;
            }
        }
        #topnav .logo .l-dark,
        #topnav .logo .logo-dark-mode {
            display: inline-block !important;
        }
        #topnav .logo .l-light,
        #topnav .logo .logo-light-mode {
            display: none !important;
        }
        #topnav .navbar-toggle span {
            background-color: #212529;
        }
        .position-relative .shape.overflow-hidden.text-color-white {
            display: none !important;
        }
        section.bg-half-260 {
            padding-top: 80px !important;
            padding-bottom: 50px;
        }
        @media (min-width: 992px) {
            #navigation {
                margin-left: auto !important;
                margin-right: 1.5rem !important;
            }
        }
        .navbar-get-started {
            background: linear-gradient(to right, #4B5EBD, #576CC0) !important;
            color: #fff !important;
            padding: 0.5rem 1.5rem;
            border-radius: 6px !important;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .navbar-get-started:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(75, 94, 189, 0.4);
        }
        /* Trusted Clients Carousel */
        .clients-carousel img {
            max-height: 60px;
            max-width: 180px;
            object-fit: contain;
            filter: grayscale(100%);
            opacity: 0.7;
            transition: all 0.4s ease;
        }
        .clients-carousel img:hover {
            filter: grayscale(0%);
            opacity: 1;
        }
        /* Gallery Multi-Item Carousel */
        .gallery-multi .gallery-item {
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 15px 45px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
        }
        .gallery-multi .gallery-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 70px rgba(75,94,189,0.18);
        }
        .gallery-multi .gallery-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        .gallery-multi .gallery-item:hover img {
            transform: scale(1.08);
        }
        .gallery-multi .gallery-caption {
            padding: 1rem;
            text-align: center;
            background: #fff;
        }
        .gallery-multi .gallery-caption h5 {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
            margin: 0;
        }
        /* Carousel Indicators */
        .gallery-multi .carousel-indicators {
            bottom: -60px;
        }
        .gallery-multi .carousel-indicators [data-bs-target] {
            width: 30px;
            height: 8px;
            border-radius: 2px;
            background-color: #ccc;
            opacity: 0.7;
            transform: skew(-20deg);
            transition: all 0.3s ease;
            margin: 0 6px;
        }
        .gallery-multi .carousel-indicators .active {
            background-color: #2f55d4;
            opacity: 1;
        }
        /* Prev/Next Buttons */
        .gallery-multi .carousel-control-prev,
        .gallery-multi .carousel-control-next {
            width: 50px;
            height: 50px;
            background: none;
            opacity: 1;
            top: 50%;
            transform: translateY(-50%);
        }
        .gallery-multi .carousel-control-prev {
            left: 20px;
        }
        .gallery-multi .carousel-control-next {
            right: 20px;
        }
        .gallery-multi .carousel-control-prev-icon,
        .gallery-multi .carousel-control-next-icon {
            width: 40px;
            height: 40px;
            background-size: 100%, 100%;
            background-color: #2f55d4;
            border-radius: 50%;
        }
        /* Why Choose Netacube Section */
        .why-choose {
            background: linear-gradient(135deg, #f8f9ff 0%, #e9ecff 100%);
        }
        .why-choose .display-4 {
            font-weight: 700;
            color: #2f55d4;
        }
        .why-choose .lead {
            font-size: 1.15rem;
            line-height: 1.8;
        }
        .why-choose .icon-box {
            width: 80px;
            height: 80px;
            background: #2f55d4;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            box-shadow: 0 10px 30px rgba(47, 85, 212, 0.3);
        }
        /* Uniform section padding */
        .section-uniform {
            padding-top: 80px;
            padding-bottom: 80px;
        }
        @media (max-width: 768px) {
            .section-uniform {
                padding-top: 60px;
                padding-bottom: 60px;
            }
        }
        /* Onboarding Section Styling */
        .hover-lift {
            transition: all 0.3s ease;
        }
        .hover-lift:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(47, 85, 212, 0.15) !important;
        }
        .step-number {
            transition: all 0.3s ease;
        }
        .step-number:hover {
            transform: scale(1.1);
        }
        @media (max-width: 767px) {
            .step-number {
                width: 60px !important;
                height: 60px !important;
                font-size: 1.6rem !important;
            }
            .p-4 {
                padding: 1.5rem !important;
            }
            .ri-file-add-line, .ri-mail-line, .ri-dashboard-line, 
            .ri-money-dollar-circle-line, .ri-customer-service-2-line {
                font-size: 2.4rem !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <header id="topnav" class="defaultscroll nav-sticky">
        <div class="container">
            <a class="logo" href="/">
                <img src="website/images/netacube1.png" height="40" alt="Netacube">
            </a>
            <div class="menu-extras">
                <div class="menu-item">
                    <a class="navbar-toggle" id="isToggle" onclick="toggleMenu()">
                        <div class="lines">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </a>
                </div>
            </div>
            <ul class="buy-button list-inline mb-0">
                <li class="list-inline-item mb-0">
                    <a href="/get-started" class="navbar-get-started btn btn-primary">
                        Get Started
                    </a>
                </li>
            </ul>
            <div id="navigation">
                <ul class="navigation-menu">
                    <li><a href="/" class="sub-menu-item {{ request()->is('/') ? 'active' : '' }}">Home</a></li>
                    <li><a href="/about-netacube" class="sub-menu-item {{ request()->is('about-netacube') ? 'active' : '' }}">About</a></li>
                    <li><a href="/features" class="sub-menu-item {{ request()->is('features') ? 'active' : '' }}">Features</a></li>
                    <li><a href="/pricing" class="sub-menu-item {{ request()->is('pricing') ? 'active' : '' }}">Pricing</a></li>
                    <li><a href="/contact" class="sub-menu-item {{ request()->is('contact') ? 'active' : '' }}">Contact</a></li>
                    <li><a href="/help-center" class="sub-menu-item {{ request()->is('help-center') ? 'active' : '' }}">Help Center</a></li>
                </ul>
            </div>
        </div>
    </header>

     @yield('content', View::make('website.homedefault'))
    <!-- FAQ Section - White -->
    <section class="section-uniform bg-white">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 text-center">
                    <div class="section-title mb-4 pb-2">
                        <h4 class="title mb-4">Frequently Asked Questions</h4>
                        <p class="text-muted para-desc mx-auto mb-0">Common inquiries about Netacube</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item rounded shadow mt-4">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#q1">
                                    How does Netacube differ from other systems?
                                </button>
                            </h2>
                            <div id="q1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Netacube integrates all core business modules (inventory, POS, HR, payroll, invoicing, reporting) into a single unified platform with seamless data flow and offline capability.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item rounded shadow mt-4">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q2">
                                    Does Netacube work offline?
                                </button>
                            </h2>
                            <div id="q2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes. Core operations including sales, inventory updates, and document generation continue without internet. Data automatically synchronizes when connectivity is restored.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item rounded shadow mt-4">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q3">
                                    Can I customize invoices and other documents?
                                </button>
                            </h2>
                            <div id="q3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes. Templates support full customization including logo, colors, fonts, layout, and additional fields.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item rounded shadow mt-4">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q4">
                                    How secure is my data?
                                </button>
                            </h2>
                            <div id="q4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We use end-to-end encryption, daily backups, role-based access controls, audit logs, and regular security audits to protect your data.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item rounded shadow mt-4">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q5">
                                    Does Netacube support multiple branches?
                                </button>
                            </h2>
                            <div id="q5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes. Full multi-branch support with centralized management, inter-branch transfers, and consolidated reporting.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item rounded shadow mt-4">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q6">
                                    Is there a free trial?
                                </button>
                            </h2>
                            <div id="q6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, we offer a 14-day free trial with full feature access and no credit card required.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item rounded shadow mt-4">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q7">
                                    What kind of support do you provide?
                                </button>
                            </h2>
                            <div id="q7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    All plans include email and chat support. Higher-tier plans include priority response, phone support, and dedicated account management.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item rounded shadow mt-4">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q8">
                                    Can I cancel or change my plan anytime?
                                </button>
                            </h2>
                            <div id="q8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Monthly plans can be canceled anytime. Annual and two-year plans are commitment-based for the discount but can be upgraded or discussed for special circumstances.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item rounded shadow mt-4">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q9">
                                    Do you offer data migration assistance?
                                </button>
                            </h2>
                            <div id="q9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, our team can assist with importing data from spreadsheets or existing systems during onboarding.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- CTA Section - Light -->
    <section class="section-uniform bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 text-center">
                    <div class="section-title">
                        <h4 class="title mb-4">Ready to Transform Your Business?</h4>
                        <p class="text-muted para-desc mx-auto mb-0">Join businesses trusting Netacube for efficient, reliable management.</p>
                        <div class="mt-4 pt-2">
                            <a href="/get-started" class="btn btn-primary">Start Free Trial</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="footer-py-60">
                        <div class="row">
                            <div class="col-lg-5 col-md-6 col-12 mb-4 mb-md-0">
                                <a href="/" class="logo-footer">
                                    <img src="website/images/wlogo.png" height="50" alt="Netacube">
                                </a>
                                <p class="mt-4 text-foot">Netacube is a complete business solution that integrates inventory, sales, HR, payroll, invoicing, reporting, and analytics into one reliable platform for retail, wholesale, and service enterprises.</p>
                               
                            </div>
                            <div class="col-lg-3 col-md-3 col-12 mt-4 mt-md-0">
                                <h5 class="footer-head">Quick Links</h5>
                                <ul class="list-unstyled footer-list mt-4">
                                    <li><a href="/" class="text-foot"><i class="uil uil-angle-right-b me-1"></i> Home</a></li>
                                    <li><a href="/about-netacube" class="text-foot"><i class="uil uil-angle-right-b me-1"></i> About Us</a></li>
                                    <li><a href="/features" class="text-foot"><i class="uil uil-angle-right-b me-1"></i> Features</a></li>
                                    <li><a href="/pricing" class="text-foot"><i class="uil uil-angle-right-b me-1"></i> Pricing</a></li>
                                     <li><a href="/login" class="text-foot"><i class="uil uil-angle-right-b me-1"></i>Login</a></li>
                                </ul>
                            </div>
                            <div class="col-lg-4 col-md-3 col-12 mt-4 mt-md-0">
                                <h5 class="footer-head">Contact Us</h5>
                                <ul class="list-unstyled footer-list mt-4">
                                    <li class="text-foot"><i class="uil uil-envelope me-2"></i> info@netamind.com</li>
                                    <li class="text-foot"><i class="uil uil-phone me-2"></i> +265 888 377 462</li>
                                    <li class="text-foot"><i class="uil uil-map-marker me-2"></i> Lilongwe, Malawi</li>
                                </ul>
                                <p class="mt-4 text-foot">We're here to help! Reach out for demos, support, or inquiries.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-py-30 footer-bar">
            <div class="container text-center">
                <p class="mb-0">© 2026 Netacube. All rights reserved. Powered by Netamind Technology.</p>
            </div>
        </div>
    </footer>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    <script>
        function toggleMenu() {
            document.getElementById('isToggle').classList.toggle('open');
            const nav = document.getElementById('navigation');
            nav.style.display = (nav.style.display === 'block') ? 'none' : 'block';
        }
        document.addEventListener('DOMContentLoaded', () => {
            GLightbox({ selector: '.glightbox' });
        });
    </script>
</body>
</html>