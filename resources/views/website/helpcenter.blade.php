@extends('website.homepage')

@section('title', 'Netacube Help Center — Guides & Video Tutorials')
@section('meta_description', 'Step-by-step guides and official video tutorials for Netacube — from first login to point of sale, inventory, payroll and offline mode.')

@section('head_extra')
<style>
    .page-body { padding-top: var(--nav-h); }

    /* ══ Help hero ══ */
    .help-hero {
        background: var(--gradient-deep);
        padding: 80px 0 72px;
        position: relative;
        overflow: hidden;
    }
    .help-hero-grid {
        position: absolute; inset: 0;
        background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
        background-size: 46px 46px; pointer-events: none;
        mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
    }
    .help-hero-inner { position: relative; z-index: 2; max-width: 700px; }
    .help-hero .eyebrow { color: #aab8ff; }
    .help-hero .eyebrow::before { background: #aab8ff; }
    .help-hero h1 { color: #fff; font-size: clamp(2rem, 4.2vw, 2.9rem); font-weight: 800; line-height: 1.16; letter-spacing: -0.025em; margin: 14px 0 16px; }
    .help-hero p { color: rgba(255,255,255,0.72); font-size: 1.04rem; line-height: 1.75; margin: 0 0 28px; }

    /* Search bar sitting inside the hero */
    .help-search { display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; padding: 6px 6px 6px 18px; max-width: 480px; }
    .help-search i { color: rgba(255,255,255,0.6); font-size: 1.1rem; flex-shrink: 0; }
    .help-search input { flex: 1; background: none; border: none; outline: none; color: #fff; font-size: 0.92rem; padding: 10px 0; }
    .help-search input::placeholder { color: rgba(255,255,255,0.45); }
    .help-search button {
        background: #fff; color: var(--brand); font-weight: 700; font-size: 0.85rem; border: none;
        border-radius: 9px; padding: 10px 20px; cursor: pointer; transition: .2s; flex-shrink: 0;
    }
    .help-search button:hover { box-shadow: 0 6px 18px rgba(0,0,0,.2); }

    /* ══ Topic cards ══ */
    .topic-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .topic-card {
        background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 28px;
        transition: .2s; display: block; position: relative; overflow: hidden;
    }
    .topic-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--gradient); opacity: 0; transition: .2s; }
    .topic-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-4px); border-color: transparent; }
    .topic-card:hover::before { opacity: 1; }
    .topic-icon { width: 48px; height: 48px; background: var(--brand-light); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .topic-icon i { font-size: 1.4rem; color: var(--brand); }
    .topic-card h5 { font-size: 0.97rem; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
    .topic-card p { font-size: 0.85rem; color: var(--muted); line-height: 1.65; margin: 0 0 14px; }
    .topic-card .topic-link { font-size: 0.8rem; font-weight: 700; color: var(--brand); display: inline-flex; align-items: center; gap: 5px; }
    .topic-card:hover .topic-link i { transform: translateX(3px); }
    .topic-card .topic-link i { transition: .2s; font-size: 1rem; }

    /* ══ Video cards ══ */
    .video-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
    .video-card { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow); transition: .2s; }
    .video-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); }
    .video-frame { aspect-ratio: 16/9; background: #11142a; }
    .video-frame iframe { width: 100%; height: 100%; border: 0; display: block; }
    .video-body { padding: 20px 22px 22px; }
    .video-body h5 { font-size: 0.93rem; font-weight: 800; color: var(--ink); margin-bottom: 6px; }
    .video-body p { font-size: 0.82rem; color: var(--muted); line-height: 1.6; margin: 0 0 16px; }
    .video-link {
        display: inline-flex; align-items: center; gap: 6px; font-size: 0.81rem; font-weight: 700;
        color: var(--brand); border: 1.5px solid var(--line); border-radius: 8px; padding: 8px 14px; transition: .2s;
    }
    .video-link:hover { border-color: var(--brand); background: var(--brand-light); }

    /* ══ Manual download banner ══ */
    .manual-banner {
        background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg);
        padding: 36px 40px; display: flex; align-items: center; gap: 26px; box-shadow: var(--shadow);
    }
    .manual-icon { width: 58px; height: 58px; border-radius: 14px; background: var(--brand-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .manual-icon i { font-size: 1.7rem; color: var(--brand); }
    .manual-info { flex: 1; }
    .manual-info h5 { font-size: 1.02rem; font-weight: 800; color: var(--ink); margin-bottom: 4px; }
    .manual-info p { font-size: 0.83rem; color: var(--muted); margin: 0; }
    .manual-banner .btn-primary-nc { flex-shrink: 0; }

    .help-more-link {
        display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 700; color: var(--brand);
    }
    .help-more-link i { font-size: 1.1rem; transition: .2s; }
    .help-more-link:hover i { transform: translateX(3px); }

    @media (max-width: 991px) {
        .topic-grid { grid-template-columns: repeat(2, 1fr); }
        .video-grid { grid-template-columns: repeat(2, 1fr); }
        .manual-banner { flex-direction: column; text-align: center; padding: 32px 24px; }
    }
    @media (max-width: 767px) {
        .topic-grid, .video-grid { grid-template-columns: 1fr; }
        .help-hero { padding: 56px 0; }
        .help-search { max-width: 100%; }
    }
</style>
@endsection

@section('content')

<!-- ══ Hero ══════════════════════════════════════════════════════════════ -->
<section class="help-hero">
    <div class="help-hero-grid"></div>
    <div class="container" style="max-width:1200px;">
        <div class="help-hero-inner">
            <span class="eyebrow">Help center</span>
            <h1>Guides and tutorials to help you get the most out of Netacube</h1>
            <p>From your first login to daily operations and advanced features — find the answer, watch the walkthrough, or download the full manual.</p>
            <div class="help-search">
                <i class="ri-search-line"></i>
                <input type="text" placeholder="Search guides, e.g. &quot;refund a sale&quot;">
                <button type="button">Search</button>
            </div>
        </div>
    </div>
</section>

<!-- ══ Popular topics ══════════════════════════════════════════════════ -->
<section class="section bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">Browse by topic</span>
            <h2 class="display-section mt-2">Most popular topics</h2>
            <div class="section-divider"></div>
        </div>

        @php
            $topics = [
                ['href' => '/help/getting-started', 'icon' => 'ri-rocket-line',          'title' => 'Getting Started',           'desc' => 'Account creation, first login, initial setup and adding your first products.'],
                ['href' => '/help/pos',              'icon' => 'ri-shopping-cart-2-line', 'title' => 'Point of Sale',              'desc' => 'Making sales, barcode scanning, handling payments, refunds and daily closing.'],
                ['href' => '/help/inventory',        'icon' => 'ri-archive-drawer-line',  'title' => 'Inventory Management',       'desc' => 'Adding products, stock levels, low-stock alerts, transfers between branches.'],
                ['href' => '/help/employees',        'icon' => 'ri-team-line',            'title' => 'Employees & Attendance',     'desc' => 'Adding staff, clock in/out, leave requests and shift management.'],
                ['href' => '/help/payroll',          'icon' => 'ri-money-dollar-circle-line', 'title' => 'Payroll & Payslips',     'desc' => 'Salary calculation, deductions, generating payslips and payment records.'],
                ['href' => '/help/offline',          'icon' => 'ri-wifi-off-line',        'title' => 'Offline Mode',               'desc' => 'How to keep selling when the internet is down, and how data syncs back.'],
            ];
        @endphp

        <div class="topic-grid">
            @foreach($topics as $topic)
            <a href="{{ $topic['href'] }}" class="topic-card">
                <div class="topic-icon"><i class="{{ $topic['icon'] }}"></i></div>
                <h5>{{ $topic['title'] }}</h5>
                <p>{{ $topic['desc'] }}</p>
                <span class="topic-link">Read guide <i class="ri-arrow-right-line"></i></span>
            </a>
            @endforeach
        </div>
    </div>
</section>

<!-- ══ Video tutorials ══════════════════════════════════════════════════ -->
<section class="section bg-alt">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">Watch &amp; learn</span>
            <h2 class="display-section mt-2">Official video tutorials</h2>
            <div class="section-divider"></div>
            <p class="lead-text mt-3 mx-auto" style="max-width:560px;">
                Step-by-step videos to learn Netacube quickly. New tutorials are added regularly.
            </p>
        </div>

        @php
            $videos = [
                ['id' => 'dQw4w9WgXcQ',        'title' => 'Quick Start Guide (5 min)',        'desc' => 'Register, login, add products and make your first sale.'],
                ['id' => 'VIDEO_ID_POS',       'title' => 'Mastering the POS (12 min)',       'desc' => 'Barcode scanning, discounts, payments, receipts and refunds.'],
                ['id' => 'VIDEO_ID_INVENTORY', 'title' => 'Inventory & Stock (10 min)',       'desc' => 'Products, variants, stock levels, transfers and alerts.'],
                ['id' => 'VIDEO_ID_PAYROLL',   'title' => 'Employees & Payroll (14 min)',     'desc' => 'Add staff, track attendance, process payroll and generate payslips.'],
                ['id' => 'VIDEO_ID_OFFLINE',   'title' => 'Offline Mode Explained (8 min)',   'desc' => 'How offline functionality works, plus sync troubleshooting.'],
                ['id' => 'VIDEO_ID_REPORTS',   'title' => 'Reports & Analytics (11 min)',     'desc' => 'Sales reports, profit analysis, inventory trends and exports.'],
            ];
        @endphp

        <div class="video-grid">
            @foreach($videos as $video)
            <div class="video-card">
                <div class="video-frame">
                    <iframe src="https://www.youtube.com/embed/{{ $video['id'] }}"
                            title="{{ $video['title'] }}"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
                <div class="video-body">
                    <h5>{{ $video['title'] }}</h5>
                    <p>{{ $video['desc'] }}</p>
                    <a href="https://www.youtube.com/watch?v={{ $video['id'] }}" target="_blank" class="video-link">
                        Watch on YouTube <i class="ri-external-link-line"></i>
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-5">
            <a href="https://www.youtube.com/@NetacubeOfficial" target="_blank" class="help-more-link">
                View all tutorials on YouTube <i class="ri-arrow-right-line"></i>
            </a>
        </div>
    </div>
</section>

<!-- ══ Manual download ══════════════════════════════════════════════════ -->
<section class="section bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="manual-banner">
            <div class="manual-icon"><i class="ri-file-text-line"></i></div>
            <div class="manual-info">
                <h5>Complete user manual</h5>
                <p>Version 1.2 · Updated January 2026 · 68 pages</p>
            </div>
            <a href="/downloads/netacube-user-manual-v1.2.pdf" class="btn-primary-nc" download>
                <i class="ri-download-2-line"></i> Download PDF
            </a>
        </div>
    </div>
</section>

@endsection