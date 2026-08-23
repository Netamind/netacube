@extends('website.homepage')
@section('content')

<style>
    /* =========================================================
         HELP CENTER — USER MANUAL
    ========================================================== */

    .page-hero{position:relative;padding:56px 0 44px;background:#fff;overflow:hidden}
    .page-hero:before{
        content:"";position:absolute;width:640px;height:640px;left:-260px;top:-220px;
        border-radius:50%;
        background:radial-gradient(circle,rgba(23,111,229,.10) 0%,rgba(23,111,229,.03) 45%,transparent 72%);
        pointer-events:none;
    }
    .page-hero-inner{position:relative;z-index:2;max-width:700px;margin:0 auto;text-align:center}
    .page-hero .hero-label{margin:0 auto}
    .page-hero h1{
        margin-top:20px;color:var(--navy);font-family:"Manrope",sans-serif;
        font-size:clamp(32px,4vw,46px);line-height:1.14;letter-spacing:-2px;font-weight:800;
    }
    .page-hero p{max-width:560px;margin:14px auto 0;color:var(--muted);font-size:14px;line-height:1.8}
    .breadcrumb{display:flex;justify-content:center;gap:6px;margin-bottom:14px;color:var(--muted);font-size:11.5px;font-weight:700}
    .breadcrumb a{color:var(--blue)}

    /* ---- Download banner ---- */
    .manual-banner{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:24px;
        padding:30px 36px;
        border-radius:20px;
        color:#fff;
        background:linear-gradient(145deg,#0b56b9,#176fe5);
        box-shadow:var(--shadow-blue);
        margin-bottom:40px;
    }
    .manual-banner-text h2{font:800 19px "Manrope",sans-serif;letter-spacing:-.3px}
    .manual-banner-text p{margin-top:6px;color:rgba(255,255,255,.8);font-size:12.5px}
    .manual-banner .button-white{flex-shrink:0}

    /* ---- Chapter list ---- */
    .chapter-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
    .chapter{
        display:flex;
        align-items:flex-start;
        gap:16px;
        padding:24px;
        border:1px solid var(--line);
        border-radius:16px;
        background:#fff;
        transition:.25s;
    }
    .chapter:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:var(--blue-line)}
    .chapter-num{
        flex-shrink:0;
        width:38px;height:38px;
        display:grid;place-items:center;
        border-radius:11px;
        background:var(--blue-pale);
        color:var(--blue);
        font:800 13px "Manrope",sans-serif;
    }
    .chapter h3{color:var(--navy);font:800 14.5px "Manrope",sans-serif}
    .chapter p{margin-top:6px;color:var(--muted);font-size:11.5px;line-height:1.7}

    @media(max-width:700px){.chapter-list{grid-template-columns:1fr}}
    @media(max-width:600px){
        .manual-banner{flex-direction:column;align-items:flex-start;padding:26px}
    }
</style>

<main>

    <section class="page-hero">
        <div class="container">
            <div class="page-hero-inner reveal">
                <div class="breadcrumb"><a href="{{ url('/help-center') }}">Help center</a> / <span>User manual</span></div>
                <div class="hero-label"><i></i>User manual</div>
                <h1>The complete Netacube guide.</h1>
                <p>Every module explained in one document — download it, print it, or keep it on hand for your team.</p>
            </div>
        </div>
    </section>

    <section class="section soft">
        <div class="container">

            <div class="manual-banner reveal">
                <div class="manual-banner-text">
                    <h2>Download the full Netacube user manual</h2>
                    <p>PDF format · Updated {{ now()->format('F Y') }} · Covers every module below</p>
                </div>
                <a href="{{ asset('files/netacube-user-manual.pdf') }}" class="button button-white" download>
                    Download PDF →
                </a>
            </div>

            <div class="section-intro center reveal">
                <div class="kicker">Inside the manual</div>
                <h2>Browse by chapter.</h2>
            </div>

            @php
                $chapters = [
                    ['num' => '01', 'title' => 'Getting Started',          'desc' => 'Account setup, branches, staff and first login.'],
                    ['num' => '02', 'title' => 'Point of Sale',            'desc' => 'Sales, payments, refunds and daily closing.'],
                    ['num' => '03', 'title' => 'Inventory',                'desc' => 'Products, stock levels, transfers and stocktaking.'],
                    ['num' => '04', 'title' => 'Purchasing & Suppliers',   'desc' => 'Ordering, deliveries and supplier management.'],
                    ['num' => '05', 'title' => 'Employees & Attendance',   'desc' => 'Staff records, clock in/out and leave.'],
                    ['num' => '06', 'title' => 'Payroll',                  'desc' => 'Salary calculation, deductions and payslips.'],
                    ['num' => '07', 'title' => 'Reports',                  'desc' => 'Daily, branch and business-wide reporting.'],
                    ['num' => '08', 'title' => 'Offline Mode',             'desc' => 'Selling without internet and how syncing works.'],
                ];
            @endphp

            <div class="chapter-list">
                @foreach($chapters as $chapter)
                <div class="chapter reveal">
                    <div class="chapter-num">{{ $chapter['num'] }}</div>
                    <div>
                        <h3>{{ $chapter['title'] }}</h3>
                        <p>{{ $chapter['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>

        </div>
    </section>

</main>

@endsection