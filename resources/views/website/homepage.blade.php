<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Netacube — All-in-one Business Management Platform')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'Netacube is a business management platform for retail, wholesale, hospitality, healthcare and more — inventory, point of sale with offline support, staff, payroll, invoicing and multi-branch reporting in one place.')">
    <link rel="shortcut icon" href="{{ asset('website/images/icon.png') }}">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Remixicons (same icon set as the dashboard) -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand:        #4B5EBD;
            --brand-mid:    #576CC0;
            --brand-dark:   #3a4da0;
            --brand-light:  #eef0f9;
            --ink:          #1c2333;
            --ink-soft:     #5b6478;
            --muted:        #8189a0;
            --surface:      #ffffff;
            --surface-alt:  #f6f7fb;
            --line:         rgba(75,94,189,0.14);
            --radius:       10px;
            --radius-lg:    16px;
            --shadow:       0 4px 8px rgba(0,0,0,.08);
            --shadow-lg:    0 12px 32px rgba(75,94,189,.16);
            --nav-h:        72px;
            --gradient:     linear-gradient(100deg, #4B5EBD 0%, #576CC0 100%);
            --gradient-deep: linear-gradient(165deg, #1c2342 0%, #2a3568 50%, #3a4da0 100%);
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink-soft);
            background: var(--surface);
            margin: 0;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; display: block; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); }

        .display-hero    { font-size: clamp(2.1rem, 4.4vw, 3.2rem); font-weight: 800; line-height: 1.14; letter-spacing: -0.025em; }
        .display-section { font-size: clamp(1.5rem, 3vw, 2.05rem); font-weight: 800; line-height: 1.22; letter-spacing: -0.02em; }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
            color: var(--brand);
        }
        .eyebrow::before { content: ''; width: 18px; height: 2px; background: var(--brand); border-radius: 2px; display: inline-block; }
        .lead-text { font-size: 1.04rem; line-height: 1.75; color: var(--ink-soft); }

        #topnav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            height: var(--nav-h);
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--line);
            display: flex; align-items: center;
            transition: box-shadow .2s ease;
        }
        #topnav.scrolled { box-shadow: 0 6px 24px rgba(75,94,189,.12); }
        .nav-inner { max-width: 1240px; margin: 0 auto; padding: 0 24px; display: flex; align-items: center; width: 100%; }
        .nav-logo { display: flex; align-items: center; }
        .nav-logo img { height: 36px; }
        .nav-links { display: flex; align-items: center; gap: 2px; margin-left: 40px; margin-right: auto; list-style: none; padding: 0; margin-bottom: 0; }
        .nav-links a { font-size: 0.875rem; font-weight: 600; color: var(--ink-soft); padding: 8px 14px; border-radius: 7px; transition: .15s; }
        .nav-links a:hover, .nav-links a.active { color: var(--brand); background: var(--brand-light); }
        .nav-cta-group { display: flex; align-items: center; gap: 10px; }
        .btn-nav-login { font-size: 0.875rem; font-weight: 600; color: var(--ink-soft); padding: 9px 16px; }
        .btn-nav-login:hover { color: var(--brand); }
        .btn-nav-cta {
            background: var(--gradient); color: #fff !important; font-size: 0.875rem; font-weight: 700;
            padding: 10px 22px; border-radius: 9px; border: none; cursor: pointer; white-space: nowrap;
            transition: .2s; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-nav-cta:hover { box-shadow: 0 8px 22px rgba(75,94,189,.38); transform: translateY(-1px); color: #fff; }

        .nav-toggle {
            display: none; flex-direction: column; justify-content: center; align-items: center;
            width: 38px; height: 38px; background: none; border: 1px solid var(--line); border-radius: 8px;
            cursor: pointer; margin-left: auto; gap: 4px; padding: 6px;
        }
        .nav-toggle span { display: block; width: 18px; height: 2px; background: var(--ink); border-radius: 2px; transition: .2s; }
        .nav-toggle.open span:nth-child(1) { transform: translateY(6px) rotate(45deg); }
        .nav-toggle.open span:nth-child(2) { opacity: 0; }
        .nav-toggle.open span:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

        #nav-mobile-menu {
            display: none; position: fixed; top: var(--nav-h); left: 0; right: 0; background: #fff;
            border-bottom: 1px solid var(--line); padding: 14px 24px 22px; z-index: 999; box-shadow: 0 10px 30px rgba(0,0,0,.08);
        }
        #nav-mobile-menu.open { display: block; }
        #nav-mobile-menu a { display: block; padding: 11px 0; font-size: 0.95rem; font-weight: 600; color: var(--ink-soft); border-bottom: 1px solid var(--line); }
        #nav-mobile-menu a:last-child { border-bottom: none; }
        #nav-mobile-menu a:hover { color: var(--brand); }
        #nav-mobile-menu .btn-nav-cta { display: block; text-align: center; margin-top: 14px; padding: 13px; }

        .page-body { padding-top: var(--nav-h); }

        .section { padding: 96px 0; }
        .section-sm { padding: 64px 0; }
        .bg-alt { background: var(--surface-alt); }
        .bg-white { background: #fff; }
        .section-divider { width: 44px; height: 3px; background: var(--gradient); border-radius: 2px; margin-top: 14px; }
        .center .section-divider { margin-left: auto; margin-right: auto; }

        .btn-primary-nc {
            display: inline-flex; align-items: center; gap: 8px; background: var(--gradient); color: #fff;
            font-size: 0.93rem; font-weight: 700; padding: 14px 28px; border-radius: 10px; border: none; cursor: pointer;
            transition: .2s;
        }
        .btn-primary-nc:hover { box-shadow: 0 10px 28px rgba(75,94,189,.36); transform: translateY(-2px); color: #fff; }
        .btn-ghost-nc {
            display: inline-flex; align-items: center; gap: 8px; background: #fff; color: var(--brand);
            font-size: 0.93rem; font-weight: 700; padding: 13px 26px; border-radius: 10px; border: 1.5px solid var(--line); cursor: pointer;
            transition: .2s;
        }
        .btn-ghost-nc:hover { border-color: var(--brand); background: var(--brand-light); }

        .nc-card { background: #fff; border: none; border-radius: var(--radius-lg); box-shadow: var(--shadow); overflow: hidden; }

        .faq-wrap { max-width: 760px; margin: 0 auto; }
        .faq-item { border: 1px solid var(--line); border-radius: var(--radius); margin-bottom: 10px; overflow: hidden; background: #fff; }
        .faq-question {
            width: 100%; background: none; border: none; padding: 18px 22px; display: flex; justify-content: space-between;
            align-items: center; font-size: 0.95rem; font-weight: 700; color: var(--ink); cursor: pointer; text-align: left; gap: 12px;
        }
        .faq-question i { font-size: 1.2rem; color: var(--brand); transition: transform .2s; flex-shrink: 0; }
        .faq-question.open i { transform: rotate(45deg); }
        .faq-answer { display: none; padding: 0 22px 18px; font-size: 0.92rem; color: var(--ink-soft); line-height: 1.7; }
        .faq-answer.open { display: block; }

        .cta-banner { background: var(--gradient); border-radius: var(--radius-lg); padding: 56px 48px; text-align: center; color: #fff; position: relative; overflow: hidden; }
        .cta-banner::before {
            content: ''; position: absolute; width: 380px; height: 380px; border-radius: 50%;
            background: rgba(255,255,255,0.08); top: -160px; right: -100px;
        }
        .cta-banner h2 { color: #fff; font-size: 2rem; font-weight: 800; margin-bottom: 12px; position: relative; }
        .cta-banner p { color: rgba(255,255,255,0.85); font-size: 1.05rem; margin-bottom: 28px; position: relative; }
        .btn-cta-white {
            background: #fff; color: var(--brand); font-weight: 700; font-size: 0.95rem; padding: 14px 32px; border-radius: 10px;
            border: none; cursor: pointer; transition: .2s; display: inline-block; position: relative;
        }
        .btn-cta-white:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,.2); color: var(--brand-dark); }

        footer {
            background: var(--gradient-deep);
            color: rgba(255,255,255,0.72);
            position: relative;
        }
        footer .footer-accent { height: 3px; background: linear-gradient(90deg, #aab8ff 0%, #fff 50%, #aab8ff 100%); opacity: .55; }
        .footer-top { padding: 60px 0 50px; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; max-width: 1240px; margin: 0 auto; padding: 0 24px; }
        .footer-brand-mark { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
        .footer-brand-mark .mark {
            width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.22);
            display: flex; align-items: center; justify-content: center;
        }
        .footer-brand-mark .mark i { color: #fff; font-size: 1.15rem; }
        .footer-brand-mark span { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.15rem; color: #fff; }
        .footer-col p { font-size: 0.875rem; line-height: 1.8; color: rgba(255,255,255,0.62); max-width: 300px; }
        .footer-col h6 { font-size: 0.74rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.9); margin-bottom: 18px; }
        .footer-col ul { list-style: none; padding: 0; margin: 0; }
        .footer-col ul li { margin-bottom: 10px; }
        .footer-col ul li a { font-size: 0.875rem; color: rgba(255,255,255,0.62); transition: .15s; }
        .footer-col ul li a:hover { color: #fff; }
        .footer-col .contact-item { display: flex; align-items: flex-start; gap: 10px; font-size: 0.875rem; color: rgba(255,255,255,0.62); margin-bottom: 12px; }
        .footer-col .contact-item i { color: #aab8ff; margin-top: 1px; flex-shrink: 0; font-size: 1.05rem; }
        .footer-social { display: flex; gap: 10px; margin-top: 18px; }
        .footer-social a { width: 36px; height: 36px; border-radius: 9px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.16); display: flex; align-items: center; justify-content: center; transition: .2s; }
        .footer-social a:hover { background: #fff; border-color: transparent; }
        .footer-social a:hover i { color: var(--brand); }
        .footer-social a i { font-size: 1.05rem; color: rgba(255,255,255,0.85); transition: .2s; }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.12);
            padding: 22px 24px;
            text-align: center;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.5);
            max-width: 1240px;
            margin: 0 auto;
        }

        @media (max-width: 991px) {
            .nav-links, .nav-cta-group { display: none !important; }
            .nav-toggle { display: flex; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .section { padding: 64px 0; }
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
            <img src="{{ asset('website/images/blogo.png') }}" alt="" style="height:42px;">
        </a>

        <ul class="nav-links">
            <li><a href="/"           class="{{ request()->is('/')          ? 'active' : '' }}">Home</a></li>
            <li><a href="/about" class="{{ request()->is('about') ? 'active' : '' }}">About</a></li>
            <li><a href="/features"   class="{{ request()->is('features')   ? 'active' : '' }}">Features</a></li>
           <!-- <li><a href="/help-center"   class="{{ request()->is('help-center')   ? 'active' : '' }}">Help Center</a></li>-->
            <li><a href="/pricing"    class="{{ request()->is('pricing')    ? 'active' : '' }}">Pricing</a></li>
            <li><a href="/contact"    class="{{ request()->is('contact')    ? 'active' : '' }}">Contact</a></li>
        </ul>

        

        <div class="nav-cta-group">
            <a href="/login" class="btn-nav-login">Sign in</a>
            <a href="/get-started" class="btn-nav-cta"><i class="ri-rocket-line"></i> Get started</a>
        </div>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<div id="nav-mobile-menu">
    <a href="/">Home</a>
    <a href="/industries">Who it's for</a>
    <a href="/features">Features</a>
    <a href="/pricing">Pricing</a>
    <a href="/contact">Contact</a>
    <a href="/login">Sign in</a>
    <a href="/get-started" class="btn-nav-cta">Get started free</a>
</div>

<!-- ══ Page content ════════════════════════════════════════════════════════ -->
<div class="page-body">

    @yield('content', View::make('website.homedefault'))

    @unless(request()->is('get-started'))

    <!-- ══ FAQ ══════════════════════════════════════════════════════════════ -->
    <section class="section bg-white">
        <div class="container" style="max-width:1200px;">
            <div class="text-center center mb-5">
                <span class="eyebrow">Questions</span>
                <h2 class="display-section mt-2">Frequently asked questions</h2>
                <div class="section-divider"></div>
            </div>
            <div class="faq-wrap">
                @php
                $faqs = [
                    ['Is my business information kept separate from other businesses on Netacube?',
                     'Yes. Every business that joins Netacube gets its own private, secure account. Your records, sales, staff and files are never mixed with another business — what you see is only ever yours.'],
                    ['Does Netacube work for my type of business?',
                     'Netacube is built to support retail shops, wholesalers, hotels and restaurants, clinics, finance and consulting firms, IT service providers, and property and rental businesses — each with workflows, documents and reports suited to how they actually operate.'],
                    ['What happens if my branch loses internet connection?',
                     'Sales and stock changes keep recording right at the till. Once the connection comes back, everything updates automatically in the background — no sales are lost and nothing needs to be re-entered.'],
                    ['Can I manage more than one branch from a single account?',
                     'Yes. Add as many branches as your business needs. Each branch records its own sales and stock, while owners and managers see one combined view of performance across every location.'],
                    ['How is our information kept safe?',
                     'Your data is encrypted, backed up daily, and access is controlled by staff roles and permissions — so people only see what their role allows.'],
                    ['Is there a free trial?',
                     'Yes. Every new business gets a 14-day free trial with full access to the system — no card details required to get started.'],
                    ['What support do we get after signing up?',
                     'All plans include email and WhatsApp support, with guided onboarding to help you set up your branches, currencies and company details correctly from day one.'],
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

    <!-- ══ CTA ══════════════════════════════════════════════════════════════ -->
    <section class="section-sm bg-alt">
        <div class="container" style="max-width:1200px;">
            <div class="cta-banner">
                <h2>Ready to run your business on one platform?</h2>
                <p>Start your free 14-day trial — set up your branches and start selling in minutes.</p>
                <a href="/get-started" class="btn-cta-white">Create your free account</a>
            </div>
        </div>
    </section>

    @endunless

    <!-- ══ Footer ══════════════════════════════════════════════════════════════ -->
    <footer>
        <div class="footer-accent"></div>
        <div class="footer-top">
            <div class="footer-grid">
                <div class="footer-col footer-brand">
                    <div class="footer-brand-mark">
                        <img src="{{ asset('website/images/wlogo.png') }}" alt="Netacube" style="height:34px;">
                    </div>
                    <p>A complete business management platform unifying inventory, point of sale, branches, staff, payroll, invoicing and reporting — built for retail, wholesale, hospitality, healthcare and service businesses.</p>
                    <div class="footer-social">
                        <a href="#" aria-label="Facebook"><i class="ri-facebook-fill"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="ri-linkedin-fill"></i></a>
                        <a href="https://wa.me/265992522601" aria-label="WhatsApp"><i class="ri-whatsapp-line"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h6>Platform</h6>
                    <ul>
                        <li><a href="/about">About</a></li>
                        <li><a href="/features">Features</a></li>
                        <li><a href="/pricing">Pricing</a></li>
                        <li><a href="/get-started">Get started</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h6>Company</h6>
                    <ul>
                        <li><a href="/contact">Contact</a></li>
                        <li><a href="/login">Sign in</a></li>
                        <li><a href="#">Terms of service</a></li>
                        <li><a href="#">Privacy policy</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h6>Contact</h6>
                    <div class="contact-item"><i class="ri-mail-line"></i><span>info@netamind.com</span></div>
                    <div class="contact-item"><i class="ri-whatsapp-line"></i><span>+265 99 25 22 601</span></div>
                    <div class="contact-item"><i class="ri-map-pin-line"></i><span>Mzuzu, Malawi - Best oil (Room No 11)</span></div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            © {{ date('Y') }} Netacube. All rights reserved. Powered by Netamind Technology.
        </div>
    </footer>

</div><!-- /page-body -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.addEventListener('scroll', function() {
        document.getElementById('topnav').classList.toggle('scrolled', window.scrollY > 10);
    });

    var navToggle = document.getElementById('navToggle');
    var mobileMenu = document.getElementById('nav-mobile-menu');
    navToggle.addEventListener('click', function() {
        this.classList.toggle('open');
        mobileMenu.classList.toggle('open');
    });

    function toggleFaq(btn) {
        var answer = btn.nextElementSibling;
        var isOpen = answer.classList.contains('open');
        document.querySelectorAll('.faq-answer').forEach(function(a) { a.classList.remove('open'); });
        document.querySelectorAll('.faq-question').forEach(function(b) { b.classList.remove('open'); });
        if (!isOpen) { answer.classList.add('open'); btn.classList.add('open'); }
    }
    document.addEventListener('DOMContentLoaded', function() {
        var firstBtn = document.querySelector('.faq-question');
        if (firstBtn) firstBtn.classList.add('open');
    });
</script>

@yield('scripts')
</body>
</html>