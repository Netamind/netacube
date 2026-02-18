@extends('website.homepage')

@section('title', 'Netacube Pricing')

@section('styles')
  
@endsection

@section('content')

    <!-- Hero Section -->
    <section class="bg-half-260 bg-primary d-table w-100" style="background: url('website/assets/images/software/bg.png') center center;">
        <div class="bg-overlay"></div>
        <div class="container">
            <div class="row align-items-center position-relative mt-5" style="z-index: 1;">
                <div class="col-lg-7 col-md-12 text-center text-lg-start">
                    <div class="title-heading mt-4">
                        <h1 class="heading mb-3 text-white">Pricing</h1>
                        <p class="para-desc text-white-50 mx-auto mx-lg-0">
                            Flexible payment terms with full access to all features — choose the duration that best suits your business needs.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section - White background -->
    <section class="section-uniform bg-white">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 text-center">
                    <div class="section-title mb-4 pb-2">
                        <h4 class="title mb-4">Select the Plan That Best Suits Your Business</h4>
                        <p class="text-muted para-desc mb-0 mx-auto">
                            Every plan provides complete and unrestricted access to the entire Netacube system. The only difference is the payment period you choose — we offer flexible options to accommodate your preferred commitment level, with greater value for longer-term partnerships. You may upgrade to a longer plan at any time and receive prorated credit for the remaining period.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 col-md-6 col-12 mt-4 pt-2">
                    <div class="card pricing pricing-primary business-rate shadow bg-light border-0 rounded h-100">
                        <div class="card-body d-flex flex-column">
                            <h6 class="title name fw-bold text-uppercase mb-4">6 Months</h6>
                            <div class="d-flex mb-4 justify-content-center">
                                <span class="h1 mb-0 text-primary fw-bold">$120</span>
                                <span class="h5 align-self-end mb-1 text-muted ms-2">USD total</span>
                            </div>
                            <p class="text-muted text-center mb-4 flex-grow-1">
                                Ideal for businesses seeking shorter-term flexibility and the ability to start immediately with a 6-month commitment.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-12 mt-4 pt-2">
                    <div class="card pricing pricing-primary business-rate shadow border-0 rounded h-100 position-relative overflow-hidden bg-light">
                        <!-- Original ribbon style maintained - only margins adjusted to prevent cutting -->
                        <div class="ribbon ribbon-right ribbon-warning overflow-hidden" style="margin-top: 1.25rem; margin-right: 1.25rem;">
                            <span class="text-center d-block shadow small h6">Most Popular</span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h6 class="title name fw-bold text-uppercase mb-4">1 Year</h6>
                            <div class="d-flex mb-4 justify-content-center">
                                <span class="h1 mb-0 text-primary fw-bold">$220</span>
                                <span class="h5 align-self-end mb-1 text-muted ms-2">USD total</span>
                            </div>
                            <p class="text-muted text-center mb-4 flex-grow-1">
                                Balanced commitment with enhanced value — the perfect blend of flexibility and savings for growing businesses.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-12 mt-4 pt-2">
                    <div class="card pricing pricing-primary business-rate shadow bg-light border-0 rounded h-100">
                        <div class="card-body d-flex flex-column">
                            <h6 class="title name fw-bold text-uppercase mb-4">2 Years</h6>
                            <div class="d-flex mb-4 justify-content-center">
                                <span class="h1 mb-0 text-primary fw-bold">$400</span>
                                <span class="h5 align-self-end mb-1 text-muted ms-2">USD total</span>
                            </div>
                            <p class="text-muted text-center mb-4 flex-grow-1">
                                Maximum value with approximately 33% savings compared to shorter terms — designed for long-term partnership and stability.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Closing value section -->
    <section class="section-uniform bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h4 class="title mb-4">Value That Grows With Your Business</h4>
                    <p class="text-muted para-desc mx-auto mb-0" style="max-width: 780px;">
                        Our pricing is designed to be straightforward and fair — no hidden fees, no feature restrictions. 
                        Every plan gives you complete access to the full Netacube system, including offline functionality, 
                        enterprise-grade security, multi-branch support, and ongoing updates. 
                        The longer you commit, the more you save, allowing your business to scale confidently while keeping costs predictable.
                    </p>
                </div>
            </div>
        </div>
    </section>

@endsection