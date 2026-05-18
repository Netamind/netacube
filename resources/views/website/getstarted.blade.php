@extends('website.homepage')

@section('title', 'Create your Netacube account')
@section('meta_description', 'Register your business on Netacube and get full access to inventory, sales, staff and payroll tools. 14-day free trial, no card required.')

@section('head_extra')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="{{ asset('library/toastr/toastr.min.css') }}" rel="stylesheet">
<style>
    .page-body { padding-top: var(--nav-h); }

    #progressBar { position: fixed; top: 0; left: 0; right: 0; height: 3px; background: transparent; z-index: 9999; display: none; }
    #progressBar .bar { height: 100%; background: var(--gradient); width: 0; animation: progress-run 1.4s ease-in-out infinite; }
    @keyframes progress-run { 0% { width:0; margin-left:0; } 50% { width:60%; margin-left:20%; } 100% { width:0; margin-left:100%; } }

    /* ══ Centered, single-column registration layout ══
       No side panel — the form is the page. The brand still shows up
       through the eyebrow label and the gradient button,
       so it stays recognisably Netacube without competing for attention. */
    .reg-split {
        min-height: calc(100vh - var(--nav-h));
        background: var(--surface-alt);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 64px 20px;
    }
    .reg-form-panel { width: 100%; display: flex; justify-content: center; }
    .reg-form-wrap { width: 100%; max-width: 480px; margin: 0 auto; }

    .reg-form-heading { text-align: center; margin-bottom: 14px; }
    .reg-form-heading .eyebrow-sm { font-size: 0.74rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--brand); margin-bottom: 6px; display: block; }
    .reg-form-heading h1 { font-size: 1.55rem; font-weight: 800; color: var(--ink); letter-spacing: -0.01em; margin-bottom: 8px; }
    .reg-form-heading p { font-size: 0.9rem; color: var(--muted); margin: 0 auto; max-width: 380px; line-height: 1.6; }

    /* Small rule under the intro copy — separates "what this page is" from "the form itself" */
    .reg-form-divider { width: 100%; max-width: 480px; margin: 26px auto 28px; height: 1px; background: var(--line); position: relative; }
    .reg-form-divider span {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        background: var(--surface-alt); padding: 0 14px; font-size: 0.72rem; font-weight: 700;
        letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); white-space: nowrap;
    }

    .reg-steps { display: flex; align-items: center; margin-bottom: 28px; max-width: 280px; margin-left: auto; margin-right: auto; }
    .reg-step { display: flex; flex-direction: column; align-items: center; position: relative; flex: 1; }
    .reg-step-dot {
        width: 32px; height: 32px; border-radius: 50%; border: 2px solid var(--line); background: #fff;
        display: flex; align-items: center; justify-content: center; font-size: 0.78rem; font-weight: 700;
        color: var(--muted); z-index: 2; position: relative; transition: .2s;
    }
    .reg-step.active .reg-step-dot { background: var(--brand); border-color: var(--brand); color: #fff; box-shadow: 0 4px 14px rgba(75,94,189,0.35); }
    .reg-step.done .reg-step-dot { background: #1a8754; border-color: #1a8754; color: #fff; }
    .reg-step-label { font-size: 0.7rem; font-weight: 600; color: var(--muted); margin-top: 6px; text-align: center; white-space: nowrap; }
    .reg-step.active .reg-step-label { color: var(--brand); }
    .reg-step.done .reg-step-label { color: #1a8754; }
    .reg-step-line { position: absolute; top: 16px; left: calc(50% + 16px); right: calc(-50% + 16px); height: 2px; background: var(--line); z-index: 1; }
    .reg-step.done .reg-step-line { background: #1a8754; }

    .reg-card { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 36px; box-shadow: var(--shadow-lg); }
    .reg-card-title { font-size: 1.15rem; font-weight: 800; color: var(--ink); margin-bottom: 6px; }
    .reg-card-sub { font-size: 0.85rem; color: var(--muted); margin-bottom: 26px; }

    .field-group { margin-bottom: 18px; }
    .field-group label { display: block; font-size: 0.82rem; font-weight: 700; color: var(--ink); margin-bottom: 7px; }
    .field-group label .req { color: var(--brand); margin-left: 2px; }
    .field-input-wrap { position: relative; }
    .field-input-wrap i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 1rem; pointer-events: none; }
    .field-input-wrap input, .field-input-wrap select {
        width: 100%; height: 46px; padding: 0 14px 0 40px; border: 1.5px solid var(--line); border-radius: 10px;
        font-size: 0.9rem; color: var(--ink); background: #fff; transition: .2s; outline: none; appearance: none;
    }
    .field-input-wrap select { cursor: pointer; }
    .field-input-wrap input:focus, .field-input-wrap select:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(75,94,189,0.12); }
    .field-input-wrap input.error, .field-input-wrap select.error { border-color: #e5484d; }
    .field-error { font-size: 0.77rem; color: #e5484d; margin-top: 5px; display: none; }
    .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

    .plan-options { display: flex; flex-direction: column; gap: 10px; }
    .plan-opt { display: flex; align-items: center; gap: 14px; border: 1.5px solid var(--line); border-radius: 10px; padding: 14px 16px; cursor: pointer; transition: .2s; position: relative; }
    .plan-opt:hover { border-color: var(--brand); background: var(--brand-light); }
    .plan-opt.selected { border-color: var(--brand); background: var(--brand-light); }
    .plan-opt input[type=radio] { position: absolute; opacity: 0; width: 0; }
    .plan-radio { width: 18px; height: 18px; border-radius: 50%; border: 2px solid rgba(75,94,189,0.3); background: #fff; flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: .2s; }
    .plan-opt.selected .plan-radio { border-color: var(--brand); background: var(--brand); }
    .plan-opt.selected .plan-radio::after { content: ''; width: 7px; height: 7px; border-radius: 50%; background: #fff; display: block; }
    .plan-info { flex: 1; }
    .plan-info .plan-name { font-size: 0.88rem; font-weight: 700; color: var(--ink); }
    .plan-info .plan-detail { font-size: 0.78rem; color: var(--muted); margin-top: 2px; }
    .plan-price { font-size: 1rem; font-weight: 800; color: var(--brand); }
    .plan-badge { position: absolute; top: -1px; right: 12px; background: var(--gradient); color: #fff; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; padding: 2px 8px; border-radius: 0 0 6px 6px; }

    /* ── Front-end-only human check: a simple maths question instead of an OTP ── */
    .human-check {
        display: flex; align-items: center; gap: 14px; background: var(--brand-light);
        border: 1px solid var(--line); border-radius: 10px; padding: 14px 16px; margin-bottom: 6px;
    }
    .human-check .hc-icon { width: 38px; height: 38px; border-radius: 9px; background: var(--gradient); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .human-check .hc-icon i { color: #fff; font-size: 1.1rem; }
    .human-check .hc-question { font-size: 0.95rem; font-weight: 700; color: var(--ink); white-space: nowrap; }
    .human-check input {
        width: 64px; height: 38px; border: 1.5px solid var(--line); border-radius: 8px; text-align: center;
        font-size: 0.95rem; font-weight: 700; color: var(--ink); outline: none; transition: .2s;
    }
    .human-check input:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(75,94,189,0.12); }
    .human-check input.error { border-color: #e5484d; }
    .human-check-refresh { background: none; border: none; color: var(--brand); cursor: pointer; font-size: 1.1rem; padding: 6px; margin-left: auto; flex-shrink: 0; }
    .human-check-refresh:hover { color: var(--brand-dark); }

    .btn-reg-submit {
        width: 100%; background: var(--gradient); color: #fff; font-size: 0.95rem; font-weight: 700; padding: 14px;
        border-radius: 10px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
        gap: 8px; transition: .2s; margin-top: 24px; text-decoration: none;
    }
    .btn-reg-submit:hover:not(:disabled) { box-shadow: 0 8px 24px rgba(75,94,189,0.32); transform: translateY(-1px); color: #fff; }
    .btn-reg-submit:disabled { opacity: 0.65; cursor: not-allowed; }
    .btn-back { background: none; border: none; color: var(--muted); font-size: 0.85rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 0; margin-bottom: 20px; transition: color .15s; }
    .btn-back:hover { color: var(--ink); }

    .terms-notice { font-size: 0.76rem; color: var(--muted); text-align: center; margin-top: 16px; }
    .terms-notice a { color: var(--brand); }
    .reg-footer-links { text-align: center; margin-top: 20px; font-size: 0.85rem; color: var(--muted); }
    .reg-footer-links a { color: var(--brand); font-weight: 600; }

    .hp-field { opacity: 0; position: absolute; top: 0; left: 0; height: 0; width: 0; z-index: -1; pointer-events: none; }

    #successPanel { display: none; text-align: center; padding: 20px 0; }
    .success-icon { width: 72px; height: 72px; background: #e6f7ee; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
    .success-icon i { font-size: 2rem; color: #1a8754; }
    #successPanel h3 { font-size: 1.3rem; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
    #successPanel p { font-size: 0.88rem; color: var(--muted); line-height: 1.7; }

    @media (max-width: 480px) {
        .reg-split { padding: 40px 16px; }
        .reg-card { padding: 24px 20px; }
        .field-row { grid-template-columns: 1fr; }
        .human-check { flex-wrap: wrap; }
        .human-check-refresh { margin-left: 0; }
        .reg-form-divider { margin: 22px auto 24px; }
    }
</style>
@endsection

@section('content')
<div id="progressBar"><div class="bar"></div></div>

<div class="reg-split">
    <div class="reg-form-panel">
    <div class="reg-form-wrap">

        <div class="reg-form-heading">
            <span class="eyebrow-sm">Create your account</span>
            <h1>Set up your business on Netacube</h1>
            <p>One account gives every branch, till and team member access to the same live data — tell us a little about your business and you'll be ready to start selling in minutes.</p>
        </div>

        <div class="reg-form-divider"><span>Get started</span></div>

        <div class="reg-steps" id="stepsNav">
            <div class="reg-step active" id="stepDot1">
                <div class="reg-step-dot" id="dot1">1</div>
                <span class="reg-step-label">Your details</span>
                <div class="reg-step-line"></div>
            </div>
            <div class="reg-step" id="stepDot2">
                <div class="reg-step-dot" id="dot2">2</div>
                <span class="reg-step-label">Quick check</span>
            </div>
        </div>

        <div class="reg-card">

            <!-- ── STEP 1 ── -->
            <div id="step1">
                <form id="registrationForm" autocomplete="off" novalidate>
                    @csrf

                    <!-- Honeypot fields: invisible to real users, bots tend to fill every input they find.
                         Names are deliberately non-standard (not "website"/"email"/etc.) and use
                         autocomplete="new-password" because that's the one value browsers reliably
                         refuse to autofill — common names like "website" get silently autofilled by
                         the browser itself, which would wrongly flag real visitors as bots. -->
                    <div class="hp-field" aria-hidden="true" tabindex="-1">
                        <input type="text" name="hp_field_a" id="hp_field_a" autocomplete="new-password" tabindex="-1">
                        <input type="text" name="hp_field_b" id="hp_field_b" autocomplete="new-password" tabindex="-1">
                    </div>
                    <input type="hidden" name="form_loaded_at" id="formLoadedAt" value="">

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

                    <button type="button" class="btn-reg-submit" id="continueBtn">
                        <i class="ri-arrow-right-line"></i> Continue
                    </button>
                </form>
            </div>

            <!-- ── STEP 2: quick human check (front-end only, no OTP/email step) ── -->
            <div id="step2" style="display:none;">
                <button class="btn-back" onclick="goBack()"><i class="ri-arrow-left-line"></i> Back to details</button>
                <div class="reg-card-title">Just one quick check</div>
                <div class="reg-card-sub">Answer this so we know you're not a robot, then create your account.</div>

                <div class="human-check">
                    <span class="hc-icon"><i class="ri-calculator-line"></i></span>
                    <span class="hc-question" id="hcQuestion">3 + 4 =</span>
                    <input type="text" inputmode="numeric" id="hcAnswer" placeholder="?" maxlength="3">
                    <button type="button" class="human-check-refresh" id="hcRefresh" title="Get a new question"><i class="ri-refresh-line"></i></button>
                </div>
                <div class="field-error" id="err-hcAnswer"></div>

                <button type="button" class="btn-reg-submit" id="createAccountBtn" style="margin-top:18px;">
                    <i class="ri-check-double-line"></i> Create my account
                </button>
            </div>

            <!-- ── Success ── -->
            <div id="successPanel">
                <div class="success-icon"><i class="ri-checkbox-circle-line"></i></div>
                <h3>Account created!</h3>
                <p>Thank you for registering with Netacube. Your login details will be sent to your email shortly. If you need them sooner, feel free to reach us on WhatsApp at <a href="https://wa.me/265992522601" target="_blank" rel="noopener">099 252 2601</a> and our team will assist you right away.</p>
            </div>

        </div><!-- /reg-card -->

        <div class="terms-notice" id="termsNotice">
            By registering, you agree to our <a href="/terms">Terms of Service</a> and <a href="/privacy">Privacy Policy</a>.
        </div>
        <div class="reg-footer-links">
            Already have an account? <a href="/login">Sign in here</a>
        </div>

    </div><!-- /reg-form-wrap -->
    </div><!-- /reg-form-panel -->
</div><!-- /reg-split -->
@endsection

@section('scripts')
<script src="{{ asset('library/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('library/toastr/toastr.min.js') }}"></script>
<script>
$(function() {

    toastr.options = { closeButton: true, progressBar: true, timeOut: 6000 };
    document.getElementById('formLoadedAt').value = Date.now();

    // Defensive: force-clear honeypot fields shortly after load, in case a
    // browser extension or autofill still manages to populate them before
    // the user interacts with the page.
    setTimeout(function() {
        $('#hp_field_a').val('');
        $('#hp_field_b').val('');
    }, 600);

    function showProgress() { document.getElementById('progressBar').style.display = 'block'; }
    function hideProgress() { document.getElementById('progressBar').style.display = 'none'; }

    document.querySelectorAll('.plan-opt').forEach(function(opt) {
        opt.addEventListener('click', function() {
            document.querySelectorAll('.plan-opt').forEach(function(o) { o.classList.remove('selected'); });
            this.classList.add('selected');
            this.querySelector('input[type=radio]').checked = true;
        });
    });

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

        // Note: honeypot fields are still collected and sent to the server,
        // but we no longer block on them in the browser — some password
        // manager extensions auto-fill hidden inputs regardless of name or
        // autocomplete attribute, which was causing false positives for
        // real visitors. The timing check below and the maths check on
        // step 2 are the front-end bot defenses that actually run here.

        // A genuine visitor takes a moment to read the form before submitting.
        var loadedAt = parseInt(document.getElementById('formLoadedAt').value) || 0;
        if (loadedAt && (Date.now() - loadedAt) < 4000) {
            toastr.warning('Please take a moment to review your details before continuing.', 'Hold on');
            return false;
        }

        if (!name)  { showError('full_name', 'Full name is required.'); ok = false; }
        if (!phone) { showError('phone_number', 'Phone number is required.'); ok = false; }
        if (!email) { showError('email', 'Email address is required.'); ok = false; }
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('email', 'Please enter a valid email address.'); ok = false; }
        if (!biz)   { showError('business_name', 'Business name is required.'); ok = false; }
        if (!plan)  { showError('subscription_plan', 'Please select a plan.'); ok = false; }
        return ok;
    }

    $('#continueBtn').on('click', function() {
        if (!validateStep1()) return;
        goToStep2();
    });

    function goToStep2() {
        $('#step1').hide(); $('#step2').show();
        $('#stepDot1').addClass('done').removeClass('active');
        $('#dot1').html('<i class="ri-check-line" style="font-size:0.85rem;"></i>');
        $('#stepDot2').addClass('active');
        newHumanCheck();
        setTimeout(function() { document.getElementById('hcAnswer').focus(); }, 150);
    }
    window.goBack = function() {
        $('#step2').hide(); $('#step1').show();
        $('#stepDot1').removeClass('done').addClass('active');
        $('#dot1').text('1');
        $('#stepDot2').removeClass('active');
    };

    /* ── Simple front-end-only maths check, entirely client-side ── */
    var hcAnswer = 0;
    function newHumanCheck() {
        var a = Math.floor(Math.random() * 8) + 1;
        var b = Math.floor(Math.random() * 8) + 1;
        var ops = ['+', '+', '-']; // bias toward addition, occasional subtraction
        var op = ops[Math.floor(Math.random() * ops.length)];
        if (op === '-' && a < b) { var t = a; a = b; b = t; } // keep subtraction non-negative
        hcAnswer = (op === '+') ? (a + b) : (a - b);
        $('#hcQuestion').text(a + ' ' + op + ' ' + b + ' =');
        $('#hcAnswer').val('');
        $('#hcAnswer').removeClass('error');
        $('#err-hcAnswer').hide();
    }
    $('#hcRefresh').on('click', newHumanCheck);
    $('#hcAnswer').on('keypress', function(e) {
        if (e.which === 13) { e.preventDefault(); $('#createAccountBtn').click(); }
    });

    $('#createAccountBtn').on('click', function() {
        var given = parseInt($('#hcAnswer').val(), 10);

        if (isNaN(given) || given !== hcAnswer) {
            $('#hcAnswer').addClass('error');
            $('#err-hcAnswer').text('That doesn\'t look right — please check your answer.').show();
            toastr.warning('Please answer the check correctly to continue.', 'Quick check');
            newHumanCheck();
            return;
        }

        var $btn = $(this).prop('disabled', true).html('<i class="ri-loader-4-line"></i> Creating your account…');
        showProgress();

        $.ajax({
            type: 'POST',
            url: '{{ route("client.registration") }}',
            data: {
                full_name: $('#full_name').val(), phone_number: $('#phone_number').val(),
                email: $('#email').val(), business_name: $('#business_name').val(),
                subscription_plan: $('input[name="subscription_plan"]:checked').val(),
                form_loaded_at: $('#formLoadedAt').val(),
                website: $('#hp_field_a').val(), company_url: $('#hp_field_b').val(),
                _token: '{{ csrf_token() }}'
            },
            timeout: 60000,
            complete: function() { hideProgress(); },
            success: function(data) {
                if (data.status === 201) {
                    $('#step2').hide();
                    $('#stepsNav').hide();
                    $('#termsNotice').hide();
                    $('#successPanel').show();
                    $('#stepDot2').addClass('done').removeClass('active');
                    $('#dot2').html('<i class="ri-check-line" style="font-size:0.85rem;"></i>');
                } else if (data.status === 409) {
                    toastr.warning(data.error || 'An account with this email already exists.', 'Already registered');
                    goBack();
                    showError('email', data.error || 'Email already registered.');
                    $btn.prop('disabled', false).html('<i class="ri-check-double-line"></i> Create my account');
                } else if (data.status === 423) {
                    toastr.warning(data.error || 'Suspicious request detected. Please try again.', 'Blocked');
                    $btn.prop('disabled', false).html('<i class="ri-check-double-line"></i> Create my account');
                } else if (data.status === 422) {
                    var msgs = Array.isArray(data.error) ? data.error.join('\n') : (data.error || 'Please check your details.');
                    toastr.error(msgs, 'Validation Error');
                    goBack();
                    $btn.prop('disabled', false).html('<i class="ri-check-double-line"></i> Create my account');
                } else {
                    toastr.error(data.error || 'Something went wrong. Please try again.', 'Error');
                    $btn.prop('disabled', false).html('<i class="ri-check-double-line"></i> Create my account');
                }
            },
            error: function(xhr) {
                hideProgress();
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(k, v) { showError(k, v[0]); });
                    goBack();
                } else if (xhr.status === 429) {
                    toastr.warning('Too many attempts. Please wait a minute and try again.', 'Slow down');
                } else if (xhr.status === 419) {
                    toastr.error('Session expired. Please refresh the page and try again.', 'Session Error');
                } else {
                    toastr.error('Unable to connect. Please check your internet and try again.', 'Error');
                }
                $btn.prop('disabled', false).html('<i class="ri-check-double-line"></i> Create my account');
            }
        });
    });

});
</script>
@endsection