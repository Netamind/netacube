@extends('website.homepage')
@section('content')

<style>
    /* =========================================================
         CONTACT PAGE — built on the tokens & utility classes
         already defined in website.homepage (var(--blue), .section,
         .kicker, .checks, .business, .cta, .reveal, etc.)
    ========================================================== */

    /* ---- Page hero ---- */
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
        max-width:680px;
        margin:0 auto;
        text-align:center;
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

    /* ---- Contact channel grid ---- */
    .channel-grid{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:18px;
        align-items:stretch;
    }
    .channel-card{
        min-height:200px;
        height:100%;
        padding:28px;
        border:1px solid var(--line);
        border-radius:20px;
        background:#fff;
        text-align:center;
        transition:.25s;
    }
    .channel-card:hover{transform:translateY(-5px);box-shadow:var(--shadow);border-color:var(--blue-line)}
    .channel-icon{
        width:44px;height:44px;
        margin:0 auto 16px;
        display:grid;place-items:center;
        border-radius:12px;
        color:var(--blue);
        background:var(--blue-pale);
        font-weight:900;
        font-size:14px;
    }
    .channel-card h3{color:var(--navy);font:800 15px "Manrope",sans-serif}
    .channel-card p{margin-top:8px;color:var(--muted);font-size:12px;line-height:1.7}
    .channel-link{
        display:inline-block;
        margin-top:16px;
        padding:9px 15px;
        border:1px solid var(--line);
        border-radius:9px;
        color:var(--blue);
        font-size:11px;
        font-weight:800;
        transition:.2s;
    }
    .channel-link:hover{background:var(--blue-pale);border-color:var(--blue-line)}

    /* ---- Contact form ---- */
    .form-card{
        max-width:760px;
        margin:0 auto;
        padding:40px;
        border:1px solid var(--line);
        border-radius:22px;
        background:#fff;
        box-shadow:var(--shadow);
    }
    .form-row{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:16px;
    }
    .form-group{margin-bottom:18px}
    .form-group label{
        display:block;
        margin-bottom:7px;
        color:var(--navy);
        font-size:11px;
        font-weight:800;
    }
    .form-group input,
    .form-group textarea{
        width:100%;
        padding:12px 14px;
        border:1.5px solid var(--line);
        border-radius:10px;
        color:var(--text);
        background:#fff;
        font-family:"DM Sans",sans-serif;
        font-size:13px;
        outline:none;
        transition:.2s;
    }
    .form-group input{height:46px}
    .form-group textarea{resize:vertical;min-height:130px}
    .form-group input:focus,
    .form-group textarea:focus{
        border-color:var(--blue);
        box-shadow:0 0 0 3px rgba(23,111,229,.12);
    }
    .form-submit{
        width:100%;
        justify-content:center;
        border:0;
        cursor:pointer;
    }
    .secure-note{
        display:flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        margin-top:18px;
        color:var(--muted);
        font-size:11px;
        font-weight:700;
    }

    @media(max-width:1050px){
        .channel-grid{grid-template-columns:repeat(2,1fr)}
    }
    @media(max-width:600px){
        .channel-grid{grid-template-columns:1fr}
        .form-row{grid-template-columns:1fr}
        .form-card{padding:26px 22px}
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
                    We're here to help
                </div>

                <h1>Get in touch</h1>

                <p>
                    Whether you need a demo, have questions about features,
                    need support, or want to talk through how Netacube can
                    fit your business — our team is ready to help.
                </p>
            </div>
        </div>
    </section>

    <!-- =========================================================
         CONTACT CHANNELS
    ========================================================== -->
    <section class="section soft">
        <div class="container">

            <div class="section-intro center reveal">
                <div class="kicker">Reach us</div>
                <h2>Pick the channel that suits you.</h2>
            </div>

            <div class="channel-grid">

                <div class="channel-card reveal">
                    <div class="channel-icon">E</div>
                    <h3>Email us</h3>
                    <p>The fastest written response, during business hours.</p>
                    <a href="mailto:info@netamind.com" class="channel-link">info@netamind.com</a>
                </div>

                <div class="channel-card reveal">
                    <div class="channel-icon">W</div>
                    <h3>WhatsApp / call</h3>
                    <p>Quick answers and real-time support — our preferred method.</p>
                    <a href="https://wa.me/265992522601" target="_blank" rel="noopener" class="channel-link">+265 992 522 601</a>
                </div>

                <div class="channel-card reveal">
                    <div class="channel-icon">L</div>
                    <h3>Our location</h3>
                    <p>Mzuzu, Malawi — Best Oil Filling Station, Room No. 11.</p>
                    <a href="https://wa.me/265992522601" target="_blank" rel="noopener" class="channel-link">Schedule a visit</a>
                </div>

                <div class="channel-card reveal">
                    <div class="channel-icon">R</div>
                    <h3>Response time</h3>
                    <p>Usually within 1–4 hours during business hours, with 24/7 availability via WhatsApp for urgent matters.</p>
                </div>

                <div class="channel-card reveal">
                    <div class="channel-icon">S</div>
                    <h3>What we help with</h3>
                    <p>Demos, feature questions, pricing, onboarding, technical support, data migration and custom requests.</p>
                </div>

                <div class="channel-card reveal">
                    <div class="channel-icon">H</div>
                    <h3>Business hours</h3>
                    <p>Mon–Fri: 8am–5pm CAT · Sat: 9am–1pm CAT · Sun: emergency support via WhatsApp only.</p>
                </div>

            </div>
        </div>
    </section>

    <!-- =========================================================
         CONTACT FORM
    ========================================================== -->
    <section class="section">
        <div class="container">

            <div class="section-intro center reveal">
                <div class="kicker">Prefer to write?</div>
                <h2>Send us a message.</h2>
                <p>
                    Interested in a demo, have questions about implementation,
                    or want to talk through your specific business
                    requirements? We'd love to hear from you.
                </p>
            </div>

            <div class="form-card reveal">
                <form method="POST" action="/contact">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Your name</label>
                            <input name="name" id="name" type="text" placeholder="Enter your full name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email address</label>
                            <input name="email" id="email" type="email" placeholder="your@email.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input name="subject" id="subject" type="text" placeholder="e.g. Demo request, support question, pricing inquiry" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Your message</label>
                        <textarea name="message" id="message" rows="6" placeholder="Tell us how we can help you..." required></textarea>
                    </div>
                    <button type="submit" class="button button-blue form-submit">
                        Send message <span>→</span>
                    </button>
                </form>
                <div class="secure-note">🔒 Your information is secure and only used to respond to your inquiry.</div>
            </div>

        </div>
    </section>

</main>

@endsection