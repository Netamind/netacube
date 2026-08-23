@extends('website.homepage')
@section('content')

<style>
    /* =========================================================
         HELP CENTER — FAQ
    ========================================================== */

    .page-hero{
        position:relative;
        padding:56px 0 44px;
        background:#fff;
        overflow:hidden;
    }
    .page-hero:before{
        content:"";
        position:absolute;
        width:640px;
        height:640px;
        left:-260px;
        top:-220px;
        border-radius:50%;
        background:radial-gradient(circle,rgba(23,111,229,.10) 0%,rgba(23,111,229,.03) 45%,transparent 72%);
        pointer-events:none;
    }
    .page-hero-inner{position:relative;z-index:2;max-width:700px;margin:0 auto;text-align:center}
    .page-hero .hero-label{margin:0 auto}
    .page-hero h1{
        margin-top:20px;
        color:var(--navy);
        font-family:"Manrope",sans-serif;
        font-size:clamp(32px,4vw,46px);
        line-height:1.14;
        letter-spacing:-2px;
        font-weight:800;
    }
    .page-hero p{max-width:560px;margin:14px auto 0;color:var(--muted);font-size:14px;line-height:1.8}
    .breadcrumb{
        display:flex;justify-content:center;gap:6px;
        margin-bottom:14px;color:var(--muted);font-size:11.5px;font-weight:700;
    }
    .breadcrumb a{color:var(--blue)}

    /* ---- Layout: topic nav + accordions ---- */
    .faq-shell{display:grid;grid-template-columns:230px minmax(0,1fr);gap:44px;align-items:start}
    .faq-nav{position:sticky;top:100px;padding:20px;border:1px solid var(--line);border-radius:16px;background:#fff}
    .faq-nav span{display:block;margin-bottom:10px;color:var(--muted);font-size:10px;font-weight:800;letter-spacing:.6px;text-transform:uppercase}
    .faq-nav a{display:block;padding:8px 10px;margin:2px 0;border-radius:8px;color:#475467;font-size:12px;font-weight:700;transition:.2s}
    .faq-nav a:hover{color:var(--blue);background:var(--blue-pale)}

    .faq-group{margin-bottom:36px;scroll-margin-top:100px}
    .faq-group-title{display:flex;align-items:center;gap:12px;margin-bottom:16px}
    .faq-group-title .icon{
        width:36px;height:36px;flex-shrink:0;
        display:grid;place-items:center;
        border-radius:11px;
        color:var(--blue);background:var(--blue-pale);
        font-weight:900;font-size:15px;
    }
    .faq-group-title h2{color:var(--navy);font:800 18px "Manrope",sans-serif;letter-spacing:-.3px}

    .faq-item{border:1px solid var(--line);border-radius:14px;background:#fff;margin-bottom:10px;overflow:hidden}
    .faq-item summary{
        list-style:none;
        cursor:pointer;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:16px;
        padding:16px 20px;
        color:var(--navy);
        font-size:13.5px;
        font-weight:700;
    }
    .faq-item summary::-webkit-details-marker{display:none}
    .faq-item summary .plus{
        flex-shrink:0;
        width:22px;height:22px;
        display:grid;place-items:center;
        border-radius:7px;
        background:var(--blue-pale);
        color:var(--blue);
        font-weight:800;
        transition:.2s;
    }
    .faq-item[open] summary .plus{transform:rotate(45deg)}
    .faq-item[open]{border-color:var(--blue-line)}
    .faq-item .faq-answer{padding:0 20px 18px;color:var(--muted);font-size:13px;line-height:1.85}

    @media(max-width:900px){
        .faq-shell{grid-template-columns:1fr}
        .faq-nav{position:static;top:auto}
    }
</style>

<main>

    <section class="page-hero">
        <div class="container">
            <div class="page-hero-inner reveal">
                <div class="breadcrumb">
                    <a href="{{ url('/help-center') }}">Help center</a> / <span>FAQ</span>
                </div>
                <div class="hero-label"><i></i>Frequently asked questions</div>
                <h1>Answers to the questions we hear most.</h1>
                <p>Can't find what you're looking for? <a href="{{ url('/contact') }}" style="color:var(--blue);font-weight:700">Talk to our team</a>.</p>
            </div>
        </div>
    </section>

    <section class="section soft">
        <div class="container">
            <div class="faq-shell">

                <aside class="faq-nav reveal">
                    <span>Topics</span>
                    <a href="#getting-started">Getting Started</a>
                    <a href="#pos">Point of Sale</a>
                    <a href="#inventory">Inventory</a>
                    <a href="#employees">Employees &amp; Attendance</a>
                    <a href="#payroll">Payroll</a>
                    <a href="#offline">Offline Mode</a>
                </aside>

                <div class="faq-content">

                    <div class="faq-group reveal" id="getting-started">
                        <div class="faq-group-title"><div class="icon">➜</div><h2>Getting Started</h2></div>

                        <details class="faq-item">
                            <summary>How do I create a Netacube account?<span class="plus">+</span></summary>
                            <div class="faq-answer">Go to the <a href="{{ url('/get-started') }}">Get Started</a> page, fill in your business details and choose a subscription plan. We'll email your login details once your workspace is ready.</div>
                        </details>
                        <details class="faq-item">
                            <summary>What happens after I register?<span class="plus">+</span></summary>
                            <div class="faq-answer">Your dedicated tenant workspace is set up, and you'll receive login details by email so you can sign in and start configuring branches, staff and products.</div>
                        </details>
                        <details class="faq-item">
                            <summary>Can I add multiple branches?<span class="plus">+</span></summary>
                            <div class="faq-answer">Yes. Netacube supports multi-location businesses out of the box — you can add branches and assign staff, stock and sales to each one from your admin dashboard.</div>
                        </details>
                    </div>

                    <div class="faq-group reveal" id="pos">
                        <div class="faq-group-title"><div class="icon">₵</div><h2>Point of Sale</h2></div>

                        <details class="faq-item">
                            <summary>Can I process a refund from the POS screen?<span class="plus">+</span></summary>
                            <div class="faq-answer">Yes. Open the original sale from the transaction history and use the refund action. Refunds are logged against the original sale for reporting and audit purposes.</div>
                        </details>
                        <details class="faq-item">
                            <summary>How does daily closing work?<span class="plus">+</span></summary>
                            <div class="faq-answer">At the end of a shift, close the register from the POS settings menu. This reconciles cash and card totals against recorded sales for that session.</div>
                        </details>
                        <details class="faq-item">
                            <summary>Does the POS work without internet?<span class="plus">+</span></summary>
                            <div class="faq-answer">Yes — see the Offline Mode section below for how sales continue while offline and sync automatically once connectivity returns.</div>
                        </details>
                    </div>

                    <div class="faq-group reveal" id="inventory">
                        <div class="faq-group-title"><div class="icon">▦</div><h2>Inventory</h2></div>

                        <details class="faq-item">
                            <summary>How are low-stock alerts triggered?<span class="plus">+</span></summary>
                            <div class="faq-answer">Each product can have a minimum stock threshold. When on-hand quantity falls below it at any branch, Netacube flags it on your inventory dashboard.</div>
                        </details>
                        <details class="faq-item">
                            <summary>Can I transfer stock between branches?<span class="plus">+</span></summary>
                            <div class="faq-answer">Yes, using delivery notes. Create a transfer from the source branch and it appears as incoming stock at the destination branch once received.</div>
                        </details>
                        <details class="faq-item">
                            <summary>How do I count stock without stopping sales?<span class="plus">+</span></summary>
                            <div class="faq-answer">Use Partial Stocktaking to count select products while sales continue, or Full Stocktaking for a complete periodic count.</div>
                        </details>
                    </div>

                    <div class="faq-group reveal" id="employees">
                        <div class="faq-group-title"><div class="icon">◉</div><h2>Employees &amp; Attendance</h2></div>

                        <details class="faq-item">
                            <summary>How do employees clock in and out?<span class="plus">+</span></summary>
                            <div class="faq-answer">Employees clock in and out from their assigned branch device. Attendance records feed directly into payroll calculations.</div>
                        </details>
                        <details class="faq-item">
                            <summary>Can I manage leave requests in Netacube?<span class="plus">+</span></summary>
                            <div class="faq-answer">Yes. Employees can submit leave requests and managers can approve or decline them from the HR module.</div>
                        </details>
                    </div>

                    <div class="faq-group reveal" id="payroll">
                        <div class="faq-group-title"><div class="icon">$</div><h2>Payroll</h2></div>

                        <details class="faq-item">
                            <summary>How is salary calculated?<span class="plus">+</span></summary>
                            <div class="faq-answer">Salary is calculated from attendance, configured pay rates and any statutory deductions for the payroll period, then compiled into a wage bill for review before payslips are generated.</div>
                        </details>
                        <details class="faq-item">
                            <summary>Where can employees view their payslips?<span class="plus">+</span></summary>
                            <div class="faq-answer">Payslips are generated as downloadable PDFs and made available to employees once a payroll period is finalised.</div>
                        </details>
                    </div>

                    <div class="faq-group reveal" id="offline">
                        <div class="faq-group-title"><div class="icon">◌</div><h2>Offline Mode</h2></div>

                        <details class="faq-item">
                            <summary>What happens if my internet goes down mid-sale?<span class="plus">+</span></summary>
                            <div class="faq-answer">The POS keeps taking sales locally. Nothing is lost — transactions are queued on the device.</div>
                        </details>
                        <details class="faq-item">
                            <summary>How does syncing work once I'm back online?<span class="plus">+</span></summary>
                            <div class="faq-answer">Queued sales and stock changes sync automatically to your workspace in the background as soon as connectivity is restored.</div>
                        </details>
                    </div>

                </div>
            </div>
        </div>
    </section>

</main>

@endsection