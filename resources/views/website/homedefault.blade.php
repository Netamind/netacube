

{{--
    ============================================================================
    WEBSITE — RETAIL-ERA HOME CONTENT
    ============================================================================
    Rendered via @yield('content', View::make('website.homedefault')) inside
    resources/views/website/homepage.blade.php, which supplies the fixed nav,
    the CSS custom properties (--brand, --ink, --muted, --line, --radius,
    --shadow, --gradient, etc.), the FAQ section, the closing CTA banner and
    the footer. This file only needs to own the content between the nav and
    the FAQ.

    Netacube currently has ONE sector fully built: Retail. This page is
    written for that reality — it sells what exists today (POS, inventory,
    stocktaking, multi-branch reporting, payroll) and previews the sectors
    still coming, rather than promising features that aren't live yet.

    SCREENSHOTS
    ------------------------------------------------------------------------
    No dashboard screenshots were available to ship with this page without
    exposing a real tenant's live data (business name, email, phone number
    visible in the only captures on disk). Every "device frame" below is
    built with plain HTML/CSS instead, styled in the product's own colours
    and using the same widget-icon-box / avatar-title language as the real
    dashboard, so the page still reads as a genuine product preview. Each
    frame carries an HTML comment naming exactly which real screen to
    screenshot and drop in later — search this file for "SCREENSHOT:".
    A future "Website Display" setting (mentioned in the request) is the
    natural place to let a tenant swap these for their own screenshots or
    turn sections on/off per sector as more sectors go live.
    ============================================================================
--}}

