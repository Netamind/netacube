@extends('website.homepage')
@section('content')

<style>
    /* =========================================================
         LEGAL PAGE (Terms / Privacy) — shares tokens from
         website.homepage (var(--blue), .section, .kicker, .reveal)
    ========================================================== */

    .legal-hero{
        position:relative;
        padding:60px 0 50px;
        background:#fff;
        overflow:hidden;
    }
    .legal-hero:before{
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
    .legal-hero-inner{
        position:relative;
        z-index:2;
        max-width:700px;
        margin:0 auto;
        text-align:center;
    }
    .legal-hero .hero-label{margin:0 auto}
    .legal-hero h1{
        margin-top:20px;
        color:var(--navy);
        font-family:"Manrope",sans-serif;
        font-size:clamp(32px,4vw,46px);
        line-height:1.14;
        letter-spacing:-2px;
        font-weight:800;
    }
    .legal-hero p{
        max-width:560px;
        margin:14px auto 0;
        color:var(--muted);
        font-size:13px;
    }

    .legal-shell{
        display:grid;
        grid-template-columns:250px minmax(0,1fr);
        gap:48px;
        align-items:start;
    }
    .legal-nav{
        position:sticky;
        top:100px;
        padding:22px;
        border:1px solid var(--line);
        border-radius:16px;
        background:#fff;
    }
    .legal-nav span{
        display:block;
        margin-bottom:10px;
        color:var(--muted);
        font-size:10px;
        font-weight:800;
        letter-spacing:.6px;
        text-transform:uppercase;
    }
    .legal-nav a{
        display:block;
        padding:8px 10px;
        margin:2px 0;
        border-radius:8px;
        color:#475467;
        font-size:12px;
        font-weight:700;
        transition:.2s;
    }
    .legal-nav a:hover{color:var(--blue);background:var(--blue-pale)}

    .legal-doc{
        padding:36px 40px;
        border:1px solid var(--line);
        border-radius:20px;
        background:#fff;
        box-shadow:0 12px 35px rgba(16,24,40,.04);
    }
    .legal-doc .updated{
        display:inline-block;
        margin-bottom:26px;
        padding:6px 12px;
        border-radius:999px;
        background:var(--blue-pale);
        color:var(--blue);
        font-size:10.5px;
        font-weight:800;
    }
    .legal-doc section{
        padding:22px 0;
        border-top:1px solid var(--line);
    }
    .legal-doc section:first-of-type{border-top:0;padding-top:0}
    .legal-doc h2{
        color:var(--navy);
        font:800 17px "Manrope",sans-serif;
        letter-spacing:-.3px;
        scroll-margin-top:100px;
    }
    .legal-doc h2 small{
        display:inline-block;
        margin-right:8px;
        color:#c6d3e2;
        font:800 13px "Manrope",sans-serif;
    }
    .legal-doc p{
        margin-top:10px;
        color:var(--text);
        font-size:13px;
        line-height:1.85;
    }
    .legal-doc ul{
        margin:12px 0 0 18px;
        color:var(--text);
        font-size:13px;
        line-height:1.85;
    }
    .legal-doc li{margin-bottom:4px}
    .legal-doc a.inline-link{color:var(--blue);font-weight:700}

    @media(max-width:900px){
        .legal-shell{grid-template-columns:1fr}
        .legal-nav{position:static;top:auto}
        .legal-doc{padding:28px 22px}
    }
</style>

<main>

    <section class="legal-hero">
        <div class="container">
            <div class="legal-hero-inner reveal">
                <div class="hero-label">
                    <i></i>
                    Legal
                </div>
                <h1>Privacy Policy</h1>
                <p>How we collect, use and protect information across Netacube.</p>
            </div>
        </div>
    </section>

    <section class="section soft">
        <div class="container">
            <div class="legal-shell">

                <aside class="legal-nav reveal">
                    <span>On this page</span>
                    <a href="#collect">1. Information we collect</a>
                    <a href="#use">2. How we use information</a>
                    <a href="#tenant-data">3. Tenant data</a>
                    <a href="#sharing">4. How we share information</a>
                    <a href="#security">5. Data storage &amp; security</a>
                    <a href="#retention">6. Data retention</a>
                    <a href="#cookies">7. Cookies</a>
                    <a href="#rights">8. Your rights</a>
                    <a href="#children">9. Children's privacy</a>
                    <a href="#changes">10. Changes to this policy</a>
                    <a href="#contact">11. Contact</a>
                </aside>

                <article class="legal-doc reveal">
                    <div class="updated">Last updated: {{ now()->format('F j, Y') }}</div>

                    <section id="collect">
                        <h2><small>1.</small>Information we collect</h2>
                        <p>We collect information in the following ways:</p>
                        <ul>
                            <li><strong>Account information</strong> — full name, email, phone number and business name provided when you register.</li>
                            <li><strong>Usage information</strong> — how you interact with the Service, including pages visited and features used.</li>
                            <li><strong>Device information</strong> — browser type, IP address and general location inferred from it.</li>
                            <li><strong>Tenant data</strong> — sales, inventory, employee and customer records you or your team enter into your workspace.</li>
                        </ul>
                    </section>

                    <section id="use">
                        <h2><small>2.</small>How we use information</h2>
                        <p>We use collected information to:</p>
                        <ul>
                            <li>Provide, operate and maintain the Service, including your dedicated tenant workspace.</li>
                            <li>Process subscription billing and send related account communications.</li>
                            <li>Respond to support requests and improve platform reliability.</li>
                            <li>Detect, prevent and investigate fraud, abuse and security incidents.</li>
                        </ul>
                    </section>

                    <section id="tenant-data">
                        <h2><small>3.</small>Tenant data</h2>
                        <p>
                            Data you enter into your Netacube workspace (sales, stock, employee
                            and customer records) remains yours. It is logically separated from
                            other tenants and is accessed by our team only to provide support,
                            maintain the Service, or where required by law.
                        </p>
                    </section>

                    <section id="sharing">
                        <h2><small>4.</small>How we share information</h2>
                        <p>
                            We do not sell your information. We may share information with
                            infrastructure and payment providers who help us operate the Service,
                            each bound by confidentiality obligations, or where disclosure is
                            required to comply with law or protect our legal rights.
                        </p>
                    </section>

                    <section id="security">
                        <h2><small>5.</small>Data storage &amp; security</h2>
                        <p>
                            We apply technical and organisational measures — including access
                            controls, encryption in transit, and tenant-scoped database isolation
                            — to protect information against unauthorised access, loss or misuse.
                            No method of transmission or storage is completely secure, and we
                            cannot guarantee absolute security.
                        </p>
                    </section>

                    <section id="retention">
                        <h2><small>6.</small>Data retention</h2>
                        <p>
                            We retain account and tenant data for as long as your subscription is
                            active and for a reasonable period afterward to allow data export,
                            unless a longer retention period is required by law.
                        </p>
                    </section>

                    <section id="cookies">
                        <h2><small>7.</small>Cookies</h2>
                        <p>
                            We use essential cookies to keep you signed in and remember basic
                            preferences. We do not use cookies for third-party advertising.
                        </p>
                    </section>

                    <section id="rights">
                        <h2><small>8.</small>Your rights</h2>
                        <p>
                            Depending on your location, you may have the right to access, correct,
                            or request deletion of your personal information. To exercise these
                            rights, contact us through the <a href="{{ url('/contact') }}" class="inline-link">contact page</a>.
                        </p>
                    </section>

                    <section id="children">
                        <h2><small>9.</small>Children's privacy</h2>
                        <p>
                            Netacube is intended for business use and is not directed at
                            individuals under the age of 18. We do not knowingly collect
                            information from children.
                        </p>
                    </section>

                    <section id="changes">
                        <h2><small>10.</small>Changes to this policy</h2>
                        <p>
                            We may update this Privacy Policy periodically. Material changes will
                            be communicated by email or in-app notice before they take effect.
                        </p>
                    </section>

                    <section id="contact">
                        <h2><small>11.</small>Contact</h2>
                        <p>
                            For privacy-related questions, reach our team via the
                            <a href="{{ url('/contact') }}" class="inline-link">contact page</a>.
                        </p>
                    </section>
                </article>

            </div>
        </div>
    </section>

</main>

@endsection