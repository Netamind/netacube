@extends('website.homepage')

@section('title', 'Netacube Pricing — One Price, Full Access')
@section('meta_description', 'Simple, flexible Netacube pricing. Every plan unlocks the full platform — choose 6 months, 1 year or 2 years, with a 14-day free trial and no hidden fees.')

@section('head_extra')
<style>
    .page-hero {
        background: var(--gradient-deep);
        padding: 84px 0 76px;
        position: relative;
        overflow: hidden;
        text-align: center;
    }
    .page-hero::before {
        content: '';
        position: absolute; inset: 0;
        background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
        background-size: 46px 46px;
        mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
    }
    .page-hero-inner { position: relative; z-index: 2; max-width: 640px; margin: 0 auto; }
    .page-hero .hero-badge {
        display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.22); color: #d7deff; font-size: 0.78rem; font-weight: 700;
        letter-spacing: 0.04em; padding: 7px 15px; border-radius: 20px; margin-bottom: 20px;
    }
    .page-hero h1 { font-size: clamp(2rem, 4.2vw, 2.85rem); font-weight: 800; line-height: 1.15; letter-spacing: -0.025em; color: #fff; margin-bottom: 16px; }
    .page-hero p { font-size: 1.02rem; line-height: 1.75; color: rgba(255,255,255,0.72); margin: 0; }

    /* ══ Pricing cards — larger variant of the homepage pricing teaser ══ */
    .pricing-grid-page { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; max-width: 980px; margin: 0 auto; }
    .pricing-card-page {
        background: #fff; border: 1.5px solid var(--line); border-radius: var(--radius-lg); padding: 38px 28px;
        text-align: center; position: relative; transition: .2s; display: flex; flex-direction: column;
    }
    .pricing-card-page:hover { box-shadow: var(--shadow-lg); transform: translateY(-4px); }
    .pricing-card-page.featured { border-color: var(--brand); border-width: 2px; box-shadow: 0 6px 26px rgba(75,94,189,.18); }
    .pricing-badge-page {
        position: absolute; top: -13px; left: 50%; transform: translateX(-50%); background: var(--gradient); color: #fff;
        font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; padding: 4px 16px;
        border-radius: 20px; white-space: nowrap;
    }
    .pricing-period-page { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 18px; }
    .pricing-price-page { font-size: 2.7rem; font-weight: 800; color: var(--brand); line-height: 1; margin-bottom: 4px; }
    .pricing-price-page sup { font-size: 1.1rem; vertical-align: top; margin-top: 8px; margin-right: 2px; }
    .pricing-sub-page { font-size: 0.79rem; color: var(--muted); margin-bottom: 22px; }
    .pricing-desc-page { font-size: 0.87rem; color: var(--ink-soft); line-height: 1.65; margin-bottom: 26px; flex-grow: 1; }

    /* ══ Value / reassurance strip ══ */
    .value-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
    .value-card { text-align: center; padding: 26px 20px; background: #fff; border: 1px solid var(--line); border-radius: var(--radius); transition: .2s; }
    .value-card:hover { box-shadow: var(--shadow); transform: translateY(-3px); }
    .value-icon { width: 52px; height: 52px; background: var(--brand-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
    .value-icon i { font-size: 1.4rem; color: var(--brand); }
    .value-card h5 { font-size: 0.88rem; font-weight: 800; color: var(--ink); margin-bottom: 6px; }
    .value-card p { font-size: 0.8rem; color: var(--muted); line-height: 1.6; margin: 0; }

    @media (max-width: 1100px) { .value-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 991px) { .pricing-grid-page { grid-template-columns: 1fr; max-width: 420px; } }
    @media (max-width: 767px) {
        .value-grid { grid-template-columns: 1fr; }
        .page-hero { padding: 56px 0 48px; }
    }
</style>
@endsection

@section('content')

<!-- ══ Hero ══════════════════════════════════════════════════════════════ -->
<section class="page-hero">
    <div class="container" style="max-width:1200px;">
        <div class="page-hero-inner">
            <div class="hero-badge"><i class="ri-price-tag-3-line me-1"></i> Simple pricing</div>
            <h1>One price, full access</h1>
            <p>Every plan unlocks the entire system — every sector, every branch, every module. The only difference is your billing period.</p>
        </div>
    </div>
</section>

<!-- ══ Pricing cards ══════════════════════════════════════════════════════ -->
<section class="section bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">Choose your plan</span>
            <h2 class="display-section mt-2">Pick the duration that suits your business</h2>
            <div class="section-divider"></div>
            <p class="lead-text mt-3 mx-auto" style="max-width:580px;">
                Longer commitments unlock greater savings. You can upgrade to a longer plan at any time and receive
                prorated credit for the time you have left.
            </p>
        </div>

        <div class="pricing-grid-page">
            <div class="pricing-card-page">
                <div class="pricing-period-page">6 Months</div>
                <div class="pricing-price-page"><sup>$</sup>120</div>
                <div class="pricing-sub-page">$20 / month · USD total</div>
                <p class="pricing-desc-page">Ideal for businesses wanting shorter-term flexibility, with the ability to start immediately on a 6-month commitment.</p>
                <a href="/get-started" class="btn-pricing btn-pricing-outline">Get started</a>
            </div>
            <div class="pricing-card-page featured">
                <div class="pricing-badge-page">Most popular</div>
                <div class="pricing-period-page">1 Year</div>
                <div class="pricing-price-page"><sup>$</sup>220</div>
                <div class="pricing-sub-page">$18.33 / month · USD total</div>
                <p class="pricing-desc-page">A balanced commitment with enhanced value — the right blend of flexibility and savings for a growing business.</p>
                <a href="/get-started" class="btn-pricing btn-pricing-primary">Get started</a>
            </div>
            <div class="pricing-card-page">
                <div class="pricing-period-page">2 Years</div>
                <div class="pricing-price-page"><sup>$</sup>400</div>
                <div class="pricing-sub-page">$16.67 / month · USD total</div>
                <p class="pricing-desc-page">Maximum value, with roughly 33% savings compared to shorter terms — built for long-term partnership and stability.</p>
                <a href="/get-started" class="btn-pricing btn-pricing-outline">Get started</a>
            </div>
        </div>
    </div>
</section>

<!-- ══ Value strip ══════════════════════════════════════════════════════ -->
<section class="section bg-alt">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">What's included</span>
            <h2 class="display-section mt-2">Value that grows with your business</h2>
            <div class="section-divider"></div>
            <p class="lead-text mt-3 mx-auto" style="max-width:600px;">
                Straightforward and fair pricing — no hidden fees, no feature restrictions. Every plan includes the
                full Netacube system, and the longer you commit, the more you save.
            </p>
        </div>
        <div class="value-grid">
            <div class="value-card"><div class="value-icon"><i class="ri-wifi-off-line"></i></div><h5>Offline functionality</h5><p>Full offline point of sale and stock updates, included on every plan.</p></div>
            <div class="value-card"><div class="value-icon"><i class="ri-shield-keyhole-line"></i></div><h5>Enterprise-grade security</h5><p>Encryption, daily backups and role-based access on every account.</p></div>
            <div class="value-card"><div class="value-icon"><i class="ri-building-4-line"></i></div><h5>Unlimited branches</h5><p>Add as many branches as you need at no extra cost.</p></div>
            <div class="value-card"><div class="value-icon"><i class="ri-refresh-line"></i></div><h5>Ongoing updates</h5><p>New features and improvements roll out regularly, included automatically.</p></div>
        </div>
    </div>
</section>

@endsection