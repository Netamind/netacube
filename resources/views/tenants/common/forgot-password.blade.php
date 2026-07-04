<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="utf-8" />
         <title>Netacube - Password Reset</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />
        <meta content="Coderthemes" name="author" />

        {{-- ✅ FIX 1: CSRF meta tag was missing — needed by the AJAX header below --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">

         <!--favicon-->
	    <link rel="icon" href="{{asset('dashboard/images/icon.png')}}" type="image/x-icon">

        <!-- Theme Config Js -->
        <script src="{{asset('dashboard/assets/js/config.js')}}"></script>
      
        <!-- App css -->
        <link href="{{asset('dashboard/assets/css/app.min.css')}}" rel="stylesheet" type="text/css" id="app-style" />

        <!-- Icons css -->
        <link href="{{asset('dashboard/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />

           <!-- Remixicons  -->
        <link href="{{asset('dashboard/assets/remixicons/remixicon.css')}}" rel="stylesheet" type="text/css" />

        <!-- Toastr -->
        <link href="{{ asset('library/toastr/toastr.min.css') }}" rel="stylesheet" type="text/css" />

        {{-- ✅ FIX 2: Standardize card width across screen sizes (mobile, tablet, smaller laptops, desktops) --}}
        <style>
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

        
    
    <div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height: 8px; transform: rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
    </div>

        <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
            <div class="container">
                <div class="row justify-content-center">
                    {{-- ✅ FIX 3: Narrowed column so the card stays a standard, sane size on mobile and smaller laptops --}}
                    <div class="col-11 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-3">
                        <div class="auth-card-wrap">
                        <div class="card">

                            <!-- Logo -->
                            <div class="card-header pt-4 text-center">
                                <div class="auth-brand mb-0">
                            
                                <a href="#" class="logo-dark">
                                <img src="{{asset('dashboard/images/netacube1.png')}}" alt="" style="height:52px">
                                </a>

                                 <?php
                                    
                                  $companyNeme = DB::table('company_info')->first();
                             
                                 ?>   
                            </div>
                            </div>

                            <div class="text-center mt-2 mb-0">
                            <h3 class="text-dark-50 text-center">{{optional($companyNeme)->business_name ?? "Company not set"}}</h3>
                            </div>

                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <h4 class="text-dark-50">Reset Your Password</h4>
                                    <p class="text-muted">Enter your email address to receive a password reset link.</p>
                                </div>

                                <form  method="post" id="dataForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="emailaddress" class="form-label">Email address</label>
                                        <input class="form-control" type="email" name="email" placeholder="Enter your email" required>
                                    </div>

                                    <div class="mt-2 mb-3">
                                        <a href="#" class="text-muted fs-15" id="cancelDataBtn2">Cancel</a>
                                        {{-- ✅ FIX 4: No longer routes to the master login page --}}
                                        <a href="#" class="text-muted float-end fs-15">Back to Login</a>
                                    </div>

                                    <div class="text-center">
                                        <button class="btn btn-primary form-control" id="sendPasswordResetLinkBtn"> <i class="ri-mail-send-fill"></i> Send Reset Link </button>
                                    </div>

                                    <div class="mt-4 mb-2 text-center">
                                        <a href="#" class="text-muted fs-15">Contact support <i class="ri-send-plane-fill"></i></a>
                                    </div>
                                </form>
                            </div> <!-- end card-body -->
                        </div>
                        <!-- end card -->
                        </div> <!-- end auth-card-wrap -->
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
       
       <!-- jQuery -->
    <script src="{{ asset('library/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('library/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('library/toastr/toastr.min.js') }}"></script>

    <!-- Vendor js -->
    <script src="{{ asset('dashboard/assets/js/vendor.min.js') }}"></script>
    
    <!-- App js -->
    <script src="{{ asset('dashboard/assets/js/app.min.js') }}"></script>

    <!-- AJAX Form Submission -->
    <script> 
     $(document).ready(function() {

     var Toast = Swal.mixin({
         toast: true,
         position: 'top-end',
         showConfirmButton: false,
         timer: 12000
     });

       $('#sendPasswordResetLinkBtn').click(function(e) {
        var self = $(this);
        $(this).prop("disabled", true);
        var form = document.getElementById("dataForm");
        e.preventDefault(); 
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            type:"post",
            url: "{{ route('master.password.reset.link') }}",
            data: $(form).serialize(),
            timeout: 60000,
            timeout: 60000,
             beforeSend: function() {
                 $('#progressBar').show();
             },
             complete: function() {
                 $('#progressBar').hide();
                 self.prop("disabled", false);
             },
            success: function(data) {
                if(data.status===201){
                toastr.success(data.success,'Success',{ timeOut : 10000 ,	progressBar: true});
                }else if(data.status===400){
                toastr.error(data.error,'Error',{ timeOut : 5000 , 	progressBar: true})  
                }else{
                toastr.error('Unspecified error occured try again later','Unspecified Error',{ timeOut : 5000 , 	progressBar: true}); 
                }
            },
            error: function(xhr, status, error) {
            if (xhr.status === 0 && xhr.readyState === 0) {
                toastr.error('Timeout check your internet connect and try again','Timeout Error',{ timeOut : 5000 , 	progressBar: true})  
            } else if (xhr.status === 422) {
                var errorPassage = '';
                var errors = xhr.responseJSON.errors;
                $.each(errors, function(key, value) { errorPassage += value + '\n'});
                toastr.error(errorPassage, 'Validation Errors', {timeOut: 5000, 	progressBar: true});
            } else if (xhr.status === 500) {
                var errorMessage = xhr.responseText;
                toastr.error('Internal server error occured try again later', 'Server Error', {timeOut: 5000 , 	progressBar: true});
            } else {
            toastr.error('Unspecified error occured try again later', 'Unspecified Error',{timeOut: 5000 ,	progressBar: true});
            }
            }  
            });
        });



    });
   </script>
    </body>
</html>