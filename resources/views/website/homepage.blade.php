<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Netacube | Business Management Platform</title>

    <meta name="description"
          content="Netacube brings sales, inventory, purchasing, customers, people and reporting together in one unified business management platform.">

    <link rel="icon" type="image/png" href="{{ asset('images/home/icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root{
            --blue:#176fe5;
            --blue-dark:#0b56bd;
            --blue-deep:#073b88;
            --blue-pale:#eef6ff;
            --blue-line:#d9e9ff;
            --navy:#071b35;
            --text:#344054;
            --muted:#667085;
            --light:#f7faff;
            --line:#e8eef6;
            --white:#fff;
            --green:#12b76a;
            --orange:#f79009;
            --container:1180px;
            --shadow:0 20px 60px rgba(14,43,83,.08);
            --shadow-blue:0 24px 70px rgba(23,111,229,.18);
        }

        *{box-sizing:border-box;margin:0;padding:0}
        html{scroll-behavior:smooth}
        body{
            font-family:"DM Sans",sans-serif;
            color:var(--text);
            background:#fff;
            line-height:1.6;
            overflow-x:hidden;
        }
        a{text-decoration:none;color:inherit}
        img{display:block;max-width:100%}
        button{font:inherit}
        .container{width:min(var(--container),calc(100% - 48px));margin:auto}

        /* =========================
           NAV
        ========================= */
        .nav{
            position:sticky;
            top:0;
            z-index:1000;
            height:76px;
            background:rgba(255,255,255,.92);
            border-bottom:1px solid rgba(232,238,246,.9);
            backdrop-filter:blur(18px);
        }
        .nav-inner{
            height:100%;
            display:flex;
            align-items:center;
            gap:28px;
        }
        .brand{
            width:190px;
            flex:0 0 190px;
            display:flex;
            align-items:center;
            min-height:42px;
        }
        /* blogo.png is the supplied readable Netacube brand mark. */
        .brand img{
            width:166px;
            height:auto;
            max-height:48px;
            object-fit:contain;
            object-position:left center;
        }
        .nav-links{
            flex:1;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:4px;
        }
        .nav-links a{
            position:relative;
            padding:9px 13px;
            border-radius:9px;
            color:#475467;
            font-size:12px;
            font-weight:700;
            transition:.2s;
        }
        .nav-links a.active{
            color:var(--blue);
            font-weight:800;
            background:var(--blue-pale);
        }

        .nav-links a.active:after{
            content:"";
            position:absolute;\n            left:0;\n            right:0;\n            bottom:-10px;\n            height:2px;\n            border-radius:999px;\n            background:var(--blue);
        }

        .nav-links a:hover{
            color:var(--blue);
            background:var(--blue-pale);
        }
        .nav-right{
            display:flex;
            align-items:center;
            gap:9px;
        }
        .sign-in{
            padding:10px 11px;
            color:#344054;
            font-size:12px;
            font-weight:800;
        }
        .button{
            min-height:43px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            padding:0 17px;
            border-radius:10px;
            font-size:12px;
            font-weight:800;
            transition:.22s;
        }
        .button:hover{transform:translateY(-2px)}
        .button-blue{
            color:#fff;
            background:var(--blue);
            box-shadow:0 10px 25px rgba(23,111,229,.2);
        }
        .button-blue:hover{background:var(--blue-dark);box-shadow:0 15px 32px rgba(23,111,229,.27)}
        .button-light{
            color:var(--navy);
            background:#fff;
            border:1px solid var(--line);
        }
        .button-white{color:var(--blue);background:#fff}
        .menu{
            display:none;
            width:40px;height:40px;
            border:1px solid var(--line);
            border-radius:9px;
            background:#fff;
        }
        .menu span{display:block;width:17px;height:2px;margin:4px auto;background:var(--navy);border-radius:5px}

        /* =========================
           HERO — clean, product-first composition
        ========================= */
        .hero{
            position:relative;
            padding:42px 0 56px;
            background:#fff;
            overflow:hidden;
        }
        .hero:before{
            content:"";
            position:absolute;
            width:720px;
            height:720px;
            right:-280px;
            top:40px;
            border-radius:50%;
            background:radial-gradient(circle,rgba(23,111,229,.10) 0%,rgba(23,111,229,.03) 45%,transparent 72%);
            pointer-events:none;
        }
        .hero-panel{
            position:relative;
            display:grid;
            grid-template-columns:minmax(420px,48fr) minmax(380px,52fr);
            align-items:center;
            justify-content:space-between;
            gap:70px;
            min-height:560px;
        }
        .hero-copy{
            position:relative;
            z-index:5;
            padding:20px 0;
        }
        .hero-label{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:7px 11px;
            border:1px solid var(--blue-line);
            border-radius:999px;
            color:var(--blue);
            background:#fff;
            box-shadow:0 6px 20px rgba(16,24,40,.045);
            font-size:9px;
            font-weight:900;
            letter-spacing:.8px;
            text-transform:uppercase;
        }
        .hero-label i{
            width:7px;height:7px;
            border-radius:50%;
            background:var(--green);
            box-shadow:0 0 0 4px rgba(18,183,106,.10);
        }
        .hero h1{
            max-width:560px;
            margin-top:20px;
            color:var(--navy);
            font-family:"Manrope",sans-serif;
            font-size:clamp(46px,4.8vw,66px);
            line-height:1.02;
            letter-spacing:-3px;
            font-weight:800;
        }
        .hero h1 span{display:block;color:var(--blue)}
        .hero-copy p{
            max-width:500px;
            margin-top:18px;
            color:var(--muted);
            font-size:15px;
            line-height:1.75;
        }
        .hero-actions{
            display:flex;
            flex-wrap:wrap;
            gap:12px;
            margin-top:26px;
        }
        .hero-actions .button{min-height:47px}
        .hero-actions .button-light{
            background:#fff;
            color:var(--navy);
            border-color:#dfe7f0;
        }
        .hero-checks{
            display:flex;
            flex-wrap:wrap;
            gap:14px 20px;
            margin-top:22px;
        }
        .hero-check{
            display:flex;
            align-items:center;
            gap:7px;
            color:#667085;
            font-size:10px;
            font-weight:700;
        }
        .hero-check b{
            width:17px;height:17px;
            display:grid;place-items:center;
            border-radius:50%;
            color:#08734c;
            background:#bdf3d9;
            font-size:9px;
        }

        .hero-dashboard{
            position:relative;
            z-index:3;
            min-height:520px;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:0;
        }
        .hero-dashboard:before{
            content:"";
            position:absolute;
            width:560px;
            height:560px;
            right:-40px;
            top:50%;
            transform:translateY(-50%);
            border-radius:50%;
            background:radial-gradient(circle,rgba(23,111,229,.08),transparent 70%);
            pointer-events:none;
        }
        .dashboard-shadow{display:none}
        .dashboard-window{
            position:relative;
            z-index:2;
            width:100%;
            max-width:620px;
            margin:0;
            padding:8px;
            border:1px solid #e3eaf2;
            border-radius:22px;
            background:#fff;
            box-shadow:0 30px 70px rgba(16,55,105,.15),0 10px 24px rgba(16,24,40,.06);
            transition:transform .35s ease,box-shadow .35s ease;
        }
        .dashboard-window:hover{
            transform:translateY(-4px);
            box-shadow:0 40px 90px rgba(16,55,105,.18),0 14px 30px rgba(16,24,40,.07);
        }
        .dashboard-top{
            height:32px;
            display:flex;
            align-items:center;
            gap:5px;
            padding-left:12px;
            border-radius:14px 14px 0 0;
            background:#f5f7fa;
        }
        .dashboard-top i{width:7px;height:7px;border-radius:50%;background:#d0d5dd}
        .dashboard-window img{width:100%;border-radius:0 0 14px 14px}

        .stat-float{
            position:absolute;
            z-index:6;
            min-width:180px;
            padding:14px 16px;
            border:1px solid #e4ebf3;
            border-radius:14px;
            background:#fff;
            box-shadow:0 18px 42px rgba(16,24,40,.12);
        }
        .stat-float.top{right:-24px;top:18px}
        .stat-float.bottom{left:-24px;bottom:18px}
        .stat-label{color:#98a2b3;font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:.6px}
        .stat-value{margin-top:4px;color:var(--navy);font:800 18px "Manrope",sans-serif}
        .stat-sub{margin-top:2px;color:var(--green);font-size:9px;font-weight:800}

        /* =========================
           QUICK MODULE RAIL
        ========================= */
        .module-rail{padding:28px 0 82px}
        .module-shell{
            display:grid;
            grid-template-columns:235px 1fr;
            min-height:92px;
            border:1px solid var(--line);
            border-radius:18px;
            background:#fff;
            box-shadow:0 12px 35px rgba(16,24,40,.05);
        }
        .module-title{
            display:flex;
            align-items:center;
            padding:18px 24px;
            border-right:1px solid var(--line);
        }
        .module-title small{
            display:block;
            color:var(--blue);
            font-size:8px;
            font-weight:900;
            letter-spacing:.9px;
            text-transform:uppercase;
        }
        .module-title strong{
            display:block;
            margin-top:4px;
            color:var(--navy);
            font:800 15px/1.3 "Manrope",sans-serif;
        }
        .module-items{
            display:grid;
            grid-template-columns:repeat(6,1fr);
            align-items:stretch;
        }
        .module-item{
            min-height:91px;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            gap:6px;
            border-right:1px solid #f0f3f7;
            color:#475467;
            font-size:10px;
            font-weight:800;
            text-align:center;
            transition:.2s;
        }
        .module-item:last-child{border-right:0}
        .module-item:hover{color:var(--blue);background:var(--blue-pale)}
        .module-icon{
            width:31px;height:31px;
            display:grid;place-items:center;
            border-radius:9px;
            color:var(--blue);
            background:var(--blue-pale);
            font-size:12px;
            font-weight:900;
        }

        /* =========================
           SECTION UTILITIES
        ========================= */
        .section{padding:105px 0}
        .soft{background:var(--light);border-block:1px solid #edf2f7}
        .section-intro{max-width:690px;margin-bottom:50px}
        .section-intro.center{margin-inline:auto;text-align:center}
        .kicker{
            color:var(--blue);
            font-size:9px;
            font-weight:900;
            letter-spacing:1px;
            text-transform:uppercase;
        }
        .section-intro h2{
            margin-top:10px;
            color:var(--navy);
            font:800 clamp(32px,4vw,48px)/1.1 "Manrope",sans-serif;
            letter-spacing:-2px;
        }
        .section-intro p{
            margin-top:14px;
            color:var(--muted);
            font-size:14px;
            line-height:1.8;
        }

        /* =========================
           BENTO CORE SYSTEM
        ========================= */
        .bento{
            display:grid;
            grid-template-columns:repeat(4,minmax(0,1fr));
            grid-auto-rows:minmax(205px,auto);
            gap:18px;
            align-items:stretch;
        }
        .bento-card{
            position:relative;
            overflow:hidden;
            padding:28px;
            border:1px solid var(--line);
            border-radius:20px;
            background:#fff;
            box-shadow:0 8px 25px rgba(16,24,40,.035);
            transition:.25s;
        }
        .bento-card:hover{transform:translateY(-5px);box-shadow:var(--shadow);border-color:var(--blue-line)}
        .bento-card.large{
            grid-row:span 2;
            color:#fff;
            background:linear-gradient(145deg,#0b5ec8,#176fe5);
            border:0;
        }
        .bento-card.wide{grid-column:span 1}
        .bento-card:after{
            content:"";
            position:absolute;
            width:170px;height:170px;
            right:-95px;bottom:-95px;
            border-radius:50%;
            background:rgba(23,111,229,.055);
        }
        .bento-card.large:after{
            width:300px;height:300px;
            right:-170px;bottom:-180px;
            border:1px solid rgba(255,255,255,.1);
            background:transparent;
        }
        .icon{
            width:44px;height:44px;
            display:grid;place-items:center;
            border-radius:12px;
            color:var(--blue);
            background:var(--blue-pale);
            font-weight:900;
        }
        .large .icon{color:var(--blue);background:#fff}
        .bento-card h3{
            position:relative;z-index:2;
            margin-top:18px;
            color:var(--navy);
            font:800 18px "Manrope",sans-serif;
        }
        .large h3{color:#fff;font-size:23px}
        .bento-card p{
            position:relative;z-index:2;
            max-width:450px;
            margin-top:8px;
            color:var(--muted);
            font-size:12px;
            line-height:1.7;
        }
        .large p{color:rgba(255,255,255,.74);font-size:13px}
        .card-link{
            position:absolute;
            z-index:3;
            left:28px;bottom:26px;
            color:var(--blue);
            font-size:10px;
            font-weight:900;
        }
        .large .card-link{color:#fff}

        /* =========================
           INVENTORY VISUAL
        ========================= */
        .split{
            display:grid;
            grid-template-columns:.85fr 1.15fr;
            gap:75px;
            align-items:center;
        }
        .split.reverse{grid-template-columns:1.15fr .85fr}
        .split.reverse .copy{order:2}
        .split.reverse .visual{order:1}
        .copy h2{
            margin-top:10px;
            color:var(--navy);
            font:800 clamp(32px,4vw,46px)/1.1 "Manrope",sans-serif;
            letter-spacing:-1.8px;
        }
        .copy>p{margin-top:15px;color:var(--muted);font-size:14px;line-height:1.8}
        .checks{display:grid;gap:11px;margin-top:24px}
        .check-row{display:flex;align-items:center;gap:10px;color:#475467;font-size:12px;font-weight:700}
        .check-row i{
            width:22px;height:22px;
            display:grid;place-items:center;
            flex:0 0 22px;
            border-radius:7px;
            color:var(--blue);
            background:var(--blue-pale);
            font-style:normal;
            font-size:9px;
            font-weight:900;
        }
        .visual-card{
            position:relative;
            padding:9px;
            border:1px solid var(--line);
            border-radius:22px;
            background:#fff;
            box-shadow:var(--shadow);
        }
        .visual-card img{border-radius:15px;width:100%}

        .inventory-board{
            position:relative;
            padding:26px;
            border-radius:22px;
            background:#fff;
            border:1px solid var(--line);
            box-shadow:var(--shadow);
        }
        .board-head{display:flex;align-items:center;justify-content:space-between}
        .board-title{font:800 15px "Manrope";color:var(--navy)}
        .board-tag{padding:6px 9px;border-radius:999px;background:#ecfdf3;color:#087443;font-size:8px;font-weight:900}
        .stock-list{display:grid;gap:10px;margin-top:22px}
        .stock-row{
            display:grid;
            grid-template-columns:1.3fr .6fr .9fr;
            gap:12px;
            align-items:center;
            padding:12px 13px;
            border:1px solid #edf1f5;
            border-radius:12px;
        }
        .stock-name{color:var(--navy);font-size:10px;font-weight:800}
        .stock-bar{height:6px;border-radius:10px;background:#eaf0f7;overflow:hidden}
        .stock-bar span{display:block;height:100%;border-radius:10px;background:var(--blue)}
        .stock-num{text-align:right;color:#667085;font-size:9px;font-weight:800}

        /* =========================
           PEOPLE / HR
        ========================= */
        .people-grid{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:18px;
            align-items:stretch;
        }
        .people-card{
            min-height:330px;
            height:100%;
            padding:32px;
            border-radius:22px;
            border:1px solid var(--line);
            background:#fff;
        }
        .people-card.blue{
            color:#fff;
            border:0;
            background:linear-gradient(145deg,#0b56b9,#176fe5);
            box-shadow:var(--shadow-blue);
        }
        .people-card .mini-kicker{
            color:var(--blue);
            font-size:8px;
            font-weight:900;
            letter-spacing:1px;
            text-transform:uppercase;
        }
        .people-card.blue .mini-kicker{color:#b8d8ff}
        .people-card h3{
            max-width:490px;
            margin-top:10px;
            color:var(--navy);
            font:800 25px/1.2 "Manrope",sans-serif;
            letter-spacing:-1px;
        }
        .people-card.blue h3{color:#fff}
        .people-card p{
            max-width:500px;
            margin-top:11px;
            color:var(--muted);
            font-size:12px;
            line-height:1.75;
        }
        .people-card.blue p{color:rgba(255,255,255,.74)}
        .pills{display:flex;flex-wrap:wrap;gap:8px;margin-top:23px}
        .pill{
            padding:7px 10px;
            border-radius:999px;
            background:var(--blue-pale);
            color:#475467;
            font-size:9px;
            font-weight:800;
        }
        .blue .pill{color:#fff;background:rgba(255,255,255,.11)}

        /* =========================
           REPORTING / METRICS
        ========================= */
        .report-layout{
            display:grid;
            grid-template-columns:.75fr 1.25fr;
            gap:50px;
            align-items:center;
        }
        .metrics-board{
            padding:22px;
            border-radius:23px;
            background:#fff;
            border:1px solid var(--line);
            box-shadow:var(--shadow);
        }
        .metric-top{
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding-bottom:17px;
            border-bottom:1px solid #edf1f5;
        }
        .metric-top strong{font:800 15px "Manrope";color:var(--navy)}
        .metric-top span{font-size:8px;font-weight:900;color:#087443;background:#ecfdf3;padding:6px 8px;border-radius:999px}
        .metric-grid{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:10px;
            margin-top:14px;
        }
        .metric{
            padding:15px;
            border-radius:13px;
            background:#f8fafc;
            border:1px solid #eef2f6;
        }
        .metric small{color:#98a2b3;font-size:8px;font-weight:900;text-transform:uppercase}
        .metric strong{display:block;margin-top:4px;color:var(--navy);font:800 20px "Manrope"}
        .metric em{display:block;margin-top:3px;color:var(--green);font-size:8px;font-style:normal;font-weight:800}
        .chart{
            position:relative;
            height:145px;
            margin-top:14px;
            padding:16px;
            border-radius:14px;
            background:#f8fafc;
            overflow:hidden;
        }
        .chart-grid{position:absolute;inset:20px 15px;display:grid;grid-template-rows:repeat(4,1fr)}
        .chart-grid i{border-top:1px dashed #dce5ef}
        .bars{
            position:absolute;
            left:20px;right:20px;bottom:18px;top:20px;
            display:flex;
            align-items:flex-end;
            justify-content:space-around;
            gap:8px;
        }
        .bars i{
            width:100%;
            max-width:25px;
            border-radius:5px 5px 2px 2px;
            background:linear-gradient(#3b91f2,#176fe5);
        }

        /* =========================
           BUSINESS TYPES
        ========================= */
        .business-types{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:18px;
            align-items:stretch;
        }
        .business{
            min-height:270px;
            height:100%;
            display:flex;
            flex-direction:column;
            padding:28px;
            border:1px solid var(--line);
            border-radius:21px;
            background:#fff;
            transition:.25s;
        }
        .business:hover{transform:translateY(-5px);box-shadow:var(--shadow);border-color:var(--blue-line)}
        .business-number{color:#c6d3e2;font:800 12px "Manrope"}
        .business-icon{
            width:48px;height:48px;
            display:grid;place-items:center;
            margin-top:20px;
            border-radius:14px;
            color:var(--blue);
            background:var(--blue-pale);
            font-weight:900;
        }
        .business h3{margin-top:18px;color:var(--navy);font:800 18px "Manrope"}
        .business p{margin-top:8px;color:var(--muted);font-size:12px;line-height:1.7}

        /* =========================
           CTA
        ========================= */
        .cta{padding:90px 0}
        .cta-box{
            position:relative;
            overflow:hidden;
            min-height:350px;
            display:flex;
            align-items:center;
            padding:55px 62px;
            border-radius:28px;
            background:linear-gradient(120deg,#073f8f,#176fe5);
            box-shadow:var(--shadow-blue);
        }
        .cta-box:before{
            content:"";
            position:absolute;
            width:500px;height:500px;
            right:-210px;top:-250px;
            border:1px solid rgba(255,255,255,.13);
            border-radius:50%;
            box-shadow:0 0 0 65px rgba(255,255,255,.035);
        }
        .cta-content{position:relative;z-index:2;max-width:700px}
        .cta-content small{color:#b8d8ff;font-size:9px;font-weight:900;letter-spacing:1px;text-transform:uppercase}
        .cta h2{margin-top:9px;color:#fff;font:800 clamp(33px,4vw,49px)/1.1 "Manrope";letter-spacing:-1.8px}
        .cta p{max-width:620px;margin-top:13px;color:rgba(255,255,255,.75);font-size:14px;line-height:1.8}
        .cta-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:25px}
        .cta .button-light{background:rgba(255,255,255,.1);color:#fff;border-color:rgba(255,255,255,.2)}

        /* =========================
           FOOTER
        ========================= */
        footer{
            position:relative;
            background:#061629;
            color:#fff;
            overflow:hidden;
        }
        footer:before{
            content:"";
            position:absolute;
            width:520px;
            height:520px;
            right:-270px;
            top:-250px;
            border:1px solid rgba(255,255,255,.045);
            border-radius:50%;
            box-shadow:
                0 0 0 70px rgba(255,255,255,.012),
                0 0 0 140px rgba(255,255,255,.008);
            pointer-events:none;
        }
        .footer-main{
            position:relative;
            z-index:1;
            padding:88px 0 70px;
        }
        .footer-top{
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            gap:40px;
            padding-bottom:48px;
            margin-bottom:48px;
            border-bottom:1px solid rgba(255,255,255,.09);
        }
        .footer-heading{
            max-width:650px;
        }
        .footer-eyebrow{
            color:#8dbdf8;
            font-size:9px;
            font-weight:900;
            letter-spacing:1.1px;
            text-transform:uppercase;
        }
        .footer-heading h2{
            margin-top:10px;
            color:#fff;
            font:800 clamp(27px,3.4vw,40px)/1.12 "Manrope",sans-serif;
            letter-spacing:-1.5px;
        }
        .footer-heading p{
            max-width:620px;
            margin-top:12px;
            color:rgba(255,255,255,.55);
            font-size:12px;
            line-height:1.8;
        }
        .footer-top-action{
            flex:0 0 auto;
        }
        .footer-grid{
            display:grid;
            grid-template-columns:2fr repeat(4,1fr);
            gap:42px;
        }
        .footer-brand{
            display:inline-flex;
            align-items:center;
        }
        .footer-brand img{width:160px;height:auto}
        .footer-desc{
            max-width:365px;
            margin-top:20px;
            color:rgba(255,255,255,.5);
            font-size:11px;
            line-height:1.85;
        }
        .footer-meta{
            display:flex;
            flex-wrap:wrap;
            gap:8px;
            margin-top:20px;
        }
        .footer-meta span{
            padding:7px 10px;
            border:1px solid rgba(255,255,255,.09);
            border-radius:999px;
            color:rgba(255,255,255,.5);
            font-size:8px;
            font-weight:800;
        }
        .footer-col h4{
            margin-bottom:19px;
            color:#fff;
            font-size:9px;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.9px;
        }
        .footer-col a{
            display:block;
            width:max-content;
            margin:11px 0;
            color:rgba(255,255,255,.5);
            font-size:10px;
            transition:.2s;
        }
        .footer-col a:hover{
            color:#fff;
            transform:translateX(2px);
        }
        .footer-bottom{
            position:relative;
            z-index:1;
            border-top:1px solid rgba(255,255,255,.09);
            padding:23px 0;
        }
        .footer-bottom-inner{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:25px;
        }
        .copyright,.footer-bottom a{
            color:rgba(255,255,255,.36);
            font-size:9px;
        }
        .footer-links{
            display:flex;
            gap:20px;
            flex-wrap:wrap;
        }
        .footer-bottom-left{
            display:flex;
            align-items:center;
            gap:16px;
            flex-wrap:wrap;
        }
        .legal-links{
            display:flex;
            gap:14px;
        }

        /* =========================
           ANIMATION
        ========================= */
        .reveal{opacity:0;transform:translateY(20px);transition:.7s ease}
        .reveal.show{opacity:1;transform:none}

        /* =========================
           RESPONSIVE
        ========================= */
        @media(max-width:1050px){
            .hero{padding:36px 0 52px}
            .hero-panel{grid-template-columns:1fr;gap:40px;min-height:auto}
            .hero-copy{text-align:center;padding:10px 20px 0}
            .hero-copy h1,.hero-copy p{margin-left:auto;margin-right:auto}
            .hero-label{margin:0 auto}
            .hero-actions,.hero-checks{justify-content:center}
            .hero-dashboard{min-height:auto;padding:10px 20px 30px}
            .dashboard-window{width:100%;max-width:700px;margin:0}
            .stat-float.top{top:-10px;right:0}
            .stat-float.bottom{bottom:-10px;left:0}
            .module-shell{grid-template-columns:1fr}
            .module-title{border-right:0;border-bottom:1px solid var(--line)}
            .bento{grid-template-columns:repeat(2,minmax(0,1fr))}
            .bento-card.large{grid-row:auto}
            .bento-card.wide{grid-column:span 1}
            .split,.split.reverse,.report-layout{grid-template-columns:1fr;gap:45px}
            .split.reverse .copy,.split.reverse .visual{order:initial}
        }
        @media(max-width:800px){
            .nav-links,.nav-right{display:none}
            .menu{display:block;margin-left:auto}
            .nav.open{height:auto}
            .nav.open .nav-inner{flex-wrap:wrap;padding:12px 0}
            .nav.open .nav-links{
                display:flex;
                order:3;
                width:100%;
                flex-direction:column;
                align-items:stretch;
            }
            .nav.open .nav-right{display:flex;order:4;width:100%;padding-bottom:10px}
            .nav.open .nav-links a{width:100%}
            .module-items{grid-template-columns:repeat(3,1fr)}
            .module-item{border-bottom:1px solid #f0f3f7}
            .people-grid,.business-types{grid-template-columns:1fr}
            .footer-top{align-items:flex-start;flex-direction:column}
            .footer-grid{grid-template-columns:1.5fr 1fr 1fr}
            .footer-grid>:first-child{grid-column:1/-1}
        }
        @media(max-width:600px){
            .container{width:calc(100% - 32px)}
            .nav-inner{width:calc(100% - 32px)}
            .brand{width:150px;flex-basis:150px}
            .brand img{width:145px}
            .hero{padding:28px 0 44px}
            .hero-copy{padding:0}
            .hero h1{font-size:40px;letter-spacing:-2px}
            .hero-dashboard{min-height:auto;padding:0 0 24px}
            .dashboard-window{width:100%;max-width:100%;margin:0}
            .stat-float{position:relative;min-width:100%;left:auto;right:auto;top:auto;bottom:auto;margin-top:12px}
            .module-rail{padding-bottom:50px}
            .module-items{grid-template-columns:repeat(2,1fr)}
            .module-item{min-height:75px}
            .bento{grid-template-columns:1fr}
            .bento-card.wide{grid-column:auto}
            .section{padding:75px 0}
            .metric-grid{grid-template-columns:1fr}
            .stock-row{grid-template-columns:1fr .65fr}
            .stock-bar{display:none}
            .cta{padding:65px 0}
            .cta-box{padding:43px 25px}
            .footer-main{padding:65px 0 50px}
            .footer-grid{grid-template-columns:1fr 1fr;gap:32px 20px}
            .footer-grid>:first-child{grid-column:1/-1}
            .footer-bottom-inner{flex-direction:column;align-items:flex-start}
        }
        @media(prefers-reduced-motion:reduce){
            html{scroll-behavior:auto}
            .reveal{opacity:1;transform:none;transition:none}
            *{scroll-behavior:auto!important}
        }
    </style>
</head>

<body>

<header class="nav" id="siteNav">
    <div class="container nav-inner">

        <a href="{{ url('/') }}" class="brand" aria-label="Netacube home">
            <img
                src="{{ asset('images/home/blogo.png') }}"
                alt="Netacube"
            >
        </a>

        <nav class="nav-links" aria-label="Main navigation">
            <a href="{{ url('/') }}" class="{{ request()->is('/') ? 'active' : '' }}">Home</a>
            <a href="{{ url('/about') }}" class="{{ request()->is('about') ? 'active' : '' }}">About</a>
            <a href="{{ url('/features') }}" class="{{ request()->is('features') ? 'active' : '' }}">Features</a>
            <a href="{{ url('/pricing') }}" class="{{ request()->is('pricing') ? 'active' : '' }}">Pricing</a>
      
            <a href="{{ url('/contact') }}" class="{{ request()->is('contact') ? 'active' : '' }}">Contact</a>
            <a href="{{ url('/help-center') }}" class="{{ request()->is('help-center') ? 'active' : '' }}">Support</a>
        </nav>

        <div class="nav-right">
            <a href="{{ url('/login') }}" class="sign-in">Sign in</a>
            <a href="{{ url('/get-started') }}" class="button button-blue">Get started <span>→</span></a>
        </div>

        <button class="menu" id="menuButton" aria-label="Open navigation">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>
<div>@yield('content',View::make('website.homedefault'))</div>
<footer>
    <div class="footer-main">
        <div class="container">

            <div class="footer-top">
                <div class="footer-heading">
                    <div class="footer-eyebrow">One connected platform for your business</div>
                    <h2>Manage operations. Monitor performance. Control every location.</h2>
                    <p>
                        Bring everyday operatioAns into one organised system so
                        your team can move faster, while management always has
                        access to current business information.
                    </p>
                </div>

                <div class="footer-top-action">
                    <a href="{{ url('/get-started') }}" class="button button-white">
                        Get started →
                    </a>
                </div>
            </div>

            <div class="footer-grid">

                <div>
                    <div class="footer-brand">
                        <img
                            src="{{ asset('images/home/blogo.png') }}"
                            alt="#"
                        >
                    </div>

                    <p class="footer-desc">
                        Netacube helps businesses organise sales, products,
                        inventory, customers, employees and business
                        information in one connected platform.
                    </p>

                    <div class="footer-meta">
                        <span>Sales</span>
                        <span>Inventory</span>
                        <span>People</span>
                        <span>Reports</span>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Platform</h4>
                    <a href="{{ url('/features') }}">Overview</a>
                    <a href="{{ url('/features') }}">Sales & POS</a>
                    <a href="{{ url('/features') }}">Inventory</a>
                    <a href="{{ url('/features') }}">Products</a>
                    <a href="{{ url('/features') }}">Customers</a>
                    <a href="{{ url('/features') }}">Reports</a>
                </div>

                <div class="footer-col">
                    <h4>Business</h4>
                    <a href="{{ url('/features') }}">Retail & stores</a>
                    <a href="{{ url('/features') }}">Wholesale</a>
                    <a href="{{ url('/features') }}">Growing businesses</a>
                    <a href="{{ url('/features') }}">Multi-location</a>
                </div>

                <div class="footer-col">
                    <h4>People</h4>
                    <a href="{{ url('/features') }}">Human resources</a>
                    <a href="{{ url('/features') }}">Employees</a>
                    <a href="{{ url('/features') }}">Attendance</a>
                    <a href="{{ url('/features') }}">Leave management</a>
                    <a href="{{ url('/features') }}">Payroll</a>
                </div>

                <div class="footer-col">
                    <h4>Company</h4>
                    <a href="{{ url('/about') }}">About Netacube</a>
                    <a href="{{ url('/pricing') }}" class="{{ request()->is('pricing') ? 'active' : '' }}">Pricing</a>
                    <a href="{{ url('/contact') }}">Contact us</a>
                    <a href="{{ url('/help-center') }}">Help center</a>
                    <a href="{{ url('/login') }}">Sign in</a>
                </div>

            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container footer-bottom-inner">
            <div class="footer-bottom-left">
                <div class="copyright">
                    © {{ date('Y') }} Netacube. All rights reserved.
                </div>

                <div class="legal-links">
                    <a href="{{ url('/terms') }}" class="{{ request()->is('terms') ? 'active' : '' }}">Terms</a>
                    <a href="{{ url('/privacy-policy') }}" class="{{ request()->is('privacy-policy') ? 'active' : '' }}">Privacy Policy</a>
                </div>
            </div>

            <div class="footer-links">
                <a href="{{ url('/') }}" class="{{ request()->is('/') ? 'active' : '' }}">Home</a>
                <a href="{{ url('/about') }}" class="{{ request()->is('about') ? 'active' : '' }}">About</a>
                <a href="{{ url('/features') }}" class="{{ request()->is('features') ? 'active' : '' }}">Features</a>
                <a href="{{ url('/pricing') }}" class="{{ request()->is('pricing') ? 'active' : '' }}">Pricing</a>
                <a href="{{ url('/contact') }}" class="{{ request()->is('contact') ? 'active' : '' }}">Contact</a>
                <a href="{{ url('/help-center') }}" class="{{ request()->is('help-center') ? 'active' : '' }}">Help Center</a>
            </div>
        </div>
    </div>
</footer>

<script>
(function(){
    const nav = document.getElementById('siteNav');
    const button = document.getElementById('menuButton');

    if(button && nav){
        button.addEventListener('click', function(){
            nav.classList.toggle('open');
        });

        nav.querySelectorAll('a').forEach(function(link){
            link.addEventListener('click', function(){
                nav.classList.remove('open');
            });
        });
    }

    const elements = document.querySelectorAll('.reveal');

    if('IntersectionObserver' in window){
        const observer = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(entry.isIntersecting){
                    entry.target.classList.add('show');
                    observer.unobserve(entry.target);
                }
            });
        }, {threshold:.08});

        elements.forEach(function(el){ observer.observe(el); });
    }else{
        elements.forEach(function(el){ el.classList.add('show'); });
    }
})();
</script>

</body>
</html>