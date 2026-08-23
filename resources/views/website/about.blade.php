@extends('website.homepage')
@section('content')

<style>
    /* =========================================================
         ABOUT PAGE — extends the tokens & utility classes
         already defined in website.homepage (var(--blue), .section,
         .kicker, .checks, .split, .business, .cta, .reveal, etc.)
    ========================================================== */

    /* ---- About hero ---- */
    .about-hero{
        position:relative;
        padding:64px 0 60px;
        background:#fff;
        overflow:hidden;
    }
    .about-hero:before{
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
    .about-hero-inner{
        position:relative;
        z-index:2;
        max-width:760px;
        margin:0 auto;
        text-align:center;
    }
    .about-hero .hero-label{margin:0 auto}
    .about-hero h1{
        margin-top:20px;
        color:var(--navy);
        font-family:"Manrope",sans-serif;
        font-size:clamp(38px,4.6vw,58px);
        line-height:1.08;
        letter-spacing:-2.5px;
        font-weight:800;
    }
    .about-hero h1 span{color:var(--blue)}
    .about-hero p{
        max-width:600px;
        margin:18px auto 0;
        color:var(--muted);
        font-size:15px;
        line-height:1.8;
    }
    .about-hero-actions{
        display:flex;
        flex-wrap:wrap;
        justify-content:center;
        gap:12px;
        margin-top:28px;
    }
    .about-hero-actions .button{min-height:47px}

    /* ---- Stats strip ---- */
    .stats-strip{padding:0 0 30px}
    .stats-shell{
        display:grid;
        grid-template-columns:repeat(4,1fr);
        border:1px solid var(--line);
        border-radius:18px;
        background:#fff;
        box-shadow:0 12px 35px rgba(16,24,40,.05);
        overflow:hidden;
    }
    .stat-block{
        padding:26px 22px;
        text-align:center;
        border-right:1px solid #f0f3f7;
    }
    .stat-block:last-child{border-right:0}
    .stat-block strong{
        display:block;
        color:var(--navy);
        font:800 30px "Manrope",sans-serif;
        letter-spacing:-1px;
    }
    .stat-block span{
        display:block;
        margin-top:6px;
        color:var(--muted);
        font-size:10px;
        font-weight:700;
        line-height:1.5;
    }

    /* ---- Mission / Vision ---- */
    .mv-grid{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:18px;
    }
    .mv-card{
        padding:32px;
        border-radius:22px;
        border:1px solid var(--line);
        background:#fff;
    }
    .mv-card.blue{
        color:#fff;
        border:0;
        background:linear-gradient(145deg,#0b56b9,#176fe5);
        box-shadow:var(--shadow-blue);
    }
    .mv-card .icon{margin-bottom:16px}
    .mv-card.blue .icon{color:var(--blue);background:#fff}
    .mv-card h3{color:var(--navy);font:800 21px "Manrope",sans-serif;letter-spacing:-.5px}
    .mv-card.blue h3{color:#fff}
    .mv-card p{margin-top:10px;color:var(--muted);font-size:13px;line-height:1.8}
    .mv-card.blue p{color:rgba(255,255,255,.78)}

    /* ---- Values grid (mirrors .business-types) ---- */
    .values-grid{
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:18px;
        align-items:stretch;
    }
    .value{
        min-height:220px;
        height:100%;
        display:flex;
        flex-direction:column;
        padding:26px;
        border:1px solid var(--line);
        border-radius:20px;
        background:#fff;
        transition:.25s;
    }
    .value:hover{transform:translateY(-5px);box-shadow:var(--shadow);border-color:var(--blue-line)}
    .value .icon{margin-bottom:6px}
    .value h3{margin-top:16px;color:var(--navy);font:800 16px "Manrope",sans-serif}
    .value p{margin-top:8px;color:var(--muted);font-size:12px;line-height:1.7}

    /* ---- Process rail (mirrors .module-shell / module-items) ---- */
    .process-shell{
        display:grid;
        grid-template-columns:repeat(4,1fr);
        border:1px solid var(--line);
        border-radius:18px;
        background:#fff;
        box-shadow:0 12px 35px rgba(16,24,40,.05);
        overflow:hidden;
    }
    .process-step{
        position:relative;
        padding:30px 26px;
        border-right:1px solid #f0f3f7;
    }
    .process-step:last-child{border-right:0}
    .process-num{
        color:#c6d3e2;
        font:800 12px "Manrope",sans-serif;
    }
    .process-step h4{
        margin-top:14px;
        color:var(--navy);
        font:800 15px "Manrope",sans-serif;
    }
    .process-step p{
        margin-top:8px;
        color:var(--muted);
        font-size:11px;
        line-height:1.7;
    }

    @media(max-width:1050px){
        .mv-grid{grid-template-columns:1fr}
        .values-grid{grid-template-columns:repeat(2,1fr)}
        .stats-shell,.process-shell{grid-template-columns:repeat(2,1fr)}
        .stat-block:nth-child(2),.process-step:nth-child(2){border-right:0}
        .stat-block,.process-step{border-bottom:1px solid #f0f3f7}
    }
    @media(max-width:600px){
        .values-grid,.stats-shell,.process-shell{grid-template-columns:1fr}
        .stat-block,.process-step{border-right:0;border-bottom:1px solid #f0f3f7}
        .about-hero{padding:44px 0 40px}
    }
</style>

<main>

    <!-- =========================================================
         ABOUT HERO
    ========================================================== -->
    <section class="about-hero">
        <div class="container">
            <div class="about-hero-inner reveal">
                <div class="hero-label">
                    <i></i>
                    About Netacube
                </div>

                <h1>
                    We build the connected way
                    <span>growing businesses run.</span>
                </h1>

                <p>
                    Netacube started with a simple observation: businesses don't fail
                    because they lack effort, they struggle because their sales,
                    inventory, people and reports live in disconnected tools. We
                    build one platform so every part of your business works
                    together — from a single counter to a growing network of
                    branches.
                </p>

                <div class="about-hero-actions">
                    <a href="{{ url('/get-started') }}" class="button button-blue">
                        Get started <span>→</span>
                    </a>
                    <a href="{{ url('/contact') }}" class="button button-light">
                        Talk to our team
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================
         STATS STRIP
    ========================================================== -->
    <section class="stats-strip">
        <div class="container">
            <div class="stats-shell reveal">
                <div class="stat-block">
                    <strong>10,000+</strong>
                    <span>Businesses running on Netacube</span>
                </div>
                <div class="stat-block">
                    <strong>99.9%</strong>
                    <span>Platform uptime</span>
                </div>
                <div class="stat-block">
                    <strong>6</strong>
                    <span>Connected business modules</span>
                </div>
                <div class="stat-block">
                    <strong>24/7</strong>
                    <span>Support for growing teams</span>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================
         OUR STORY
    ========================================================== -->
    <section class="section" id="story">
        <div class="container split">

            <div class="copy reveal">
                <div class="kicker">Our story</div>
                <h2>Built by people who have run businesses, not just software.</h2>
                <p>
                    Netacube began as a response to a familiar problem — spreadsheets
                    that didn't talk to each other, stock counts that never matched
                    the shelf, and owners finding out how their business performed
                    weeks too late. We set out to remove that gap, connecting the
                    everyday work of sales, inventory, people and reporting into a
                    single, dependable system.
                </p>
                <p>
                    Today Netacube supports retailers, wholesalers and growing
                    multi-location businesses who need one clear view of their
                    operations — without the complexity that usually comes with it.
                </p>
            </div>

            <div class="visual-card reveal">
                <img
                    src="{{ asset('images/home/dashboard.png') }}"
                    alt="Netacube business dashboard"
                >
            </div>

        </div>
    </section>

    <!-- =========================================================
         MISSION & VISION
    ========================================================== -->
    <section class="section soft" id="mission">
        <div class="container">

            <div class="section-intro center reveal">
                <div class="kicker">What drives us</div>
                <h2>A clear mission, and a longer-term view of where business management is heading.</h2>
            </div>

            <div class="mv-grid">
                <div class="mv-card blue reveal">
                    <div class="icon">◆</div>
                    <h3>Our mission</h3>
                    <p>
                        To give businesses of every size one connected platform to
                        manage sales, inventory, people and performance — so decisions
                        can be made with current information, not guesswork.
                    </p>
                </div>

                <div class="mv-card reveal">
                    <div class="icon">✦</div>
                    <h3>Our vision</h3>
                    <p>
                        A future where running a business doesn't mean juggling
                        disconnected tools — where one platform, built around how
                        businesses actually operate, is the standard.
                    </p>
                </div>
            </div>

        </div>
    </section>

    <!-- =========================================================
         VALUES
    ========================================================== -->
    <section class="section" id="values">
        <div class="container">

            <div class="section-intro center reveal">
                <div class="kicker">What we stand for</div>
                <h2>The principles behind every part of the platform.</h2>
                <p>
                    These are the standards we hold ourselves to, whether we're
                    building a new feature or answering a support request.
                </p>
            </div>

            <div class="values-grid">

                <div class="value reveal">
                    <div class="business-icon">S</div>
                    <h3>Simplicity</h3>
                    <p>Powerful tools should still feel simple to use, every single day.</p>
                </div>

                <div class="value reveal">
                    <div class="business-icon">R</div>
                    <h3>Reliability</h3>
                    <p>Your business runs on Netacube, so it has to work — every time.</p>
                </div>

                <div class="value reveal">
                    <div class="business-icon">C</div>
                    <h3>Customer-first</h3>
                    <p>We build around real operational problems, not assumptions.</p>
                </div>

                <div class="value reveal">
                    <div class="business-icon">G</div>
                    <h3>Built to grow</h3>
                    <p>From one counter to many branches, the platform grows with you.</p>
                </div>

            </div>
        </div>
    </section>

    <!-- =========================================================
         HOW WE WORK
    ========================================================== -->
    <section class="section soft" id="how-we-work">
        <div class="container">

            <div class="section-intro center reveal">
                <div class="kicker">How we work with you</div>
                <h2>From first conversation to a fully connected business.</h2>
            </div>

            <div class="process-shell reveal">
                <div class="process-step">
                    <div class="process-num">01</div>
                    <h4>Understand</h4>
                    <p>We learn how your business actually operates, day to day.</p>
                </div>
                <div class="process-step">
                    <div class="process-num">02</div>
                    <h4>Set up</h4>
                    <p>Products, branches, staff and stock are configured around you.</p>
                </div>
                <div class="process-step">
                    <div class="process-num">03</div>
                    <h4>Go live</h4>
                    <p>Your team starts working from one connected system.</p>
                </div>
                <div class="process-step">
                    <div class="process-num">04</div>
                    <h4>Grow together</h4>
                    <p>Ongoing support as your business and locations expand.</p>
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
                    <small>Let's build a more connected business</small>
                    <h2>Ready to bring your business together?</h2>
                    <p>
                        Talk to our team or get started today — see how Netacube
                        connects sales, inventory, people and reporting into one
                        platform built for growing businesses.
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