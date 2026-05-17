<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Netacube — Business Management System')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'Netacube is a secure, intuitive all-in-one business management platform for retail, wholesale, hospitality and service businesses.')">
    <link rel="shortcut icon" href="{{ asset('dashboard/images/icon.png') }}">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Remixicons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        /* ── Design tokens ── */
        :root {
            --brand:        #4B5EBD;
            --brand-dark:   #3a4da0;
            --brand-light:  #eef0f9;
            --brand-mid:    #576CC0;
            --text-dark:    #0f1623;
            --text-body:    #374151;
            --text-muted:   #6b7280;
            --surface:      #ffffff;
            --surface-alt:  #f5f6fb;
            --border:       rgba(75,94,189,0.12);
            --radius-sm:    8px;
            --radius-md:    12px;
            --radius-lg:    18px;
            --shadow-card:  0 2px 16px rgba(75,94,189,0.08);
            --nav-h:        68px;
            --section-py:   88px;
        }

        /* ── Reset & base ── */
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-body);
            background: var(--surface);
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; display: block; }

        /* ── Typography ── */
        .display-hero {
            font-size: clamp(2.2rem, 5vw, 3.4rem);
            font-weight: 800;
            line-height: 1.13;
            letter-spacing: -0.03em;
            color: var(--text-dark);
        }
        .display-section {
            font-size: clamp(1.6rem, 3.5vw, 2.2rem);
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
            color: var(--text-dark);
        }
        .eyebrow {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--brand);
        }
        .lead-text {
            font-size: 1.05rem;
            line-height: 1.7;
            color: var(--text-muted);
        }

        /* ── Navbar ── */
        #topnav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            height: var(--nav-h);
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            transition: box-shadow 0.2s ease;
        }
        #topnav.scrolled {
            box-shadow: 0 4px 24px rgba(75,94,189,0.10);
        }
        .nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .nav-logo img { height: 38px; }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-left: auto;
            margin-right: 24px;
            list-style: none;
            padding: 0;
            margin-bottom: 0;
        }
        .nav-links a {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-body);
            padding: 6px 14px;
            border-radius: 7px;
            transition: color 0.15s, background 0.15s;
        }
        .nav-links a:hover,
        .nav-links a.active {
            color: var(--brand);
            background: var(--brand-light);
        }
        .btn-nav-cta {
            background: var(--brand);
            color: #fff !important;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 9px 22px;
            border-radius: 9px;
            border: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            white-space: nowrap;
            display: inline-block;
        }
        .btn-nav-cta:hover {
            background: var(--brand-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(75,94,189,0.30);
        }

        /* Hamburger */
        .nav-toggle {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 38px;
            height: 38px;
            background: none;
            border: 1px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            margin-left: auto;
            gap: 4px;
            padding: 6px;
        }
        .nav-toggle span {
            display: block;
            width: 18px;
            height: 2px;
            background: var(--text-dark);
            border-radius: 2px;
            transition: all 0.2s;
        }
        .nav-toggle.open span:nth-child(1) { transform: translateY(6px) rotate(45deg); }
        .nav-toggle.open span:nth-child(2) { opacity: 0; }
        .nav-toggle.open span:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

        #nav-mobile-menu {
            display: none;
            position: fixed;
            top: var(--nav-h);
            left: 0; right: 0;
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 16px 24px 24px;
            z-index: 999;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
        }
        #nav-mobile-menu.open { display: block; }
        #nav-mobile-menu a {
            display: block;
            padding: 11px 0;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-body);
            border-bottom: 1px solid var(--border);
        }
        #nav-mobile-menu a:last-child { border-bottom: none; }
        #nav-mobile-menu a:hover { color: var(--brand); }
        #nav-mobile-menu .btn-nav-cta {
            display: block;
            text-align: center;
            margin-top: 16px;
            padding: 13px;
        }

        /* ── Page offset ── */
        .page-body { padding-top: var(--nav-h); }

        /* ── Section utility ── */
        .section { padding: var(--section-py) 0; }
        .section-sm { padding: 60px 0; }
        .bg-alt { background: var(--surface-alt); }
        .bg-white { background: #fff; }

        /* ── Buttons ── */
        .btn-primary-nc {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--brand);
            color: #fff;
            font-size: 0.93rem;
            font-weight: 600;
            padding: 13px 28px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        }
        .btn-primary-nc:hover {
            background: var(--brand-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(75,94,189,0.32);
            color: #fff;
        }
        .btn-ghost-nc {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: transparent;
            color: var(--brand);
            font-size: 0.93rem;
            font-weight: 600;
            padding: 12px 26px;
            border-radius: 10px;
            border: 1.5px solid var(--brand);
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .btn-ghost-nc:hover {
            background: var(--brand-light);
            color: var(--brand-dark);
        }

        /* ── Divider ── */
        .section-divider {
            width: 40px;
            height: 3px;
            background: var(--brand);
            border-radius: 2px;
            margin: 12px auto 0;
        }

        /* ── FAQ ── */
        .faq-wrap { max-width: 760px; margin: 0 auto; }
        .faq-item {
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            margin-bottom: 10px;
            overflow: hidden;
        }
        .faq-question {
            width: 100%;
            background: none;
            border: none;
            padding: 18px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-dark);
            cursor: pointer;
            text-align: left;
            gap: 12px;
        }
        .faq-question i {
            font-size: 1.1rem;
            color: var(--brand);
            transition: transform 0.2s;
            flex-shrink: 0;
        }
        .faq-question.open i { transform: rotate(45deg); }
        .faq-answer {
            display: none;
            padding: 0 22px 18px;
            font-size: 0.92rem;
            color: var(--text-muted);
            line-height: 1.7;
        }
        .faq-answer.open { display: block; }

        /* ── CTA Banner ── */
        .cta-banner {
            background: linear-gradient(135deg, var(--brand) 0%, #6b7de8 100%);
            border-radius: var(--radius-lg);
            padding: 56px 48px;
            text-align: center;
            color: #fff;
        }
        .cta-banner h2 { color: #fff; font-size: 2rem; font-weight: 700; margin-bottom: 12px; }
        .cta-banner p { color: rgba(255,255,255,0.82); font-size: 1.05rem; margin-bottom: 28px; }
        .btn-cta-white {
            background: #fff;
            color: var(--brand);
            font-weight: 700;
            font-size: 0.95rem;
            padding: 14px 32px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.2s;
            display: inline-block;
        }
        .btn-cta-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(0,0,0,0.18);
            color: var(--brand-dark);
        }

        /* ── Footer ── */
        footer {
            background: #0c1128;
            color: rgba(255,255,255,0.65);
            padding: 64px 0 0;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 48px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }
        .footer-brand p {
            font-size: 0.875rem;
            line-height: 1.75;
            margin-top: 16px;
            color: rgba(255,255,255,0.55);
            max-width: 320px;
        }
        .footer-logo { height: 40px; }
        .footer-col h6 {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.9);
            margin-bottom: 20px;
        }
        .footer-col ul { list-style: none; padding: 0; margin: 0; }
        .footer-col ul li { margin-bottom: 10px; }
        .footer-col ul li a {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.55);
            transition: color 0.15s;
        }
        .footer-col ul li a:hover { color: #fff; }
        .footer-col .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.875rem;
            color: rgba(255,255,255,0.55);
            margin-bottom: 10px;
        }
        .footer-col .contact-item i { color: var(--brand-mid); margin-top: 1px; flex-shrink: 0; }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.07);
            margin-top: 48px;
            padding: 20px 24px;
            text-align: center;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.35);
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ── Responsive ── */
        @media (max-width: 991px) {
            .nav-links, .btn-nav-cta { display: none !important; }
            .nav-toggle { display: flex; }
            :root { --section-py: 64px; }
        }
        @media (max-width: 767px) {
            .footer-grid { grid-template-columns: 1fr; gap: 32px; }
            .cta-banner { padding: 40px 24px; }
            .cta-banner h2 { font-size: 1.5rem; }
        }
    </style>

    @yield('head_extra')
