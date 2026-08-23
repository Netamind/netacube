@extends('website.homepage')
@section('content')

<style>
    /* =========================================================
         FEATURES PAGE — built on the tokens & utility classes
         already defined in website.homepage
    ========================================================== */

    .page-hero{
        position:relative;
        padding:64px 0 60px;
        background:#fff;
        overflow:hidden;
    }
    .page-hero:before{
        content:"";
        position:absolute;
        width:640px;
        height:640px;
        left:-260px;
        top:-220px;
        border-radius:50%;
        background:radial-gradient(circle,rgba(23,111,229,.10) 0%,rgba(23,111,229,.03) 45%,transparent 72%);
        pointer-events:none;
    }
    .page-hero-inner{
        position:relative;
        z-index:2;
        max-width:720px;
        margin:0 auto;
        text-align:center;
    }
    .page-hero .hero-label{margin:0 auto}
    .page-hero h1{
        margin-top:20px;
        color:var(--navy);
        font-family:"Manrope",sans-serif;
        font-size:clamp(36px,4.4vw,54px);
        line-height:1.08;
        letter-spacing:-2.5px;
        font-weight:800;
    }
    .page-hero p{
        max-width:620px;
        margin:18px auto 0;
        color:var(--muted);
        font-size:15px;
        line-height:1.8;
    }

    /* ---- Feature grid (mirrors .business-types / .business) ---- */
    .feature-grid{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:18px;
        align-items:stretch;
    }
    .feature-card{
        min-height:210px;
        height:100%;
        display:flex;
        flex-direction:column;
        padding:28px;
        border:1px solid var(--line);
        border-radius:20px;
        background:#fff;
        transition:.25s;
    }
    .feature-card:hover{transform:translateY(-5px);box-shadow:var(--shadow);border-color:var(--blue-line)}
    .feature-card .icon{margin-bottom:2px}
    .feature-card h3{margin-top:18px;color:var(--navy);font:800 16px "Manrope",sans-serif}
    .feature-card p{margin-top:8px;color:var(--muted);font-size:12px;line-height:1.7}

    /* ---- Philosophy copy ---- */
    .philosophy-copy{max-width:720px;margin:0 auto;text-align:center}
    .philosophy-copy p{color:var(--muted);font-size:14px;line-height:1.85;margin-top:14px}

    /* ---- Pillars (reuses .business-types pattern) ---- */
    .pillar-grid{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:18px;
        max-width:1000px;
        margin:44px auto 0;
        align-items:stretch;
    }
    .pillar-card{
        padding:26px;
        border:1px solid var(--line);
        border-radius:20px;
        background:#fff;
        transition:.25s;
    }
    .pillar-card:hover{transform:translateY(-5px);box-shadow:var(--shadow);border-color:var(--blue-line)}
    .pillar-card h3{margin-top:16px;color:var(--navy);font:800 15px "Manrope",sans-serif}
    .pillar-card p{margin-top:8px;color:var(--muted);font-size:12px;line-height:1.7}

    .page-hero-visual{
        max-width:880px;
        margin:36px auto 0;
    }

    @media(max-width:1050px){
        .feature-grid{grid-template-columns:repeat(2,1fr)}
        .pillar-grid{grid-template-columns:1fr}
    }
    @media(max-width:600px){
        .feature-grid{grid-template-columns:1fr}
        .page-hero{padding:44px 0 40px}
    }
</style>

