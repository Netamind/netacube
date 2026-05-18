<style>
    .hero {
        background: linear-gradient(165deg, #232c52 0%, #2f3a72 55%, var(--brand) 100%);
        display: flex; align-items: center; position: relative; overflow: hidden; padding: 64px 0;
    }
    .hero-bg-grid {
        position: absolute; inset: 0;
        background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
        background-size: 46px 46px; pointer-events: none;
        mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
    }
    .hero-content { position: relative; z-index: 2; }
    .hero-badge {
        display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.22); color: #d7deff; font-size: 0.78rem; font-weight: 700;
        letter-spacing: 0.04em; padding: 7px 15px; border-radius: 20px; margin-bottom: 22px;
    }
    .hero h1 { font-size: clamp(2.2rem, 4.8vw, 3.3rem); font-weight: 800; line-height: 1.13; letter-spacing: -0.03em; color: #fff; margin-bottom: 18px; }
    .hero h1 .accent { color: #aab8ff; }
    .hero-lead { font-size: 1.08rem; line-height: 1.75; color: rgba(255,255,255,0.72); max-width: 510px; margin-bottom: 32px; }
    .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    .btn-hero-primary {
        background: #fff; color: var(--brand); font-weight: 700; font-size: 0.95rem; padding: 14px 28px; border-radius: 10px;
        display: inline-flex; align-items: center; gap: 8px; transition: .2s; border: none;
    }
    .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(0,0,0,.28); color: var(--brand-dark); }
    .btn-hero-ghost {
        background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.92); font-weight: 600; font-size: 0.95rem; padding: 13px 26px;
        border-radius: 10px; display: inline-flex; align-items: center; gap: 8px; border: 1px solid rgba(255,255,255,0.2); transition: .2s;
    }
    .btn-hero-ghost:hover { background: rgba(255,255,255,0.16); color: #fff; }

    /* ── Hero visual ── */
    .hero-visual { position: relative; z-index: 2; }
    .hero-image-frame {
        background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 32px 70px rgba(0,0,0,0.35);
        border: 1px solid rgba(255,255,255,0.12);
    }
    .hero-image-frame img { width: 100%; height: auto; display: block; }

    .hero-float-card {
        position: absolute; background: #fff; border-radius: 12px; padding: 12px 16px; color: var(--ink);
        font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 9px; box-shadow: var(--shadow-lg);
        z-index: 3; animation: float 4.2s ease-in-out infinite;
    }
    .hero-float-card i { font-size: 1.25rem; color: var(--brand); }
    .hero-float-card .sub { font-size: 0.68rem; font-weight: 500; color: var(--muted); display: block; margin-top: 1px; }
    @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

    /* ══ Trust strip ══ */
    .trust-strip { background: var(--surface-alt); border-bottom: 1px solid var(--line); padding: 26px 0; }
    .trust-strip-inner { max-width: 1200px; margin: 0 auto; padding: 0 24px; display: flex; align-items: center; gap: 20px; justify-content: center; flex-wrap: wrap; }
    .trust-label { font-size: 0.76rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); white-space: nowrap; }
    .trust-dots { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .trust-dot { background: #fff; border: 1px solid var(--line); border-radius: 30px; padding: 7px 16px; font-size: 0.81rem; font-weight: 600; color: var(--brand); white-space: nowrap; }

    /* ══ Sectors ══ */
    .sector-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
    .sector-card {
        background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 24px;
        transition: .2s; cursor: default; position: relative; overflow: hidden;
    }
    .sector-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--gradient); opacity: 0; transition: .2s; }
    .sector-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-4px); border-color: transparent; }
    .sector-card:hover::before { opacity: 1; }
    .sector-icon { width: 46px; height: 46px; background: var(--brand-light); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
    .sector-icon i { font-size: 1.3rem; color: var(--brand); }
    .sector-card h5 { font-size: 0.97rem; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
    .sector-card p { font-size: 0.82rem; color: var(--muted); line-height: 1.6; margin: 0; }

    /* ══ Story blocks ══ */
    .story-block { display: flex; align-items: center; gap: 56px; }
    .story-block:nth-child(even) { flex-direction: row-reverse; }
    .story-copy { flex: 1; }
    .story-copy .num-tag {
        display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px;
        border-radius: 10px; background: var(--gradient); color: #fff; font-weight: 800; font-size: 0.95rem; margin-bottom: 16px;
    }
    .story-copy h3 { font-size: 1.5rem; font-weight: 800; margin-bottom: 12px; letter-spacing: -0.01em; }
    .story-copy p { font-size: 0.96rem; line-height: 1.75; color: var(--ink-soft); }
    .story-copy ul { margin: 18px 0 0; padding: 0; list-style: none; }
    .story-copy ul li { display: flex; align-items: flex-start; gap: 10px; font-size: 0.88rem; color: var(--ink-soft); margin-bottom: 10px; }
    .story-copy ul li i { color: var(--brand); font-size: 1.05rem; margin-top: 1px; flex-shrink: 0; }
    .story-visual { flex: 0 0 420px; }
    .story-image-frame { background: #fff; border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-lg); }
    .story-image-frame img { width: 100%; height: auto; display: block; }

    /* ══ Interface showcase ══ */
    .showcase-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
    .showcase-card {
        background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); overflow: hidden;
        cursor: pointer; transition: .25s ease; box-shadow: var(--shadow); position: relative;
    }
    .showcase-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-4px); }
    .showcase-thumb { position: relative; overflow: hidden; aspect-ratio: 16 / 10.5; background: var(--surface-alt); }
    .showcase-thumb img { width: 100%; height: 100%; object-fit: cover; object-position: top; transition: transform .35s ease; }
    .showcase-card:hover .showcase-thumb img { transform: scale(1.045); }
    .showcase-thumb::after {
        content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(28,35,66,0) 55%, rgba(28,35,66,0.55) 100%);
        opacity: 0; transition: .25s;
    }
    .showcase-card:hover .showcase-thumb::after { opacity: 1; }
    .showcase-expand {
        position: absolute; top: 14px; right: 14px; width: 36px; height: 36px; border-radius: 9px;
        background: rgba(255,255,255,0.92); display: flex; align-items: center; justify-content: center;
        opacity: 0; transform: translateY(-4px); transition: .2s; box-shadow: var(--shadow);
    }
    .showcase-card:hover .showcase-expand { opacity: 1; transform: translateY(0); }
    .showcase-expand i { color: var(--brand); font-size: 1.05rem; }
    .showcase-body { padding: 18px 20px 20px; }
    .showcase-body h5 { font-size: 0.93rem; font-weight: 800; color: var(--ink); margin-bottom: 6px; }
    .showcase-body p { font-size: 0.82rem; color: var(--muted); line-height: 1.6; margin: 0; }

    /* ── Lightbox overlay ── */
    .lightbox-overlay {
        position: fixed; inset: 0; background: rgba(20,24,46,0.92); backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center; padding: 40px 20px; z-index: 2000;
        opacity: 0; visibility: hidden; transition: opacity .2s ease;
    }
    .lightbox-overlay.open { opacity: 1; visibility: visible; }
    .lightbox-inner { position: relative; max-width: 1100px; width: 100%; }
    .lightbox-inner img { width: 100%; height: auto; max-height: 82vh; object-fit: contain; border-radius: 12px; box-shadow: 0 30px 80px rgba(0,0,0,.45); display: block; margin: 0 auto; }
    .lightbox-caption { text-align: center; color: rgba(255,255,255,0.85); font-size: 0.88rem; font-weight: 600; margin-top: 16px; }
    .lightbox-close {
        position: absolute; top: -46px; right: 0; width: 38px; height: 38px; border-radius: 9px;
        background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.22); color: #fff;
        display: flex; align-items: center; justify-content: center; cursor: pointer; transition: .2s; font-size: 1.2rem;
    }
    .lightbox-close:hover { background: rgba(255,255,255,0.2); }

    /* ── Lightbox prev / next arrows ── */
    .lightbox-nav {
        position: absolute; top: 50%; transform: translateY(-50%);
        width: 48px; height: 48px; border-radius: 50%;
        background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.22); color: #fff;
        display: flex; align-items: center; justify-content: center; cursor: pointer; transition: .2s;
        font-size: 1.7rem; z-index: 10; flex-shrink: 0;
    }
    .lightbox-nav:hover { background: rgba(255,255,255,0.22); }
    .lightbox-prev { left: -68px; }
    .lightbox-next { right: -68px; }

    /* ── Counter pill at the bottom of the lightbox ── */
    .lightbox-counter {
        text-align: center; margin-top: 10px;
        font-size: 0.76rem; font-weight: 600; color: rgba(255,255,255,0.45);
        letter-spacing: 0.08em;
    }

    /* ══ Features ══ */
    .feat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .feat-card { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 28px; transition: .2s; }
    .feat-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); border-color: transparent; }
    .feat-icon { width: 48px; height: 48px; background: var(--brand-light); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .feat-icon i { font-size: 1.4rem; color: var(--brand); }
    .feat-card h5 { font-size: 0.95rem; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
    .feat-card p { font-size: 0.85rem; color: var(--muted); line-height: 1.65; margin: 0; }

    /* ══ Architecture strip ══ */
    .arch-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
    .arch-card { text-align: center; padding: 26px 20px; background: #fff; border: 1px solid var(--line); border-radius: var(--radius); transition: .2s; }
    .arch-card:hover { box-shadow: var(--shadow); transform: translateY(-3px); }
    .arch-icon { width: 52px; height: 52px; background: var(--brand-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
    .arch-icon i { font-size: 1.4rem; color: var(--brand); }
    .arch-card h5 { font-size: 0.88rem; font-weight: 800; color: var(--ink); margin-bottom: 6px; }
    .arch-card p { font-size: 0.8rem; color: var(--muted); line-height: 1.6; margin: 0; }

    /* ══ Pricing teaser ══ */
    .pricing-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; max-width: 880px; margin: 0 auto; }
    .pricing-card { background: #fff; border: 1.5px solid var(--line); border-radius: var(--radius-lg); padding: 34px 26px; text-align: center; position: relative; transition: .2s; }
    .pricing-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-4px); }
    .pricing-card.featured { border-color: var(--brand); border-width: 2px; box-shadow: 0 6px 26px rgba(75,94,189,.18); }
    .pricing-badge { position: absolute; top: -13px; left: 50%; transform: translateX(-50%); background: var(--gradient); color: #fff; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; padding: 4px 16px; border-radius: 20px; white-space: nowrap; }
    .pricing-period { font-size: 0.77rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 18px; }
    .pricing-price { font-size: 2.6rem; font-weight: 800; color: var(--brand); line-height: 1; margin-bottom: 4px; }
    .pricing-price sup { font-size: 1.1rem; vertical-align: top; margin-top: 8px; margin-right: 2px; }
    .pricing-sub { font-size: 0.79rem; color: var(--muted); margin-bottom: 26px; }
    .btn-pricing { display: block; width: 100%; padding: 12px; border-radius: 9px; font-size: 0.89rem; font-weight: 700; text-align: center; transition: .2s; }
    .btn-pricing-primary { background: var(--gradient); color: #fff; }
    .btn-pricing-primary:hover { box-shadow: 0 8px 22px rgba(75,94,189,.32); transform: translateY(-1px); color: #fff; }
    .btn-pricing-outline { background: transparent; color: var(--brand); border: 1.5px solid var(--brand); }
    .btn-pricing-outline:hover { background: var(--brand-light); }

    @media (max-width: 1200px) {
        .lightbox-prev { left: -56px; }
        .lightbox-next { right: -56px; }
    }
    @media (max-width: 1100px) {
        .sector-grid { grid-template-columns: repeat(2, 1fr); }
        .feat-grid { grid-template-columns: repeat(2, 1fr); }
        .arch-grid { grid-template-columns: repeat(2, 1fr); }
        .showcase-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 991px) {
        .hero { padding: 48px 0; }
        .hero-float-card { display: none; }
        .story-block, .story-block:nth-child(even) { flex-direction: column; gap: 32px; }
        .story-visual { flex: 1; width: 100%; }
        .pricing-grid { grid-template-columns: 1fr; max-width: 380px; }
        /* On smaller screens, nav arrows sit inside the image rather than outside */
        .lightbox-prev { left: 8px; }
        .lightbox-next { right: 8px; }
        .lightbox-nav { background: rgba(20,24,46,0.65); width: 42px; height: 42px; font-size: 1.4rem; }
    }
    @media (max-width: 767px) {
        .sector-grid, .feat-grid, .arch-grid, .showcase-grid { grid-template-columns: 1fr; }
        .hero { padding: 40px 0; }
        .hero h1 { font-size: 2rem; }
    }
</style>

<!-- ══ Hero ══════════════════════════════════════════════════════════════ -->
<section class="hero">
    <div class="hero-bg-grid"></div>
    <div class="container" style="max-width:1200px;">
        <div class="row align-items-center hero-content">
            <div class="col-lg-6">
                <div class="hero-badge"><i class="ri-stack-line me-1"></i> All-in-one business platform</div>
                {{-- "Platform" is now the last word, sitting on the accented second line --}}
                <h1>Run Your Entire Business<br>On One <span class="accent">Platform.</span></h1>
                <p class="hero-lead">
                    Netacube unifies sales, inventory, branches, staff and payroll in a single secure platform, giving you a real-time, accurate view of your business from anywhere. Built for owners who want clarity and control without juggling separate systems.
                </p>
                <div class="hero-actions">
                    <a href="/get-started" class="btn-hero-primary"><i class="ri-rocket-line"></i> Start free trial</a>
                    <a href="/features" class="btn-hero-ghost"><i class="ri-play-circle-line"></i> Explore features</a>
                </div>
            </div>
            <div class="col-lg-6 hero-visual mt-5 mt-lg-0">
                <div style="position:relative;">
                    <div class="hero-image-frame">
                        <img src="{{ asset('website/images/s7.png') }}" alt="Netacube business dashboard overview showing sales, revenue and branch status">
                    </div>
                    <div class="hero-float-card" style="bottom:-22px; left:-26px;">
                        <i class="ri-wifi-off-line"></i>
                        <div><span>Sale recorded offline</span><span class="sub">Syncs the moment you're back online</span></div>
                    </div>
                    <div class="hero-float-card" style="top:-18px; right:-22px;">
                        <i class="ri-shield-check-line"></i>
                        <div><span>Private &amp; secure</span><span class="sub">Your business, your data, kept safe</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ Trust strip ══════════════════════════════════════════════════════ -->
<div class="trust-strip">
    <div class="trust-strip-inner">
        <span class="trust-label">Built for</span>
        <div class="trust-dots">
            <span class="trust-dot">Retail &amp; Wholesale</span>
            <span class="trust-dot">Hospitality</span>
            <span class="trust-dot">Healthcare</span>
            <span class="trust-dot">Finance</span>
            <span class="trust-dot">Consultancy</span>
            <span class="trust-dot">IT Services</span>
            <span class="trust-dot">Properties</span>
        </div>
    </div>
</div>

<!-- ══ Sectors ══════════════════════════════════════════════════════════ -->
<section class="section bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">Built around your sector</span>
            <h2 class="display-section mt-2">One platform, tailored to how your industry actually runs</h2>
            <div class="section-divider"></div>
            <p class="lead-text mt-3 mx-auto" style="max-width:580px;">
                From retail stock control to property lease tracking, every Netacube module adapts to the way your business actually operates, not the other way around.
            </p>
        </div>
        <div class="sector-grid">
            @php
                $sectors = [
                    ['name' => 'Retail',      'icon' => 'ri-shopping-bag-3-line',  'desc' => 'Real-time inventory, sales by product and location, loyalty programmes and stock control across every till.'],
                    ['name' => 'Wholesale',   'icon' => 'ri-truck-line',           'desc' => 'Bulk orders, tiered pricing, customer credit limits and warehouse stock across multiple locations.'],
                    ['name' => 'Finance',     'icon' => 'ri-bank-line',            'desc' => 'Client portfolios, loans, fee structures, compliance documents and transaction history.'],
                    ['name' => 'Consultancy', 'icon' => 'ri-presentation-line',    'desc' => 'Client engagements, billable hours, proposals, consultant allocation and per-project profitability.'],
                    ['name' => 'IT',          'icon' => 'ri-terminal-box-line',    'desc' => 'Support tickets, SLAs, asset and licence inventory, maintenance contracts and uptime reporting.'],
                    ['name' => 'Healthcare',  'icon' => 'ri-stethoscope-line',     'desc' => 'Appointments, prescriptions, medicine and supply inventory, billing and insurance claims.'],
                    ['name' => 'Hospitality', 'icon' => 'ri-hotel-bed-line',       'desc' => 'Room and table bookings, point of sale, staff shifts, housekeeping and occupancy analytics.'],
                    ['name' => 'Properties',  'icon' => 'ri-home-4-line',          'desc' => 'Properties, units, tenants, lease agreements, rent payments and maintenance requests.'],
                ];
            @endphp
            @foreach($sectors as $sector)
                <div class="sector-card">
                    <div class="sector-icon"><i class="{{ $sector['icon'] }}"></i></div>
                    <h5>{{ $sector['name'] }}</h5>
                    <p>{{ $sector['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ══ Multi-branch + offline sync story ══════════════════════════════════ -->
<section class="section bg-alt">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">How it works</span>
            <h2 class="display-section mt-2">Built for businesses with more than one front door</h2>
            <div class="section-divider"></div>
        </div>

        <div class="story-block mb-5 pb-3">
            <div class="story-copy">
                <span class="num-tag">1</span>
                <h3>Every branch, one business</h3>
                <p>Add as many branches as your business needs. Each one records its own sales, stock and staff, while owners get a single combined view of performance across every location.</p>
                <ul>
                    <li><i class="ri-checkbox-circle-fill"></i> Branch-level sales, stock and cash tracking</li>
                    <li><i class="ri-checkbox-circle-fill"></i> Inter-branch stock transfers</li>
                    <li><i class="ri-checkbox-circle-fill"></i> One combined, business-wide report</li>
                </ul>
            </div>
            <div class="story-visual">
                <div class="story-image-frame">
                    <img src="{{ asset('website/images/s2.png') }}" alt="Netacube multi-branch sales report">
                </div>
            </div>
        </div>

        <div class="story-block">
            <div class="story-copy">
                <span class="num-tag">2</span>
                <h3>Offline sales, zero data loss</h3>
                <p>When the internet drops at the till, Netacube doesn't stop. Sales, stock deductions and receipts keep recording on the device, then sync automatically the moment connectivity returns.</p>
                <ul>
                    <li><i class="ri-checkbox-circle-fill"></i> Point of sale keeps working with no connection</li>
                    <li><i class="ri-checkbox-circle-fill"></i> Automatic background sync on reconnection</li>
                    <li><i class="ri-checkbox-circle-fill"></i> No duplicate or lost transactions</li>
                </ul>
            </div>
            <div class="story-visual">
                <div class="story-image-frame">
                    <img src="{{ asset('website/images/s8.png') }}" alt="Netacube point of sale screen, which keeps working offline">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ Interface showcase — lightbox gallery ══════════════════════════════ -->
<section class="section bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">See it in action</span>
            <h2 class="display-section mt-2">A closer look at the screens your team uses every day</h2>
            <div class="section-divider"></div>
            <p class="lead-text mt-3 mx-auto" style="max-width:560px;">
                Real screens from the live platform. Select any preview to view it in full size.
            </p>
        </div>

        @php
            $showcase = [
                ['img' => 's7.png', 'title' => 'Dashboard &amp; analytics', 'desc' => 'Sales, revenue and branch performance at a glance, updated in real time.'],
                ['img' => 's2.png', 'title' => 'Multi-branch reporting',     'desc' => 'One combined view of every branch, with the ability to drill into each location.'],
                ['img' => 's8.png', 'title' => 'Point of sale',              'desc' => 'A fast, focused checkout screen that keeps working even without internet.'],
            ];
        @endphp

        <div class="showcase-grid">
            @foreach($showcase as $i => $shot)
                <div class="showcase-card" onclick="openLightbox({{ $i }})">
                    <div class="showcase-thumb">
                        <img src="{{ asset('website/images/'.$shot['img']) }}" alt="{{ strip_tags($shot['title']) }} screenshot">
                        <div class="showcase-expand"><i class="ri-zoom-in-line"></i></div>
                    </div>
                    <div class="showcase-body">
                        <h5>{!! $shot['title'] !!}</h5>
                        <p>{{ $shot['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ══ Lightbox overlay ══════════════════════════════════════════════════ -->
<div class="lightbox-overlay" id="lightboxOverlay" onclick="if(event.target===this) closeLightbox();">
    <div class="lightbox-inner">
        {{-- Close --}}
        <button type="button" class="lightbox-close" onclick="closeLightbox()" aria-label="Close preview">
            <i class="ri-close-line"></i>
        </button>
        {{-- Previous --}}
        <button type="button" class="lightbox-nav lightbox-prev" id="lbPrev" onclick="shiftLightbox(-1)" aria-label="Previous image">
            <i class="ri-arrow-left-s-line"></i>
        </button>
        {{-- Next --}}
        <button type="button" class="lightbox-nav lightbox-next" id="lbNext" onclick="shiftLightbox(1)" aria-label="Next image">
            <i class="ri-arrow-right-s-line"></i>
        </button>

        <img id="lightboxImg" src="" alt="">
        <div class="lightbox-caption" id="lightboxCaption"></div>
        <div class="lightbox-counter" id="lightboxCounter"></div>
    </div>
</div>

<!-- ══ Key features ══════════════════════════════════════════════════════ -->
<section class="section bg-alt">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">What's inside</span>
            <h2 class="display-section mt-2">Everything your business runs on, working together</h2>
            <div class="section-divider"></div>
            <p class="lead-text mt-3 mx-auto" style="max-width:560px;">
                The same modules running in your dashboard today — inventory, sales, people and money — connected in one system instead of scattered across many.
            </p>
        </div>
        <div class="feat-grid">
            <div class="feat-card"><div class="feat-icon"><i class="ri-shopping-cart-2-line"></i></div><h5>Point of sale</h5><p>Fast, branch-aware checkout that keeps working offline and syncs automatically.</p></div>
            <div class="feat-card"><div class="feat-icon"><i class="ri-archive-drawer-line"></i></div><h5>Inventory control</h5><p>Real-time stock per branch, low-stock alerts and inter-branch transfers.</p></div>
            <div class="feat-card"><div class="feat-icon"><i class="ri-team-line"></i></div><h5>Employees &amp; payroll</h5><p>Staff profiles, roles, branches, salaries and payslip generation.</p></div>
            <div class="feat-card"><div class="feat-icon"><i class="ri-file-text-line"></i></div><h5>Invoices &amp; documents</h5><p>Branded invoices and statements generated from your own company profile.</p></div>
            <div class="feat-card"><div class="feat-icon"><i class="ri-money-dollar-circle-line"></i></div><h5>Multi-currency</h5><p>Configure the currencies your business trades in.</p></div>
            <div class="feat-card"><div class="feat-icon"><i class="ri-bar-chart-grouped-line"></i></div><h5>Reports &amp; analytics</h5><p>Branch and business-wide dashboards for sales, revenue and overdue accounts.</p></div>
            <div class="feat-card"><div class="feat-icon"><i class="ri-folder-2-line"></i></div><h5>Company profile &amp; files</h5><p>Centralised business details, licences and documents used across the system.</p></div>
            <div class="feat-card"><div class="feat-icon"><i class="ri-shield-check-line"></i></div><h5>Role-based security</h5><p>Granular permissions so staff see only what their role allows.</p></div>
            <div class="feat-card"><div class="feat-icon"><i class="ri-building-4-line"></i></div><h5>Your own private account</h5><p>Your records are kept separate and secure, never shared with another business.</p></div>
        </div>
    </div>
</section>

<!-- ══ Why Netacube ══════════════════════════════════════════════════════ -->
<section class="section bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">Why businesses choose us</span>
            <h2 class="display-section mt-2">Built to be reliable, simple and safe to depend on</h2>
            <div class="section-divider"></div>
        </div>
        <div class="arch-grid">
            <div class="arch-card"><div class="arch-icon"><i class="ri-shield-keyhole-line"></i></div><h5>Private &amp; secure</h5><p>Your business records are kept private and are never mixed with another business.</p></div>
            <div class="arch-card"><div class="arch-icon"><i class="ri-wifi-off-line"></i></div><h5>Always working</h5><p>Sales continue uninterrupted even without internet, and sync once you're back online.</p></div>
            <div class="arch-card"><div class="arch-icon"><i class="ri-refresh-line"></i></div><h5>Always improving</h5><p>New features and updates roll out regularly, at no extra effort on your part.</p></div>
            <div class="arch-card"><div class="arch-icon"><i class="ri-customer-service-2-line"></i></div><h5>Real support</h5><p>Friendly help over email and WhatsApp whenever you need it, not just a help centre.</p></div>
        </div>
    </div>
</section>

<!-- ══ Pricing teaser ══════════════════════════════════════════════════ -->
<section class="section bg-alt">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">Simple pricing</span>
            <h2 class="display-section mt-2">One price, full access</h2>
            <div class="section-divider"></div>
            <p class="lead-text mt-3 mx-auto" style="max-width:540px;">
                Every plan unlocks the entire system — every sector, every branch, every module. The only difference is your billing period.
            </p>
        </div>
        <div class="pricing-grid">
            <div class="pricing-card">
                <div class="pricing-period">6 months</div>
                <div class="pricing-price"><sup>$</sup>120</div>
                <div class="pricing-sub">$20 / month</div>
                <a href="/get-started" class="btn-pricing btn-pricing-outline">Get started</a>
            </div>
            <div class="pricing-card featured">
                <div class="pricing-badge">Most popular</div>
                <div class="pricing-period">1 year</div>
                <div class="pricing-price"><sup>$</sup>220</div>
                <div class="pricing-sub">$18.33 / month</div>
                <a href="/get-started" class="btn-pricing btn-pricing-primary">Get started</a>
            </div>
            <div class="pricing-card">
                <div class="pricing-period">2 years</div>
                <div class="pricing-price"><sup>$</sup>400</div>
                <div class="pricing-sub">$16.67 / month</div>
                <a href="/get-started" class="btn-pricing btn-pricing-outline">Get started</a>
            </div>
        </div>
        <p class="text-center mt-4" style="font-size:0.85rem; color:var(--muted);">
            All plans include a 14-day free trial, 24/7 support and unlimited branches. No hidden fees.
        </p>
    </div>
</section>

<script>
    /* ── Lightbox items — populated from the same $showcase array above ── */
    var lbItems = [
        @foreach($showcase as $shot)
        { src: '{{ asset('website/images/'.$shot['img']) }}', caption: '{!! addslashes($shot['title']) !!}' },
        @endforeach
    ];
    var lbIndex = 0;

    function openLightbox(idx) {
        lbIndex = idx;
        renderLightbox();
        document.getElementById('lightboxOverlay').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        document.getElementById('lightboxOverlay').classList.remove('open');
        document.body.style.overflow = '';
    }

    function shiftLightbox(dir) {
        lbIndex = (lbIndex + dir + lbItems.length) % lbItems.length;
        renderLightbox();
    }

    function renderLightbox() {
        var item = lbItems[lbIndex];
        var img  = document.getElementById('lightboxImg');
        img.src  = item.src;
        img.alt  = item.caption;
        document.getElementById('lightboxCaption').innerHTML = item.caption;
        document.getElementById('lightboxCounter').textContent = (lbIndex + 1) + ' / ' + lbItems.length;

        /* Hide nav arrows when there is only one image */
        var showNav = lbItems.length > 1;
        document.getElementById('lbPrev').style.display = showNav ? 'flex' : 'none';
        document.getElementById('lbNext').style.display = showNav ? 'flex' : 'none';
    }

    document.addEventListener('keydown', function(e) {
        if (!document.getElementById('lightboxOverlay').classList.contains('open')) return;
        if (e.key === 'Escape')     closeLightbox();
        if (e.key === 'ArrowLeft')  shiftLightbox(-1);
        if (e.key === 'ArrowRight') shiftLightbox(1);
    });
</script>