</head>
<body>

<!-- ══ Navbar ══════════════════════════════════════════════════════════════ -->
<nav id="topnav">
    <div class="nav-inner">
        <a href="/" class="nav-logo">
            <img src="{{ asset('website/images/netacube1.png') }}" alt="Netacube">
        </a>

        <ul class="nav-links">
            <li><a href="/"               class="{{ request()->is('/')               ? 'active' : '' }}">Home</a></li>
            <li><a href="/about-netacube" class="{{ request()->is('about-netacube')  ? 'active' : '' }}">About</a></li>
            <li><a href="/features"       class="{{ request()->is('features')        ? 'active' : '' }}">Features</a></li>
            <li><a href="/pricing"        class="{{ request()->is('pricing')         ? 'active' : '' }}">Pricing</a></li>
            <li><a href="/contact"        class="{{ request()->is('contact')         ? 'active' : '' }}">Contact</a></li>
            <li><a href="/help-center"    class="{{ request()->is('help-center')     ? 'active' : '' }}">Help</a></li>
        </ul>

        <a href="/get-started" class="btn-nav-cta">Get Started</a>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- Mobile Menu -->
<div id="nav-mobile-menu">
    <a href="/">Home</a>
    <a href="/about-netacube">About</a>
    <a href="/features">Features</a>
    <a href="/pricing">Pricing</a>
    <a href="/contact">Contact</a>
    <a href="/help-center">Help Center</a>
    <a href="/login">Login</a>
    <a href="/get-started" class="btn-nav-cta">Get Started Free</a>
</div>

