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
           <!-- Remixicons  -->
        <link href="{{asset('dashboard/assets/remixicons/remixicon.css')}}" rel="stylesheet" type="text/css" />
   
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
                                        <span><img src="{{asset('dashboard/images/netacube1.png')}}" alt="" height="50"></span>
                                    </a>
                                    <a href="#" class="logo-light">
                                        <span><img src="assets/images/logo.png" alt="logo" height="28"></span>
                                    </a>
                                </div>
                            </div>

                        
                         <div class="card-body p-4">
                            <div class="text-center">
                                     <h1 class="text-error">5<i class="ri-emotion-sad-line"></i> <i class="ri-emotion-sad-line"></i>  </h1>
                                <h4 class="text-uppercase text-danger mt-3"></h4>
                                <p class="text-muted mt-3">
                                {{$response}}
                               </p>
                                <a class="btn btn-info mt-3" href="https://wa.me/265992522601?text=Hello%20Netacube%20Having%20Issues%20With%20Client%20Not%20Found">
                                    <i class="ri-whatsapp-line"></i> Contact Administrator
                              </a>
                            </div>
                        </div> <!-- end card-body-->


                          
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
    </body>
</html>
