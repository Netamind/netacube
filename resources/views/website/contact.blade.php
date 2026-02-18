@extends('website.homepage')

@section('title', 'Contact Netacube - Get in Touch')

@section('styles')
  
@endsection

@section('content')

    <!-- Hero Section - Contact focused -->
    <section class="bg-half-260 bg-primary d-table w-100" style="background: url('website/assets/images/software/bg.png') center center;">
        <div class="bg-overlay"></div>
        <div class="container">
            <div class="row align-items-center position-relative mt-5" style="z-index: 1;">
                <div class="col-lg-8 col-md-12 text-center text-lg-start">
                    <div class="title-heading mt-4">
                        <h1 class="heading mb-3 text-white">Get in Touch</h1>
                        <p class="para-desc text-white-50 mx-auto mx-lg-0">
                            We're here to help! Whether you need a demo, have questions about features, need support, 
                            or want to discuss how Netacube can transform your business — our team is ready to assist you 24/7.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Information Cards Grid -->
    <section class="section-uniform bg-white">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-12">
                    <h4 class="title mb-5">Reach Us Through Your Preferred Channel</h4>
                </div>
            </div>

            <div class="row g-4">
                <!-- Email -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-envelope-check h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Email Us</h5>
                        <p class="text-muted mb-3">
                            Get the fastest written response during business hours
                        </p>
                        <a href="mailto:info@netamind.com" class="btn btn-outline-primary btn-sm">
                            info@netamind.com
                        </a>
                    </div>
                </div>

                <!-- WhatsApp / Phone -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-whatsapp h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">WhatsApp / Call</h5>
                        <p class="text-muted mb-3">
                            Quick answers & real-time support (preferred method)
                        </p>
                        <a href="https://wa.me/+265888377462" target="_blank" class="btn btn-outline-primary btn-sm">
                            +265 888 377 462
                        </a>
                    </div>
                </div>

                <!-- Location -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-map-marker-alt h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Our Location</h5>
                        <p class="text-muted mb-3">
                            Lilongwe, Malawi<br>
                            (Office visits by appointment only)
                        </p>
                        <a href="https://wa.me/+265888377462" class="btn btn-outline-primary btn-sm">
                            Schedule Visit
                        </a>
                    </div>
                </div>

                <!-- Response Time -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-clock h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Response Time</h5>
                        <p class="text-muted mb-0">
                            Usually within 1–4 hours during business hours<br>
                            24/7 availability via WhatsApp for urgent matters
                        </p>
                    </div>
                </div>

                <!-- Support Scope -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-headset h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">What We Help With</h5>
                        <p class="text-muted mb-0">
                            Demos • Feature questions • Pricing • Onboarding • Technical support • Data migration • Custom requests
                        </p>
                    </div>
                </div>

                <!-- Business Hours -->
                <div class="col-lg-4 col-md-6">
                    <div class="features feature-primary text-center hover-lift shadow-sm rounded p-4 h-100 bg-light">
                        <div class="image position-relative d-inline-block mb-3">
                            <i class="uil uil-calendar-alt h2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Business Hours</h5>
                        <p class="text-muted mb-0">
                            Monday – Friday: 8:00 AM – 5:00 PM CAT<br>
                            Saturday: 9:00 AM – 1:00 PM CAT<br>
                            Sunday: Emergency support only via WhatsApp
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form + Final Message Section -->
    <section class="section-uniform bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h4 class="title mb-4">Prefer to Write? Send Us a Message</h4>
                    <p class="text-muted para-desc mx-auto mb-5" style="max-width: 780px;">
                        Whether you're interested in a demo, have questions about implementation, need help with pricing, 
                        or want to discuss your specific business requirements — we're excited to hear from you!
                    </p>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow rounded p-4 p-lg-5 bg-white">
                        <form method="POST" action="/contact" class="contact-form">
                            @csrf

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Your Name</label>
                                        <input name="name" type="text" class="form-control" placeholder="Enter your full name" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Email Address</label>
                                        <input name="email" type="email" class="form-control" placeholder="your@email.com" required>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Subject</label>
                                        <input name="subject" type="text" class="form-control" placeholder="e.g. Demo Request, Support Question, Pricing Inquiry" required>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Your Message</label>
                                        <textarea name="message" rows="6" class="form-control" placeholder="Tell us how we can help you..." required></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center mt-5">
                <div class="col-lg-8 text-center">
                    <p class="text-muted">
                        <i class="uil uil-lock me-1"></i> 
                        Your information is secure and will only be used to respond to your inquiry.
                    </p>
                </div>
            </div>
        </div>
    </section>

@endsection