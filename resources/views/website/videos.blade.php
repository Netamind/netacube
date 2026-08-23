@extends('website.homepage')
@section('content')

<style>
    /* =========================================================
         HELP CENTER — VIDEO TUTORIALS
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

    .video-filters{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin-bottom:34px}
    .video-filters a{
        padding:8px 14px;border:1px solid var(--line);border-radius:999px;
        color:#475467;font-size:11.5px;font-weight:800;background:#fff;transition:.2s;
    }
    .video-filters a:hover,.video-filters a.active{color:var(--blue);border-color:var(--blue-line);background:var(--blue-pale)}

    .video-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px}
    .video-card{
        border:1px solid var(--line);border-radius:18px;background:#fff;overflow:hidden;transition:.25s;
    }
    .video-card:hover{transform:translateY(-5px);box-shadow:var(--shadow);border-color:var(--blue-line)}
    .video-thumb{
        position:relative;
        height:150px;
        display:grid;place-items:center;
        background:linear-gradient(145deg,#0b56b9,#176fe5);
    }
    .video-thumb .play{
        width:46px;height:46px;border-radius:50%;
        background:#fff;color:var(--blue);
        display:grid;place-items:center;
        font-size:15px;font-weight:900;
        box-shadow:0 10px 25px rgba(7,27,53,.25);
    }
    .video-thumb .duration{
        position:absolute;right:10px;bottom:10px;
        padding:3px 8px;border-radius:6px;
        background:rgba(7,27,53,.55);
        color:#fff;font-size:10px;font-weight:800;
    }
    .video-body{padding:18px 20px 20px}
    .video-body .tag{color:var(--blue);font-size:10px;font-weight:900;letter-spacing:.4px;text-transform:uppercase}
    .video-body h3{margin-top:8px;color:var(--navy);font:800 14.5px "Manrope",sans-serif;line-height:1.4}
    .video-body p{margin-top:6px;color:var(--muted);font-size:11.5px;line-height:1.7}

    @media(max-width:1050px){.video-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:600px){.video-grid{grid-template-columns:1fr}}
</style>

<main>

    <section class="page-hero">
        <div class="container">
            <div class="page-hero-inner reveal">
                <div class="breadcrumb"><a href="{{ url('/help-center') }}">Help center</a> / <span>Videos</span></div>
                <div class="hero-label"><i></i>Video tutorials</div>
                <h1>Learn Netacube by watching, not just reading.</h1>
                <p>Short, focused walkthroughs of the features you'll use every day.</p>
            </div>
        </div>
    </section>

    <section class="section soft">
        <div class="container">

            <div class="video-filters reveal">
                <a href="#" class="active">All</a>
                <a href="#getting-started">Getting Started</a>
                <a href="#pos">Point of Sale</a>
                <a href="#inventory">Inventory</a>
                <a href="#employees">Employees</a>
                <a href="#payroll">Payroll</a>
                <a href="#offline">Offline Mode</a>
            </div>

            @php
                $videos = [
                    ['tag' => 'Getting Started', 'title' => 'Setting up your Netacube workspace',   'desc' => 'Create your account, add your first branch and invite your team.', 'time' => '4:12'],
                    ['tag' => 'Getting Started', 'title' => 'A tour of the admin dashboard',          'desc' => 'Where to find everything after your first login.',                     'time' => '3:05'],
                    ['tag' => 'Point of Sale',   'title' => 'Making your first sale',                 'desc' => 'Ring up items, take payment and print a receipt.',                     'time' => '5:40'],
                    ['tag' => 'Point of Sale',   'title' => 'Processing a refund',                    'desc' => 'Handle returns and refunds correctly from the POS.',                   'time' => '2:58'],
                    ['tag' => 'Inventory',       'title' => 'Adding products and setting stock levels','desc' => 'Get your product catalogue and opening stock into Netacube.',          'time' => '6:21'],
                    ['tag' => 'Inventory',       'title' => 'Transferring stock between branches',    'desc' => 'Move stock safely using delivery notes.',                              'time' => '4:47'],
                    ['tag' => 'Employees',       'title' => 'Adding employees and clock-in devices',  'desc' => 'Set up staff accounts and daily attendance.',                          'time' => '3:52'],
                    ['tag' => 'Payroll',         'title' => 'Running your first payroll period',      'desc' => 'From attendance to finalised payslips.',                              'time' => '7:15'],
                    ['tag' => 'Offline Mode',    'title' => 'Selling while offline',                  'desc' => 'What changes on the POS when the internet drops, and how sync works.', 'time' => '3:30'],
                ];
            @endphp

            <div class="video-grid">
                @foreach($videos as $video)
                <a href="#" class="video-card reveal">
                    <div class="video-thumb">
                        <div class="play">▶</div>
                        <span class="duration">{{ $video['time'] }}</span>
                    </div>
                    <div class="video-body">
                        <div class="tag">{{ $video['tag'] }}</div>
                        <h3>{{ $video['title'] }}</h3>
                        <p>{{ $video['desc'] }}</p>
                    </div>
                </a>
                @endforeach
            </div>

        </div>
    </section>

</main>

@endsection