<!-- ══ Page Content ═════════════════════════════════════════════════════════ -->
<div class="page-body">

    @yield('content', View::make('website.homedefault'))

    @unless(request()->is('get-started'))

    <!-- ══ FAQ ════════════════════════════════════════════════════════════ -->
    <section class="section bg-white">
        <div class="container" style="max-width:1200px;">
            <div class="text-center mb-5">
                <span class="eyebrow">FAQ</span>
                <h2 class="display-section mt-2">Common questions</h2>
                <div class="section-divider"></div>
            </div>
            <div class="faq-wrap">
                @php
                $faqs = [
                    ['How does Netacube differ from other systems?',
                     'Netacube integrates all core business modules — inventory, POS, HR, payroll, invoicing and reporting — into a single unified platform with seamless data flow and offline capability.'],
                    ['Does Netacube work offline?',
                     'Yes. Core operations including sales, inventory updates and document generation continue without internet. Data synchronises automatically when connectivity is restored.'],
                    ['Can I customise invoices and other documents?',
                     'Yes. Templates support full customisation including logo, colours, fonts, layout and additional fields.'],
                    ['How secure is my data?',
                     'We use end-to-end encryption, daily backups, role-based access controls, audit logs and regular security audits to protect your data.'],
                    ['Does Netacube support multiple branches?',
                     'Yes. Full multi-branch support with centralised management, inter-branch transfers and consolidated reporting.'],
                    ['Is there a free trial?',
                     'Yes. We offer a 14-day free trial with full feature access and no credit card required.'],
                    ['What kind of support do you provide?',
                     'All plans include email and WhatsApp support. Higher-tier plans include priority response, phone support and dedicated account management.'],
                    ['Can I cancel or change my plan?',
                     'Monthly plans can be cancelled anytime. Annual and two-year plans are commitment-based for the discount but can be upgraded or discussed for special circumstances.'],
                    ['Do you offer data migration assistance?',
                     'Yes. Our team can assist with importing data from spreadsheets or existing systems during onboarding.'],
                ];
                @endphp
                @foreach($faqs as $i => $faq)
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        {{ $faq[0] }}
                        <i class="ri-add-line"></i>
                    </button>
                    <div class="faq-answer {{ $i === 0 ? 'open' : '' }}">{{ $faq[1] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ══ CTA ════════════════════════════════════════════════════════════ -->
    <section class="section-sm bg-alt">
        <div class="container" style="max-width:1200px;">
            <div class="cta-banner">
                <h2>Ready to transform your business?</h2>
                <p>Join businesses across Malawi and beyond trusting Netacube for reliable, efficient management.</p>
                <a href="/get-started" class="btn-cta-white">Start your free trial</a>
            </div>
        </div>
    </section>

    @endunless

    <!-- ══ Footer ══════════════════════════════════════════════════════════ -->
    <footer>
        <div class="footer-grid">
            <div class="footer-brand">
                <img src="{{ asset('website/images/wlogo.png') }}" alt="Netacube" class="footer-logo">
                <p>A complete business solution integrating inventory, sales, HR, payroll, invoicing, reporting and analytics into one reliable platform for retail, wholesale and service enterprises.</p>
            </div>
            <div class="footer-col">
                <h6>Quick links</h6>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/about-netacube">About us</a></li>
                    <li><a href="/features">Features</a></li>
                    <li><a href="/pricing">Pricing</a></li>
                    <li><a href="/help-center">Help centre</a></li>
                    <li><a href="/login">Login</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h6>Contact</h6>
                <div class="contact-item"><i class="ri-mail-line"></i><span>info@netamind.com</span></div>
                <div class="contact-item"><i class="ri-whatsapp-line"></i><span>+265 888 377 462</span></div>
                <div class="contact-item"><i class="ri-map-pin-line"></i><span>Lilongwe, Malawi</span></div>
            </div>
        </div>
        <div class="footer-bottom">
            © 2026 Netacube. All rights reserved. Powered by Netamind Technology.
        </div>
    </footer>

</div><!-- /page-body -->

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    /* Navbar scroll effect */
    window.addEventListener('scroll', function() {
        document.getElementById('topnav').classList.toggle('scrolled', window.scrollY > 10);
    });

    /* Mobile menu */
    var navToggle = document.getElementById('navToggle');
    var mobileMenu = document.getElementById('nav-mobile-menu');
    navToggle.addEventListener('click', function() {
        this.classList.toggle('open');
        mobileMenu.classList.toggle('open');
    });

    /* FAQ accordion */
    function toggleFaq(btn) {
        var answer = btn.nextElementSibling;
        var isOpen = answer.classList.contains('open');
        document.querySelectorAll('.faq-answer').forEach(function(a) { a.classList.remove('open'); });
        document.querySelectorAll('.faq-question').forEach(function(b) { b.classList.remove('open'); });
        if (!isOpen) {
            answer.classList.add('open');
            btn.classList.add('open');
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        var firstBtn = document.querySelector('.faq-question');
        if (firstBtn) firstBtn.classList.add('open');
    });
</script>

@yield('scripts')
</body>
</html>