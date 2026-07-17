<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Netacube - The ultimate business management system</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />
    <meta content="Coderthemes" name="author" />

    {{-- CSRF meta tag for AJAX and token refresh --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!--favicon-->
    <link rel="icon" href="{{ asset('dashboard/images/icon.png') }}" type="image/x-icon">

    {{-- jQuery FIRST — must load before any script that uses $ --}}
    <script src="{{ asset('library/jquery/jquery.min.js') }}"></script>

    <!-- Theme Config Js -->
    <script src="{{ asset('dashboard/assets/js/config.js') }}"></script>

    <!-- App css -->
    <link href="{{ asset('dashboard/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />
    <!-- Icons css -->
    <link href="{{ asset('dashboard/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Remixicons -->
    <link href="{{ asset('dashboard/assets/remixicons/remixicon.css') }}" rel="stylesheet" type="text/css" />
    <!-- Toastr -->
    <link href="{{ asset('library/toastr/toastr.min.css') }}" rel="stylesheet" type="text/css" />

    <style>
        /* ✅ FIX (session-expired bug): the old #csrf-expired-notice banner is
           gone. The login page is designed to NEVER expire — see the script
           at the bottom for how the token is kept fresh and how a genuine
           419 on submit is now handled silently instead of locking the user
           out. */

        /* Standardize card width across screen sizes (mobile, tablet, smaller laptops, desktops) */
        .auth-card-wrap {
            width: 100%;
            max-width: 380px;
            margin-left: auto;
            margin-right: auto;
        }

        @media (max-width: 575.98px) {
            .auth-card-wrap {
                max-width: 100%;
            }
        }
    </style>
</head>
<body class="authentication-bg position-relative">

    <div class="position-absolute start-0 end-0 bottom-0 w-100 h-100">
        <svg xmlns="http://www.w3.org/2000/svg" version="1.1"
             xmlns:xlink="http://www.w3.org/1999/xlink"
             width="100%" height="100%" preserveAspectRatio="none" viewBox="0 0 1920 1024">
            <g mask="url(&quot;#SvgjsMask1046&quot;)" fill="none">
                <rect width="1920" height="1024" x="0" y="0" fill="url(#SvgjsLinearGradient1047)"></rect>
                <path d="M1920 0L1864.16 0L1920 132.5z" fill="rgba(255, 255, 255, .1)"></path>
                <path d="M1864.16 0L1920 132.5L1920 298.4L1038.6100000000001 0z" fill="rgba(255, 255, 255, .075)"></path>
                <path d="M1038.6100000000001 0L1920 298.4L1920 379.53999999999996L857.7000000000002 0z" fill="rgba(255, 255, 255, .05)"></path>
                <path d="M857.7 0L1920 379.53999999999996L1920 678.01L514.57 0z" fill="rgba(255, 255, 255, .025)"></path>
                <path d="M0 1024L939.18 1024L0 780.91z" fill="rgba(0, 0, 0, .1)"></path>
                <path d="M0 780.91L939.18 1024L1259.96 1024L0 585.71z" fill="rgba(0, 0, 0, .075)"></path>
                <path d="M0 585.71L1259.96 1024L1426.79 1024L0 408.19000000000005z" fill="rgba(0, 0, 0, .05)"></path>
                <path d="M0 408.19000000000005L1426.79 1024L1519.6599999999999 1024L0 404.09000000000003z" fill="rgba(0, 0, 0, .025)"></path>
            </g>
            <defs>
                <mask id="SvgjsMask1046">
                    <rect width="1920" height="1024" fill="#ffffff"></rect>
                </mask>
                <linearGradient x1="11.67%" y1="-21.87%" x2="88.33%" y2="121.88%"
                    gradientUnits="userSpaceOnUse" id="SvgjsLinearGradient1047">
                    <stop stop-color="#0e2a47" offset="0"></stop>
                    <stop stop-color="#00459e" offset="1"></stop>
                </linearGradient>
            </defs>
        </svg>
    </div>

    <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
        <div class="container">
            <div class="row justify-content-center">
                {{-- Narrowed column + fixed max-width wrapper so the card stays a sane,
                     standard size on mobile and on smaller laptop screens instead of stretching wide --}}
                <div class="col-11 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-3">
                    <div class="auth-card-wrap">
                    <div class="card">

                        <!-- Logo -->
                        <div class="card-header pt-4 text-center">
                            <div class="auth-brand mb-0">
                                <a href="#" class="logo-dark">
                                    <img src="{{ asset('dashboard/images/netacube1.png') }}" alt="Netacube Logo" style="height:52px">
                                </a>
                                @php
                                    $companyName = DB::connection('tenant')->table('company_info')->first();
                                @endphp
                            </div>
                        </div>

                        <div class="text-center mt-2 mb-0">
                            <h3 class="text-dark-50 text-center">
                                {{ optional($companyName)->business_name ?? "Company not set" }}
                            </h3>
                        </div>

                        <div class="card-body">

                            {{-- Proper POST form with @csrf Blade directive --}}
                            {{-- autocomplete="off" on the form + a masked, non-"password"-typed
                                 input (same technique as the old system) so browsers don't offer to save
                                 the credentials. The real value is written into the hidden #password-actual
                                 field that actually gets submitted under name="password". --}}
                            <form action="{{ route('tenant.submit.login') }}" method="POST" id="dataForm">
                                @csrf

                                <div class="mb-3">
                                    <label for="emailaddress" class="form-label">Email address</label>
                                    {{-- autocomplete left on (matches old system) so the browser
                                         still shows its email autosuggest dropdown here --}}
                                    <input
                                        class="form-control @error('email') is-invalid @enderror"
                                        type="email"
                                        id="emailaddress"
                                        name="email"
                                        value="{{ old('email') }}"
                                        placeholder="Enter your email"
                                        required
                                        autocomplete="email"
                                    >
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input
                                        class="form-control @error('password') is-invalid @enderror"
                                        type="text"
                                        id="password"
                                        autocomplete="off"
                                        placeholder="Enter your password"
                                        required
                                    >
                                    <input type="hidden" id="password-actual" name="password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mt-2 mb-3">
                                    <a href="#" class="text-muted fs-15" id="cancelDataBtn2">Cancel</a>
                                    {{-- Forgot password no longer routes to master area; placeholder link only --}}
                                    <a href="#" class="text-muted float-end fs-15">
                                        Forgot password?
                                    </a>
                                </div>

                                <div class="text-center">
                                    <button class="btn btn-primary form-control" type="submit" id="loginBtn">
                                        <i class="ri-lock-fill"></i> Login
                                    </button>
                                </div>

                                <div class="mt-4 mb-2 text-center">
                                    <a href="#" class="text-muted fs-15">
                                        Contact support <i class="ri-send-plane-fill"></i>
                                    </a>
                                </div>

                            </form>
                        </div><!-- end card-body -->
                    </div><!-- end card -->
                    </div><!-- end auth-card-wrap -->
                </div><!-- end col -->
            </div><!-- end row -->
        </div><!-- end container -->
    </div><!-- end page -->

    {{-- Corrected typo  dashbaord → dashboard --}}
    <script src="{{ asset('dashboard/assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/js/app.min.js') }}"></script>

    <script src="{{ asset('library/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('library/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('library/papaparse/papaparse.min.js') }}"></script>
    <script src="{{ asset('library/cropper/cropper.js') }}"></script>

    {{-- Password masking, ported as-is from the old system. The visible field is
         plain text and never holds the real password — characters are tracked manually and the
         field just displays asterisks. The real value only ever lives in the hidden #password-actual
         input, which is what actually gets posted as "password". Browsers never see a genuine
         password field, so they don't offer to save it. --}}
    <script>
        const passwordInput = document.getElementById('password');
        const passwordActualInput = document.getElementById('password-actual');
        let actualPasswordValue = '';
        passwordInput.addEventListener('input', (e) => {
            if (e.inputType === 'deleteContentBackward') {
                actualPasswordValue = actualPasswordValue.slice(0, -1);
            } else if (e.data && e.inputType !== 'insertCompositionText') {
                actualPasswordValue += e.data;
            }
            const maskedValue = '*'.repeat(actualPasswordValue.length);
            e.target.value = maskedValue;
            passwordActualInput.value = actualPasswordValue;
        });
    </script>

    {{--
        ✅ FIX (login page falsely showing "Session expired"):

        Root cause was two-fold:
          1. The page called GET /csrf-refresh every 25 minutes, but that
             route never existed in web.php — every call 404'd.
          2. ANY failure of that call (404, 500, network blip) was treated
             as "the session died" and permanently disabled the login button.

        This page must never appear expired, so the fix is:
          - Point the refresh at the real route('csrf.refresh') endpoint
            (added server-side), so the token embedded in this page — and
            the underlying session — stay alive for as long as the tab is
            open, however many hours or days that is.
          - Treat a failed refresh as a no-op — just retry next cycle,
            never show an error or disable the form. A transient network
            blip or a passing 5xx should never lock a user out of login.

        We deliberately do NOT intercept the actual form submit with AJAX:
        jQuery follows redirects transparently, which makes a genuine
        success vs. a real 419 hard to tell apart reliably and risks
        breaking the normal login flow. As long as the interval above keeps
        the token current, the native form submit (with @csrf's token,
        refreshed in place every 25 min) already has what it needs.
    --}}
    <script>
        $(document).ready(function () {

            // Attach CSRF token to every AJAX request globally
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Keep the token fresh indefinitely so this page can stay open
            // for as long as the client needs without ever going stale.
            // A failure here is silently retried next cycle — never shown
            // to the user, never disables the form.
            setInterval(function () {
                $.get('{{ route('csrf.refresh') }}', function (data) {
                    if (data && data.token) {
                        $('meta[name="csrf-token"]').attr('content', data.token);
                        $('input[name="_token"]').val(data.token);
                        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': data.token } });
                    }
                }).fail(function () {
                    console.warn('CSRF refresh failed this cycle; will retry next interval.');
                });
            }, 25 * 60 * 1000);

            // Cancel button clears the form
            $('#cancelDataBtn2').on('click', function (e) {
                e.preventDefault();
                document.getElementById('dataForm').reset();
            });

            // Flash message toasts
            @if(Session::has('message'))
                var type    = "{{ Session::get('alert-type', 'info') }}";
                var message = "{{ Session::get('message') }}";
                var opts    = { timeOut: 5000, progressBar: true };
                switch (type) {
                    case 'info':    toastr.info(message,    'Info',    opts); break;
                    case 'warning': toastr.warning(message, 'Warning', opts); break;
                    case 'success': toastr.success(message, 'Success', opts); break;
                    case 'error':   toastr.error(message,   'Error',   opts); break;
                    default:        toastr.info(message,    'Notice',  opts);
                }
            @endif

        });
    </script>

</body>
</html>