{{-- resources/views/master-point-of-sales.blade.php --}}
@extends('master.dashboard')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">

<style>
    /* === ONLY THIS PART CHANGED – makes totals stick to bottom perfectly === */
    .cart-area {
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
        height: 100%;                 /* critical */
    }
    .cart-body {
        flex: 1;
        overflow-y: auto;
        padding: 1rem 0;
        background: #f8f9fa;
    }
    .cart-totals {
        background: #ffffff !important;
        border-top: 2px solid #4B5EBD !important;
        padding: 1rem 1.5rem;
        flex-shrink: 0;               /* never shrink */
        position: sticky;
        bottom: 0;
        z-index: 10;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.08);
    }

    /* Your original styles – 100% untouched */
    .client-controls { background: #f8f9fa; border-bottom: 1px solid #dee2e6; }
    .search-area { background: #ffffff; }
    .search-input-group { display: flex; height: 42px; border: 1px solid #ced4da; border-radius: 8px; overflow: hidden; background: #fff; position: relative; }
    .search-input-group .form-control { flex: 1; border: none; padding: 0 1rem 0 2.8rem; font-size: .9rem; height: 100%; }
    .search-input-group .form-control:focus { outline: none; box-shadow: none; }
    .search-input-group .search-icon { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); color: #6c757d; font-size: 1.1rem; pointer-events: none; z-index: 5; }
    .btn-add-manual { background: #f8f9fa; border: none; width: 46px; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #495057; transition: all .2s; }
    .btn-add-manual:hover { background: #e9ecef; color: #1e40af; }
    .section-header { height: 60px; padding: 0 1rem; background: #f1f3f5; border-bottom: 1px solid #dee2e6; font-weight: 600; color: #495057; font-size: .95rem; display: flex; align-items: center; }
    .cart-item { padding: .75rem 1rem; border-bottom: 1px solid #e9ecef; font-size: .9rem; background: white; margin: 0 0.5rem; border-radius: 6px; }
    .cart-item-name { font-weight: 500; flex: 1; }
    .cart-item-price { color: #1e40af; font-weight: 600; }
    .cart-item-remove { color: #dc3545; font-size: 1.1rem; opacity: .7; }
    .cart-item-remove:hover { opacity: 1; }
    .empty-cart { text-align: center; color: #6c757d; padding: 3rem 1rem; }
    .empty-cart i { font-size: 3rem; color: #ced4da; margin-bottom: .75rem; }
    .btn-complete-inline { background: linear-gradient(135deg, #10b981, #059669); color: #fff; border: none; border-radius: 10px; padding: .5rem 1rem; font-weight: 600; font-size: .9rem; display: flex; align-items: center; gap: .4rem; }

    /* Full height fix (already working from previous version) */
    .content-page, .content, .container-fluid, .card { height: 100% !important; }
    .card { display: flex; flex-direction: column; }
    .row.g-0.flex-fill { flex: 1; }
</style>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <div class="row mb-3"></div>

            <div class="card border-0 shadow-sm" style="border-radius:10px 10px 0 0; overflow:hidden;">

                <!-- HEADER -->
                <div class="card-header d-flex justify-content-between align-items-center"
                     style="padding:.5rem 1rem; background:linear-gradient(to right,#4B5EBD,#576CC0); color:#fff;">
                    <h4 class="mb-0 text-white fw-semibold fs-5 d-flex align-items-center gap-2">
                        <i class="ri-shopping-cart-line"></i> Point of Sales
                    </h4>
                    <div class="d-flex gap-1">
                        <a href="#" class="btn btn-sm text-white" style="background:rgba(255,255,255,.15);"><i class="ri-file-list-3-line"></i></a>
                        <a href="#" class="btn btn-sm text-white" style="background:rgba(255,255,255,.15);"><i class="ri-file-list-2-line"></i></a>
                        <a href="#" class="btn btn-sm text-white active" style="background:rgba(255,255,255,.3);box-shadow:0 0 8px rgba(255,255,255,.2);"><i class="ri-calculator-line"></i></a>
                    </div>
                </div>

                <!-- CLIENT CONTROLS -->
                <div class="client-controls p-3">
                    <div class="row g-3">
                        <div class="col-6 col-md-3"><label class="form-label mb-1">Client</label>
                            <select class="form-select form-select-sm" id="clientSelect" required>
                                <option value="">Select</option>
                                <option value="1">Mzuzu Hospital</option>
                                <option value="2">Katoto Clinic</option>
                                <option value="3">Lilongwe Hospital</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3"><label class="form-label mb-1">Type</label>
                            <select class="form-select form-select-sm" id="docType">
                                <option value="invoice">Invoice</option>
                                <option value="quotation">Quotation</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3"><label class="form-label mb-1">Issue</label>
                            <input type="date" class="form-control form-control-sm" id="issueDate" value="{{ date('Y-m-d') }}" readonly>
                        </div>
                        <div class="col-6 col-md-3"><label class="form-label mb-1">Due</label>
                            <input type="date" class="form-control form-control-sm" id="dueDate" required>
                        </div>
                    </div>
                </div>

                <!-- MAIN GRID -->
                <div class="row g-0 flex-fill">
                    <!-- LEFT: SEARCH -->
                    <div class="col-lg-5 d-flex flex-column border-end search-area">
                        <div class="section-header px-3">
                            <div class="search-input-group w-100">
                                <i class="ri-search-line search-icon"></i>
                                <input type="text" class="form-control" placeholder="Search..." id="productSearch">
                                <button type="button" class="btn btn-add-manual" data-bs-toggle="modal" data-bs-target="#manualAddModal">
                                    <i class="ri-add-line"></i>
                                </button>
                            </div>
                        </div>
                        <div id="searchResults" class="p-3 flex-grow-1 bg-white d-none"></div>
                    </div>

                    <!-- RIGHT: CART – TOTALS NOW GLUED TO BOTTOM -->
                    <div class="col-lg-7 cart-area">
                        <div class="section-header d-flex justify-content-between px-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ri-shopping-cart-line"></i>
                                <span>Cart Items</span>
                            </div>
                            <button type="button" class="btn btn-complete-inline" id="completeSale">
                                Complete <i class="ri-arrow-right-s-line"></i>
                            </button>
                        </div>

                        <div class="cart-body" id="cartBody">
                            <div class="empty-cart">
                                <i class="ri-shopping-cart-2-line"></i>
                                <div>No items in cart</div>
                                <small class="text-muted">Search and add services</small>
                            </div>
                        </div>

                        <!-- THIS IS NOW PINNED TO BOTTOM -->
                        <div class="cart-totals">
                            <div class="total-row"><span>Subtotal</span><span id="subtotal">MWK 0.00</span></div>
                            <div class="total-row"><span>Tax (0%)</span><span id="tax">MWK 0.00</span></div>
                            <div class="total-row final"><span>Total</span><span id="grandTotal">MWK 0.00</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="manualAddModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="ri-edit-box-line"></i> Add Service</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" id="manualName" placeholder="e.g., Consultation">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Price</label>
                                    <input type="number" class="form-control" id="manualPrice" value="0" step="0.01">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Qty</label>
                                    <input type="number" class="form-control" id="manualQty" value="1" min="1">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveManualItem">
                                <i class="ri-check-line"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

{{-- Your original script – untouched --}}
@section('scripts')
<script>
$(document).ready(function() {
    let cart = []; let itemId = 1;
    const issue = $('#issueDate').val();
    const due = new Date(issue); due.setDate(due.getDate() + 30);
    $('#dueDate').val(due.toISOString().split('T')[0]);

    function updateCart() {
        const $body = $('#cartBody'); $body.empty();
        if (cart.length === 0) {
            $body.html(`<div class="empty-cart"><i class="ri-shopping-cart-2-line"></i><div>No items in cart</div><small class="text-muted">Search and add services</small></div>`);
            calculateTotals(); return;
        }
        cart.forEach((item, i) => {
            $body.append(`<div class="cart-item d-flex justify-content-between align-items-center">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">MWK ${parseFloat(item.total).toFixed(2)}</div>
                <button class="btn btn-link p-0 cart-item-remove" data-index="${i}"><i class="ri-close-line"></i></button>
            </div>`);
        });
        calculateTotals();
    }

    function calculateTotals() {
        const subtotal = cart.reduce((s, it) => s + parseFloat(it.total), 0);
        $('#subtotal').text('MWK ' + subtotal.toFixed(2));
        $('#tax').text('MWK 0.00');
        $('#grandTotal').text('MWK ' + subtotal.toFixed(2));
    }

    $('#saveManualItem').on('click', function() {
        const name = $('#manualName').val().trim();
        const price = parseFloat($('#manualPrice').val()) || 0;
        const qty = parseFloat($('#manualQty').val()) || 1;
        if (!name || price <= 0) return alert('Fill all fields correctly.');
        const total = (price * qty).toFixed(2);
        cart.push({ id: itemId++, name, qty, price, total: parseFloat(total) });
        updateCart();
        $('#manualAddModal').modal('hide');
        $('#manualName').val(''); $('#manualPrice').val('0'); $('#manualQty').val('1');
    });

    $(document).on('click', '.cart-item-remove', function() {
        cart.splice($(this).data('index'), 1);
        updateCart();
    });

    $('#completeSale').on('click', function() {
        if (cart.length === 0) return alert('Cart is empty!');
        if (!$('#clientSelect').val()) return alert('Select a client.');
        alert('Document saved!');
    });

    updateCart();
});
</script>
@endsection