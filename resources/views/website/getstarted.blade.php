<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Netacube - Sign Up - The ultimate business management system</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />
    <meta content="Coderthemes" name="author" />

    <!-- favicon -->
    <link rel="icon" href="{{ asset('dashboard/images/icon.png') }}" type="image/x-icon">

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
</head>

<body class="authentication-bg position-relative">
    <div class="position-absolute start-0 end-0 bottom-0 w-100 h-100">
        <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" 
             width="100%" height="100%" preserveAspectRatio="none" viewBox="0 0 1920 1024">
            <g mask="url(#SvgjsMask1046)" fill="none">
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

    
<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height: 8px; transform: rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
</div>

    <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-5 col-lg-6 col-md-8"> <!-- Slightly wider for more fields -->

                    <div class="card">
                        <!-- Logo -->
                        <div class="card-header pt-4 text-center">
                            <div class="auth-brand mb-0">
                                <a href="#" class="logo-dark">
                                    <img src="{{ asset('dashboard/images/netacube1.png') }}" alt="Netacube" style="height:52px">
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <h4 class="text-uppercase ">Create Your Account</h4>
                                <p class="text-muted">Start managing your business with Netacube today</p>
                            </div>

                            <form  action="#" id="registrationForm"  method="POST">
                                @csrf

                                <div class="row g-3">
                                    
                                    <div class="col-md-12">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" name="full_name"  class="form-control" placeholder="Enter your full name">
                                    </div>

                                    <div class="col-md-12">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <input type="text" name="phone_number"  class="form-control" placeholder="Enter your phone number (with country code)">
                                    </div>

                                    <div class="col-12">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email address">
                                    </div>

                                    <div class="col-12">
                                        <label for="business_name" class="form-label">Business / Company Name</label>
                                        <input type="text" name="business_name" id="business_name" class="form-control" placeholder="Enter your business name">
                                    </div>

                                    <div class="col-12">
                                        <label for="subscription_plan" class="form-label">Choose Your Plan</label>
                                        <select name="subscription_plan" id="subscription_plan" class="form-select" >
                                            <option value="" disabled selected>Select subscription period</option>
                                            <?php $plans = DB::table('subscription_plans')->get();?>
                                            @if($plans)
                                            @foreach($plans as $plan)
                                            <option value="{{ $plan->id }}">
                                                {{ $plan->plan_name }} — {{ $plan->plan_amount }} USD / {{ $plan->plan_period }}
                                            </option>
                                             @endforeach
                                            @else
                                                <option value="" disabled>No plans available right now</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>

                                <!-- Hidden field (keeping your original) -->
                                <input type="hidden" name="licensenumber" value="">

                                <div class="mt-4">
                                    <button type="submit" id="submitDataBtn" 
                                            class="btn btn-primary w-100">
                                        <i class="ri-user-add-line me-1"></i> Create Account
                                    </button>
                                </div>

                                <div class="mt-4 text-center">
                                    <p class="text-muted mb-0">
                                        Already have an account? 
                                        <a href="/login" class="text-primary">Sign in here</a>
                                    </p>
                                </div>

                                <div class="mt-3 text-center">
                                    <a href="/contact" class="text-muted fs-15">
                                        Need help? Contact support <i class="ri-send-plane-fill"></i>
                                    </a>
                                </div>
                            </form>
                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
            </div> <!-- end row -->
        </div> <!-- end container -->
    </div> <!-- end account-pages -->

    <!-- Scripts -->
    <script src="{{ asset('dashboard/assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/js/app.min.js') }}"></script>

    <!-- jQuery -->
    <script src="{{ asset('library/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('library/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('library/toastr/toastr.min.js') }}"></script>

    <script>



$('#submitDataBtn').click(function (e) {
    e.preventDefault();
    var self = $(this); 
    self.prop('disabled', true);

    var formData = $('#registrationForm').serialize();

    $.ajax({
        type: 'POST',
        url: "{{ route('client.registration') }}",
        data: formData + '&_token={{ csrf_token() }}',
        timeout: 60000,
        beforeSend: function () { 
            $('#progressBar').show(); 
        },
        complete: function () { 
            $('#progressBar').hide(); 
            self.prop('disabled', false); 
        },
        success: function (data) {
            if (data.status === 201) {
                toastr.success(data.success || 'Registration successful!', 'Success');
               $('#registrationForm')[0].reset();
            } 
            else if (data.status === 422) {
                var errorPassage = ''; $.each(data.responseJSON.errors, function (k, v) { errorPassage += v + '\n'; });
                toastr.error(errorPassage, 'Validation Errors');
            }
            else if (data.status === 423) {
                toastr.warning(data.error || 'Suspicious request detected', 'Warning');
            }
            else if (data.status === 500) {
                toastr.error(data.error || 'Server error occurred', 'Server Error');
            }
            else {
                toastr.error(data.error || 'Unexpected response from server', 'Error');
            }
        },
        error: function (xhr, status, error) {
            if (status === 'timeout') {
                toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout');
            } 
            else if (xhr.status === 0) {
                toastr.error('Cannot connect to server. Check your internet or firewall.', 'Connection Failed');
            } 
            else if (xhr.status === 423) {
                toastr.error( resp.error ||'Suspicious request detected.', 'Warning');
            } 
            else if (xhr.status === 422) {
                var errorPassage = ''; $.each(xhr.responseJSON.errors, function (k, v) { errorPassage += v + '\n'; });
                toastr.error(errorPassage, 'Validation Errors');
            }
            else if (xhr.status === 500) {
                toastr.error('Server encountered an error. Please try again later.', 'Server Error');
            } 
            else if (xhr.status === 419) {
                toastr.error('Session expired or invalid CSRF token. Please refresh the page.', 'CSRF / Session Error');
            } 
            else {
                toastr.error('Unexpected error (' + xhr.status + '). Please try again.', 'Error');
            }
        }
    });
});
    </script>
</body>
</html>