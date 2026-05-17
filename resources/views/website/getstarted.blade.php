@extends('website.homepage')

@section('title', 'Create your Netacube account')
@section('meta_description', 'Register your business and get full access to Netacube — inventory, sales, HR, payroll and more. 14-day free trial, no credit card required.')

@section('head_extra')
<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- Toastr -->
<link href="{{ asset('library/toastr/toastr.min.css') }}" rel="stylesheet">
<style>
    /* ── Override page-body padding so registration fills the space ── */
    .page-body { padding-top: var(--nav-h); }

    /* ── Progress bar ── */
    #progressBar {
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: transparent;
        z-index: 9999;
        display: none;
    }
    #progressBar .bar {
        height: 100%;
        background: linear-gradient(90deg, var(--brand), #93a8f0);
        width: 0;
        animation: progress-run 1.4s ease-in-out infinite;
    }
    @keyframes progress-run {
        0%   { width: 0; margin-left: 0; }
        50%  { width: 60%; margin-left: 20%; }
        100% { width: 0; margin-left: 100%; }
    }

    /* ── Registration layout ── */
    .reg-wrap {
        min-height: calc(100vh - var(--nav-h));
        display: flex;
        align-items: stretch;
        background: #f5f6fb;
    }

    /* ── Left branding panel ── */
    .reg-left {
        flex: 0 0 400px;
        background: #0c1128;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        padding: 48px 44px;
    }
    .reg-left-bg-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(75,94,189,0.08) 1px, transparent 1px),
            linear-gradient(90deg, rgba(75,94,189,0.08) 1px, transparent 1px);
        background-size: 40px 40px;
        pointer-events: none;
    }
    .reg-left-glow {
        position: absolute;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(75,94,189,0.22) 0%, transparent 70%);
        top: -100px; right: -150px;
        pointer-events: none;
    }
    .reg-left-glow-2 {
        position: absolute;
        width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(107,125,232,0.12) 0%, transparent 70%);
        bottom: 0; left: -50px;
        pointer-events: none;
    }
    .reg-left-content { position: relative; z-index: 2; flex: 1; display: flex; flex-direction: column; }

    .reg-left h2 {
        font-size: 1.75rem;
        font-weight: 800;
        color: #fff;
        line-height: 1.18;
        letter-spacing: -0.025em;
        margin-bottom: 16px;
    }
    .reg-left h2 span {
        background: linear-gradient(135deg, #6b7de8 0%, #93a8f0 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .reg-left > .reg-left-content > p {
        font-size: 0.875rem;
        color: rgba(255,255,255,0.52);
        line-height: 1.7;
        margin-bottom: 36px;
    }

    .reg-features { flex: 1; }
    .reg-feature {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin-bottom: 20px;
    }
    .reg-feature-icon {
        width: 36px; height: 36px;
        background: rgba(75,94,189,0.2);
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .reg-feature-icon i { font-size: 1.1rem; color: #93a8f0; }
    .reg-feature-text h6 { font-size: 0.83rem; font-weight: 700; color: rgba(255,255,255,0.9); margin-bottom: 2px; }
    .reg-feature-text p  { font-size: 0.77rem; color: rgba(255,255,255,0.45); margin: 0; line-height: 1.5; }

    .reg-trial-badge {
        margin-top: auto;
        padding: 16px 18px;
        background: rgba(75,94,189,0.15);
        border: 1px solid rgba(75,94,189,0.25);
        border-radius: 12px;
    }
    .reg-trial-badge .big { font-size: 1.35rem; font-weight: 800; color: #fff; }
    .reg-trial-badge .sub { font-size: 0.78rem; color: rgba(255,255,255,0.5); margin-top: 2px; }

    /* ── Right form panel ── */
    .reg-right {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 48px 32px;
        overflow-y: auto;
    }
    .reg-form-wrap {
        width: 100%;
        max-width: 480px;
    }

    /* ── Step indicators ── */
    .reg-steps {
        display: flex;
        align-items: center;
        margin-bottom: 36px;
    }
    .reg-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        flex: 1;
    }
    .reg-step-dot {
        width: 32px; height: 32px;
        border-radius: 50%;
        border: 2px solid var(--border);
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.78rem; font-weight: 700;
        color: var(--text-muted);
        z-index: 2;
        position: relative;
        transition: all 0.2s;
    }
    .reg-step.active .reg-step-dot {
        background: var(--brand);
        border-color: var(--brand);
        color: #fff;
        box-shadow: 0 4px 14px rgba(75,94,189,0.35);
    }
    .reg-step.done .reg-step-dot { background: #10b981; border-color: #10b981; color: #fff; }
    .reg-step-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-top: 6px;
        text-align: center;
        white-space: nowrap;
    }
    .reg-step.active .reg-step-label { color: var(--brand); }
    .reg-step.done .reg-step-label   { color: #10b981; }
    .reg-step-line {
        position: absolute;
        top: 16px;
        left: calc(50% + 16px);
        right: calc(-50% + 16px);
        height: 2px;
        background: var(--border);
        z-index: 1;
    }
    .reg-step.done .reg-step-line { background: #10b981; }

    /* ── Card ── */
    .reg-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 36px;
        box-shadow: 0 4px 24px rgba(75,94,189,0.08);
    }
    .reg-card-title { font-size: 1.2rem; font-weight: 800; color: var(--text-dark); margin-bottom: 6px; }
    .reg-card-sub   { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 28px; }

    /* ── Form fields ── */
    .field-group { margin-bottom: 18px; }
    .field-group label {
        display: block;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 7px;
    }
    .field-group label .req { color: var(--brand); margin-left: 2px; }
    .field-input-wrap { position: relative; }
    .field-input-wrap i {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 1rem;
        pointer-events: none;
    }
    .field-input-wrap input,
    .field-input-wrap select {
        width: 100%;
        height: 46px;
        padding: 0 14px 0 40px;
        border: 1.5px solid rgba(75,94,189,0.18);
        border-radius: 10px;
        font-size: 0.9rem;
        color: var(--text-dark);
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
        appearance: none;
    }
    .field-input-wrap select { cursor: pointer; }
    .field-input-wrap input:focus,
    .field-input-wrap select:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(75,94,189,0.12);
    }
    .field-input-wrap input.error,
    .field-input-wrap select.error { border-color: #ef4444; }
    .field-error {
        font-size: 0.77rem;
        color: #ef4444;
        margin-top: 5px;
        display: none;
    }
    .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

    /* ── Plan cards ── */
    .plan-options { display: flex; flex-direction: column; gap: 10px; }
    .plan-opt {
        display: flex;
        align-items: center;
        gap: 14px;
        border: 1.5px solid rgba(75,94,189,0.15);
        border-radius: 10px;
        padding: 14px 16px;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
        position: relative;
    }
    .plan-opt:hover { border-color: var(--brand); background: var(--brand-light); }
    .plan-opt.selected { border-color: var(--brand); background: var(--brand-light); }
    .plan-opt input[type=radio] { position: absolute; opacity: 0; width: 0; }
    .plan-radio {
        width: 18px; height: 18px;
        border-radius: 50%;
        border: 2px solid rgba(75,94,189,0.3);
        background: #fff;
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.2s;
    }
    .plan-opt.selected .plan-radio { border-color: var(--brand); background: var(--brand); }
    .plan-opt.selected .plan-radio::after {
        content: '';
        width: 7px; height: 7px;
        border-radius: 50%;
        background: #fff;
        display: block;
    }
    .plan-info { flex: 1; }
    .plan-info .plan-name   { font-size: 0.88rem; font-weight: 700; color: var(--text-dark); }
    .plan-info .plan-detail { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }
    .plan-price { font-size: 1rem; font-weight: 800; color: var(--brand); }
    .plan-badge {
        position: absolute;
        top: -1px; right: 12px;
        background: var(--brand);
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 2px 8px;
        border-radius: 0 0 6px 6px;
    }

    /* ── OTP ── */
    #step2 { display: none; }
    .otp-intro {
        background: var(--brand-light);
        border: 1px solid rgba(75,94,189,0.2);
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 24px;
        font-size: 0.85rem;
        color: var(--brand-dark);
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    .otp-intro i { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
    .otp-boxes { display: flex; gap: 10px; justify-content: center; margin: 24px 0; }
    .otp-box {
        width: 52px; height: 58px;
        border: 1.5px solid rgba(75,94,189,0.2);
        border-radius: 10px;
        font-size: 1.5rem;
        font-weight: 800;
        text-align: center;
        color: var(--text-dark);
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        background: #fff;
    }
    .otp-box:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(75,94,189,0.12);
    }
    .otp-resend { text-align: center; font-size: 0.83rem; color: var(--text-muted); }
    .otp-resend a { color: var(--brand); font-weight: 600; cursor: pointer; }
    .otp-resend a.disabled { color: var(--text-muted); pointer-events: none; }
    .otp-timer { font-weight: 600; color: var(--brand); }

    /* ── Buttons ── */
    .btn-reg-submit {
        width: 100%;
        background: var(--brand);
        color: #fff;
        font-size: 0.95rem;
        font-weight: 700;
        padding: 14px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        margin-top: 24px;
        text-decoration: none;
    }
    .btn-reg-submit:hover:not(:disabled) {
        background: var(--brand-dark);
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(75,94,189,0.32);
        color: #fff;
    }
    .btn-reg-submit:disabled { opacity: 0.65; cursor: not-allowed; }
    .btn-back {
        background: none;
        border: none;
        color: var(--text-muted);
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 0;
        margin-bottom: 20px;
        transition: color 0.15s;
    }
    .btn-back:hover { color: var(--text-dark); }

    /* ── Misc ── */
    .terms-notice {
        font-size: 0.76rem;
        color: var(--text-muted);
        text-align: center;
        margin-top: 16px;
    }
    .terms-notice a { color: var(--brand); }
    .reg-footer-links {
        text-align: center;
        margin-top: 20px;
        font-size: 0.85rem;
        color: var(--text-muted);
    }
    .reg-footer-links a { color: var(--brand); font-weight: 600; }

    /* ── Honeypot ── */
    .hp-field { opacity: 0; position: absolute; top: 0; left: 0; height: 0; width: 0; z-index: -1; pointer-events: none; }

    /* ── Success ── */
    #successPanel { display: none; text-align: center; padding: 20px 0; }
    .success-icon {
        width: 72px; height: 72px;
        background: #ecfdf5;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 20px;
    }
    .success-icon i { font-size: 2rem; color: #10b981; }
    #successPanel h3 { font-size: 1.3rem; font-weight: 800; color: var(--text-dark); margin-bottom: 8px; }
    #successPanel p  { font-size: 0.88rem; color: var(--text-muted); line-height: 1.7; }

    /* ── Responsive ── */
    @media (max-width: 900px) {
        .reg-left { display: none; }
        .reg-right { padding: 32px 20px; }
    }
    @media (max-width: 480px) {
        .reg-card { padding: 24px 20px; }
        .field-row { grid-template-columns: 1fr; }
        .otp-box { width: 44px; height: 52px; font-size: 1.3rem; }
    }
</style>
@endsection

@section('content')
<div id="progressBar"><div class="bar"></div></div>

<div class="reg-wrap">

    <!-- ══ Left panel ════════════════════════════════════════════════════ -->
    <div class="reg-left">
        <div class="reg-left-bg-grid"></div>
        <div class="reg-left-glow"></div>
        <div class="reg-left-glow-2"></div>
        <div class="reg-left-content">

            <h2>Start managing<br>your business<br><span>the smarter way</span></h2>
            <p>Join businesses across Malawi using Netacube to manage inventory, sales, staff, finances and more — all in one place.</p>

            <div class="reg-features">
                <div class="reg-feature">
                    <div class="reg-feature-icon"><i class="ri-rocket-line"></i></div>
                    <div class="reg-feature-text">
                        <h6>Instant access</h6>
                        <p>Full dashboard access as soon as you register — no waiting.</p>
                    </div>
                </div>
                <div class="reg-feature">
                    <div class="reg-feature-icon"><i class="ri-shield-check-line"></i></div>
                    <div class="reg-feature-text">
                        <h6>Enterprise-grade security</h6>
                        <p>Encrypted data, role-based access and daily backups.</p>
                    </div>
                </div>
                <div class="reg-feature">
                    <div class="reg-feature-icon"><i class="ri-building-2-line"></i></div>
                    <div class="reg-feature-text">
                        <h6>Multi-branch ready</h6>
                        <p>Manage all your locations from a single platform.</p>
                    </div>
                </div>
                <div class="reg-feature">
                    <div class="reg-feature-icon"><i class="ri-customer-service-2-line"></i></div>
                    <div class="reg-feature-text">
                        <h6>24/7 support</h6>
                        <p>Real humans available via email and WhatsApp.</p>
                    </div>
                </div>
            </div>

            <div class="reg-trial-badge">
                <div class="big">14-day free trial</div>
                <div class="sub">Full access · No credit card required · Pay after 14 days</div>
            </div>

        </div>
    </div>

    <!-- ══ Right panel ════════════════════════════════════════════════════ -->
    <div class="reg-right">
        <div class="reg-form-wrap">

            <!-- Step indicators -->
            <div class="reg-steps" id="stepsNav">
                <div class="reg-step active" id="stepDot1">
                    <div class="reg-step-dot" id="dot1">1</div>
                    <span class="reg-step-label">Your details</span>
                    <div class="reg-step-line"></div>
                </div>
                <div class="reg-step" id="stepDot2">
                    <div class="reg-step-dot" id="dot2">2</div>
                    <span class="reg-step-label">Verify email</span>
                </div>
            </div>

            <div class="reg-card">

                <!-- ── STEP 1 ── -->
                <div id="step1">
                    <div class="reg-card-title">Create your account</div>
                    <div class="reg-card-sub">Start managing your business with Netacube — it takes under 2 minutes.</div>

                    <form id="registrationForm" autocomplete="off" novalidate>
                        @csrf

                        <!-- Honeypot -->
                        <div class="hp-field" aria-hidden="true" tabindex="-1">
                            <input type="text" name="website"     id="website"     autocomplete="off" tabindex="-1">
                            <input type="text" name="company_url" id="company_url" autocomplete="off" tabindex="-1">
                        </div>
                        <input type="hidden" name="licensenumber"  id="licensenumber"  value="">
                        <input type="hidden" name="form_loaded_at" id="formLoadedAt"   value="">

                        <div class="field-row">
                            <div class="field-group">
                                <label for="full_name">Full name <span class="req">*</span></label>
                                <div class="field-input-wrap">
                                    <i class="ri-user-3-line"></i>
                                    <input type="text" name="full_name" id="full_name" placeholder="Your full name" autocomplete="off">
                                </div>
                                <div class="field-error" id="err-full_name"></div>
                            </div>
                            <div class="field-group">
                                <label for="phone_number">Phone number <span class="req">*</span></label>
                                <div class="field-input-wrap">
                                    <i class="ri-phone-line"></i>
                                    <input type="tel" name="phone_number" id="phone_number" placeholder="+265 XXX XXX XXX">
                                </div>
                                <div class="field-error" id="err-phone_number"></div>
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="email">Email address <span class="req">*</span></label>
                            <div class="field-input-wrap">
                                <i class="ri-mail-line"></i>
                                <input type="email" name="email" id="email" placeholder="you@yourbusiness.com">
                            </div>
                            <div class="field-error" id="err-email"></div>
                        </div>

                        <div class="field-group">
                            <label for="business_name">Business / company name <span class="req">*</span></label>
                            <div class="field-input-wrap">
                                <i class="ri-building-line"></i>
                                <input type="text" name="business_name" id="business_name" placeholder="Your business name">
                            </div>
                            <div class="field-error" id="err-business_name"></div>
                        </div>

                        <div class="field-group">
                            <label>Choose your plan <span class="req">*</span></label>
                            <div class="plan-options" id="planOptions">
                                @php $plans = DB::table('subscription_plans')->orderBy('id')->get(); @endphp
                                @foreach($plans as $idx => $plan)
                                <label class="plan-opt {{ $idx === 1 ? 'selected' : '' }}" data-value="{{ $plan->id }}">
                                    <input type="radio" name="subscription_plan" value="{{ $plan->id }}" {{ $idx === 1 ? 'checked' : '' }}>
                                    @if($idx === 1)<div class="plan-badge">Popular</div>@endif
                                    <div class="plan-radio">@if($idx === 1)<span></span>@endif</div>
                                    <div class="plan-info">
                                        <div class="plan-name">{{ $plan->plan_name }}</div>
                                        <div class="plan-detail">{{ $plan->plan_period }} · Full access to all features</div>
                                    </div>
                                    <div class="plan-price">${{ $plan->plan_amount }}</div>
                                </label>
                                @endforeach
                            </div>
                            <div class="field-error" id="err-subscription_plan"></div>
                        </div>

                        <button type="button" class="btn-reg-submit" id="sendOtpBtn">
                            <i class="ri-mail-send-line"></i> Continue — verify your email
                        </button>
                    </form>
                </div>

                <!-- ── STEP 2 ── -->
                <div id="step2">
                    <button class="btn-back" onclick="goBack()">
                        <i class="ri-arrow-left-line"></i> Back to details
                    </button>
                    <div class="reg-card-title">Verify your email</div>
                    <div class="reg-card-sub">We've sent a 6-digit code to your email address.</div>

                    <div class="otp-intro">
                        <i class="ri-information-line"></i>
                        <div>
                            A verification code was sent to <strong id="otpEmailDisplay"></strong>.
                            Enter it below to complete registration. The code expires in 10 minutes.
                        </div>
                    </div>

                    <div class="otp-boxes">
                        <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp0" aria-label="OTP digit 1">
                        <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp1" aria-label="OTP digit 2">
                        <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp2" aria-label="OTP digit 3">
                        <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp3" aria-label="OTP digit 4">
                        <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp4" aria-label="OTP digit 5">
                        <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp5" aria-label="OTP digit 6">
                    </div>

                    <div class="otp-resend">
                        Didn't receive it?
                        <a id="resendLink" class="disabled" onclick="resendOtp()">Resend in <span class="otp-timer" id="otpTimer">60s</span></a>
                    </div>

                    <button type="button" class="btn-reg-submit" id="verifyOtpBtn">
                        <i class="ri-check-double-line"></i> Verify and create account
                    </button>
                </div>

                <!-- ── Success ── -->
                <div id="successPanel">
                    <div class="success-icon"><i class="ri-checkbox-circle-line"></i></div>
                    <h3>Account created!</h3>
                    <p>Welcome to Netacube. Your login credentials have been sent to your email address. You can now log in and start exploring your dashboard.</p>
                    <a href="/login" class="btn-reg-submit" style="display:inline-flex; width:auto; padding:13px 32px;">
                        <i class="ri-login-circle-line"></i> Go to login
                    </a>
                </div>

            </div><!-- /reg-card -->

            <div class="terms-notice" id="termsNotice">
                By registering, you agree to our <a href="/terms">Terms of Service</a> and <a href="/privacy">Privacy Policy</a>.
            </div>
            <div class="reg-footer-links">
                Already have an account? <a href="/login">Sign in here</a>
            </div>

        </div>
    </div>

</div><!-- /reg-wrap -->
@endsection

@section('scripts')
<script src="{{ asset('library/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('library/toastr/toastr.min.js') }}"></script>
<script>
$(function() {

    toastr.options = { closeButton: true, progressBar: true, timeOut: 6000 };

    document.getElementById('formLoadedAt').value = Date.now();

    function showProgress() { document.getElementById('progressBar').style.display = 'block'; }
    function hideProgress() { document.getElementById('progressBar').style.display = 'none'; }

    /* Plan selector */
    document.querySelectorAll('.plan-opt').forEach(function(opt) {
        opt.addEventListener('click', function() {
            document.querySelectorAll('.plan-opt').forEach(function(o) { o.classList.remove('selected'); });
            this.classList.add('selected');
            this.querySelector('input[type=radio]').checked = true;
        });
    });

    /* Validation helpers */
    function clearErrors() {
        document.querySelectorAll('.field-error').forEach(function(e) { e.style.display = 'none'; e.textContent = ''; });
        document.querySelectorAll('.field-input-wrap input, .field-input-wrap select').forEach(function(i) { i.classList.remove('error'); });
    }
    function showError(field, msg) {
        var errEl = document.getElementById('err-' + field);
        var inputEl = document.getElementById(field);
        if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
        if (inputEl) { inputEl.classList.add('error'); }
    }
    function validateStep1() {
        clearErrors();
        var ok    = true;
        var name  = $('#full_name').val().trim();
        var phone = $('#phone_number').val().trim();
        var email = $('#email').val().trim();
        var biz   = $('#business_name').val().trim();
        var plan  = $('input[name="subscription_plan"]:checked').val();
        var hp1   = $('#website').val();
        var hp2   = $('#company_url').val();

        if (hp1 || hp2) { toastr.error('Something went wrong. Please try again.', 'Error'); return false; }

        var loadedAt = parseInt(document.getElementById('formLoadedAt').value) || 0;
        if (loadedAt && (Date.now() - loadedAt) < 4000) {
            toastr.warning('Please take a moment to review your details before submitting.', 'Hold on');
            return false;
        }

        if (!name)  { showError('full_name', 'Full name is required.'); ok = false; }
        if (!phone) { showError('phone_number', 'Phone number is required.'); ok = false; }
        if (!email) {
            showError('email', 'Email address is required.'); ok = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('email', 'Please enter a valid email address.'); ok = false;
        }
        if (!biz)  { showError('business_name', 'Business name is required.'); ok = false; }
        if (!plan) { showError('subscription_plan', 'Please select a plan.'); ok = false; }
        return ok;
    }

    /* Send OTP */
    $('#sendOtpBtn').on('click', function() {
        if (!validateStep1()) return;
        var $btn = $(this).prop('disabled', true).html('<i class="ri-loader-4-line"></i> Sending code…');
        showProgress();

        $.ajax({
            type: 'POST', url: '/send-otp',
            data: {
                full_name: $('#full_name').val(), phone_number: $('#phone_number').val(),
                email: $('#email').val(), business_name: $('#business_name').val(),
                subscription_plan: $('input[name="subscription_plan"]:checked').val(),
                _token: '{{ csrf_token() }}'
            },
            timeout: 30000,
            complete: function() { hideProgress(); },
            success: function(data) {
                if (data.status === 200 || data.status === 201) {
                    toastr.success('A verification code has been sent to your email.', 'Code sent');
                    goToStep2();
                } else if (data.status === 409) {
                    toastr.warning(data.error || 'An account with this email already exists.', 'Already registered');
                    showError('email', data.error || 'Email already registered.');
                    $btn.prop('disabled', false).html('<i class="ri-mail-send-line"></i> Continue — verify your email');
                } else {
                    toastr.error(data.error || 'Something went wrong. Please try again.', 'Error');
                    $btn.prop('disabled', false).html('<i class="ri-mail-send-line"></i> Continue — verify your email');
                }
            },
            error: function(xhr) {
                hideProgress();
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(k, v) { showError(k, v[0]); });
                } else if (xhr.status === 429) {
                    toastr.warning('Too many attempts. Please wait a minute and try again.', 'Slow down');
                } else {
                    toastr.error('Unable to connect. Please check your internet and try again.', 'Error');
                }
                $btn.prop('disabled', false).html('<i class="ri-mail-send-line"></i> Continue — verify your email');
            }
        });
    });

    /* Step navigation */
    function goToStep2() {
        $('#step1').hide(); $('#step2').show();
        $('#otpEmailDisplay').text($('#email').val());
        $('#stepDot1').addClass('done').removeClass('active');
        $('#dot1').html('<i class="ri-check-line" style="font-size:0.85rem;"></i>');
        $('#stepDot2').addClass('active');
        startOtpTimer(60);
        setTimeout(function() { document.getElementById('otp0').focus(); }, 200);
    }
    window.goBack = function() {
        $('#step2').hide(); $('#step1').show();
        $('#stepDot1').removeClass('done').addClass('active');
        $('#dot1').text('1');
        $('#stepDot2').removeClass('active');
        clearOtpBoxes();
    };

    /* OTP boxes */
    var otpBoxes = document.querySelectorAll('.otp-box');
    otpBoxes.forEach(function(box, idx) {
        box.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(-1);
            if (this.value && idx < otpBoxes.length - 1) otpBoxes[idx + 1].focus();
        });
        box.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && idx > 0) {
                otpBoxes[idx - 1].focus();
                otpBoxes[idx - 1].value = '';
            }
        });
        box.addEventListener('paste', function(e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            text.split('').slice(0, 6).forEach(function(ch, i) { if (otpBoxes[i]) otpBoxes[i].value = ch; });
            otpBoxes[Math.min(text.length, 5)].focus();
        });
    });
    function clearOtpBoxes() { otpBoxes.forEach(function(b) { b.value = ''; }); }
    function getOtpValue()   { return Array.from(otpBoxes).map(function(b) { return b.value; }).join(''); }

    /* OTP timer */
    var timerInterval;
    function startOtpTimer(seconds) {
        clearInterval(timerInterval);
        var remaining = seconds;
        var link  = document.getElementById('resendLink');
        var timer = document.getElementById('otpTimer');
        link.classList.add('disabled');
        timer.style.display = 'inline';
        timerInterval = setInterval(function() {
            remaining--;
            timer.textContent = remaining + 's';
            if (remaining <= 0) {
                clearInterval(timerInterval);
                timer.style.display = 'none';
                link.textContent = 'Resend code';
                link.classList.remove('disabled');
            }
        }, 1000);
    }
    window.resendOtp = function() {
        showProgress();
        $.ajax({
            type: 'POST', url: '/send-otp',
            data: {
                full_name: $('#full_name').val(), phone_number: $('#phone_number').val(),
                email: $('#email').val(), business_name: $('#business_name').val(),
                subscription_plan: $('input[name="subscription_plan"]:checked').val(),
                resend: 1, _token: '{{ csrf_token() }}'
            },
            complete: hideProgress,
            success: function(data) {
                if (data.status === 200 || data.status === 201) {
                    toastr.success('A new code has been sent.', 'Code resent');
                    clearOtpBoxes(); startOtpTimer(60);
                    document.getElementById('otp0').focus();
                } else {
                    toastr.error(data.error || 'Could not resend code.', 'Error');
                }
            },
            error: function() { hideProgress(); toastr.error('Unable to resend. Please try again.', 'Error'); }
        });
    };

    /* Verify OTP & register */
    $('#verifyOtpBtn').on('click', function() {
        var otp = getOtpValue();
        if (otp.length !== 6) { toastr.warning('Please enter the complete 6-digit code.', 'Incomplete code'); return; }

        var $btn = $(this).prop('disabled', true).html('<i class="ri-loader-4-line"></i> Creating your account…');
        showProgress();

        $.ajax({
            type: 'POST', url: '/get-started',
            data: {
                full_name: $('#full_name').val(), phone_number: $('#phone_number').val(),
                email: $('#email').val(), business_name: $('#business_name').val(),
                subscription_plan: $('input[name="subscription_plan"]:checked').val(),
                otp: otp, form_loaded_at: $('#formLoadedAt').val(),
                _token: '{{ csrf_token() }}'
            },
            timeout: 60000,
            complete: function() { hideProgress(); },
            success: function(data) {
                if (data.status === 201) {
                    clearInterval(timerInterval);
                    $('#step2').hide();
                    $('#stepsNav').hide();
                    $('#termsNotice').hide();
                    $('#successPanel').show();
                    $('#stepDot2').addClass('done').removeClass('active');
                    $('#dot2').html('<i class="ri-check-line" style="font-size:0.85rem;"></i>');
                } else if (data.status === 400) {
                    toastr.error(data.error || 'Invalid or expired code. Please try again.', 'Wrong code');
                    clearOtpBoxes(); document.getElementById('otp0').focus();
                    $btn.prop('disabled', false).html('<i class="ri-check-double-line"></i> Verify and create account');
                } else if (data.status === 423) {
                    toastr.warning(data.error || 'Suspicious request detected. Please try again.', 'Blocked');
                    $btn.prop('disabled', false).html('<i class="ri-check-double-line"></i> Verify and create account');
                } else {
                    toastr.error(data.error || 'Unexpected error. Please try again.', 'Error');
                    $btn.prop('disabled', false).html('<i class="ri-check-double-line"></i> Verify and create account');
                }
            },
            error: function(xhr) {
                hideProgress();
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var msgs = Object.values(xhr.responseJSON.errors).flat().join('\n');
                    toastr.error(msgs, 'Validation error');
                } else if (xhr.status === 429) {
                    toastr.warning('Too many attempts. Please wait before trying again.', 'Rate limited');
                } else {
                    toastr.error('Unable to complete registration. Please try again.', 'Error');
                }
                $btn.prop('disabled', false).html('<i class="ri-check-double-line"></i> Verify and create account');
            }
        });
    });

});
</script>
@endsection