<main>

    <!-- =========================================================
         HERO
    ========================================================== -->
    <section class="page-hero">
        <div class="container">
            <div class="page-hero-inner reveal">
                <div class="hero-label">
                    <i></i>
                    Platform features
                </div>

                <h1>Everything your business runs on, working together.</h1>

                <p>
                    Netacube brings point of sale, inventory, people, payroll,
                    documents and reporting into one secure platform — built
                    so a single sale updates stock, finances and dashboards
                    at the same time, with nothing to reconcile by hand.
                </p>
            </div>

            <div class="page-hero-visual reveal">
                <div class="visual-card">
                    <img
                        src="{{ asset('images/home/dashboard.png') }}"
                        alt="Netacube business dashboard"
                    >
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================
         FEATURE GRID
    ========================================================== -->
    <section class="section soft" id="platform">
        <div class="container">

            <div class="section-intro center reveal">
                <div class="kicker">Core features</div>
                <h2>The tools that drive your business forward.</h2>
            </div>

            <div class="feature-grid">

                <div class="feature-card reveal">
                    <div class="icon">₵</div>
                    <h3>Point of sale</h3>
                    <p>Fast, reliable checkout with barcode scanning, multiple payment methods and customisable receipts — built to keep moving even during peak hours.</p>
                </div>

                <div class="feature-card reveal">
                    <div class="icon">▦</div>
                    <h3>Inventory management</h3>
                    <p>Real-time stock tracking, low-stock alerts, batch and variant management, multi-location support and automatic reorder suggestions.</p>
                </div>

                <div class="feature-card reveal">
                    <div class="icon">◌</div>
                    <h3>Full offline functionality</h3>
                    <p>Continue sales, stock updates and daily operations without an internet connection — everything syncs automatically the moment you're back online.</p>
                </div>

                <div class="feature-card reveal">
                    <div class="icon">◉</div>
                    <h3>Employee management</h3>
                    <p>Attendance tracking, leave management, shift scheduling, performance reviews and role-based access permissions, all in one place.</p>
                </div>

                <div class="feature-card reveal">
                    <div class="icon">$</div>
                    <h3>Payroll & deductions</h3>
                    <p>Automated payroll processing, salary calculations, statutory deductions, payslip generation and full payment history tracking.</p>
                </div>

                <div class="feature-card reveal">
                    <div class="icon">▤</div>
                    <h3>Document generation</h3>
                    <p>Professional, branded invoices, quotations, delivery notes, purchase orders and receipts generated from your own company profile.</p>
                </div>

                <div class="feature-card reveal">
                    <div class="icon">↗</div>
                    <h3>Advanced reporting & analytics</h3>
                    <p>Dashboards and reports covering sales, profit margins, inventory trends and staff performance — exportable in multiple formats.</p>
                </div>

                <div class="feature-card reveal">
                    <div class="icon">🛡</div>
                    <h3>Enterprise-grade security</h3>
                    <p>Role-based access control, activity audit logs, encrypted data and daily backups to keep your business information protected.</p>
                </div>

                <div class="feature-card reveal">
                    <div class="icon">⌂</div>
                    <h3>Multi-branch / multi-location</h3>
                    <p>Centralised control across stores or branches, with inter-branch transfers, consolidated reporting and location-specific settings.</p>
                </div>

            </div>
        </div>
    </section>

    <!-- =========================================================
         INTEGRATION & PHILOSOPHY
    ========================================================== -->
    <section class="section" id="why">
        <div class="container">

            <div class="section-intro center reveal">
                <div class="kicker">Why it feels different</div>
                <h2>A truly unified business platform.</h2>
            </div>

            <div class="philosophy-copy reveal">
                <p>
                    Netacube is built as one connected system, not a bundle of
                    separate tools. A sale completed at the till instantly
                    adjusts stock levels, updates financial records and feeds
                    your dashboards — no manual reconciliation required. Staff
                    shifts and attendance flow straight into payroll, while
                    the same security layer protects every module.
                </p>
                <p>
                    This integration removes repeated data entry and reduces
                    errors, giving you one accurate picture of how your
                    business is performing. It's designed so non-technical
                    teams can use it confidently from day one, whether you're
                    running a single shop or a growing network of branches.
                </p>
            </div>

            <div class="pillar-grid">

                <div class="pillar-card reveal">
                    <div class="business-icon">⇄</div>
                    <h3>Seamless integration</h3>
                    <p>Every module communicates in real time, so your data stays consistent across the whole operation.</p>
                </div>

                <div class="pillar-card reveal">
                    <div class="business-icon">🛡</div>
                    <h3>Built for reliability</h3>
                    <p>Offline capability and robust security keep your business running smoothly, whatever the conditions.</p>
                </div>

                <div class="pillar-card reveal">
                    <div class="business-icon">↗</div>
                    <h3>Scalable growth</h3>
                    <p>From a single shop to a multi-branch enterprise, Netacube adapts with flexible, expandable features.</p>
                </div>

            </div>
        </div>
    </section>

    <!-- =========================================================
         FINAL CTA
    ========================================================== -->
    <section class="cta">
        <div class="container">
            <div class="cta-box reveal">
                <div class="cta-content">
                    <small>See the platform for yourself</small>
                    <h2>Ready to bring it all together?</h2>
                    <p>
                        Start a free trial or talk to our team to see how
                        Netacube fits your business.
                    </p>

                    <div class="cta-actions">
                        <a href="{{ url('/get-started') }}" class="button button-white">
                            Get started →
                        </a>
                        <a href="{{ url('/contact') }}" class="button button-light">
                            Talk to us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

@endsection