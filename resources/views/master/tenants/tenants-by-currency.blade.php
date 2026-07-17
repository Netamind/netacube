@extends('master.dashboard')
@section('content')
<style>
    .card-header {
        padding: 0.5rem 1.5rem !important;
        background: linear-gradient(to right, #4B5EBD, #576CC0);
        color: #fff;
        border-radius: 10px 10px 0 0;
    }
    .card {
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,.1);
        border-radius: 10px;
    }
    .card-header h4 {
        color: #fff;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
    }
    .card-header h4 i { margin-right: .25rem; }
    .card-body { padding: 1.5rem !important; }
    .card-header .btn-light {
        height: 28px;
        padding: 0 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    .card-header .btn-light:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease-in-out;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 50px;
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
    }
    .status-pill.paid { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
    .status-pill.not-paid { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
    .totals-card {
        background: #f0f2fa;
        border: 1px solid #dfe3f5;
        border-radius: 10px;
        padding: 1rem 1.25rem;
    }
    .totals-card .amount {
        font-size: 1.6rem;
        font-weight: 700;
        color: #4B5EBD;
    }
    .custom-invoice-section {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1.25rem;
    }
    .dt-buttons .btn {
        background: transparent !important;
        background-image: none !important;
        box-shadow: none !important;
        border-color: #5bc0de;
        color: #5bc0de;
    }
    .dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }
    table.dataTable.fixedHeader-floating, table.dataTable.fixedHeader-locked { background: #fff !important; border-bottom: none !important; }
    table.dataTable thead th.fixedHeader-floating { background: #e2e2e9 !important; }
</style>

<div class="progress" id="progressBar" role="progressbar" style="height: 8px; transform: rotate(180deg); display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
</div>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <div class="row mb-3"></div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">
                        <i class="ri-currency-line"></i> Billing by Currency
                    </h4>
                    <a href="{{ route('master.tenants') }}" class="btn btn-light text-primary fs-16 mx-1" title="Back to Tenants">
                        <i class="ri-arrow-left-line"></i>
                    </a>
                </div>

                <div class="card-body">

                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Year</label>
                            <select class="form-select" id="filterYear">
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Currency</label>
                            <select class="form-select" id="filterCurrency">
                                <option value="">-- Select Currency --</option>
                                @foreach($currencies as $curr)
                                    <option value="{{ $curr->code }}">{{ $curr->name }} ({{ $curr->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary" id="loadReportBtn">
                                <i class="ri-search-line"></i> View
                            </button>
                        </div>
                        <div class="col-md-3">
                            <div class="totals-card text-end" id="totalsCard" style="display:none;">
                                <div class="text-muted small">Total Due (<span id="totalsCurrencyLabel"></span>)</div>
                                <div class="amount"><span id="totalsAmount">0.00</span></div>
                            </div>
                        </div>
                    </div>

                    <div id="emptyState" class="text-center text-muted py-5">
                        <i class="ri-bar-chart-line fs-1 d-block mb-2"></i>
                        Select a year and currency, then click <strong>View</strong>.
                    </div>

                    <div id="resultsWrapper" style="display:none;">
                        <table class="table table-sm table-striped row-border order-column w-100" id="currencyReportTable">
                            <thead style="background-color:#e2e2e9">
                                <tr>
                                    <th>Tenant</th>
                                    <th>Plan</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th style="width:80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="currencyReportBody"></tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generate / Send Invoice Modal (reused for any tenant in the results table) -->
<div class="modal fade" id="reportInvoiceModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalTitle">
                    <i class="ri-file-pdf-2-line"></i> Generate Invoice
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reportInvoiceForm">
                @csrf
                <input type="hidden" name="tenant_id" id="report_tenant_id" value="">
                <input type="hidden" name="is_custom" id="report_is_custom" value="0">

                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Tenant</label>
                            <input type="text" class="form-control" id="report_tenant_name" disabled>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-success active" id="reportChooseSystem">
                                <i class="ri-checkbox-circle-line"></i> System Invoice (Subscription Plan)
                            </button>
                            <button type="button" class="btn btn-outline-info" id="reportChooseCustom">
                                <i class="ri-edit-2-line"></i> Custom Invoice
                            </button>
                        </div>
                    </div>

                    <div id="reportSystemSection" class="border p-3 rounded bg-light mb-3">
                        <div class="fw-bold text-success mb-2">Subscription Plan</div>
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Plan</label>
                                <input type="text" class="form-control" id="report_plan_name" disabled>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Amount</label>
                                <input type="text" class="form-control" id="report_plan_amount" disabled>
                            </div>
                        </div>
                    </div>

                    <div id="reportCustomSection" class="custom-invoice-section mb-3" style="display: none;">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="description" id="report_description" rows="4"
                                          placeholder="Enter invoice description"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Currency <span class="text-danger">*</span></label>
                                <select class="form-select" name="currency" id="report_currency">
                                    <option value="">-- Select Currency --</option>
                                    @foreach($currencies as $curr)
                                        <option value="{{ $curr->code }}">{{ $curr->name }} ({{ $curr->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="report_amountCurrencyLabel">Amt</span>
                                    <input type="number" step="0.01" min="0.01" class="form-control"
                                           name="amount" id="report_amount" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <label class="form-label">Due Date</label>
                                <input type="date" class="form-control" name="due_date" id="report_due_date"
                                       value="{{ now()->addDays(14)->format('Y-m-d') }}">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_method" id="report_payment_method" required>
                            <option value="">-- Select Payment Method --</option>
                            @foreach(DB::table('payment_methods')->orderBy('method_type')->get() as $method)
                                <option value="{{ $method->id }}">
                                    {{ $method->method_type }}
                                    @if($method->method_type === 'Bank')
                                        - {{ $method->bank_name }} ({{ $method->account_number }})
                                    @elseif($method->method_type === 'Mobile')
                                        - {{ $method->mobile_operator }} ({{ $method->mobile_number }})
                                    @elseif($method->method_type === 'Paypal')
                                        - {{ $method->paypal_email }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <div class="d-flex align-items-center justify-content-between border rounded px-3 py-2 bg-light">
                            <label class="form-check-label mb-0" for="report_sendEmailToggle">
                                <i class="ri-mail-send-line me-1"></i> Also email this invoice to the tenant
                            </label>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="report_sendEmailToggle"
                                       name="send_email" value="1" checked style="width:2.5em; height:1.3em;">
                            </div>
                        </div>
                        <small class="text-muted">If unticked, the invoice is only generated and recorded — it won't be sent by email yet.</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="reportSendInvoiceBtn">
                        <i class="ri-file-add-line"></i> Generate & Send Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {

    toastr.options = {
        closeButton: true,
        progressBar: true,
        showMethod: 'slideDown',
        timeOut: 5000,
        allowHtml: true
    };

    function statusPill(status) {
        return status === 'Paid'
            ? '<span class="status-pill paid"><i class="ri-checkbox-circle-fill"></i> Paid</span>'
            : '<span class="status-pill not-paid"><i class="ri-close-circle-fill"></i> Not Paid</span>';
    }

    // ==================== INIT DATATABLE (fixed first column, matches currency.blade style) ====================
    var currencyTable = $('#currencyReportTable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, "All"]],
        fixedColumns: { left: 1 },
        scrollX: true,
        order: [],
        columnDefs: [{ orderable: false, targets: -1 }],
        language: {
            emptyTable: 'No tenants found for this year/currency.'
        }
    });

    // ==================== LOAD REPORT ====================
    $('#loadReportBtn').click(function(e) {
        e.preventDefault();

        var year = $('#filterYear').val();
        var currency = $('#filterCurrency').val();

        if (!currency) {
            toastr.warning('Please select a currency.', 'Missing Filter');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            type: 'GET',
            url: '{{ route("master.tenants.by_currency.data") }}',
            data: { year: year, currency: currency },
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete: function() { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function(data) {
                $('#emptyState').hide();
                $('#resultsWrapper').show();

                currencyTable.clear();

                $.each(data.tenants, function(i, t) {
                    currencyTable.row.add([
                        t.tenant_name,
                        t.plan_name || '—',
                        t.amount + ' ' + data.currency,
                        t.due_date,
                        statusPill(t.status),
                        '<button type="button" class="btn btn-sm btn-primary generate-invoice-row-btn" ' +
                        'data-tenant-id="' + t.tenant_id + '" data-tenant-name="' + t.tenant_name + '" ' +
                        'data-plan-name="' + (t.plan_name || '') + '" data-amount="' + t.amount + '" data-currency="' + data.currency + '" title="Generate Invoice">' +
                        '<i class="ri-file-pdf-2-line"></i></button>'
                    ]);
                });

                currencyTable.draw();

                $('#totalsCard').show();
                $('#totalsCurrencyLabel').text(data.currency);
                $('#totalsAmount').text(data.total_amount);
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.error || 'Failed to load report.';
                toastr.error(msg, 'Error');
            }
        });
    });

    // ==================== GENERATE INVOICE (per row) ====================
    $(document).on('click', '.generate-invoice-row-btn', function(e) {
        e.preventDefault();
        var $b = $(this);

        $('#report_tenant_id').val($b.data('tenant-id'));
        $('#report_tenant_name').val($b.data('tenant-name'));
        $('#report_plan_name').val($b.data('plan-name') || '—');
        $('#report_plan_amount').val($b.data('amount') + ' ' + $b.data('currency'));

        // Default to system invoice each time the modal opens
        $('#reportChooseSystem').click();
        $('#report_sendEmailToggle').prop('checked', true);
        updateReportBtnLabel();

        $('#reportInvoiceModal').modal('show');
    });

    $('#reportChooseSystem').click(function() {
        $(this).addClass('active btn-outline-success').removeClass('btn-outline-secondary');
        $('#reportChooseCustom').removeClass('active btn-outline-info').addClass('btn-outline-secondary');
        $('#report_is_custom').val('0');
        $('#reportSystemSection').show();
        $('#reportCustomSection').hide();
    });

    $('#reportChooseCustom').click(function() {
        $(this).addClass('active btn-outline-info').removeClass('btn-outline-secondary');
        $('#reportChooseSystem').removeClass('active btn-outline-success').addClass('btn-outline-secondary');
        $('#report_is_custom').val('1');
        $('#reportSystemSection').hide();
        $('#reportCustomSection').show();
    });

    $('#report_currency').on('change', function() {
        var code = $(this).val();
        $('#report_amountCurrencyLabel').text(code ? code : 'Amt');
    });

    function updateReportBtnLabel() {
        var willSend = $('#report_sendEmailToggle').is(':checked');
        $('#reportSendInvoiceBtn').html(
            '<i class="ri-file-add-line"></i> ' + (willSend ? 'Generate & Send Invoice' : 'Generate Invoice')
        );
    }
    $('#report_sendEmailToggle').on('change', updateReportBtnLabel);

    $('#reportInvoiceForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#reportSendInvoiceBtn');
        $btn.prop('disabled', true);

        var isCustom = $('#report_is_custom').val() == "1";
        var url = isCustom
            ? '{{ route("master.tenant.send.custom.invoice") }}'
            : '{{ route("master.tenant.send.invoice") }}';

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#reportInvoiceForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete: function() { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function(data) {
                var msg = data.success || 'Invoice generated successfully!';
                if (data.invoice_number) {
                    msg += '<br><small><strong>Invoice #:</strong> ' + data.invoice_number + '</small>';
                }
                toastr.success(msg, 'Success', { timeOut: 10000, escapeHtml: false });
                $('#reportInvoiceModal').modal('hide');
                $('#reportInvoiceForm')[0].reset();
                $('#loadReportBtn').click();
            },
            error: function(xhr) {
                var errorMessage = 'An unexpected error occurred.';
                if (xhr.status === 422 && xhr.responseJSON) {
                    errorMessage = xhr.responseJSON.error || Object.values(xhr.responseJSON.errors || {}).flat().join('<br>');
                } else if (xhr.status === 404) {
                    errorMessage = xhr.responseJSON?.error || 'Tenant not found.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please try again later.';
                }
                toastr.error(errorMessage, 'Error', { timeOut: 12000, escapeHtml: false });
            }
        });
    });

});
</script>
@endsection