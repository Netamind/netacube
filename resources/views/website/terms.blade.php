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

    /* ---- Layout: side nav + document ---- */
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
                <h1>Terms of Service</h1>
                <p>The terms that govern your use of Netacube. Please read them carefully.</p>
            </div>
        </div>
    </section>

    <section class="section soft">
        <div class="container">
            <div class="legal-shell">

                <aside class="legal-nav reveal">
                    <span>On this page</span>
                    <a href="#acceptance">1. Acceptance of terms</a>
                    <a href="#description">2. Description of service</a>
                    <a href="#accounts">3. Accounts &amp; registration</a>
                    <a href="#subscriptions">4. Subscriptions &amp; billing</a>
                    <a href="#acceptable-use">5. Acceptable use</a>
                    <a href="#data-ownership">6. Your data</a>
                    <a href="#ip">7. Intellectual property</a>
                    <a href="#termination">8. Suspension &amp; termination</a>
                    <a href="#liability">9. Disclaimers &amp; liability</a>
                    <a href="#changes">10. Changes to these terms</a>
                    <a href="#contact">11. Contact</a>
                </aside>

                <article class="legal-doc reveal">
                    <div class="updated">Last updated: {{ now()->format('F j, Y') }}</div>

                    <section id="acceptance">
                        <h2><small>1.</small>Acceptance of terms</h2>
                        <p>
                            By accessing or using Netacube (the "Service"), operated by Netamind
                            Technology ("we", "us", "our"), you agree to be bound by these Terms
                            of Service. If you are registering an account on behalf of a business,
                            you confirm you have authority to bind that business to these terms.
                            If you do not agree, you may not use the Service.
                        </p>
                    </section>

                    <section id="description">
                        <h2><small>2.</small>Description of service</h2>
                        <p>
                            Netacube is a multi-tenant business management platform providing
                            point of sale, inventory, purchasing, customer, employee and reporting
                            tools. Features available to your account depend on your selected
                            subscription plan and may change as the Service is improved.
                        </p>
                    </section>

                    <section id="accounts">
                        <h2><small>3.</small>Accounts &amp; registration</h2>
                        <p>
                            When you register, you agree to provide accurate business and contact
                            information and to keep your login credentials confidential. You are
                            responsible for all activity that occurs under your account and for
                            the accounts of employees you create within your tenant workspace.
                        </p>
                    </section>

                    <section id="subscriptions">
                        <h2><small>4.</small>Subscriptions &amp; billing</h2>
                        <p>Access to Netacube is provided on a subscription basis. By subscribing, you agree that:</p>
                        <ul>
                            <li>Fees are billed in advance for the plan and billing cycle you select.</li>
                            <li>Prices may change with advance notice before your next billing cycle.</li>
                            <li>Failure to pay may result in suspension of access to your workspace.</li>
                            <li>You may cancel at any time; cancellation takes effect at the end of the current billing period.</li>
                        </ul>
                    </section>

                    <section id="acceptable-use">
                        <h2><small>5.</small>Acceptable use</h2>
                        <p>You agree not to:</p>
                        <ul>
                            <li>Use the Service for any unlawful purpose or in violation of any applicable regulation.</li>
                            <li>Attempt to gain unauthorized access to another tenant's data or to the Service's infrastructure.</li>
                            <li>Reverse engineer, resell, or white-label the Service without our written consent.</li>
                            <li>Upload content that infringes the rights of any third party.</li>
                        </ul>
                    </section>

                    <section id="data-ownership">
                        <h2><small>6.</small>Your data</h2>
                        <p>
                            You retain ownership of all business data you enter into Netacube
                            ("Tenant Data"), including sales, inventory, employee and customer
                            records. We process Tenant Data only to provide and support the
                            Service, as described in our <a href="{{ url('/privacy-policy') }}" class="inline-link">Privacy Policy</a>.
                            You are responsible for the accuracy and legality of the data you upload.
                        </p>
                    </section>

                    <section id="ip">
                        <h2><small>7.</small>Intellectual property</h2>
                        <p>
                            Netacube, its underlying software, design and branding are the
                            property of Netamind Technology and are protected by applicable
                            intellectual property laws. These Terms do not grant you any rights
                            to our trademarks, logos, or source code.
                        </p>
                    </section>

                    <section id="termination">
                        <h2><small>8.</small>Suspension &amp; termination</h2>
                        <p>
                            We may suspend or terminate access to the Service for accounts that
                            violate these Terms, engage in fraudulent activity, or have significant
                            outstanding fees. You may request export of your Tenant Data for a
                            reasonable period following termination, subject to our data retention
                            practices.
                        </p>
                    </section>

                    <section id="liability">
                        <h2><small>9.</small>Disclaimers &amp; liability</h2>
                        <p>
                            The Service is provided "as is" without warranties of any kind, express
                            or implied. To the maximum extent permitted by law, Netamind Technology
                            will not be liable for indirect, incidental, or consequential damages
                            arising from your use of the Service.
                        </p>
                    </section>

                    <section id="changes">
                        <h2><small>10.</small>Changes to these terms</h2>
                        <p>
                            We may update these Terms from time to time. Material changes will be
                            communicated by email or in-app notice. Continued use of the Service
                            after changes take effect constitutes acceptance of the revised Terms.
                        </p>
                    </section>

                    <section id="contact">
                        <h2><small>11.</small>Contact</h2>
                        <p>
                            Questions about these Terms can be sent to our team via the
                            <a href="{{ url('/contact') }}" class="inline-link">contact page</a>.
                        </p>
                    </section>
                </article>

            </div>
        </div>
    </section>

</main>

@endsection