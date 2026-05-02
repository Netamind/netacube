<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Netacube - The ultimate business management system</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />
    <meta content="Coderthemes" name="author" />

    {{-- ✅ FIX 1: CSRF meta tag for AJAX and token refresh --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!--favicon-->
    <link rel="icon" href="{{ asset('dashboard/images/icon.png') }}" type="image/x-icon">

    {{-- ✅ FIX 2: jQuery FIRST — must load before any script that uses $ --}}
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
        #csrf-expired-notice {
            display: none;
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 12px;
            font-size: 14px;
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
                <div class="col-xxl-4 col-lg-5">
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

                            {{-- ✅ FIX 3: Expired session warning banner --}}
                            <div id="csrf-expired-notice">
                                <strong>Session expired.</strong> The page was refreshed for security. Please log in again.
                            </div>

                            {{-- ✅ FIX 4: Proper POST form with @csrf Blade directive --}}
                            <form action="{{ route('tenant.submit.login') }}" method="POST" id="dataForm">
                                @csrf

                                <div class="mb-3">
                                    <label for="emailaddress" class="form-label">Email address</label>
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
                                        type="password"
                                        id="password"
                                        name="password"
                                        placeholder="Enter your password"
                                        required
                                        autocomplete="current-password"
                                    >
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mt-2 mb-3">
                                    <a href="#" class="text-muted fs-15" id="cancelDataBtn2">Cancel</a>
                                    <a href="{{ route('master.forgot.password') }}" class="text-muted float-end fs-15">
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
                </div><!-- end col -->
            </div><!-- end row -->
        </div><!-- end container -->
    </div><!-- end page -->

    {{-- ✅ FIX 5: Corrected typo  dashbaord → dashboard --}}
    <script src="{{ asset('dashboard/assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/js/app.min.js') }}"></script>

    <script src="{{ asset('library/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('library/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('library/papaparse/papaparse.min.js') }}"></script>
    <script src="{{ asset('library/cropper/cropper.js') }}"></script>

    <script>
        $(document).ready(function () {

            // ✅ FIX 6: Attach CSRF token to every AJAX request globally
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // ✅ FIX 7: Silently refresh CSRF token every 25 minutes
            // Prevents 419 on pages left open in the browser tab
            setInterval(function () {
                $.get('/csrf-refresh', function (data) {
                    if (data.token) {
                        $('meta[name="csrf-token"]').attr('content', data.token);
                        $('input[name="_token"]').val(data.token);
                    }
                }).fail(function () {
                    // Session ended while page was open — warn the user
                    $('#csrf-expired-notice').show();
                    $('#loginBtn')
                        .prop('disabled', true)
                        .html('<i class="ri-refresh-line"></i> Session expired — please refresh the page');
                });
            }, 25 * 60 * 1000);

            // Cancel button clears the form
            $('#cancelDataBtn2').on('click', function (e) {
                e.preventDefault();
                document.getElementById('dataForm').reset();
            });

            // Show expired notice if server flashed it back
            @if(session('csrf_expired'))
                $('#csrf-expired-notice').show();
            @endif

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