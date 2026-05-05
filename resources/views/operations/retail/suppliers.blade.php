@extends('operations.retail.dashboard')
@section('content')
<style>
.dt-buttons .btn {
  background: transparent !important; background-image: none !important;
  box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }
.card-header { padding: 0.5rem 1.5rem !important; background: linear-gradient(to right,#4B5EBD,#576CC0); color:#fff; }
.card-body { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card-header .btn-light { height:28px; padding:0 10px; display:flex; align-items:center; justify-content:center; line-height:1; }
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color .2s ease-in-out; }
.card { border:none; box-shadow:0 4px 8px rgba(0,0,0,.1); border-radius:10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header h4 i { margin-right:.25rem; }
table.dataTable.fixedHeader-floating,
table.dataTable.fixedHeader-locked { background:#fff !important; border-bottom:none !important; }
table.dataTable thead th.fixedHeader-floating { background:#e2e2e9 !important; }

/* ── status badges ── */
.badge-active      { background:#198754; color:#fff; padding:3px 9px; border-radius:4px; font-size:11px; white-space:nowrap; }
.badge-inactive    { background:#6c757d; color:#fff; padding:3px 9px; border-radius:4px; font-size:11px; white-space:nowrap; }
.badge-blacklisted { background:#dc3545; color:#fff; padding:3px 9px; border-radius:4px; font-size:11px; white-space:nowrap; }

/* ── tabs ── */
.supplier-tabs { border-bottom:2px solid #dee2e6; }
.supplier-tabs .nav-link {
    color:#4B5EBD; font-weight:500; border:1px solid transparent;
    border-radius:.25rem .25rem 0 0; padding:.45rem .9rem; font-size:.875rem;
}
.supplier-tabs .nav-link.active {
    background:#4B5EBD; color:#fff;
    border-color:#4B5EBD #4B5EBD #fff;
}
.supplier-tabs .nav-link:not(.active):hover { background:#eef0fb; }

/* ── view modal layout ── */
.view-section-title {
    font-size:11px; text-transform:uppercase; letter-spacing:.07em;
    color:#4B5EBD; font-weight:700;
    border-bottom:2px solid #eef0fb; padding-bottom:4px;
    margin-bottom:10px; margin-top:14px;
}
.view-section-title:first-child { margin-top:4px; }
.view-label { font-size:11px; color:#6c757d; margin-bottom:1px; font-weight:500; }
.view-value { font-size:13px; font-weight:600; margin-bottom:8px; word-break:break-word; color:#212529; }
.view-value.empty { color:#adb5bd; font-weight:400; font-style:italic; }

/* ── branded modal header ── */
.modal-header-brand {
    background: linear-gradient(to right,#4B5EBD,#576CC0);
    color:#fff; border-bottom:none;
    border-top-left-radius:calc(.3rem - 1px);
    border-top-right-radius:calc(.3rem - 1px);
    padding:.75rem 1rem;
}
.modal-header-brand .modal-title { color:#fff; font-weight:600; font-size:1rem; }
.modal-header-brand .btn-close { filter:brightness(0) invert(1); opacity:.85; }

/* ── action icons spacing ── */
.action-icons a { margin: 0 3px; }
</style>

<div class="progress" id="progressBar" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"
     style="height:8px;transform:rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0"><i class="ri-truck-line"></i> Retail Suppliers</h4>
    <div class="d-flex align-items-center">
        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info"><i class="ri-information-line"></i></a>
        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download options"><i class="ri-download-line"></i></a>
    </div>
    <?php
        $maintableTitle = "Retail Suppliers";
        $retailSector   = DB::connection('tenant')->table('sectors')->where('sector', 'Retail')->value('sector');
        $suppliers      = DB::connection('tenant')->table('suppliers')
                            ->leftJoin('categories', 'suppliers.category', '=', 'categories.id')
                            ->select('suppliers.*', 'categories.category as category_name')
                            ->where('suppliers.sector', $retailSector)
                            ->get();
    ?>
</div>

<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
    <thead style="background-color:#e2e2e9">
    <tr>
        <th>Supplier Name</th>
        <th>Contact Person</th>
        <th>Phone</th>
        <th>Email</th>
        <th style="text-align:center">Bank</th>
        <th style="text-align:center">Account Name</th>
        <th style="text-align:center">Account No.</th>
        <th style="text-align:center">Category</th>
        <th style="text-align:center;width:55px">View</th>
    </tr>
    </thead>
    <tbody id="tbody">
    @foreach($suppliers as $s)
        <?php $row = 'row'.$s->id; ?>
        <tr id="{{ $row }}">
            <td>{{ $s->name }}</td>
            <td>{{ $s->contact_person }}</td>
            <td>{{ $s->phone }}</td>
            <td>{{ $s->email }}</td>
            <td style="text-align:center">{{ $s->bank_name }}</td>
            <td style="text-align:center">{{ $s->bank_account_name }}</td>
            <td style="text-align:center">{{ $s->bank_account_number }}</td>
            <td style="text-align:center">{{ $s->category_name }}</td>
            <td style="text-align:center" class="action-icons">
                <a href="#" class="viewDataBtn"
                    viewName="{{ $s->name }}"
                    viewTradingName="{{ $s->trading_name }}"
                    viewRegistrationNumber="{{ $s->registration_number }}"
                    viewContactPerson="{{ $s->contact_person }}"
                    viewPhone="{{ $s->phone }}"
                    viewPhoneAlt="{{ $s->phone_alt }}"
                    viewEmail="{{ $s->email }}"
                    viewWebsite="{{ $s->website }}"
                    viewAddress="{{ $s->address }}"
                    viewCity="{{ $s->city }}"
                    viewCountry="{{ $s->country }}"
                    viewCategoryName="{{ $s->category_name }}"
                    viewPaymentTerms="{{ $s->payment_terms }}"
                    viewCurrency="{{ $s->currency }}"
                    viewStatus="{{ $s->status }}"
                    viewNotes="{{ $s->notes }}"
                    viewBankName="{{ $s->bank_name }}"
                    viewBankAccountName="{{ $s->bank_account_name }}"
                    viewBankAccountNumber="{{ $s->bank_account_number }}"
                    viewBankBranch="{{ $s->bank_branch }}"
                    viewBankSwiftCode="{{ $s->bank_swift_code }}"
                    title="View details">
                    <i class="ri-eye-line text-secondary" style="font-size:16px"></i>
                </a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
</div>

</div>
</div>
</div>


{{-- ══════════════════════════════════════════
     INFO MODAL
══════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-brand">
                <h5 class="modal-title"><i class="ri-information-line me-1"></i> Retail Suppliers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Lists all suppliers under the Retail sector.</p>
                <p class="mb-0 text-muted" style="font-size:13px">
                    Use the <i class="ri-eye-line"></i> icon on any row to view the full supplier record.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════
     DOWNLOAD MODAL
══════════════════════════════════════════ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-brand">
                <h5 class="modal-title">Download</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Click on respective button to download supplier data</p>
                <div class="buttons"></div>
            </div>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════
     VIEW MODAL
══════════════════════════════════════════ --}}
<div class="modal fade" id="viewDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header modal-header-brand">
                <h5 class="modal-title"><i class="ri-eye-line me-1"></i> Supplier Details — <span id="viewModalName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size:13px">

                <div class="view-section-title"><i class="ri-building-4-line me-1"></i>Identity</div>
                <div class="row g-0">
                    <div class="col-6 pe-3">
                        <div class="view-label">Supplier Name</div>
                        <div class="view-value" id="viewName"></div>
                    </div>
                    <div class="col-6">
                        <div class="view-label">Trading Name</div>
                        <div class="view-value" id="viewTradingName"></div>
                    </div>
                    <div class="col-6 pe-3">
                        <div class="view-label">Registration Number</div>
                        <div class="view-value" id="viewRegistrationNumber"></div>
                    </div>
                    <div class="col-6">
                        <div class="view-label">Status</div>
                        <div class="view-value" id="viewStatus"></div>
                    </div>
                </div>

                <div class="view-section-title"><i class="ri-contacts-line me-1"></i>Contact</div>
                <div class="row g-0">
                    <div class="col-6 pe-3">
                        <div class="view-label">Contact Person</div>
                        <div class="view-value" id="viewContactPerson"></div>
                    </div>
                    <div class="col-6">
                        <div class="view-label">Phone</div>
                        <div class="view-value" id="viewPhone"></div>
                    </div>
                    <div class="col-6 pe-3">
                        <div class="view-label">Alternative Phone</div>
                        <div class="view-value" id="viewPhoneAlt"></div>
                    </div>
                    <div class="col-6">
                        <div class="view-label">Email</div>
                        <div class="view-value" id="viewEmail"></div>
                    </div>
                    <div class="col-6 pe-3">
                        <div class="view-label">Website</div>
                        <div class="view-value" id="viewWebsite"></div>
                    </div>
                </div>

                <div class="view-section-title"><i class="ri-bank-line me-1"></i>Bank &amp; Payment</div>
                <div class="row g-0">
                    <div class="col-6 pe-3">
                        <div class="view-label">Bank Name</div>
                        <div class="view-value" id="viewBankName"></div>
                    </div>
                    <div class="col-6">
                        <div class="view-label">Account Name</div>
                        <div class="view-value" id="viewBankAccountName"></div>
                    </div>
                    <div class="col-6 pe-3">
                        <div class="view-label">Account Number</div>
                        <div class="view-value" id="viewBankAccountNumber"></div>
                    </div>
                    <div class="col-6">
                        <div class="view-label">Bank Branch</div>
                        <div class="view-value" id="viewBankBranch"></div>
                    </div>
                    <div class="col-6 pe-3">
                        <div class="view-label">SWIFT / Sort Code</div>
                        <div class="view-value" id="viewBankSwiftCode"></div>
                    </div>
                    <div class="col-6">
                        <div class="view-label">Payment Terms</div>
                        <div class="view-value" id="viewPaymentTerms"></div>
                    </div>
                    <div class="col-6 pe-3">
                        <div class="view-label">Currency</div>
                        <div class="view-value" id="viewCurrency"></div>
                    </div>
                </div>

                <div class="view-section-title"><i class="ri-map-pin-line me-1"></i>Address</div>
                <div class="row g-0">
                    <div class="col-6 pe-3">
                        <div class="view-label">Physical Address</div>
                        <div class="view-value" id="viewAddress"></div>
                    </div>
                    <div class="col-6">
                        <div class="view-label">City</div>
                        <div class="view-value" id="viewCity"></div>
                    </div>
                    <div class="col-6 pe-3">
                        <div class="view-label">Country</div>
                        <div class="view-value" id="viewCountry"></div>
                    </div>
                </div>

                <div class="view-section-title"><i class="ri-folder-info-line me-1"></i>Classification</div>
                <div class="row g-0">
                    <div class="col-6 pe-3">
                        <div class="view-label">Category</div>
                        <div class="view-value" id="viewCategory"></div>
                    </div>
                </div>

                <div class="view-section-title"><i class="ri-sticky-note-line me-1"></i>Notes</div>
                <div class="view-value" id="viewNotes"></div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection


@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000, allowHtml:true };

    function badgeHtml(status) {
        var s = (status || 'active').toLowerCase();
        return '<span class="badge-' + s + '">' + s.charAt(0).toUpperCase() + s.slice(1) + '</span>';
    }

    function orDash(v) {
        return (v !== null && v !== undefined && String(v).trim() !== '')
            ? v
            : '<span class="text-muted fst-italic" style="font-size:12px;font-weight:400">—</span>';
    }

    /* ── DataTable ───────────────────────────────────────── */
    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, 'All']],
        fixedColumns: { left: 1 },
        scrollX: true,
        buttons: [
            { extend: 'excelHtml5', title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' },
              customize: function (doc) { doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split(''); }
            }
        ]
    });

    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    /* ── header buttons ──────────────────────────────────── */
    $('#infoBtn').click(function (e) {
        e.preventDefault();
        $('#infoModal').modal('show');
    });

    $('#tableButtonsBtn').click(function (e) {
        e.preventDefault();
        $('#buttonsModal').modal('show');
    });

    /* ── VIEW ────────────────────────────────────────────── */
    $('#tbody').on('click', '.viewDataBtn', function (e) {
        e.preventDefault();
        var t = $(this);
        $('#viewModalName').text(t.attr('viewName') || '');
        $('#viewName').html(orDash(t.attr('viewName')));
        $('#viewTradingName').html(orDash(t.attr('viewTradingName')));
        $('#viewRegistrationNumber').html(orDash(t.attr('viewRegistrationNumber')));
        $('#viewStatus').html(badgeHtml(t.attr('viewStatus')));
        $('#viewContactPerson').html(orDash(t.attr('viewContactPerson')));
        $('#viewPhone').html(orDash(t.attr('viewPhone')));
        $('#viewPhoneAlt').html(orDash(t.attr('viewPhoneAlt')));
        $('#viewEmail').html(orDash(t.attr('viewEmail')));
        $('#viewWebsite').html(orDash(t.attr('viewWebsite')));
        $('#viewBankName').html(orDash(t.attr('viewBankName')));
        $('#viewBankAccountName').html(orDash(t.attr('viewBankAccountName')));
        $('#viewBankAccountNumber').html(orDash(t.attr('viewBankAccountNumber')));
        $('#viewBankBranch').html(orDash(t.attr('viewBankBranch')));
        $('#viewBankSwiftCode').html(orDash(t.attr('viewBankSwiftCode')));
        $('#viewPaymentTerms').html(orDash(t.attr('viewPaymentTerms')));
        $('#viewCurrency').html(orDash(t.attr('viewCurrency')));
        $('#viewAddress').html(orDash(t.attr('viewAddress')));
        $('#viewCity').html(orDash(t.attr('viewCity')));
        $('#viewCountry').html(orDash(t.attr('viewCountry')));
        $('#viewCategory').html(orDash(t.attr('viewCategoryName')));
        $('#viewNotes').html(orDash(t.attr('viewNotes')));
        $('#viewDataModal').modal('show');
    });

});
</script>
@endsection