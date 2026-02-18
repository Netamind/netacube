{{-- resources/views/invoices/index.blade.php --}}
@extends('master.dashboard')

@section('content')
<style>
    /* ──────────────────────────────────────
       Export Buttons
       ────────────────────────────────────── */
    .dt-buttons .btn {
        background: transparent !important;
        background-image: none !important;
        box-shadow: none !important;
        border-color: #5bc0de;
        color: #5bc0de;
    }
    .dt-buttons .btn:hover {
        background: #5bc0de !important;
        color: #fff;
    }

    /* ──────────────────────────────────────
       Card
       ────────────────────────────────────── */
    .card-header {
        padding: 0.5rem 1.5rem !important;
        background: linear-gradient(to right, #4B5EBD, #576CC0);
        color: #fff;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }
    .card-body { padding: 0 1.5rem 1.5rem 1.5rem !important; }

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
        transition: background-color .2s ease-in-out;
    }

    .card {
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,.1);
        border-radius: 10px;
        overflow: hidden;
    }
    .card-header h4 {
        color: #fff;
        font-weight: 600;
        margin-bottom: 0;
        display: flex;
        align-items: center;
    }
    .card-header h4 i { margin-right: .25rem; }

    /* ──────────────────────────────────────
       Fixed header
       ────────────────────────────────────── */
    table.dataTable.fixedHeader-floating,
    table.dataTable.fixedHeader-locked {
        background: #fff !important;
        border-bottom: none !important;
    }
    table.dataTable thead th.fixedHeader-floating {
        background: #e2e2e9 !important;
    }

    /* ──────────────────────────────────────
       Tabs
       ────────────────────────────────────── */
    .tab-header-container {
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }
    .nav-pills .nav-link {
        border-radius: 0 !important;
        padding: 0.75rem 1rem;
        font-weight: 500;
        color: #495057;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
    }
    .nav-pills .nav-link:hover {
        background-color: #e9ecef;
        color: #4B5EBD;
    }
    .nav-pills .nav-link.active {
        background-color: transparent !important;
        color: #4B5EBD !important;
        border-bottom-color: #4B5EBD;
        font-weight: 600;
    }
    .nav-pills .nav-link i {
        font-size: 1.1rem;
        margin-right: 0.35rem;
    }

    .tab-content {
        margin-top: 0 !important;
        padding-top: 0.75rem;
    }

    /* ──────────────────────────────────────
       Action icons
       ────────────────────────────────────── */
    .action-icon {
        font-size: 17px;
        font-weight: bold;
        margin: 0 4px;
        text-decoration: none;
    }

    /* ──────────────────────────────────────
       Top Controls
       ────────────────────────────────────── */
    .top-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        gap: 1rem;
    }

    .client-filter {
        width: 200px;
        height: 38px;
        font-size: 0.875rem;
        padding: 0 12px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        background-color: #fff;
        color: #495057;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236c757d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 12px;
        transition: border-color 0.2s ease;
    }
    .client-filter:focus {
        outline: none;
        border-color: #4B5EBD;
        box-shadow: 0 0 0 3px rgba(75, 94, 189, 0.15);
    }

    /* ──────────────────────────────────────
       SELECTED TOTAL – SWEET, MODERN, SINGLE LINE
       ────────────────────────────────────── */
    .selected-total {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f0f9ff;
        padding: 8px 16px;
        border: 1px solid #0ea5e9;
        border-radius: 12px;
        height: 44px;
        font-size: 0.875rem;
        min-width: 260px;
        white-space: nowrap;
        box-shadow: 0 2px 6px rgba(14, 165, 233, 0.15);
        transition: all 0.2s ease;
    }
    .selected-total:hover {
        background: #e0f2fe;
        box-shadow: 0 3px 8px rgba(14, 165, 233, 0.2);
        transform: translateY(-1px);
    }
    .selected-total i {
        color: #0284c7;
        font-size: 1.25rem;
    }
    .selected-total .label-text {
        color: #0369a1;
        font-weight: 600;
        font-size: 0.875rem;
        letter-spacing: 0.3px;
    }
    .selected-total .amount {
        font-weight: 700;
        color: #1e40af;
        font-size: 1rem;
        min-width: 110px;
        text-align: right;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
</style>

<div class="progress" id="progressBar"
     role="progressbar" aria-label="Animated striped"
     aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"
     style="height:8px; transform:rotate(180deg); display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated"
         style="width:100%"></div>
</div>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <div class="row mb-3"></div>

            <div class="card">
                {{-- Header --}}
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">
                       <i class="ri-file-list-3-line"></i> Invoice Management
                    </h4>
                    <div class="d-flex align-items-center gap-1">
                        <a href="#" class="btn btn-light text-primary fs-16" id="infoBtn" title="Info">
                            <i class="ri-information-line"></i>
                        </a>
                        <a href="#" class="btn btn-light text-primary fs-16" id="tableButtonsBtn" title="Download options">
                            <i class="ri-download-line"></i>
                        </a>
                        <a href="{{ route('master.point.of.sales') }}" class="btn btn-light text-success fs-16" title="Create New Invoice">
                            <i class="ri-add-circle-line"></i>
                        </a>
                    </div>

                    <?php
                        $maintableTitle = "Running Invoices";
                        $invoices = collect([
                            (object)[ 'id'=>1, 'client'=>'Mzuzu Central Hospital', 'invoice_no'=>'#0001', 'date'=>'14 Nov 2025', 'due_date'=>'14 Dec 2025', 'amount'=>25630000 ],
                            (object)[ 'id'=>4, 'client'=>'Katoto Clinic', 'invoice_no'=>'#0004', 'date'=>'12 Nov 2025', 'due_date'=>'12 Dec 2025', 'amount'=>750000000 ],
                        ]);
                    ?>
                </div>

                {{-- Tabs --}}
                <div class="tab-header-container">
                    <ul class="nav nav-pills nav-justified mb-0">
                        <li class="nav-item">
                            <a href="#running" data-bs-toggle="tab" class="nav-link active">
                                <i class="ri-play-circle-line"></i> Running
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#paid" data-bs-toggle="tab" class="nav-link">
                                <i class="ri-check-double-line"></i> Paid
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#canceled" data-bs-toggle="tab" class="nav-link">
                                <i class="ri-close-circle-line"></i> Canceled
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Tab Content --}}
                <div class="card-body">
                    <div class="tab-content">

                        {{-- Running --}}
                        <div class="tab-pane show active" id="running">
                            <div class="top-controls">
                                <select class="client-filter" id="runningClientFilter">
                                    <option value="">All Clients</option>
                                    <option value="Mzuzu Central Hospital">Mzuzu Central Hospital</option>
                                    <option value="Katoto Clinic">Katoto Clinic</option>
                                </select>

                                <!-- SWEET SINGLE-LINE TOTAL -->
                                <div class="selected-total">
                                    <i class="ri-calculator-line"></i>
                                    <span class="label-text">Selected:</span>
                                    <span class="amount">MWK 0</span>
                                </div>
                            </div>

                            <table id="runningTable"
                                   class="table table-sm table-striped row-border order-column w-100">
                                <thead style="background-color:#e2e2e9">
                                <tr>
                                    <th>
                                        <input type="checkbox" class="select-all-checkbox" id="selectAll">
                                        Client
                                    </th>
                                    <th style="text-align:center">Invoice #</th>
                                    <th style="text-align:center">Date</th>
                                    <th style="text-align:center">Due Date</th>
                                    <th style="text-align:center">Amount</th>
                                    <th style="text-align:center">Action</th>
                                </tr>
                                </thead>
                                <tbody id="tbody">
                                @foreach($invoices as $inv)
                                    <?php $row = "row".$inv->id ?>
                                    <tr id="{{ $row }}">
                                        <td>
                                            <input type="checkbox" class="invoice-checkbox" data-amount="{{ $inv->amount }}" data-row="{{ $row }}">
                                            {{ $inv->client }}
                                        </td>
                                        <td style="text-align:center"><strong>{{ $inv->invoice_no }}</strong></td>
                                        <td style="text-align:center">{{ $inv->date }}</td>
                                        <td style="text-align:center">{{ $inv->due_date }}</td>
                                        <td style="text-align:center">MWK {{ number_format($inv->amount) }}</td>
                                        <td style="text-align:center">
                                            <a href="#" class="action-icon text-primary" title="View PDF">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane" id="paid">
                            <p class="text-center text-muted mt-5">No paid invoices yet.</p>
                        </div>
                        <div class="tab-pane" id="canceled">
                            <p class="text-center text-muted mt-5">No canceled invoices.</p>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Export Modal --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static"
     data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Click to export invoice data</p>
                <div class="buttons"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {
    var table = $('#runningTable').DataTable({
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, "All"]],
        fixedColumns: { left: 1 },
        scrollX: true,
        buttons: [
            { extend: 'excelHtml5', title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',  title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',  title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' },
              customize: function (doc) { doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split(''); }
            }
        ]
    });

    table.buttons().container().appendTo('#buttonsModal .buttons');

    $('#infoBtn, #tableButtonsBtn').on('click', e => { e.preventDefault(); $('#buttonsModal').modal('show'); });
    $('#newDataBtn').on('click', e => { e.preventDefault(); alert('Add new invoice – implement later'); });

    // SELECT ALL + LIVE TOTAL
    let selectedTotal = 0;
    const $totalAmount = $('.selected-total .amount');

    function updateTotal() {
        $totalAmount.text('MWK ' + selectedTotal.toLocaleString());
    }

    $(document).on('change', '.invoice-checkbox', function () {
        const amount = parseFloat($(this).data('amount'));
        if ($(this).is(':checked')) {
            selectedTotal += amount;
        } else {
            selectedTotal -= amount;
        }
        updateTotal();

        const allChecked = $('.invoice-checkbox').length === $('.invoice-checkbox:checked').length;
        $('#selectAll').prop('checked', allChecked);
    });

    $('#selectAll').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('.invoice-checkbox').prop('checked', isChecked).trigger('change');
    });

    updateTotal();
});
</script>
@endsection