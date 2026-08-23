@extends('website.homepage')
@section('content')

<style>
    /* =========================================================
         HELP CENTER HUB — links out to /help-center/faq,
         /help-center/videos and /help-center/user-manual
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
        max-width:700px;
        margin:0 auto;
        text-align:center;
    }
    .page-hero .hero-label{margin:0 auto}
    .page-hero h1{
        margin-top:20px;
        color:var(--navy);
        font-family:"Manrope",sans-serif;
        font-size:clamp(34px,4.2vw,50px);
        line-height:1.12;
        letter-spacing:-2px;
        font-weight:800;
    }
    .page-hero p{
        max-width:600px;
        margin:16px auto 0;
        color:var(--muted);
        font-size:15px;
        line-height:1.8;
    }

    /* ---- Destination cards (FAQ / Videos / User Manual) ---- */
    .hub-grid{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:20px;
        align-items:stretch;
    }
    .hub-card{
        display:flex;
        flex-direction:column;
        min-height:250px;
        height:100%;
        padding:32px;
        border-radius:22px;
        transition:.25s;
    }
    .hub-card:hover{transform:translateY(-5px)}
    .hub-card.plain{
        border:1px solid var(--line);
        background:#fff;
    }
    .hub-card.plain:hover{box-shadow:var(--shadow);border-color:var(--blue-line)}
    .hub-card.blue{
        color:#fff;
        background:linear-gradient(145deg,#0b56b9,#176fe5);
        box-shadow:var(--shadow-blue);
    }
    .hub-icon{
        width:48px;height:48px;
        display:grid;place-items:center;
        border-radius:13px;
        color:var(--blue);
        background:var(--blue-pale);
        font-weight:900;
        font-size:19px;
    }
    .hub-card.blue .hub-icon{color:var(--blue);background:#fff}
    .hub-card h3{margin-top:20px;color:var(--navy);font:800 19px "Manrope",sans-serif}
    .hub-card.blue h3{color:#fff}
    .hub-card p{margin-top:8px;flex-grow:1;color:var(--muted);font-size:12.5px;line-height:1.75}
    .hub-card.blue p{color:rgba(255,255,255,.78)}
    .hub-link{
        margin-top:18px;
        color:var(--blue);
        font-size:11px;
        font-weight:900;
    }
    .hub-card.blue .hub-link{color:#fff}

    /* ---- Topic quick-links ---- */
    .topic-grid{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:18px;
        align-items:stretch;
    }
    .topic-card{
        display:block;
        min-height:150px;
        height:100%;
        padding:24px;
        border:1px solid var(--line);
        border-radius:18px;
        background:#fff;
        transition:.25s;
    }
    .topic-card:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:var(--blue-line)}
    .topic-card h3{margin-top:14px;color:var(--navy);font:800 13.5px "Manrope",sans-serif}
    .topic-card p{margin-top:6px;color:var(--muted);font-size:11px;line-height:1.6}

    @media(max-width:1050px){
        .hub-grid{grid-template-columns:1fr}
        .topic-grid{grid-template-columns:repeat(2,1fr)}
    }
    @media(max-width:600px){
        .topic-grid{grid-template-columns:1fr}
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
                    Help center
                </div>

                <h1>Everything you need to get the most out of Netacube.</h1>

                <p>
                    Browse frequently asked questions, watch step-by-step
                    video tutorials, or download the full user manual.
                </p>
            </div>
        </div>
    </section>

    <!-- =========================================================
         HUB — FAQ / VIDEOS / USER MANUAL
    ========================================================== -->
    <section class="section soft">
        <div class="container">

            <div class="section-intro center reveal">
                <div class="kicker">Where would you like to start?</div>
                <h2>Choose how you'd like to get help.</h2>
            </div>

            <div class="hub-grid">

                <a href="{{ url('/help-center/faq') }}" class="hub-card blue reveal">
                    <div class="hub-icon">?</div>
                    <h3>Frequently asked questions</h3>
                    <p>Quick answers to the most common questions about accounts, billing, features and troubleshooting.</p>
                    <span class="hub-link">Browse FAQ →</span>
                </a>

                <a href="{{ url('/help-center/videos') }}" class="hub-card plain reveal">
                    <div class="hub-icon">▶</div>
                    <h3>Video tutorials</h3>
                    <p>Step-by-step walkthroughs covering everything from your first login to daily operations.</p>
                    <span class="hub-link">Watch videos →</span>
                </a>

                <a href="{{ url('/help-center/user-manual') }}" class="hub-card plain reveal">
                    <div class="hub-icon">▤</div>
                    <h3>User manual</h3>
                    <p>The complete Netacube guide, ready to download and keep on hand for your whole team.</p>
                    <span class="hub-link">Download manual →</span>
                </a>

            </div>
        </div>
    </section>

    <!-- =========================================================
         POPULAR TOPICS (quick links into the FAQ)
    ========================================================== -->
    <section class="section">
        <div class="container">

            <div class="section-intro center reveal">
                <div class="kicker">Browse by topic</div>
                <h2>Most popular topics.</h2>
            </div>

            @php
                $topics = [
                    ['href' => '/help-center/faq#getting-started', 'icon' => '➜', 'title' => 'Getting Started',       'desc' => 'Account creation, first login and initial setup.'],
                    ['href' => '/help-center/faq#pos',              'icon' => '₵', 'title' => 'Point of Sale',          'desc' => 'Sales, payments, refunds and daily closing.'],
                    ['href' => '/help-center/faq#inventory',        'icon' => '▦', 'title' => 'Inventory',              'desc' => 'Stock levels, transfers and low-stock alerts.'],
                    ['href' => '/help-center/faq#employees',        'icon' => '◉', 'title' => 'Employees & Attendance', 'desc' => 'Staff records, clock in/out and leave.'],
                    ['href' => '/help-center/faq#payroll',          'icon' => '$', 'title' => 'Payroll',                'desc' => 'Salary calculation, deductions and payslips.'],
                    ['href' => '/help-center/faq#offline',          'icon' => '◌', 'title' => 'Offline Mode',           'desc' => 'Selling without internet and how syncing works.'],
                ];
            @endphp

            <div class="topic-grid">
                @foreach($topics as $topic)
                <a href="{{ $topic['href'] }}" class="topic-card reveal">
                    <div class="icon">{{ $topic['icon'] }}</div>
                    <h3>{{ $topic['title'] }}</h3>
                    <p>{{ $topic['desc'] }}</p>
                </a>
                @endforeach
            </div>
        </div>
    </section>

</main>

@endsection