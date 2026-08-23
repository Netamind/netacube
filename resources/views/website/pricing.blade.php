@extends('website.homepage')
@section('content')

<style>
    /* =========================================================
         PRICING PAGE — built on the tokens & utility classes
         already defined in website.homepage
    ========================================================== */

    .page-hero{
        position:relative;
        padding:64px 0 56px;
        background:#fff;
        overflow:hidden;
        text-align:center;
    }
    .page-hero:before{
        content:"";
        position:absolute;
        width:640px;
        height:640px;
        left:50%;
        transform:translateX(-50%);
        top:-320px;
        border-radius:50%;
        background:radial-gradient(circle,rgba(23,111,229,.10) 0%,rgba(23,111,229,.03) 45%,transparent 72%);
        pointer-events:none;
    }
    .page-hero-inner{
        position:relative;
        z-index:2;
        max-width:640px;
        margin:0 auto;
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
        max-width:560px;
        margin:18px auto 0;
        color:var(--muted);
        font-size:15px;
        line-height:1.8;
    }

    /* ---- Pricing cards ---- */
    .pricing-grid{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:20px;
        max-width:980px;
        margin:0 auto;
        align-items:stretch;
    }
    .pricing-card{
        position:relative;
        display:flex;
        flex-direction:column;
        text-align:center;
        padding:38px 28px;
        border:1.5px solid var(--line);
        border-radius:22px;
        background:#fff;
        transition:.25s;
    }
    .pricing-card:hover{transform:translateY(-5px);box-shadow:var(--shadow)}
    .pricing-card.featured{
        color:#fff;
        border:0;
        background:linear-gradient(145deg,#0b56b9,#176fe5);
        box-shadow:var(--shadow-blue);
    }
    .pricing-badge{
        position:absolute;
        top:-13px;left:50%;
        transform:translateX(-50%);
        padding:5px 16px;
        border-radius:999px;
        background:var(--navy);
        color:#fff;
        font-size:9px;
        font-weight:900;
        letter-spacing:.6px;
        text-transform:uppercase;
        white-space:nowrap;
    }
    .pricing-period{
        color:var(--blue);
        font-size:10px;
        font-weight:900;
        letter-spacing:1px;
        text-transform:uppercase;
    }
    .featured .pricing-period{color:#dbeaff}
    .pricing-price{
        margin-top:16px;
        color:var(--navy);
        font:800 44px "Manrope",sans-serif;
        letter-spacing:-1px;
        line-height:1;
    }
    .featured .pricing-price{color:#fff}
    .pricing-price sup{font-size:17px;vertical-align:top;margin-right:2px}
    .pricing-sub{margin-top:6px;color:var(--muted);font-size:11px;font-weight:700}
    .featured .pricing-sub{color:rgba(255,255,255,.75)}
    .pricing-desc{
        flex-grow:1;
        margin-top:20px;
        color:var(--muted);
        font-size:12.5px;
        line-height:1.7;
    }
    .featured .pricing-desc{color:rgba(255,255,255,.82)}
    .pricing-card .button{margin-top:26px;width:100%;justify-content:center}
    .featured .button-blue{background:#fff;color:var(--blue)}

    /* ---- Value strip (mirrors stat-block pattern) ---- */
    .value-shell{
        display:grid;
        grid-template-columns:repeat(4,1fr);
        border:1px solid var(--line);
        border-radius:18px;
        background:#fff;
        box-shadow:0 12px 35px rgba(16,24,40,.05);
        overflow:hidden;
    }
    .value-item{
        padding:28px 22px;
        text-align:center;
        border-right:1px solid #f0f3f7;
    }
    .value-item:last-child{border-right:0}
    .value-item .icon{margin:0 auto 14px}
    .value-item h3{color:var(--navy);font:800 13px "Manrope",sans-serif}
    .value-item p{margin-top:6px;color:var(--muted);font-size:11px;line-height:1.6}

    @media(max-width:1050px){
        .pricing-grid{grid-template-columns:1fr;max-width:420px}
        .value-shell{grid-template-columns:repeat(2,1fr)}
        .value-item:nth-child(2){border-right:0}
        .value-item{border-bottom:1px solid #f0f3f7}
    }
    @media(max-width:600px){
        .value-shell{grid-template-columns:1fr}
        .value-item{border-right:0;border-bottom:1px solid #f0f3f7}
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
                    Simple pricing
                </div>

                <h1>One price, full access.</h1>

                <p>
                    Every plan unlocks the entire system — every sector, every
                    branch, every module. The only difference is your
                    billing period.
                </p>
            </div>
        </div>
    </section>

    <!-- =========================================================
         PRICING CARDS
    ========================================================== -->
    <section class="section soft">
        <div class="container">

            <div class="section-intro center reveal">
                <div class="kicker">Choose your plan</div>
                <h2>Pick the duration that suits your business.</h2>
                <p>
                    Longer commitments unlock greater savings. You can
                    upgrade to a longer plan at any time and receive prorated
                    credit for the time you have left.
                </p>
            </div>

            <div class="pricing-grid">

                <div class="pricing-card reveal">
                    <div class="pricing-period">6 Months</div>
                    <div class="pricing-price"><sup>$</sup>120</div>
                    <div class="pricing-sub">$20 / month · USD total</div>
                    <p class="pricing-desc">Ideal for businesses wanting shorter-term flexibility, with the ability to start immediately on a 6-month commitment.</p>
                    <a href="{{ url('/get-started') }}" class="button button-light">Get started</a>
                </div>

                <div class="pricing-card featured reveal">
                    <div class="pricing-badge">Most popular</div>
                    <div class="pricing-period">1 Year</div>
                    <div class="pricing-price"><sup>$</sup>220</div>
                    <div class="pricing-sub">$18.33 / month · USD total</div>
                    <p class="pricing-desc">A balanced commitment with enhanced value — the right blend of flexibility and savings for a growing business.</p>
                    <a href="{{ url('/get-started') }}" class="button button-blue">Get started</a>
                </div>

                <div class="pricing-card reveal">
                    <div class="pricing-period">2 Years</div>
                    <div class="pricing-price"><sup>$</sup>400</div>
                    <div class="pricing-sub">$16.67 / month · USD total</div>
                    <p class="pricing-desc">Maximum value, with roughly 33% savings compared to shorter terms — built for long-term partnership and stability.</p>
                    <a href="{{ url('/get-started') }}" class="button button-light">Get started</a>
                </div>

            </div>
        </div>
    </section>

    <!-- =========================================================
         VALUE STRIP
    ========================================================== -->
    <section class="section">
        <div class="container">

            <div class="section-intro center reveal">
                <div class="kicker">What's included</div>
                <h2>Value that grows with your business.</h2>
                <p>
                    Straightforward and fair pricing — no hidden fees, no
                    feature restrictions. Every plan includes the full
                    Netacube system, and the longer you commit, the more
                    you save.
                </p>
            </div>

            <div class="value-shell reveal">
                <div class="value-item">
                    <div class="icon">◌</div>
                    <h3>Offline functionality</h3>
                    <p>Full offline point of sale and stock updates, included on every plan.</p>
                </div>
                <div class="value-item">
                    <div class="icon">🛡</div>
                    <h3>Enterprise-grade security</h3>
                    <p>Encryption, daily backups and role-based access on every account.</p>
                </div>
                <div class="value-item">
                    <div class="icon">⌂</div>
                    <h3>Unlimited branches</h3>
                    <p>Add as many branches as you need at no extra cost.</p>
                </div>
                <div class="value-item">
                    <div class="icon">↻</div>
                    <h3>Ongoing updates</h3>
                    <p>New features and improvements roll out regularly, included automatically.</p>
                </div>
            </div>
        </div>
    </section>

</main>

@endsection