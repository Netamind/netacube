<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Netacube - The ultimate business management system</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />
    <meta content="Coderthemes" name="author" />

    {{-- CSRF meta tag — needed so the refresh script below can read/update the token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!--favicon-->
    <link rel="icon" href="{{asset('dashboard/images/icon.png')}}" type="image/x-icon">

    {{-- jQuery FIRST — must load before any script that uses $ (it was loaded
         near the bottom before, after a script that already called $(...).click(...)) --}}
    <script src="{{ asset('library/jquery/jquery.min.js') }}"></script>

    <!-- Theme Config Js -->
    <script src="{{asset('dashboard/assets/js/config.js')}}"></script>
  
    <!-- App css -->
    <link href="{{asset('dashboard/assets/css/app.min.css')}}" rel="stylesheet" type="text/css" id="app-style" />
    <!-- Icons css -->
    <link href="{{asset('dashboard/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
    <!-- Remixicons -->
    <link href="{{asset('dashboard/assets/remixicons/remixicon.css')}}" rel="stylesheet" type="text/css" />

    <!-- Toastr -->
    <link href="{{ asset('library/toastr/toastr.min.css') }}" rel="stylesheet" type="text/css" />

    <style>
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
    <div class="position-absolute start-0 end-0 start-0 bottom-0 w-100 h-100">
        <svg xmlns="#" version="1.1" xmlns:xlink="" xmlns:svgjs="#" width="100%" height="100%" preserveAspectRatio="none" viewBox="0 0 1920 1024">
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
                <linearGradient x1="11.67%" y1="-21.87%" x2="88.33%" y2="121.88%" gradientUnits="userSpaceOnUse" id="SvgjsLinearGradient1047">
                    <stop stop-color="#0e2a47" offset="0"></stop>
                    <stop stop-color="#00459e" offset="1"></stop>
                </linearGradient>
            </defs>
        </svg>
    </div>

    <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
        <div class="container">
            <div class="row justify-content-center">
                {{-- Narrowed column + fixed max-width wrapper, same as the other auth pages --}}
                <div class="col-11 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-3">
                    <div class="auth-card-wrap">
                    <div class="card">
                        <!-- Logo -->
                        <div class="card-header pt-4 text-center">
                            <div class="auth-brand mb-0">
                                <a href="#" class="logo-dark">
                                    <img src="{{asset('dashboard/images/netacube1.png')}}" alt="" style="height:52px">
                                </a>
                            </div>
                        </div>

                        <div class="card-body">

                            <form action="{{route('submit.login.by.code')}}" method="post" id="dataForm">
                                @csrf

                                <div class="mb-3">
                                    <label for="code" class="form-label">Client Code</label>
                                    <input class="form-control" type="text" name="code" placeholder="Enter your code">
                                </div>


                                  <div class="mb-3">
                                    <label for="emailaddress" class="form-label">Email address</label>
                                    {{-- autocomplete left on so the browser's email autosuggest still works --}}
                                    <input class="form-control" type="email" id="emailaddress" name="email" placeholder="Enter your email" autocomplete="email">
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    {{-- Same masked-password trick as the main login page — plain text
                                         field with no real "password" semantics, so the browser never offers
                                         to save it. Real value lives only in the hidden #password-actual field. --}}
                                    <input class="form-control" type="text" id="password" placeholder="Enter your password" autocomplete="off">
                                    <input type="hidden" id="password-actual" name="password">
                                </div>

                                <div class="mt-2 mb-3">
                                    <a href="#" class="text-muted fs-15" id="cancelDataBtn2">Cancel</a>
                                    {{-- Forgot password no longer routes to the master area --}}
                                    <a href="#" class="text-muted float-end fs-15">Forgot password?</a>
                                </div>

                                <div class="text-center">
                                    <button class="btn btn-primary form-control" type="submit"> <i class="ri-lock-fill"></i> Login </button>
                                </div>

                                <div class="mt-4 mb-2 text-center">
                                    <a href="/contact" class="text-muted fs-15">Contact support <i class="ri-send-plane-fill"></i></a>
                                </div>
                            </form>
                        </div> <!-- end card-body -->
                    </div>
                    <!-- end card -->
                    </div><!-- end auth-card-wrap -->
                </div> <!-- end col -->
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>
    <!-- end page -->

    <!--<footer class="footer footer-alt">
        <span class="text-white-50"><script>document.write(new Date().getFullYear())</script> © Netamind Technology</span>
    </footer>-->

    {{-- Corrected typo  dashbaord → dashboard --}}
    <!-- Vendor js -->
    <script src="{{asset('dashboard/assets/js/vendor.min.js')}}"></script>

    <!-- App js -->
    <script src="{{asset('dashboard/assets/js/app.min.js')}}"></script>

    <script src="{{ asset('library/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('library/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('library/papaparse/papaparse.min.js') }}"></script>
    <script src="{{ asset('library/cropper/cropper.js') }}"></script>

    {{-- Password masking, ported as-is from the main login page --}}
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
        ✅ FIX (this login page can also sit open indefinitely, same as the
        main tenant login page): it had no CSRF meta tag and no refresh loop
        at all, so a token here would go stale after the app's default
        session.lifetime with nothing to recover it — submit would just
        419 with no graceful path back. Same non-fatal refresh pattern
        applied here for consistency: keep the token current in the
        background; any failure is silently retried next cycle and never
        shown to the user or used to disable the form.
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
            // for as long as needed without ever going stale.
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

            $('#cancelDataBtn2').click(function() {
                document.getElementById('dataForm').reset();
            });

            @if(Session::has('message'))
                var type = "{{ Session::get('alert-type', 'info') }}";
                switch(type){
                    case 'info':
                        toastr.info("{{ Session::get('message') }}", 'Info',{timeOut: 5000, progressBar: true});
                        break;
                    case 'warning':
                        toastr.warning("{{ Session::get('message') }}", 'Warning',{timeOut: 5000, progressBar: true});
                        break;
                    case 'success':
                        toastr.success("{{ Session::get('message') }}", 'Success',{timeOut: 5000, progressBar: true});
                        break;
                    case 'error':
                        toastr.error("{{ Session::get('message') }}", 'Error',{timeOut: 5000, progressBar: true});
                        break;
                }
            @endif

        });
    </script>
    <!--js toastr notification--> 
</body>
</html>