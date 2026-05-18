@extends('website.homepage')

@section('title', 'About Netacube — Built by Netamind Technology')
@section('meta_description', 'Netacube is developed by Netamind Technology — a business management platform built for retail, wholesale, hospitality, healthcare and service businesses that need to keep working, online or off.')

@section('head_extra')
<style>
    .page-body { padding-top: var(--nav-h); }

    /* ══ About hero ══ */
    .about-hero {
        background: var(--gradient-deep);
        padding: 80px 0 72px;
        position: relative;
        overflow: hidden;
    }
    .about-hero-grid {
        position: absolute; inset: 0;
        background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
        background-size: 46px 46px; pointer-events: none;
        mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
    }
    .about-hero-inner { position: relative; z-index: 2; max-width: 720px; }
    .about-hero .eyebrow { color: #aab8ff; }
    .about-hero .eyebrow::before { background: #aab8ff; }
    .about-hero h1 { color: #fff; font-size: clamp(2rem, 4.2vw, 2.9rem); font-weight: 800; line-height: 1.16; letter-spacing: -0.025em; margin: 14px 0 16px; }
    .about-hero p { color: rgba(255,255,255,0.72); font-size: 1.04rem; line-height: 1.75; margin: 0; }

    /* ══ Story block ══ */
    .about-story-grid { display: grid; grid-template-columns: 1.05fr 0.95fr; gap: 56px; align-items: center; }
    .about-story-copy p { font-size: 0.96rem; line-height: 1.8; color: var(--ink-soft); margin-bottom: 16px; }
    .about-story-copy p:last-child { margin-bottom: 0; }
    .about-story-copy strong { color: var(--ink); }
    .about-story-visual { background: var(--surface-alt); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 36px; }
    .about-stat-row { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
    .about-stat { padding: 18px 0; border-top: 1px solid var(--line); }
    .about-stat:nth-child(1), .about-stat:nth-child(2) { border-top: none; }
    .about-stat .num { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.7rem; font-weight: 800; color: var(--brand); line-height: 1; margin-bottom: 6px; }
    .about-stat .label { font-size: 0.8rem; color: var(--muted); line-height: 1.5; }

    /* ══ Mission / vision ══ */
    .mv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .mv-card { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 30px; transition: .2s; }
    .mv-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); border-color: transparent; }
    .mv-icon { width: 48px; height: 48px; background: var(--brand-light); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .mv-icon i { font-size: 1.4rem; color: var(--brand); }
    .mv-card h3 { font-size: 1.1rem; font-weight: 800; color: var(--ink); margin-bottom: 10px; }
    .mv-card p { font-size: 0.9rem; color: var(--ink-soft); line-height: 1.7; margin: 0; }

    /* ══ Differentiators / values — reuse arch-card + feat-icon language ══ */
    .about-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .about-card { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 28px; transition: .2s; }
    .about-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); border-color: transparent; }
    .about-card .feat-icon { width: 48px; height: 48px; background: var(--brand-light); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .about-card .feat-icon i { font-size: 1.4rem; color: var(--brand); }
    .about-card h5 { font-size: 0.97rem; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
    .about-card p { font-size: 0.85rem; color: var(--muted); line-height: 1.65; margin: 0; }

    @media (max-width: 991px) {
        .about-story-grid { grid-template-columns: 1fr; gap: 36px; }
        .mv-grid { grid-template-columns: 1fr; }
        .about-grid-3 { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 767px) {
        .about-grid-3 { grid-template-columns: 1fr; }
        .about-hero { padding: 56px 0; }
    }
</style>
@endsection

@section('content')

<!-- ══ Hero ══════════════════════════════════════════════════════════════ -->
<section class="about-hero">
    <div class="about-hero-grid"></div>
    <div class="container" style="max-width:1200px;">
        <div class="about-hero-inner">
            <span class="eyebrow">About Netacube</span>
            <h1>One platform, built by people who've run the same problems you have</h1>
            <p>Netacube is developed by Netamind Technology — built for retail, wholesale and service businesses that need their systems to keep working, whether the internet does or not.</p>
        </div>
    </div>
</section>

<!-- ══ Our story ══════════════════════════════════════════════════════════ -->
<section class="section bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="about-story-grid">
            <div class="about-story-copy">
                <span class="eyebrow">Our story</span>
                <h2 class="display-section mt-2 mb-3">Why we built Netacube</h2>
                <p>Netacube was created to address challenges we kept seeing in real businesses. Founded by <strong>Netamind Technology</strong>, we noticed that retail, wholesale and service-based businesses were held back by fragmented systems, unreliable connectivity, weak data protection and software that wasn't designed around how they actually operate day to day.</p>
                <p>We started as a small team of developers and business people, and built one unified platform that brings inventory, point of sale, staff, payroll, documents and reporting together — engineered to keep working whether you're online or offline.</p>
                <p>We're proud to build something that genuinely supports the day-to-day running and growth of the businesses that rely on it.</p>
            </div>
            <div class="about-story-visual">
                <div class="about-stat-row">
                    <div class="about-stat">
                        <div class="num">8+</div>
                        <div class="label">Industries supported, from retail to property management</div>
                    </div>
                    <div class="about-stat">
                        <div class="num">100%</div>
                        <div class="label">Sales recorded even when the connection drops</div>
                    </div>
                    <div class="about-stat">
                        <div class="num">14</div>
                        <div class="label">Days free trial, full access, no card required</div>
                    </div>
                    <div class="about-stat">
                        <div class="num">24/7</div>
                        <div class="label">Support over email and WhatsApp</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ Mission & vision ══════════════════════════════════════════════════ -->
<section class="section bg-alt">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">What drives us</span>
            <h2 class="display-section mt-2">Our mission &amp; vision</h2>
            <div class="section-divider"></div>
        </div>
        <div class="mv-grid">
            <div class="mv-card">
                <div class="mv-icon"><i class="ri-rocket-2-line"></i></div>
                <h3>Our mission</h3>
                <p>To deliver a secure, affordable and genuinely practical business management platform — one that helps businesses streamline operations, protect critical data and grow sustainably, even where connectivity can't be relied on.</p>
            </div>
            <div class="mv-card">
                <div class="mv-icon"><i class="ri-eye-line"></i></div>
                <h3>Our vision</h3>
                <p>To become the most trusted business management platform in the markets we serve — known for being reliable, practical and built around how businesses actually work, not how software vendors imagine they do.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══ What sets us apart ════════════════════════════════════════════════ -->
<section class="section bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">What sets us apart</span>
            <h2 class="display-section mt-2">Built differently, on purpose</h2>
            <div class="section-divider"></div>
            <p class="lead-text mt-3 mx-auto" style="max-width:560px;">
                The same priorities that shape every feature we ship, from the smallest till to the full reporting dashboard.
            </p>
        </div>
        <div class="about-grid-3">
            <div class="about-card">
                <div class="feat-icon"><i class="ri-wifi-off-line"></i></div>
                <h5>True offline capability</h5>
                <p>Operations carry on through internet outages, with everything syncing automatically the moment connectivity returns.</p>
            </div>
            <div class="about-card">
                <div class="feat-icon"><i class="ri-shield-check-line"></i></div>
                <h5>Enterprise-grade security</h5>
                <p>Role-based access, audit trails, encryption and daily backups — data protection built in from the ground up, not bolted on.</p>
            </div>
            <div class="about-card">
                <div class="feat-icon"><i class="ri-team-line"></i></div>
                <h5>Practical, professional design</h5>
                <p>Engineered around real business needs while holding to international standards for usability and reliability.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══ Core values ════════════════════════════════════════════════════════ -->
<section class="section bg-alt">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">How we work</span>
            <h2 class="display-section mt-2">Our core values</h2>
            <div class="section-divider"></div>
            <p class="lead-text mt-3 mx-auto" style="max-width:560px;">
                The principles behind every decision we make, from product roadmap to customer support.
            </p>
        </div>
        <div class="about-grid-3">
            <div class="about-card">
                <div class="feat-icon"><i class="ri-shield-keyhole-line"></i></div>
                <h5>Security first</h5>
                <p>Enterprise-grade protection underpins everything we build, keeping your business data confidential, accurate and available.</p>
            </div>
            <div class="about-card">
                <div class="feat-icon"><i class="ri-customer-service-2-line"></i></div>
                <h5>Customer-centred</h5>
                <p>Your success comes first. We provide real support over email and WhatsApp, and evolve the platform around genuine customer feedback.</p>
            </div>
            <div class="about-card">
                <div class="feat-icon"><i class="ri-lightbulb-line"></i></div>
                <h5>Innovation with simplicity</h5>
                <p>Powerful, forward-thinking features delivered through an interface that needs minimal training and respects your time.</p>
            </div>
        </div>
    </div>
</section>

@endsection