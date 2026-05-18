@extends('website.homepage')

@section('title', 'Netacube Features — Everything Your Business Runs On')
@section('meta_description', 'Explore Netacube’s full feature set: point of sale with offline support, inventory, employees and payroll, document generation, reporting, security and multi-branch management.')

@section('head_extra')
<style>
    /* ══ Page-local hero (lighter than the home hero — no floating cards needed) ══ */
    .page-hero {
        background: var(--gradient-deep);
        padding: 84px 0 76px;
        position: relative;
        overflow: hidden;
    }
    .page-hero::before {
        content: '';
        position: absolute; inset: 0;
        background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
        background-size: 46px 46px;
        mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
    }
    .page-hero-inner { position: relative; z-index: 2; max-width: 720px; }
    .page-hero .hero-badge {
        display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.22); color: #d7deff; font-size: 0.78rem; font-weight: 700;
        letter-spacing: 0.04em; padding: 7px 15px; border-radius: 20px; margin-bottom: 20px;
    }
    .page-hero h1 { font-size: clamp(2rem, 4.2vw, 2.85rem); font-weight: 800; line-height: 1.15; letter-spacing: -0.025em; color: #fff; margin-bottom: 16px; }
    .page-hero p { font-size: 1.02rem; line-height: 1.75; color: rgba(255,255,255,0.72); margin: 0; }

    /* ══ Feature grid (3 columns, reuses the card language from the home page) ══ */
    .feat-grid-page { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .feat-card-page {
        background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 28px;
        transition: .2s;
    }
    .feat-card-page:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); border-color: transparent; }
    .feat-card-page .feat-icon { width: 48px; height: 48px; background: var(--brand-light); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .feat-card-page .feat-icon i { font-size: 1.4rem; color: var(--brand); }
    .feat-card-page h5 { font-size: 0.97rem; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
    .feat-card-page p { font-size: 0.85rem; color: var(--muted); line-height: 1.65; margin: 0; }

    /* ══ Integration / philosophy section ══ */
    .integration-copy { max-width: 720px; margin: 0 auto; }
    .integration-copy p { font-size: 0.97rem; line-height: 1.8; color: var(--ink-soft); margin-bottom: 18px; }

    .pillar-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; max-width: 1000px; margin: 40px auto 0; }
    .pillar-card { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 26px; transition: .2s; }
    .pillar-card:hover { box-shadow: var(--shadow); transform: translateY(-3px); }
    .pillar-icon { width: 46px; height: 46px; background: var(--brand-light); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
    .pillar-icon i { font-size: 1.3rem; color: var(--brand); }
    .pillar-card h5 { font-size: 0.93rem; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
    .pillar-card p { font-size: 0.84rem; color: var(--muted); line-height: 1.65; margin: 0; }

    @media (max-width: 1100px) {
        .feat-grid-page { grid-template-columns: repeat(2, 1fr); }
        .pillar-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 767px) {
        .feat-grid-page { grid-template-columns: 1fr; }
        .page-hero { padding: 56px 0 48px; }
    }
</style>
@endsection

@section('content')

<!-- ══ Hero ══════════════════════════════════════════════════════════════ -->
<section class="page-hero">
    <div class="container" style="max-width:1200px;">
        <div class="page-hero-inner">
            <div class="hero-badge"><i class="ri-apps-2-line me-1"></i> Platform features</div>
            <h1>Everything your business runs on, working together</h1>
            <p>
                Netacube brings point of sale, inventory, people, payroll, documents and reporting into one
                secure platform — built so a single sale updates stock, finances and dashboards at the same time,
                with nothing to reconcile by hand.
            </p>
        </div>
    </div>
</section>

<!-- ══ Feature grid ══════════════════════════════════════════════════════ -->
<section class="section bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">Core features</span>
            <h2 class="display-section mt-2">The tools that drive your business forward</h2>
            <div class="section-divider"></div>
        </div>

        <div class="feat-grid-page">
            <div class="feat-card-page">
                <div class="feat-icon"><i class="ri-shopping-cart-2-line"></i></div>
                <h5>Point of sale</h5>
                <p>Fast, reliable checkout with barcode scanning, multiple payment methods and customizable receipts — built to keep moving even during peak hours.</p>
            </div>
            <div class="feat-card-page">
                <div class="feat-icon"><i class="ri-archive-drawer-line"></i></div>
                <h5>Inventory management</h5>
                <p>Real-time stock tracking, low-stock alerts, batch and variant management, multi-location support and automatic reorder suggestions.</p>
            </div>
            <div class="feat-card-page">
                <div class="feat-icon"><i class="ri-wifi-off-line"></i></div>
                <h5>Full offline functionality</h5>
                <p>Continue sales, stock updates and daily operations without an internet connection — everything syncs automatically the moment you're back online.</p>
            </div>
            <div class="feat-card-page">
                <div class="feat-icon"><i class="ri-team-line"></i></div>
                <h5>Employee management</h5>
                <p>Attendance tracking, leave management, shift scheduling, performance reviews and role-based access permissions, all in one place.</p>
            </div>
            <div class="feat-card-page">
                <div class="feat-icon"><i class="ri-money-dollar-circle-line"></i></div>
                <h5>Payroll &amp; deductions</h5>
                <p>Automated payroll processing, salary calculations, statutory deductions, payslip generation and full payment history tracking.</p>
            </div>
            <div class="feat-card-page">
                <div class="feat-icon"><i class="ri-file-text-line"></i></div>
                <h5>Document generation</h5>
                <p>Professional, branded invoices, quotations, delivery notes, purchase orders and receipts generated from your own company profile.</p>
            </div>
            <div class="feat-card-page">
                <div class="feat-icon"><i class="ri-bar-chart-grouped-line"></i></div>
                <h5>Advanced reporting &amp; analytics</h5>
                <p>Dashboards and reports covering sales, profit margins, inventory trends and staff performance — exportable in multiple formats.</p>
            </div>
            <div class="feat-card-page">
                <div class="feat-icon"><i class="ri-shield-check-line"></i></div>
                <h5>Enterprise-grade security</h5>
                <p>Role-based access control, activity audit logs, encrypted data and daily backups to keep your business information protected.</p>
            </div>
            <div class="feat-card-page">
                <div class="feat-icon"><i class="ri-building-4-line"></i></div>
                <h5>Multi-branch / multi-location</h5>
                <p>Centralized control across stores or branches, with inter-branch transfers, consolidated reporting and location-specific settings.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══ Integration & philosophy ══════════════════════════════════════════ -->
<section class="section bg-alt">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">Why it feels different</span>
            <h2 class="display-section mt-2">A truly unified business platform</h2>
            <div class="section-divider"></div>
        </div>

        <div class="integration-copy text-center">
            <p>
                Netacube is built as one connected system, not a bundle of separate tools. A sale completed at the
                till instantly adjusts stock levels, updates financial records and feeds your dashboards — no manual
                reconciliation required. Staff shifts and attendance flow straight into payroll, while the same
                security layer protects every module.
            </p>
            <p>
                This integration removes repeated data entry and reduces errors, giving you one accurate picture of
                how your business is performing. It's designed so non-technical teams can use it confidently from
                day one, whether you're running a single shop or a growing network of branches.
            </p>
        </div>

        <div class="pillar-grid">
            <div class="pillar-card">
                <div class="pillar-icon"><i class="ri-link-m"></i></div>
                <h5>Seamless integration</h5>
                <p>Every module communicates in real time, so your data stays consistent across the whole operation.</p>
            </div>
            <div class="pillar-card">
                <div class="pillar-icon"><i class="ri-shield-check-line"></i></div>
                <h5>Built for reliability</h5>
                <p>Offline capability and robust security keep your business running smoothly, whatever the conditions.</p>
            </div>
            <div class="pillar-card">
                <div class="pillar-icon"><i class="ri-rocket-2-line"></i></div>
                <h5>Scalable growth</h5>
                <p>From a single shop to a multi-branch enterprise, Netacube adapts with flexible, expandable features.</p>
            </div>
        </div>
    </div>
</section>

@endsection