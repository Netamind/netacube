@extends('website.homepage')

@section('title', 'Contact Netacube — Get in Touch')
@section('meta_description', 'Get in touch with the Netacube team for demos, pricing, onboarding or support. Reach us by email, WhatsApp, or send a message directly.')

@section('head_extra')
<style>
    .page-hero {
        background: var(--gradient-deep);
        padding: 84px 0 76px;
        position: relative;
        overflow: hidden;
    }
    .page-hero::before {
        content: '';
        position: absolute; inset: 0;
        background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
        background-size: 46px 46px;
        mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
    }
    .page-hero-inner { position: relative; z-index: 2; max-width: 680px; }
    .page-hero .hero-badge {
        display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.22); color: #d7deff; font-size: 0.78rem; font-weight: 700;
        letter-spacing: 0.04em; padding: 7px 15px; border-radius: 20px; margin-bottom: 20px;
    }
    .page-hero h1 { font-size: clamp(2rem, 4.2vw, 2.85rem); font-weight: 800; line-height: 1.15; letter-spacing: -0.025em; color: #fff; margin-bottom: 16px; }
    .page-hero p { font-size: 1.02rem; line-height: 1.75; color: rgba(255,255,255,0.72); margin: 0; }

    /* ══ Contact channel cards ══ */
    .contact-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .contact-card {
        background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 30px 26px;
        text-align: center; transition: .2s;
    }
    .contact-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); border-color: transparent; }
    .contact-card .feat-icon { width: 50px; height: 50px; background: var(--brand-light); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
    .contact-card .feat-icon i { font-size: 1.45rem; color: var(--brand); }
    .contact-card h5 { font-size: 0.96rem; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
    .contact-card p { font-size: 0.85rem; color: var(--muted); line-height: 1.65; margin-bottom: 18px; }
    .contact-card .btn-ghost-nc { font-size: 0.85rem; padding: 10px 20px; }

    /* ══ Contact form ══ */
    .contact-form-card {
        background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);
        padding: 40px; max-width: 760px; margin: 0 auto;
    }
    .cform-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .cform-group { margin-bottom: 18px; }
    .cform-group label { display: block; font-size: 0.82rem; font-weight: 700; color: var(--ink); margin-bottom: 7px; }
    .cform-group input,
    .cform-group textarea {
        width: 100%; padding: 12px 14px; border: 1.5px solid var(--line); border-radius: 10px;
        font-size: 0.9rem; color: var(--ink); background: #fff; transition: .2s; outline: none;
        font-family: 'Inter', sans-serif;
    }
    .cform-group input { height: 46px; }
    .cform-group textarea { resize: vertical; min-height: 130px; }
    .cform-group input:focus, .cform-group textarea:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(75,94,189,0.12); }

    .secure-note { display: flex; align-items: center; justify-content: center; gap: 7px; font-size: 0.83rem; color: var(--muted); margin-top: 18px; }
    .secure-note i { color: var(--brand); }

    @media (max-width: 1100px) { .contact-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 767px) {
        .contact-grid { grid-template-columns: 1fr; }
        .cform-row { grid-template-columns: 1fr; }
        .contact-form-card { padding: 26px 22px; }
        .page-hero { padding: 56px 0 48px; }
    }
</style>
@endsection

@section('content')

<!-- ══ Hero ══════════════════════════════════════════════════════════════ -->
<section class="page-hero">
    <div class="container" style="max-width:1200px;">
        <div class="page-hero-inner">
            <div class="hero-badge"><i class="ri-customer-service-2-line me-1"></i> We're here to help</div>
            <h1>Get in touch</h1>
            <p>
                Whether you need a demo, have questions about features, need support, or want to discuss how
                Netacube can fit your business — our team is ready to help.
            </p>
        </div>
    </div>
</section>

<!-- ══ Contact channels ══════════════════════════════════════════════════ -->
<section class="section bg-white">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">Reach us</span>
            <h2 class="display-section mt-2">Pick the channel that suits you</h2>
            <div class="section-divider"></div>
        </div>

        <div class="contact-grid">
            <div class="contact-card">
                <div class="feat-icon"><i class="ri-mail-line"></i></div>
                <h5>Email us</h5>
                <p>The fastest written response, during business hours.</p>
                <a href="mailto:info@netamind.com" class="btn-ghost-nc">info@netamind.com</a>
            </div>
            <div class="contact-card">
                <div class="feat-icon"><i class="ri-whatsapp-line"></i></div>
                <h5>WhatsApp / call</h5>
                <p>Quick answers and real-time support — our preferred method.</p>
                <a href="https://wa.me/265992522601" target="_blank" rel="noopener" class="btn-ghost-nc">+265992522601</a>
            </div>
            <div class="contact-card">
                <div class="feat-icon"><i class="ri-map-pin-line"></i></div>
                <h5>Our location</h5>
                <p>Mzuzu, Malawi —  Best oil filling station Room No 11.</p>
                <a href="https://wa.me/265992522601" target="_blank" rel="noopener" class="btn-ghost-nc">Schedule a visit</a>
            </div>
            <div class="contact-card">
                <div class="feat-icon"><i class="ri-time-line"></i></div>
                <h5>Response time</h5>
                <p>Usually within 1–4 hours during business hours, with 24/7 availability via WhatsApp for urgent matters.</p>
            </div>
            <div class="contact-card">
                <div class="feat-icon"><i class="ri-headphone-line"></i></div>
                <h5>What we help with</h5>
                <p>Demos, feature questions, pricing, onboarding, technical support, data migration and custom requests.</p>
            </div>
            <div class="contact-card">
                <div class="feat-icon"><i class="ri-calendar-line"></i></div>
                <h5>Business hours</h5>
                <p>Mon–Fri: 8am–5pm CAT &middot; Sat: 9am–1pm CAT &middot; Sun: emergency support via WhatsApp only.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══ Contact form ══════════════════════════════════════════════════════ -->
<section class="section bg-alt">
    <div class="container" style="max-width:1200px;">
        <div class="text-center center mb-5">
            <span class="eyebrow">Prefer to write?</span>
            <h2 class="display-section mt-2">Send us a message</h2>
            <div class="section-divider"></div>
            <p class="lead-text mt-3 mx-auto" style="max-width:560px;">
                Interested in a demo, have questions about implementation, or want to talk through your specific
                business requirements? We'd love to hear from you.
            </p>
        </div>

        <div class="contact-form-card">
            <form method="POST" action="/contact" class="contact-form">
                @csrf
                <div class="cform-row">
                    <div class="cform-group">
                        <label for="name">Your name</label>
                        <input name="name" id="name" type="text" placeholder="Enter your full name" required>
                    </div>
                    <div class="cform-group">
                        <label for="email">Email address</label>
                        <input name="email" id="email" type="email" placeholder="your@email.com" required>
                    </div>
                </div>
                <div class="cform-group">
                    <label for="subject">Subject</label>
                    <input name="subject" id="subject" type="text" placeholder="e.g. Demo request, support question, pricing inquiry" required>
                </div>
                <div class="cform-group">
                    <label for="message">Your message</label>
                    <textarea name="message" id="message" rows="6" placeholder="Tell us how we can help you..." required></textarea>
                </div>
                <button type="submit" class="btn-primary-nc" style="width:100%; justify-content:center;">
                    <i class="ri-send-plane-line"></i> Send message
                </button>
            </form>
            <div class="secure-note"><i class="ri-lock-line"></i> Your information is secure and only used to respond to your inquiry.</div>
        </div>
    </div>
</section>

@endsection