<style>
    /* ══ Hero ══════════════════════════════════════════════════════════════ */
    .rh-hero {
        background: var(--gradient-deep);
        padding: 88px 0 70px;
        position: relative;
        overflow: hidden;
    }
    .rh-hero::before {
        content: '';
        position: absolute; inset: 0;
        background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
        background-size: 46px 46px;
        mask-image: radial-gradient(ellipse at center, black 25%, transparent 75%);
    }
    .rh-hero-grid { position: relative; z-index: 2; display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center; }
    .rh-hero-badge {
        display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.22); color: #d7deff; font-size: 0.76rem; font-weight: 700;
        letter-spacing: 0.04em; padding: 7px 14px; border-radius: 20px; margin-bottom: 20px;
    }
    .rh-hero-badge .dot { width: 7px; height: 7px; border-radius: 50%; background: #4ade80; box-shadow: 0 0 0 3px rgba(74,222,128,0.25); }
    .rh-hero h1 { font-size: clamp(2.15rem, 4.4vw, 3.15rem); font-weight: 800; line-height: 1.14; letter-spacing: -0.025em; color: #fff; margin-bottom: 18px; }
    .rh-hero h1 .accent { color: #aab8ff; }
    .rh-hero-lead { font-size: 1.04rem; line-height: 1.75; color: rgba(255,255,255,0.72); max-width: 480px; margin-bottom: 28px; }
    .rh-hero-actions { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 30px; }
    .rh-hero-stats { display: flex; gap: 28px; flex-wrap: wrap; }
    .rh-hero-stats div strong { display: block; font-size: 1.35rem; font-weight: 800; color: #fff; }
    .rh-hero-stats div span { font-size: 0.76rem; color: rgba(255,255,255,0.6); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }

    /* ── Hero device mockup — pure CSS, no raster image needed ── */
    .rh-device { position: relative; z-index: 2; }
    .rh-frame {
        background: #f4f6fb; border-radius: 14px; overflow: hidden; box-shadow: 0 32px 70px rgba(0,0,0,0.35);
        border: 1px solid rgba(255,255,255,0.12);
    }
    .rh-frame-bar { background: #fff; padding: 10px 14px; display: flex; align-items: center; gap: 6px; border-bottom: 1px solid var(--line); }
    .rh-frame-bar span { width: 9px; height: 9px; border-radius: 50%; background: #e2e4ee; }
    .rh-frame-bar .url { margin-left: 10px; background: #f2f3f8; border-radius: 5px; padding: 3px 10px; font-size: 0.68rem; color: var(--muted); font-weight: 600; }
    .rh-frame-body { padding: 16px; }
    .rh-mock-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 10px; }
    .rh-mock-kpi { background: #fff; border: 1px solid var(--line); border-radius: 8px; padding: 10px; }
    .rh-mock-kpi .lbl { font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin-bottom: 6px; }
    .rh-mock-kpi .val { font-size: 0.92rem; font-weight: 800; color: var(--ink); }
    .rh-mock-kpi .chip { display: inline-block; margin-top: 5px; font-size: 0.6rem; font-weight: 700; padding: 1px 6px; border-radius: 8px; }
    .rh-mock-kpi .chip.up { background: #dcfce7; color: #15803d; }
    .rh-mock-kpi .chip.down { background: #fee2e2; color: #b91c1c; }
    .rh-mock-panel { background: #fff; border: 1px solid var(--line); border-radius: 8px; padding: 12px; }
    .rh-mock-panel .hd { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .rh-mock-panel .hd strong { font-size: 0.78rem; color: var(--ink); }
    .rh-mock-panel .hd span { font-size: 0.62rem; color: var(--brand); font-weight: 700; }
    .rh-mock-row { display: flex; align-items: center; gap: 8px; padding: 7px 0; border-top: 1px solid var(--line); }
    .rh-mock-row:first-of-type { border-top: none; }
    .rh-mock-row .dotb { width: 6px; height: 6px; border-radius: 50%; background: var(--brand); flex-shrink: 0; }
    .rh-mock-row .name { font-size: 0.68rem; color: var(--ink-soft); font-weight: 600; flex: 1; }
    .rh-mock-row .bar { height: 5px; border-radius: 4px; background: var(--brand-light); flex: 1.4; overflow: hidden; }
    .rh-mock-row .bar i { display: block; height: 100%; background: var(--gradient); border-radius: 4px; }
    .rh-mock-row .amt { font-size: 0.68rem; font-weight: 700; color: var(--ink); width: 52px; text-align: right; }

    .rh-float-card {
        position: absolute; background: #fff; border-radius: 12px; padding: 11px 15px; color: var(--ink);
        font-size: 0.78rem; font-weight: 700; display: flex; align-items: center; gap: 9px; box-shadow: var(--shadow-lg);
        z-index: 3; animation: rhFloat 4.2s ease-in-out infinite;
    }
    .rh-float-card i { font-size: 1.2rem; color: var(--brand); }
    .rh-float-card .sub { font-size: 0.66rem; font-weight: 500; color: var(--muted); display: block; margin-top: 1px; }
    .rh-float-1 { top: -18px; right: -14px; animation-delay: .3s; }
    .rh-float-2 { bottom: -16px; left: -20px; animation-delay: 1s; }
    @keyframes rhFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

    /* ══ Sector strip ══ */
    .rh-sector-strip { background: var(--surface-alt); border-bottom: 1px solid var(--line); padding: 30px 0; }
    .rh-sector-strip-head { text-align: center; margin-bottom: 20px; }
    .rh-sector-strip-head p { font-size: 0.85rem; color: var(--muted); margin: 4px 0 0; }
    .rh-sector-row { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
    .rh-sector-chip {
        display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--line);
        border-radius: 30px; padding: 8px 16px 8px 8px; font-size: 0.82rem; font-weight: 700; color: var(--ink);
    }
    .rh-sector-chip.is-live { border-color: rgba(75,94,189,0.3); box-shadow: 0 4px 14px rgba(75,94,189,.12); }
    .rh-sector-chip.is-soon { color: var(--muted); }
    .rh-sector-chip .ic { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0; }
    .rh-sector-chip .badge-live { background: #dcfce7; color: #15803d; font-size: 0.6rem; font-weight: 800; padding: 2px 7px; border-radius: 8px; margin-left: 4px; text-transform: uppercase; letter-spacing: .03em; }
    .rh-sector-chip .badge-soon { background: #f1f2f7; color: var(--muted); font-size: 0.6rem; font-weight: 800; padding: 2px 7px; border-radius: 8px; margin-left: 4px; text-transform: uppercase; letter-spacing: .03em; }

    /* ══ Feature grid ══ */
    .rh-feat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .rh-feat-card { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 26px; transition: .2s; }
    .rh-feat-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); border-color: transparent; }
    .rh-feat-card .rh-feat-icon { width: 46px; height: 46px; background: var(--brand-light); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; }
    .rh-feat-card .rh-feat-icon i { font-size: 1.3rem; color: var(--brand); }
    .rh-feat-card h5 { font-size: 0.96rem; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
    .rh-feat-card p { font-size: 0.85rem; color: var(--muted); line-height: 1.65; margin: 0; }

    /* ══ "See it in action" showcase ══ */
    .rh-showcase-tabs { display: flex; gap: 8px; justify-content: center; margin-bottom: 32px; flex-wrap: wrap; }
    .rh-showcase-tab {
        background: #fff; border: 1.5px solid var(--line); color: var(--ink-soft); font-size: 0.82rem; font-weight: 700;
        padding: 10px 18px; border-radius: 9px; cursor: pointer; transition: .15s; display: inline-flex; align-items: center; gap: 7px;
    }
    .rh-showcase-tab i { font-size: 1rem; }
    .rh-showcase-tab.active { background: var(--gradient); color: #fff; border-color: transparent; box-shadow: 0 8px 20px rgba(75,94,189,.28); }
    .rh-showcase-pane { display: none; }
    .rh-showcase-pane.active { display: grid; grid-template-columns: 1.15fr 1fr; gap: 44px; align-items: center; }
    .rh-showcase-copy h3 { font-size: 1.5rem; font-weight: 800; color: var(--ink); margin-bottom: 12px; letter-spacing: -0.02em; }
    .rh-showcase-copy p { font-size: 0.96rem; color: var(--ink-soft); line-height: 1.75; margin-bottom: 18px; }
    .rh-showcase-copy ul { list-style: none; padding: 0; margin: 0; }
    .rh-showcase-copy ul li { display: flex; align-items: flex-start; gap: 10px; font-size: 0.88rem; color: var(--ink-soft); margin-bottom: 11px; }
    .rh-showcase-copy ul li i { color: var(--brand); font-size: 1.05rem; margin-top: 1px; flex-shrink: 0; }

    /* ══ Stats banner ══ */
    .rh-stats-banner { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 36px 30px; display: grid; grid-template-columns: repeat(4,1fr); gap: 20px; }
    .rh-stats-banner div { text-align: center; }
    .rh-stats-banner strong { display: block; font-size: 1.9rem; font-weight: 800; color: var(--brand); letter-spacing: -0.02em; }
    .rh-stats-banner span { font-size: 0.8rem; color: var(--muted); font-weight: 600; }

    @media (max-width: 991px) {
        .rh-hero-grid { grid-template-columns: 1fr; }
        .rh-device { max-width: 460px; margin: 0 auto; }
        .rh-feat-grid { grid-template-columns: repeat(2, 1fr); }
        .rh-showcase-pane.active { grid-template-columns: 1fr; }
        .rh-showcase-copy { order: 2; }
        .rh-stats-banner { grid-template-columns: repeat(2,1fr); }
    }
    @media (max-width: 767px) {
        .rh-hero { padding: 56px 0 44px; }
        .rh-hero-stats { gap: 20px; }
        .rh-feat-grid { grid-template-columns: 1fr; }
        .rh-stats-banner { grid-template-columns: 1fr 1fr; padding: 26px 20px; }
    }
</style>

<!-- ══════════════════════════════════════════════════════════════════════
     HERO
     ══════════════════════════════════════════════════════════════════════ -->
<section class="rh-hero">
    <div class="rh-hero-bg-grid" aria-hidden="true"></div>
    <div class="container" style="max-width:1200px; position:relative;">
        <div class="rh-hero-grid">

            <div class="rh-hero-content">
                <span class="rh-hero-badge"><span class="dot"></span> Retail is live — more sectors coming</span>
                <h1>Run every branch of your <span class="accent">retail business</span> from one screen</h1>
                <p class="rh-hero-lead">
                    Netacube gives retail shops a single system for point of sale, live inventory, stocktaking,
                    supplier deliveries and multi-branch reporting — built to keep working even when the internet
                    doesn't, and built to grow into wholesale, hospitality, healthcare and more as your business does.
                </p>
                <div class="rh-hero-actions">
                    <a href="/get-started" class="btn-hero-primary"><i class="ri-rocket-line"></i> Start free 14-day trial</a>
                    <a href="/features" class="btn-hero-ghost"><i class="ri-play-circle-line"></i> See how it works</a>
                </div>
                <div class="rh-hero-stats">
                    <div><strong>1</strong><span>Sector live today</span></div>
                    <div><strong>5</strong><span>More on the roadmap</span></div>
                    <div><strong>24/7</strong><span>Offline-capable POS</span></div>
                </div>
            </div>

            <div class="rh-device">
                <!-- SCREENSHOT: Replace this mockup with a real capture of
                     operations/retail/default.blade.php (Retail Operations
                     dashboard — Today's Sales tab, Value Added / Shop Value
                     cards visible) at ~1280px wide. -->
                <div class="rh-frame">
                    <div class="rh-frame-bar">
                        <span></span><span></span><span></span>
                        <span class="url"><i class="ri-lock-line"></i> app.netacube.com/retail</span>
                    </div>
                    <div class="rh-frame-body">
                        <div class="rh-mock-kpis">
                            <div class="rh-mock-kpi">
                                <div class="lbl">Today</div>
                                <div class="val">MWK 842K</div>
                                <span class="chip up"><i class="ri-arrow-up-line"></i> 12%</span>
                            </div>
                            <div class="rh-mock-kpi">
                                <div class="lbl">Shop Value</div>
                                <div class="val">MWK 18.4M</div>
                                <span class="chip up"><i class="ri-checkbox-circle-line"></i> Healthy</span>
                            </div>
                            <div class="rh-mock-kpi">
                                <div class="lbl">Low Stock</div>
                                <div class="val">6 items</div>
                                <span class="chip down"><i class="ri-alert-line"></i> Review</span>
                            </div>
                        </div>
                        <div class="rh-mock-panel">
                            <div class="hd"><strong>Sales by branch</strong><span>Today</span></div>
                            <div class="rh-mock-row"><span class="dotb"></span><span class="name">City Centre</span><span class="bar"><i style="width:82%"></i></span><span class="amt">312K</span></div>
                            <div class="rh-mock-row"><span class="dotb"></span><span class="name">Area 25</span><span class="bar"><i style="width:61%"></i></span><span class="amt">248K</span></div>
                            <div class="rh-mock-row"><span class="dotb"></span><span class="name">Limbe</span><span class="bar"><i style="width:45%"></i></span><span class="amt">176K</span></div>
                            <div class="rh-mock-row"><span class="dotb"></span><span class="name">Mzuzu</span><span class="bar"><i style="width:28%"></i></span><span class="amt">106K</span></div>
                        </div>
                    </div>
                </div>

                <div class="rh-float-card rh-float-1">
                    <i class="ri-shield-check-line"></i>
                    <span>Reconciled<span class="sub">System vs. cash</span></span>
                </div>
                <div class="rh-float-card rh-float-2">
                    <i class="ri-wifi-off-line"></i>
                    <span>Offline sync<span class="sub">POS keeps selling</span></span>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════
     SECTOR STRIP — Retail live, rest on the roadmap
     ══════════════════════════════════════════════════════════════════════ -->
<section class="rh-sector-strip">
    <div class="container" style="max-width:1100px;">
        <div class="rh-sector-strip-head">
            <span class="eyebrow" style="justify-content:center; display:inline-flex;">One platform, every sector</span>
            <p>Retail is fully built and running today. Every other sector below shares the same core — branches, staff, payroll, reporting — and switches on as it ships.</p>
        </div>
        <div class="rh-sector-row">
            <span class="rh-sector-chip is-live">
                <span class="ic" style="background:#eef0f9;"><i class="ri-store-2-line" style="color:var(--brand);"></i></span>
                Retail <span class="badge-live">Live</span>
            </span>
            <span class="rh-sector-chip is-soon">
                <span class="ic" style="background:#f1f2f7;"><i class="ri-truck-line" style="color:#8189a0;"></i></span>
                Wholesale <span class="badge-soon">Soon</span>
            </span>
            <span class="rh-sector-chip is-soon">
                <span class="ic" style="background:#f1f2f7;"><i class="ri-line-chart-line" style="color:#8189a0;"></i></span>
                Finance <span class="badge-soon">Soon</span>
            </span>
            <span class="rh-sector-chip is-soon">
                <span class="ic" style="background:#f1f2f7;"><i class="ri-hotel-line" style="color:#8189a0;"></i></span>
                Hospitality <span class="badge-soon">Soon</span>
            </span>
            <span class="rh-sector-chip is-soon">
                <span class="ic" style="background:#f1f2f7;"><i class="ri-heart-pulse-line" style="color:#8189a0;"></i></span>
                Healthcare <span class="badge-soon">Soon</span>
            </span>
            <span class="rh-sector-chip is-soon">
                <span class="ic" style="background:#f1f2f7;"><i class="ri-building-4-line" style="color:#8189a0;"></i></span>
                Properties <span class="badge-soon">Soon</span>
            </span>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════
     FEATURES — what Retail on Netacube actually does today
     ══════════════════════════════════════════════════════════════════════ -->
<section class="section bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">Built for retail</span>
            <h2 class="display-section mt-2">Everything a shop needs, connected</h2>
            <div class="section-divider"></div>
            <p class="lead-text mt-3" style="max-width:640px; margin-left:auto; margin-right:auto;">
                No spreadsheets stitched together after closing time. Every sale, delivery and stock count updates
                the same live picture of the business.
            </p>
        </div>

        <div class="rh-feat-grid">
            <div class="rh-feat-card">
                <div class="rh-feat-icon"><i class="ri-shopping-basket-2-line"></i></div>
                <h5>Point of Sale, online or off</h5>
                <p>A mobile-friendly till that keeps ringing up sales during a network outage and syncs everything the moment connection returns — nothing lost, nothing re-typed.</p>
            </div>
            <div class="rh-feat-card">
                <div class="rh-feat-icon"><i class="ri-stack-line"></i></div>
                <h5>Live inventory, per branch</h5>
                <p>Real-time stock levels, reorder points and shop valuation across every branch, with a full audit trail of every stock movement in and out.</p>
            </div>
            <div class="rh-feat-card">
                <div class="rh-feat-icon"><i class="ri-truck-line"></i></div>
                <h5>Delivery notes &amp; allocation</h5>
                <p>Receive stock, verify quantities against what was ordered, and flag price or quantity discrepancies before they ever hit the shop floor.</p>
            </div>
            <div class="rh-feat-card">
                <div class="rh-feat-icon"><i class="ri-clipboard-line"></i></div>
                <h5>Full stocktaking</h5>
                <p>Scheduled physical counts with merged system-vs-counted views, missing-product detection and a password-confirmed rectification flow.</p>
            </div>
            <div class="rh-feat-card">
                <div class="rh-feat-icon"><i class="ri-bar-chart-2-line"></i></div>
                <h5>Multi-branch reporting</h5>
                <p>One owner's view of every branch — daily reconciliation, cash vs. system sales, value added and value deducted, side by side.</p>
            </div>
            <div class="rh-feat-card">
                <div class="rh-feat-icon"><i class="ri-team-line"></i></div>
                <h5>Staff, roles &amp; payroll</h5>
                <p>Branch-scoped staff accounts, role-based permissions, and full payroll — allowances, PAYE, loans and payslips — run from the same system.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════
     SEE IT IN ACTION — tabbed mockups
     ══════════════════════════════════════════════════════════════════════ -->
<section class="section bg-alt">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">See it in action</span>
            <h2 class="display-section mt-2">The screens your team will actually use</h2>
            <div class="section-divider"></div>
        </div>

        <div class="rh-showcase-tabs">
            <button class="rh-showcase-tab active" onclick="rhShowTab(this,'rhTabDash')"><i class="ri-dashboard-3-line"></i> Operations dashboard</button>
            <button class="rh-showcase-tab" onclick="rhShowTab(this,'rhTabInv')"><i class="ri-box-3-line"></i> Inventory</button>
            <button class="rh-showcase-tab" onclick="rhShowTab(this,'rhTabPos')"><i class="ri-smartphone-line"></i> Mobile POS</button>
        </div>

        {{-- Tab 1: Operations dashboard --}}
        <div class="rh-showcase-pane active" id="rhTabDash">
            <div class="rh-showcase-copy">
                <h3>One dashboard, every branch, right now</h3>
                <p>The Retail Operations home shows today's and yesterday's sales per branch, a rolling three-month trend, value added from submitted deliveries, value deducted from write-offs, and current shop valuation — the whole business at a glance.</p>
                <ul>
                    <li><i class="ri-checkbox-circle-fill"></i> System sales vs. physical cash reconciled per interval</li>
                    <li><i class="ri-checkbox-circle-fill"></i> Low and zero stock counts surfaced automatically</li>
                    <li><i class="ri-checkbox-circle-fill"></i> Drill into any branch without leaving the page</li>
                </ul>
            </div>
            <div class="rh-device" style="max-width:520px;">
                <!-- SCREENSHOT: operations/retail/default.blade.php — full page,
                     showing the KPI strip plus the Sales / Value Added / Value
                     Deducted / Shop Value cards. -->
                <div class="rh-frame">
                    <div class="rh-frame-bar"><span></span><span></span><span></span><span class="url"><i class="ri-lock-line"></i> Retail · Operations</span></div>
                    <div class="rh-frame-body">
                        <div class="rh-mock-kpis">
                            <div class="rh-mock-kpi"><div class="lbl">Value Added</div><div class="val">MWK 4.1M</div><span class="chip up"><i class="ri-truck-line"></i> Deliveries</span></div>
                            <div class="rh-mock-kpi"><div class="lbl">Value Deducted</div><div class="val">MWK 96K</div><span class="chip down"><i class="ri-close-circle-line"></i> Write-offs</span></div>
                        </div>
                        <div class="rh-mock-panel">
                            <div class="hd"><strong>Reconciliation</strong><span>Today</span></div>
                            <div class="rh-mock-row"><span class="dotb"></span><span class="name">System</span><span class="bar"><i style="width:90%"></i></span><span class="amt">842K</span></div>
                            <div class="rh-mock-row"><span class="dotb"></span><span class="name">Cash</span><span class="bar"><i style="width:86%"></i></span><span class="amt">804K</span></div>
                            <div class="rh-mock-row"><span class="dotb"></span><span class="name">Diff</span><span class="bar"><i style="width:8%"></i></span><span class="amt">-38K</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab 2: Inventory --}}
        <div class="rh-showcase-pane" id="rhTabInv">
            <div class="rh-showcase-copy">
                <h3>Stock levels you can actually trust</h3>
                <p>Branch products carry their own pricing, reorder points and stock-change history, with colour-coded pricing so it's obvious at a glance whether a branch is using its own price or the base catalogue price.</p>
                <ul>
                    <li><i class="ri-checkbox-circle-fill"></i> Bulk price updates across dozens of products at once</li>
                    <li><i class="ri-checkbox-circle-fill"></i> Separate stock-change and price-change reasons, logged</li>
                    <li><i class="ri-checkbox-circle-fill"></i> Full stocktaking with merged, missing-product detection</li>
                </ul>
            </div>
            <div class="rh-device" style="max-width:520px;">
                <!-- SCREENSHOT: operations/retail/branchproducts.blade.php —
                     the branch products table with the bulk price-setting
                     modal open, or the fullstocktaking tabs view. -->
                <div class="rh-frame">
                    <div class="rh-frame-bar"><span></span><span></span><span></span><span class="url"><i class="ri-lock-line"></i> Retail · Inventory</span></div>
                    <div class="rh-frame-body">
                        <div class="rh-mock-panel" style="margin-bottom:8px;">
                            <div class="hd"><strong>Branch products</strong><span>City Centre</span></div>
                            <div class="rh-mock-row"><span class="dotb" style="background:#22c55e;"></span><span class="name">Cooking oil 2L</span><span class="bar"><i style="width:70%"></i></span><span class="amt">142</span></div>
                            <div class="rh-mock-row"><span class="dotb" style="background:#f59e0b;"></span><span class="name">Sugar 1kg</span><span class="bar"><i style="width:22%"></i></span><span class="amt">18</span></div>
                            <div class="rh-mock-row"><span class="dotb" style="background:#ef4444;"></span><span class="name">Rice 5kg</span><span class="bar"><i style="width:4%"></i></span><span class="amt">2</span></div>
                        </div>
                        <div class="rh-mock-kpi" style="text-align:center;">
                            <div class="lbl">Reorder point breached</div>
                            <div class="val" style="color:#b91c1c;">2 products</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab 3: Mobile POS --}}
        <div class="rh-showcase-pane" id="rhTabPos">
            <div class="rh-showcase-copy">
                <h3>A till that doesn't stop when the network does</h3>
                <p>Cashiers search products, build a cart and take payment across cash, Airtel Money, Mpamba or bank — all recorded locally first, then uploaded automatically once the connection is back.</p>
                <ul>
                    <li><i class="ri-checkbox-circle-fill"></i> Works fully offline, queues sales for upload</li>
                    <li><i class="ri-checkbox-circle-fill"></i> Interval-based sales with automatic slot selection</li>
                    <li><i class="ri-checkbox-circle-fill"></i> One active session per device — no double-selling</li>
                </ul>
            </div>
            <div class="rh-device" style="max-width:360px; margin:0 auto;">
                <!-- SCREENSHOT: sales/retail/pos/mobile.blade.php — the mobile
                     POS cart view with a couple of line items and the payment
                     method panel visible. -->
                <div class="rh-frame">
                    <div class="rh-frame-bar"><span></span><span></span><span></span><span class="url"><i class="ri-signal-wifi-off-line"></i> Offline · Synced</span></div>
                    <div class="rh-frame-body">
                        <div class="rh-mock-panel" style="margin-bottom:8px;">
                            <div class="hd"><strong>Cart</strong><span>3 items</span></div>
                            <div class="rh-mock-row"><span class="dotb"></span><span class="name">Bread</span><span class="amt">1,800</span></div>
                            <div class="rh-mock-row"><span class="dotb"></span><span class="name">Milk 500ml</span><span class="amt">2,400</span></div>
                            <div class="rh-mock-row"><span class="dotb"></span><span class="name">Soap</span><span class="amt">3,200</span></div>
                        </div>
                        <div class="rh-mock-kpi" style="text-align:center;">
                            <div class="lbl">Total</div>
                            <div class="val">MWK 7,400</div>
                            <span class="chip up"><i class="ri-smartphone-line"></i> Airtel Money</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════
     STATS BANNER
     ══════════════════════════════════════════════════════════════════════ -->
<section class="section-sm bg-white">
    <div class="container" style="max-width:1100px;">
        <div class="rh-stats-banner">
            <div><strong>4</strong><span>Payment methods on POS</span></div>
            <div><strong>100%</strong><span>Offline sale capture</span></div>
            <div><strong>14</strong><span>Days free trial</span></div>
            <div><strong>1</strong><span>Login, every branch</span></div>
        </div>
    </div>
</section>

