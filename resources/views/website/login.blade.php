<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Netacube - The ultimate business management system</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />
    <meta content="Coderthemes" name="author" />

    <!--favicon-->
    <link rel="icon" href="{{asset('dashboard/images/icon.png')}}" type="image/x-icon">
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
                <div class="col-xxl-4 col-lg-5">
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
                                    <input class="form-control" type="email" name="email" placeholder="Enter your email">
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input class="form-control" type="password" name="password" placeholder="Enter your password" autocomplete="off">
                                </div>

                                <div class="mt-2 mb-3">
                                    <a href="#" class="text-muted fs-15" id="cancelDataBtn2">Cancel</a>
                                    <a href="{{ route('master.forgot.password') }}" class="text-muted float-end fs-15">Forgot password?</a>
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
   
    <!-- Vendor js -->
    <script src="{{asset('dashbaord/assets/js/vendor.min.js')}}"></script>
    
    <!-- App js -->
    <script src="{{asset('dashbaord/assets/js/app.min.js')}}"></script>
    
    <script>
        $('#cancelDataBtn2').click(function() {
            document.getElementById('dataForm').reset();
        });
    </script>

    <!-- jQuery -->
    <script src="{{ asset('library/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('library/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('library/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('library/papaparse/papaparse.min.js') }}"></script>
    <script src="{{ asset('library/cropper/cropper.js') }}"></script>
    <script>
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
    </script>
    <!--js toastr notification--> 
</body>
</html>

