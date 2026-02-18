@extends('website.homepage')

@section('title', 'Sign Up - Start with Netacube Today')

@section('styles')
@endsection

@section('content')

    <!-- Hero Section - Consistent with other main pages -->
    <section class="bg-half-260 bg-primary d-table w-100" style="background: url('website/assets/images/software/bg.png') center center;">
        <div class="bg-overlay"></div>
        <div class="container">
            <div class="row align-items-center position-relative mt-5" style="z-index: 1;">
                <div class="col-lg-8 col-md-12 text-center text-lg-start">
                    <div class="title-heading mt-4">
                        <h1 class="heading mb-3 text-white">Create Your Account</h1>
                        <p class="para-desc text-white-50 mx-auto mx-lg-0" style="max-width: 780px;">
                            Join hundreds of businesses already using Netacube to manage sales, inventory, employees, 
                            payroll and more — all in one powerful, reliable platform.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Registration Form Section -->
    <section class="section-uniform bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">

                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                        <!-- Top accent bar -->
                        <div style="height: 6px; background: linear-gradient(90deg, #4B5EBD, #576CC0);"></div>
                        
                        <div class="card-body p-5 p-lg-5">
                            <div class="text-center mb-5">
                                <h3 class="fw-bold mb-2">Get Started with Netacube</h3>
                                <p class="text-muted mb-0">
                                    Fill in your details below — we'll send your login credentials and next steps to your email
                                </p>
                            </div>

                            <form method="POST" action="/client-registration" id="registrationForm">
                                @csrf

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Full Name</label>
                                        <input type="text" name="full_name" class="form-control form-control-lg" 
                                               placeholder="John Mwale" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Phone Number</label>
                                        <input type="tel" name="phone_number" class="form-control form-control-lg" 
                                               placeholder="+265 888 123 456" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold">Email Address</label>
                                        <input type="email" name="email" class="form-control form-control-lg" 
                                               placeholder="your.business@email.com" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold">Business / Company Name</label>
                                        <input type="text" name="business_name" class="form-control form-control-lg" 
                                               placeholder="Mwale Retail Ltd" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold">Choose Your Plan</label>
                                        <select name="subscription_plan" class="form-select form-select-lg" required>
                                            <option value="" disabled selected>Select subscription period</option>
                                            @if(isset($subscriptionPlans) && $subscriptionPlans->count() > 0)
                                                @foreach($subscriptionPlans as $plan)
                                                    <option value="{{ $plan->id }}">
                                                        {{ $plan->plan_name }} — {{ $plan->plan_amount }} USD 
                                                        / {{ $plan->plan_period }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="" disabled>No plans available right now</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>

                                <!-- Hidden field (as in your original) -->
                                <input type="hidden" name="licensenumber" value="">

                                <div class="mt-5 text-center">
                                    <button type="submit" id="submitBtn" 
                                            class="btn btn-primary btn-lg px-5 py-3 fw-bold">
                                        Create My Account
                                    </button>
                                </div>

                                <div class="text-center mt-4">
                                    <p class="text-muted mb-0">
                                        Already have an account? 
                                        <a href="/login" class="text-primary fw-bold">Sign in here</a>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Trust signals below form -->
                    <div class="row justify-content-center mt-5">
                        <div class="col-auto text-center">
                            <p class="text-muted small mb-2">
                                <i class="uil uil-lock me-1"></i> Secure registration • 
                                <i class="uil uil-envelope me-1 ms-2"></i> Instant login details • 
                                <i class="uil uil-shield-check me-1 ms-2"></i> Enterprise-grade protection
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationForm');
    const submitBtn = document.getElementById('submitBtn');

    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Creating Account...';
        });
    }
});
</script>
@endsection