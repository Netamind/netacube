<style>
    /* ══ Hero ══════════════════════════════════════════════════════════════ */
    .hero {
        background: #0c1128;
        min-height: calc(100vh - 68px);
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
        padding: 80px 0 60px;
    }
    .hero-bg-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(75,94,189,0.07) 1px, transparent 1px),
            linear-gradient(90deg, rgba(75,94,189,0.07) 1px, transparent 1px);
        background-size: 48px 48px;
        pointer-events: none;
    }
    .hero-bg-glow {
        position: absolute;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(75,94,189,0.25) 0%, transparent 70%);
        top: -100px;
        right: -100px;
        pointer-events: none;
    }
    .hero-bg-glow-2 {
        position: absolute;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(107,125,232,0.15) 0%, transparent 70%);
        bottom: 0;
        left: 0;
        pointer-events: none;
    }
    .hero-content { position: relative; z-index: 2; }
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(75,94,189,0.18);
        border: 1px solid rgba(75,94,189,0.35);
        color: #93a8f0;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        padding: 6px 14px;
        border-radius: 20px;
        margin-bottom: 24px;
        text-transform: uppercase;
    }
    .hero-badge i { font-size: 0.9rem; }
    .hero h1 {
        font-size: clamp(2.4rem, 5.5vw, 3.6rem);
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -0.03em;
        color: #fff;
        margin-bottom: 20px;
    }
    .hero h1 .accent {
        background: linear-gradient(135deg, #6b7de8 0%, #93a8f0 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .hero-lead {
        font-size: 1.08rem;
        line-height: 1.72;
        color: rgba(255,255,255,0.62);
        max-width: 500px;
        margin-bottom: 36px;
    }
    .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 48px; }
    .btn-hero-primary {
        background: #4B5EBD;
        color: #fff;
        font-weight: 700;
        font-size: 0.95rem;
        padding: 14px 30px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        border: none;
        text-decoration: none;
    }
    .btn-hero-primary:hover {
        background: #3a4da0;
        transform: translateY(-2px);
        box-shadow: 0 10px 32px rgba(75,94,189,0.40);
        color: #fff;
    }
    .btn-hero-ghost {
        background: rgba(255,255,255,0.08);
        color: rgba(255,255,255,0.85);
        font-weight: 600;
        font-size: 0.95rem;
        padding: 13px 28px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid rgba(255,255,255,0.15);
        transition: background 0.2s, color 0.2s;
        text-decoration: none;
    }
    .btn-hero-ghost:hover {
        background: rgba(255,255,255,0.14);
        color: #fff;
    }
    .hero-stats { display: flex; gap: 32px; flex-wrap: wrap; }
    .hero-stat { display: flex; flex-direction: column; }
    .hero-stat .num { font-size: 1.7rem; font-weight: 800; color: #fff; line-height: 1; }
    .hero-stat .lbl { font-size: 0.78rem; color: rgba(255,255,255,0.45); margin-top: 4px; font-weight: 500; }
    .hero-stat-divider { width: 1px; background: rgba(255,255,255,0.1); }

    .hero-visual { position: relative; z-index: 2; }
    .hero-screen {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 32px 80px rgba(0,0,0,0.4);
    }
    .hero-screen-bar {
        background: rgba(255,255,255,0.06);
        padding: 10px 16px;
        display: flex;
        align-items: center;
        gap: 6px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .hero-screen-dot { width: 8px; height: 8px; border-radius: 50%; }
    .hero-screen img { width: 100%; display: block; }

    .hero-float-card {
        position: absolute;
        background: rgba(20,26,56,0.92);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(75,94,189,0.3);
        border-radius: 12px;
        padding: 12px 16px;
        color: #fff;
        font-size: 0.8rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 9px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        z-index: 3;
        animation: float 4s ease-in-out infinite;
    }
    .hero-float-card i { font-size: 1.2rem; color: #7dd3b8; }
    .hero-float-card .sub { font-size: 0.68rem; font-weight: 400; color: rgba(255,255,255,0.5); display: block; margin-top: 1px; }
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50%       { transform: translateY(-8px); }
    }

    /* ══ Trust strip ══ */
    .trust-strip {
        background: #f5f6fb;
        border-top: 1px solid rgba(75,94,189,0.12);
        border-bottom: 1px solid rgba(75,94,189,0.12);
        padding: 28px 0;
    }
    .trust-strip-inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 24px;
        display: flex;
        align-items: center;
        gap: 24px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .trust-label {
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #6b7280;
        white-space: nowrap;
    }
    .trust-dots { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .trust-dot {
        background: #eef0f9;
        border: 1px solid rgba(75,94,189,0.12);
        border-radius: 30px;
        padding: 7px 18px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #4B5EBD;
        white-space: nowrap;
    }

    /* ══ Section utility ══ */
    .hd-section      { padding: 88px 0; }
    .hd-section-sm   { padding: 60px 0; }
    .hd-bg-alt       { background: #f5f6fb; }
    .hd-bg-white     { background: #fff; }
    .hd-eyebrow {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #4B5EBD;
    }
    .hd-display-section {
        font-size: clamp(1.6rem, 3.5vw, 2.2rem);
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: -0.02em;
        color: #0f1623;
    }
    .hd-lead {
        font-size: 1.05rem;
        line-height: 1.7;
        color: #6b7280;
    }
    .hd-divider {
        width: 40px;
        height: 3px;
        background: #4B5EBD;
        border-radius: 2px;
        margin: 12px auto 0;
    }

    /* ══ Features ══ */
    .hd-features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
    .hd-feature-card {
        background: #fff;
        border: 1px solid rgba(75,94,189,0.12);
        border-radius: 18px;
        padding: 28px;
        transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;
    }
    .hd-feature-card:hover {
        box-shadow: 0 8px 32px rgba(75,94,189,0.12);
        transform: translateY(-3px);
        border-color: rgba(75,94,189,0.25);
    }
    .hd-feature-icon {
        width: 48px;
        height: 48px;
        background: #eef0f9;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }
    .hd-feature-icon i { font-size: 1.4rem; color: #4B5EBD; }
    .hd-feature-card h5 { font-size: 0.95rem; font-weight: 700; color: #0f1623; margin-bottom: 8px; }
    .hd-feature-card p  { font-size: 0.85rem; color: #6b7280; line-height: 1.65; margin: 0; }

    /* ══ Why grid ══ */
    .hd-why-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }
    .hd-why-card {
        background: #fff;
        border: 1px solid rgba(75,94,189,0.12);
        border-radius: 12px;
        padding: 24px 20px;
        text-align: center;
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .hd-why-card:hover {
        box-shadow: 0 6px 24px rgba(75,94,189,0.1);
        transform: translateY(-3px);
    }
    .hd-why-icon {
        width: 52px;
        height: 52px;
        background: #eef0f9;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 14px;
    }
    .hd-why-icon i { font-size: 1.4rem; color: #4B5EBD; }
    .hd-why-card h5 { font-size: 0.88rem; font-weight: 700; color: #0f1623; margin-bottom: 6px; }
    .hd-why-card p  { font-size: 0.8rem; color: #6b7280; line-height: 1.6; margin: 0; }

    /* ══ Gallery ══ */
    .hd-gallery-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
    .hd-gallery-item {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(75,94,189,0.12);
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .hd-gallery-item:hover {
        box-shadow: 0 12px 40px rgba(75,94,189,0.14);
        transform: translateY(-4px);
    }
    .hd-gallery-item img { width: 100%; height: 200px; object-fit: cover; display: block; }
    .hd-gallery-cap {
        background: #fff;
        padding: 12px 16px;
        font-size: 0.83rem;
        font-weight: 600;
        color: #0f1623;
        border-top: 1px solid rgba(75,94,189,0.12);
    }

    /* ══ Pricing ══ */
    .hd-pricing-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        max-width: 860px;
        margin: 0 auto;
    }
    .hd-pricing-card {
        background: #fff;
        border: 1.5px solid rgba(75,94,189,0.12);
        border-radius: 18px;
        padding: 36px 28px;
        text-align: center;
        position: relative;
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .hd-pricing-card:hover {
        box-shadow: 0 8px 32px rgba(75,94,189,0.12);
        transform: translateY(-4px);
    }
    .hd-pricing-card.featured {
        border-color: #4B5EBD;
        border-width: 2px;
        box-shadow: 0 4px 24px rgba(75,94,189,0.16);
    }
    .hd-pricing-badge {
        position: absolute;
        top: -13px;
        left: 50%;
        transform: translateX(-50%);
        background: #4B5EBD;
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 4px 16px;
        border-radius: 20px;
        white-space: nowrap;
    }
    .hd-pricing-period {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #6b7280;
        margin-bottom: 20px;
    }
    .hd-pricing-price {
        font-size: 2.8rem;
        font-weight: 800;
        color: #4B5EBD;
        line-height: 1;
        margin-bottom: 4px;
    }
    .hd-pricing-price sup { font-size: 1.2rem; vertical-align: top; margin-top: 8px; margin-right: 2px; }
    .hd-pricing-sub { font-size: 0.8rem; color: #6b7280; margin-bottom: 20px; }
    .hd-pricing-desc { font-size: 0.85rem; color: #6b7280; line-height: 1.6; margin-bottom: 28px; min-height: 56px; }
    .hd-btn-pricing {
        display: block;
        width: 100%;
        padding: 12px;
        border-radius: 9px;
        font-size: 0.9rem;
        font-weight: 700;
        text-align: center;
        transition: all 0.2s;
        text-decoration: none;
    }
    .hd-btn-pricing-primary { background: #4B5EBD; color: #fff; }
    .hd-btn-pricing-primary:hover { background: #3a4da0; color: #fff; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(75,94,189,0.3); }
    .hd-btn-pricing-outline { background: transparent; color: #4B5EBD; border: 1.5px solid #4B5EBD; }
    .hd-btn-pricing-outline:hover { background: #eef0f9; }
    .hd-pricing-note { font-size: 0.78rem; color: #6b7280; margin-top: 16px; }
    .hd-pricing-note strong { color: #0f1623; }

    /* ══ Steps ══ */
    .hd-steps-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        max-width: 900px;
        margin: 0 auto;
    }
    .hd-step-card {
        background: #fff;
        border: 1px solid rgba(75,94,189,0.12);
        border-radius: 18px;
        padding: 28px 28px 28px 24px;
        display: flex;
        align-items: flex-start;
        gap: 20px;
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .hd-step-card:hover { box-shadow: 0 8px 28px rgba(75,94,189,0.10); transform: translateY(-2px); }
    .hd-step-card.full { grid-column: 1 / -1; max-width: 440px; margin: 0 auto; }
    .hd-step-num {
        width: 48px;
        height: 48px;
        background: #4B5EBD;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        font-weight: 800;
        flex-shrink: 0;
    }
    .hd-step-body i { font-size: 1.6rem; color: #4B5EBD; display: block; margin-bottom: 8px; }
    .hd-step-body h5 { font-size: 0.95rem; font-weight: 700; color: #0f1623; margin-bottom: 8px; }
    .hd-step-body p  { font-size: 0.85rem; color: #6b7280; line-height: 1.65; margin: 0 0 12px; }
    .hd-step-link {
        font-size: 0.83rem;
        font-weight: 700;
        color: #4B5EBD;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-decoration: none;
    }
    .hd-step-link:hover { color: #3a4da0; }

    /* ══ Contact strip ══ */
    .hd-contact-strip {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        max-width: 760px;
        margin: 0 auto;
    }
    .hd-contact-card {
        text-align: center;
        padding: 28px 20px;
        background: #fff;
        border: 1px solid rgba(75,94,189,0.12);
        border-radius: 12px;
    }
    .hd-contact-card i { font-size: 2rem; color: #4B5EBD; margin-bottom: 10px; display: block; }
    .hd-contact-card h6 { font-size: 0.85rem; font-weight: 700; color: #0f1623; margin-bottom: 6px; }
    .hd-contact-card span { font-size: 0.83rem; color: #6b7280; }

    /* ══ Responsive ══ */
    @media (max-width: 1100px) {
        .hd-features-grid { grid-template-columns: repeat(2, 1fr); }
        .hd-why-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 991px) {
        .hero { min-height: auto; padding: 60px 0 40px; }
        .hd-gallery-grid { grid-template-columns: repeat(2, 1fr); }
        .hd-pricing-grid { grid-template-columns: 1fr; max-width: 380px; }
        .hero-float-card { display: none; }
        .hd-section { padding: 64px 0; }
    }
    @media (max-width: 767px) {
        .hd-features-grid { grid-template-columns: 1fr; }
        .hd-why-grid { grid-template-columns: repeat(2, 1fr); }
        .hd-gallery-grid { grid-template-columns: 1fr; }
        .hd-steps-grid { grid-template-columns: 1fr; }
        .hd-step-card.full { max-width: 100%; }
        .hd-contact-strip { grid-template-columns: 1fr; }
        .hero h1 { font-size: 2.1rem; }
        .hero-stats { gap: 20px; }
    }
    @media (max-width: 480px) {
        .hd-why-grid { grid-template-columns: 1fr; }
        .hero-actions { flex-direction: column; align-items: flex-start; }
    }
</style>

<!-- ══ Hero ══════════════════════════════════════════════════════════════ -->
<section class="hero">
    <div class="hero-bg-grid"></div>
    <div class="hero-bg-glow"></div>
    <div class="hero-bg-glow-2"></div>
    <div class="container" style="max-width:1200px;">
        <div class="row align-items-center hero-content">
            <div class="col-lg-6">
                <div class="hero-badge">
                    <i class="ri-shield-check-line"></i>
                    Trusted by businesses in Malawi
                </div>
                <h1>
                    One platform.<br>
                    <span class="accent">Every part</span><br>
                    of your business.
                </h1>
                <p class="hero-lead">
                    Inventory, sales, HR, payroll, invoicing and analytics — fully integrated, secure and built for retail, wholesale, hospitality and service businesses.
                </p>
                <div class="hero-actions">
                    <a href="/get-started" class="btn-hero-primary">
                        <i class="ri-rocket-line"></i> Start free trial
                    </a>
                    <a href="/features" class="btn-hero-ghost">
                        <i class="ri-play-circle-line"></i> Explore features
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="num">14</span>
                        <span class="lbl">Days free trial</span>
                    </div>
                    <div class="hero-stat-divider"></div>
                    <div class="hero-stat">
                        <span class="num">15+</span>
                        <span class="lbl">Business modules</span>
                    </div>
                    <div class="hero-stat-divider"></div>
                    <div class="hero-stat">
                        <span class="num">24/7</span>
                        <span class="lbl">Support included</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 hero-visual mt-4 mt-lg-0">
                <div style="position:relative;">
                    <div class="hero-screen">
                        <div class="hero-screen-bar">
                            <div class="hero-screen-dot" style="background:#ff5f57;"></div>
                            <div class="hero-screen-dot" style="background:#febc2e;"></div>
                            <div class="hero-screen-dot" style="background:#28c840;"></div>
                        </div>
                        <img src="{{ asset('website/images/s8.png') }}" alt="Netacube dashboard">
                    </div>
                    <div class="hero-float-card" style="bottom:40px; left:-32px; animation-delay:0s;">
                        <i class="ri-bar-chart-2-line"></i>
                        <div>
                            <span>Sales up 24%</span>
                            <span class="sub">This month vs last</span>
                        </div>
                    </div>
                    <div class="hero-float-card" style="top:30px; right:-28px; animation-delay:2s;">
                        <i class="ri-shield-check-line" style="color:#7dd3b8;"></i>
                        <div>
                            <span>Fully encrypted</span>
                            <span class="sub">Enterprise-grade security</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ Trust strip ══════════════════════════════════════════════════════ -->
<div class="trust-strip">
    <div class="trust-strip-inner">
        <span class="trust-label">Serving businesses across</span>
        <div class="trust-dots">
            <span class="trust-dot">Retail &amp; Supermarkets</span>
            <span class="trust-dot">Restaurants</span>
            <span class="trust-dot">Wholesale</span>
            <span class="trust-dot">Hotels &amp; Hospitality</span>
            <span class="trust-dot">Service Businesses</span>
        </div>
    </div>
</div>

<!-- ══ Key Features ═════════════════════════════════════════════════════ -->
<section class="hd-section hd-bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="text-center mb-5">
            <span class="hd-eyebrow">What's inside</span>
            <h2 class="hd-display-section mt-2">Everything your business needs</h2>
            <div class="hd-divider"></div>
            <p class="hd-lead mt-3 mx-auto" style="max-width:520px;">
                Core modules designed to streamline operations and give you real-time visibility across your business.
            </p>
        </div>
        <div class="hd-features-grid">
            <div class="hd-feature-card">
                <div class="hd-feature-icon"><i class="ri-team-line"></i></div>
                <h5>Employee management</h5>
                <p>Attendance tracking, payroll, leave management and performance reviews.</p>
            </div>
            <div class="hd-feature-card">
                <div class="hd-feature-icon"><i class="ri-archive-drawer-line"></i></div>
                <h5>Inventory control</h5>
                <p>Real-time stock levels, low-stock alerts, multi-location and batch tracking.</p>
            </div>
            <div class="hd-feature-card">
                <div class="hd-feature-icon"><i class="ri-price-tag-3-line"></i></div>
                <h5>Pricing &amp; promotions</h5>
                <p>Flexible price levels, automated discounts and loyalty programmes.</p>
            </div>
            <div class="hd-feature-card">
                <div class="hd-feature-icon"><i class="ri-wifi-off-line"></i></div>
                <h5>Offline capability</h5>
                <p>Full operations continue without internet with automatic sync on reconnection.</p>
            </div>
            <div class="hd-feature-card">
                <div class="hd-feature-icon"><i class="ri-shopping-cart-2-line"></i></div>
                <h5>Point of sale</h5>
                <p>Fast checkout with barcode scanning and multiple payment methods.</p>
            </div>
            <div class="hd-feature-card">
                <div class="hd-feature-icon"><i class="ri-file-text-line"></i></div>
                <h5>Document generation</h5>
                <p>Branded invoices, quotations and delivery notes with full customisation.</p>
            </div>
            <div class="hd-feature-card">
                <div class="hd-feature-icon"><i class="ri-bar-chart-grouped-line"></i></div>
                <h5>Analytics &amp; reports</h5>
                <p>Dashboards and reports for sales, profitability, inventory and performance.</p>
            </div>
            <div class="hd-feature-card">
                <div class="hd-feature-icon"><i class="ri-shield-check-line"></i></div>
                <h5>Security</h5>
                <p>Role-based access, audit logs, data encryption and daily backups.</p>
            </div>
            <div class="hd-feature-card">
                <div class="hd-feature-icon"><i class="ri-building-2-line"></i></div>
                <h5>Multi-branch support</h5>
                <p>Centralised control, inter-branch transfers and consolidated reporting.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══ Why Choose Netacube ══════════════════════════════════════════════ -->
<section class="hd-section hd-bg-alt">
    <div class="container" style="max-width:1200px;">
        <div class="text-center mb-5">
            <span class="hd-eyebrow">Why Netacube</span>
            <h2 class="hd-display-section mt-2">Built for businesses that mean business</h2>
            <div class="hd-divider"></div>
        </div>
        <div class="hd-why-grid">
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-shield-check-line"></i></div><h5>Enterprise security</h5><p>End-to-end encryption, RBAC, audit logs and daily backups.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-layout-line"></i></div><h5>Intuitive interface</h5><p>Clean design and logical workflows with minimal training required.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-store-3-line"></i></div><h5>Industry-specific</h5><p>Retail, wholesale, restaurants, hotels and service businesses.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-calendar-event-line"></i></div><h5>Events management</h5><p>Booking, scheduling, deposits, reminders and event tracking.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-clipboard-line"></i></div><h5>Stocktaking</h5><p>Flexible stock counts with variance reporting and adjustment.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-shopping-cart-line"></i></div><h5>Fast sales interface</h5><p>Simplified checkout with fewer steps and quicker processing.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-building-line"></i></div><h5>Unlimited branches</h5><p>Any number of branches with transfers and consolidated reports.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-refresh-line"></i></div><h5>Offline ready</h5><p>Continue operations without internet, auto-sync on reconnection.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-briefcase-4-line"></i></div><h5>Multiple businesses</h5><p>Manage several businesses with separate configs from one login.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-tag-3-line"></i></div><h5>Flexible pricing</h5><p>Customer-specific pricing, promotions and discount rules.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-user-heart-line"></i></div><h5>Customer management</h5><p>Profiles, purchase history, credit limits and loyalty tracking.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-truck-line"></i></div><h5>Purchases &amp; suppliers</h5><p>Purchase orders, receiving, supplier management and payables.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-file-check-line"></i></div><h5>Document management</h5><p>Invoices, quotations, delivery notes and more with your branding.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-wallet-3-line"></i></div><h5>Expense management</h5><p>Record, categorise and track expenses with approval workflows.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-line-chart-line"></i></div><h5>Financial reports</h5><p>P&amp;L, balance sheet, cash flow and custom statements.</p></div>
            <div class="hd-why-card"><div class="hd-why-icon"><i class="ri-money-dollar-circle-line"></i></div><h5>Payroll processing</h5><p>Automated salary, deductions, taxes and payslip generation.</p></div>
        </div>
    </div>
</section>

<!-- ══ Gallery ══════════════════════════════════════════════════════════ -->
<section class="hd-section hd-bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="text-center mb-5">
            <span class="hd-eyebrow">System gallery</span>
            <h2 class="hd-display-section mt-2">See it in action</h2>
            <div class="hd-divider"></div>
            <p class="hd-lead mt-3">Screenshots from key modules and interfaces</p>
        </div>
        <div class="hd-gallery-grid">
            <div class="hd-gallery-item">
                <img src="{{ asset('website/images/s8.png') }}" alt="Point of sale interface" loading="lazy">
                <div class="hd-gallery-cap">Point of sale interface</div>
            </div>
            <div class="hd-gallery-item">
                <img src="{{ asset('website/images/s6.png') }}" alt="Inventory management" loading="lazy">
                <div class="hd-gallery-cap">Inventory management</div>
            </div>
            <div class="hd-gallery-item">
                <img src="{{ asset('website/images/s4.png') }}" alt="HR dashboard" loading="lazy">
                <div class="hd-gallery-cap">HR dashboard</div>
            </div>
            <div class="hd-gallery-item">
                <img src="{{ asset('website/images/s3.png') }}" alt="Invoice generation" loading="lazy">
                <div class="hd-gallery-cap">Invoice generation</div>
            </div>
            <div class="hd-gallery-item">
                <img src="{{ asset('website/images/s7.png') }}" alt="Admin dashboard" loading="lazy">
                <div class="hd-gallery-cap">Admin dashboard</div>
            </div>
            <div class="hd-gallery-item">
                <img src="{{ asset('website/images/s5.png') }}" alt="Analytics overview" loading="lazy">
                <div class="hd-gallery-cap">Analytics overview</div>
            </div>
        </div>
    </div>
</section>

<!-- ══ Pricing ══════════════════════════════════════════════════════════ -->
<section class="hd-section hd-bg-alt">
    <div class="container" style="max-width:1200px;">
        <div class="text-center mb-5">
            <span class="hd-eyebrow">Simple pricing</span>
            <h2 class="hd-display-section mt-2">Choose your plan</h2>
            <div class="hd-divider"></div>
            <p class="hd-lead mt-3 mx-auto" style="max-width:540px;">
                Every plan gives full, unrestricted access to the entire Netacube system. The only difference is your payment period.
            </p>
        </div>
        <div class="hd-pricing-grid">
            <div class="hd-pricing-card">
                <div class="hd-pricing-period">6 months</div>
                <div class="hd-pricing-price"><sup>$</sup>120</div>
                <div class="hd-pricing-sub">USD total · $20/month</div>
                <p class="hd-pricing-desc">Best for businesses wanting a shorter commitment before going long-term.</p>
                <a href="/get-started" class="hd-btn-pricing hd-btn-pricing-outline">Get started</a>
                <div class="hd-pricing-note"><strong>Full access</strong> to all modules</div>
            </div>
            <div class="hd-pricing-card featured">
                <div class="hd-pricing-badge">Most popular</div>
                <div class="hd-pricing-period">1 year</div>
                <div class="hd-pricing-price"><sup>$</sup>220</div>
                <div class="hd-pricing-sub">USD total · $18.33/month</div>
                <p class="hd-pricing-desc">The balanced choice — good value for growing businesses with a one-year horizon.</p>
                <a href="/get-started" class="hd-btn-pricing hd-btn-pricing-primary">Get started</a>
                <div class="hd-pricing-note"><strong>Full access</strong> · 14-day free trial</div>
            </div>
            <div class="hd-pricing-card">
                <div class="hd-pricing-period">2 years</div>
                <div class="hd-pricing-price"><sup>$</sup>400</div>
                <div class="hd-pricing-sub">USD total · $16.67/month</div>
                <p class="hd-pricing-desc">The best long-term value with the lowest cost per month for committed businesses.</p>
                <a href="/get-started" class="hd-btn-pricing hd-btn-pricing-outline">Get started</a>
                <div class="hd-pricing-note"><strong>Full access</strong> · Best value</div>
            </div>
        </div>
        <p class="text-center mt-4" style="font-size:0.85rem; color:#6b7280;">
            All plans include 24/7 support, data backups and all future feature updates. No hidden fees.
        </p>
    </div>
</section>

<!-- ══ Onboarding Steps ══════════════════════════════════════════════════ -->
<section class="hd-section hd-bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="text-center mb-5">
            <span class="hd-eyebrow">Getting started</span>
            <h2 class="hd-display-section mt-2">Up and running in minutes</h2>
            <div class="hd-divider"></div>
            <p class="hd-lead mt-3">Fast, secure onboarding with immediate full access to all features</p>
        </div>
        <div class="hd-steps-grid">
            <div class="hd-step-card">
                <div class="hd-step-num">1</div>
                <div class="hd-step-body">
                    <i class="ri-file-add-line"></i>
                    <h5>Register your business</h5>
                    <p>Complete our secure registration form with your business details and choose your preferred plan.</p>
                    <a href="/get-started" class="hd-step-link">Start registration <i class="ri-arrow-right-line"></i></a>
                </div>
            </div>
            <div class="hd-step-card">
                <div class="hd-step-num">2</div>
                <div class="hd-step-body">
                    <i class="ri-mail-line"></i>
                    <h5>Receive login &amp; invoice</h5>
                    <p>Your account credentials and a professional invoice are sent instantly to your email. Invoice is due within 14 days.</p>
                </div>
            </div>
            <div class="hd-step-card">
                <div class="hd-step-num">3</div>
                <div class="hd-step-body">
                    <i class="ri-dashboard-line"></i>
                    <h5>Access the full dashboard</h5>
                    <p>Log in immediately and explore all features with no restrictions or waiting period.</p>
                    <a href="/login" class="hd-step-link">Login now <i class="ri-arrow-right-line"></i></a>
                </div>
            </div>
            <div class="hd-step-card">
                <div class="hd-step-num">4</div>
                <div class="hd-step-body">
                    <i class="ri-money-dollar-circle-line"></i>
                    <h5>Complete payment</h5>
                    <p>Settle your invoice within 14 days to ensure uninterrupted access and service continuity.</p>
                </div>
            </div>
            <div class="hd-step-card full">
                <div class="hd-step-num">5</div>
                <div class="hd-step-body">
                    <i class="ri-customer-service-2-line"></i>
                    <h5>Ongoing dedicated support</h5>
                    <p>Our team is available 24/7 via email and WhatsApp for any assistance you need.</p>
                    <a href="/contact" class="hd-step-link">Contact support <i class="ri-arrow-right-line"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ Contact strip ════════════════════════════════════════════════════ -->
<section class="hd-section hd-bg-alt">
    <div class="container" style="max-width:1200px;">
        <div class="text-center mb-5">
            <span class="hd-eyebrow">Get in touch</span>
            <h2 class="hd-display-section mt-2">We're here to help</h2>
            <div class="hd-divider"></div>
        </div>
        <div class="hd-contact-strip">
            <div class="hd-contact-card">
                <i class="ri-mail-line"></i>
                <h6>Email</h6>
                <span><a href="mailto:info@netamind.com" style="color:#4B5EBD;">info@netamind.com</a></span>
            </div>
            <div class="hd-contact-card">
                <i class="ri-whatsapp-line"></i>
                <h6>WhatsApp</h6>
                <span>+265 888 377 462</span>
            </div>
            <div class="hd-contact-card">
                <i class="ri-map-pin-line"></i>
                <h6>Location</h6>
                <span>Lilongwe, Malawi</span>
            </div>
        </div>
    </div>
</section>