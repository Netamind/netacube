@extends('tenants.admin.dashboard')
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

/* ── form section heading ── */
.form-section-heading {
    font-size:11px; text-transform:uppercase; letter-spacing:.07em;
    color:#4B5EBD; font-weight:700;
    border-bottom:2px solid #eef0fb; padding-bottom:4px;
    margin-bottom:12px; margin-top:16px;
}
.form-section-heading:first-child { margin-top:4px; }

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

/* ── required select highlight ── */
select.form-select.is-invalid { border-color:#dc3545; }
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
    <h4 class="header-title mb-0"><i class="ri-truck-line"></i> Supplier Management</h4>
    <div class="d-flex align-items-center">
        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"    title="Info"><i class="ri-information-line"></i></a>
        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn" title="Add new supplier"><i class="ri-add-circle-line"></i></a>
    </div>
    <?php
        $maintableTitle = "Supplier List";
        $suppliers = DB::connection('tenant')->table('suppliers')
                        ->leftJoin('categories', 'suppliers.category', '=', 'categories.id')
                        ->select('suppliers.*', 'categories.category as category_name')
                        ->get();
        $categories = DB::connection('tenant')->table('categories')->orderBy('category')->get();
        $sectors    = DB::connection('tenant')->table('sectors')->orderBy('sector')->get();
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
        <th style="text-align:center">Sector</th>
        <th style="text-align:center">Category</th>
        <th style="text-align:center;width:95px">Actions</th>
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
            <td style="text-align:center">{{ $s->sector }}</td>
            <td style="text-align:center">{{ $s->category_name }}</td>
            <td style="text-align:center" class="action-icons">
                {{-- VIEW button --}}
                <a href="#" class="viewDataBtn"
                    viewId="{{ $s->id }}"
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
                    viewSector="{{ $s->sector }}"
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
                {{-- EDIT button --}}
                <a href="#" class="editDataBtn"
                    editId="{{ $s->id }}"
                    editRow="{{ $row }}"
                    editName="{{ $s->name }}"
                    editTradingName="{{ $s->trading_name }}"
                    editRegistrationNumber="{{ $s->registration_number }}"
                    editContactPerson="{{ $s->contact_person }}"
                    editPhone="{{ $s->phone }}"
                    editPhoneAlt="{{ $s->phone_alt }}"
                    editEmail="{{ $s->email }}"
                    editWebsite="{{ $s->website }}"
                    editAddress="{{ $s->address }}"
                    editCity="{{ $s->city }}"
                    editCountry="{{ $s->country }}"
                    editCategory="{{ $s->category }}"
                    editSector="{{ $s->sector }}"
                    editNotes="{{ $s->notes }}"
                    editBankName="{{ $s->bank_name }}"
                    editBankAccountName="{{ $s->bank_account_name }}"
                    editBankAccountNumber="{{ $s->bank_account_number }}"
                    editBankBranch="{{ $s->bank_branch }}"
                    title="Edit">
                    <i class="ri-edit-box-line text-info" style="font-size:16px"></i>
                </a>
                {{-- DELETE button --}}
                <a href="#" class="deleteDataBtn"
                    deleteLabel="{{ $s->name }}"
                    deleteId="{{ $s->id }}"
                    deleteRow="{{ $row }}"
                    title="Delete">
                    <i class="ri-delete-bin-line text-danger" style="font-size:16px"></i>
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
<section>
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-brand">
                <h5 class="modal-title"><i class="ri-information-line me-1"></i> Supplier Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Manage your suppliers by adding, editing, or removing them.</p>
                <p class="mb-0 text-muted" style="font-size:13px">
                    Each supplier record stores identity details, contact information, bank/payment details,
                    address, classification and status. Use the <i class="ri-eye-line"></i> icon on any row
                    to view the full record without opening the edit form.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</section>


{{-- ══════════════════════════════════════════
     VIEW MODAL
══════════════════════════════════════════ --}}
<section>
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
                    <div class="col-6">
                        <div class="view-label">Sector</div>
                        <div class="view-value" id="viewSector"></div>
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
</section>


{{-- ══════════════════════════════════════════
     ADD MODAL
══════════════════════════════════════════ --}}
<section>
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header modal-header-brand">
                <h5 class="modal-title"><i class="ri-add-circle-line me-1"></i> Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="newDataForm">
                    @csrf

                    <ul class="nav nav-tabs supplier-tabs mb-3" id="addTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="add-main-tab"
                                    data-bs-toggle="tab" data-bs-target="#add-main"
                                    type="button" role="tab">
                                <i class="ri-building-line me-1"></i>Main Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="add-other-tab"
                                    data-bs-toggle="tab" data-bs-target="#add-other"
                                    type="button" role="tab">
                                <i class="ri-file-list-3-line me-1"></i>Other Info
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="addTabsContent">

                        {{-- ── TAB 1 ── --}}
                        <div class="tab-pane fade show active" id="add-main" role="tabpanel">

                            <div class="form-section-heading"><i class="ri-building-4-line me-1"></i>Identity</div>
                            <div class="mb-3">
                                <label class="form-label">Supplier Name <span class="text-danger">*</span></label>
                                <input class="form-control form-control-sm" type="text" name="name" id="name"
                                       placeholder="Legal / registered name" autocomplete="off" required />
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Trading Name</label>
                                    <input class="form-control form-control-sm" type="text" name="trading_name" id="trading_name"
                                           placeholder="Trading or brand name" autocomplete="off" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Registration Number</label>
                                    <input class="form-control form-control-sm" type="text" name="registration_number" id="registration_number"
                                           placeholder="Company reg. no." autocomplete="off" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" name="category" id="category" required>
                                        <option value="">-- Select --</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->category }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Sector <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" name="sector" id="sector" required>
                                        <option value="">-- Select --</option>
                                        @foreach($sectors as $sec)
                                            <option value="{{ $sec->sector }}">{{ $sec->sector }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-section-heading"><i class="ri-contacts-line me-1"></i>Contact</div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Person</label>
                                    <input class="form-control form-control-sm" type="text" name="contact_person" id="contact_person"
                                           placeholder="Primary contact name" autocomplete="off" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input class="form-control form-control-sm" type="text" name="phone" id="phone"
                                           placeholder="Primary phone" autocomplete="off" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Alternative Phone</label>
                                    <input class="form-control form-control-sm" type="text" name="phone_alt" id="phone_alt"
                                           placeholder="Alternative phone" autocomplete="off" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input class="form-control form-control-sm" type="email" name="email" id="email"
                                           placeholder="Email address" autocomplete="off" />
                                </div>
                            </div>

                            <div class="form-section-heading"><i class="ri-bank-line me-1"></i>Bank &amp; Payment</div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bank Name</label>
                                    <input class="form-control form-control-sm" type="text" name="bank_name" id="bank_name"
                                           placeholder="e.g. National Bank" autocomplete="off" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Account Name</label>
                                    <input class="form-control form-control-sm" type="text" name="bank_account_name" id="bank_account_name"
                                           placeholder="Name on account" autocomplete="off" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Account Number</label>
                                    <input class="form-control form-control-sm" type="text" name="bank_account_number" id="bank_account_number"
                                           placeholder="Account number" autocomplete="off" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bank Branch</label>
                                    <input class="form-control form-control-sm" type="text" name="bank_branch" id="bank_branch"
                                           placeholder="Branch name / location" autocomplete="off" />
                                </div>
                            </div>

                        </div>{{-- end tab 1 --}}

                        {{-- ── TAB 2 ── --}}
                        <div class="tab-pane fade" id="add-other" role="tabpanel">

                            <div class="form-section-heading"><i class="ri-map-pin-line me-1"></i>Address</div>
                            <div class="mb-3">
                                <label class="form-label">Physical Address</label>
                                <input class="form-control form-control-sm" type="text" name="address" id="address"
                                       placeholder="Street / area" autocomplete="off" />
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">City</label>
                                    <input class="form-control form-control-sm" type="text" name="city" id="city"
                                           placeholder="City" autocomplete="off" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Country</label>
                                    <input class="form-control form-control-sm" type="text" name="country" id="country"
                                           placeholder="Country" value="Malawi" autocomplete="off" />
                                </div>
                            </div>

                            <div class="form-section-heading"><i class="ri-folder-info-line me-1"></i>Other</div>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">Website</label>
                                    <input class="form-control form-control-sm" type="url" name="website" id="website"
                                           placeholder="https://example.com" autocomplete="off" />
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control form-control-sm" name="notes" id="notes"
                                              rows="4" placeholder="Additional notes about this supplier"></textarea>
                                </div>
                            </div>

                        </div>{{-- end tab 2 --}}
                    </div>

                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelDataBtn">Clear</a>
                </form>
            </div>
        </div>
    </div>
</div>
</section>


{{-- ══════════════════════════════════════════
     EDIT MODAL
══════════════════════════════════════════ --}}
<section>
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header modal-header-brand">
                <h5 class="modal-title"><i class="ri-edit-box-line me-1"></i> Update Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="editDataForm">
                    @csrf
                    <input type="hidden" name="id"      id="editId">
                    <input type="hidden" name="editrow" id="editRow">

                    <ul class="nav nav-tabs supplier-tabs mb-3" id="editTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="edit-main-tab"
                                    data-bs-toggle="tab" data-bs-target="#edit-main"
                                    type="button" role="tab">
                                <i class="ri-building-line me-1"></i>Main Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="edit-other-tab"
                                    data-bs-toggle="tab" data-bs-target="#edit-other"
                                    type="button" role="tab">
                                <i class="ri-file-list-3-line me-1"></i>Other Info
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="editTabsContent">

                        {{-- ── TAB 1 ── --}}
                        <div class="tab-pane fade show active" id="edit-main" role="tabpanel">

                            <div class="form-section-heading"><i class="ri-building-4-line me-1"></i>Identity</div>
                            <div class="mb-3">
                                <label class="form-label">Supplier Name <span class="text-danger">*</span></label>
                                <input class="form-control form-control-sm" type="text" name="name" id="editName" required />
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Trading Name</label>
                                    <input class="form-control form-control-sm" type="text" name="trading_name" id="editTradingName" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Registration Number</label>
                                    <input class="form-control form-control-sm" type="text" name="registration_number" id="editRegistrationNumber" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" name="category" id="editCategory" required>
                                        <option value="">-- Select --</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->category }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Sector <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" name="sector" id="editSector" required>
                                        <option value="">-- Select --</option>
                                        @foreach($sectors as $sec)
                                            <option value="{{ $sec->sector }}">{{ $sec->sector }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-section-heading"><i class="ri-contacts-line me-1"></i>Contact</div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Person</label>
                                    <input class="form-control form-control-sm" type="text" name="contact_person" id="editContactPerson" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input class="form-control form-control-sm" type="text" name="phone" id="editPhone" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Alternative Phone</label>
                                    <input class="form-control form-control-sm" type="text" name="phone_alt" id="editPhoneAlt" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input class="form-control form-control-sm" type="email" name="email" id="editEmail" />
                                </div>
                            </div>

                            <div class="form-section-heading"><i class="ri-bank-line me-1"></i>Bank &amp; Payment</div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bank Name</label>
                                    <input class="form-control form-control-sm" type="text" name="bank_name" id="editBankName" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Account Name</label>
                                    <input class="form-control form-control-sm" type="text" name="bank_account_name" id="editBankAccountName" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Account Number</label>
                                    <input class="form-control form-control-sm" type="text" name="bank_account_number" id="editBankAccountNumber" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bank Branch</label>
                                    <input class="form-control form-control-sm" type="text" name="bank_branch" id="editBankBranch" />
                                </div>
                            </div>

                        </div>{{-- end tab 1 --}}

                        {{-- ── TAB 2 ── --}}
                        <div class="tab-pane fade" id="edit-other" role="tabpanel">

                            <div class="form-section-heading"><i class="ri-map-pin-line me-1"></i>Address</div>
                            <div class="mb-3">
                                <label class="form-label">Physical Address</label>
                                <input class="form-control form-control-sm" type="text" name="address" id="editAddress" />
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">City</label>
                                    <input class="form-control form-control-sm" type="text" name="city" id="editCity" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Country</label>
                                    <input class="form-control form-control-sm" type="text" name="country" id="editCountry" />
                                </div>
                            </div>

                            <div class="form-section-heading"><i class="ri-folder-info-line me-1"></i>Other</div>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">Website</label>
                                    <input class="form-control form-control-sm" type="url" name="website" id="editWebsite" />
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control form-control-sm" name="notes" id="editNotes" rows="4"></textarea>
                                </div>
                            </div>

                        </div>{{-- end tab 2 --}}
                    </div>

                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitUpdateDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelEditDataBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
</section>


{{-- ══════════════════════════════════════════
     DELETE MODAL
══════════════════════════════════════════ --}}
<section>
<div class="modal fade" id="singleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:350px;margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                <h4 class="mt-2">Are you sure you want to delete <span id="singleDisplayDeleteLabel"></span>?</h4>
                <h5>You won't be able to revert this!</h5>
                <input type="hidden" id="singleDeleteId">
                <input type="hidden" id="singleDeleteRow">
                <a href="#" class="btn btn-danger" id="submitSingleDeleteDataBtn" style="margin-top:10px;margin-bottom:10px;margin-right:5px">Yes, Delete it</a>
                <a href="#" class="btn btn-info"   id="keepSingleDataBtn"         style="margin-top:10px;margin-bottom:10px;">No, Keep it</a>
            </div>
        </div>
    </div>
</div>
</section>

@endsection


@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000, allowHtml:true };

    /* ── helpers ─────────────────────────────────────────── */
    function badgeHtml(status) {
        var s = (status || 'active').toLowerCase();
        return '<span class="badge-' + s + '">' + s.charAt(0).toUpperCase() + s.slice(1) + '</span>';
    }

    function orDash(v) {
        return (v !== null && v !== undefined && String(v).trim() !== '')
            ? v
            : '<span class="text-muted fst-italic" style="font-size:12px;font-weight:400">—</span>';
    }

    function buildRow(s, row) {
        return '<tr id="'+ row +'">'
            + '<td>'+ (s.name||'') +'</td>'
            + '<td>'+ (s.contact_person||'') +'</td>'
            + '<td>'+ (s.phone||'') +'</td>'
            + '<td>'+ (s.email||'') +'</td>'
            + '<td style="text-align:center">'+ (s.bank_name||'') +'</td>'
            + '<td style="text-align:center">'+ (s.bank_account_name||'') +'</td>'
            + '<td style="text-align:center">'+ (s.bank_account_number||'') +'</td>'
            + '<td style="text-align:center">'+ (s.sector||'') +'</td>'
            + '<td style="text-align:center">'+ (s.category_name||'') +'</td>'
            + '<td style="text-align:center" class="action-icons">'
            +   '<a href="#" class="viewDataBtn"'
            +     ' viewId="'+ s.id +'"'
            +     ' viewName="'+ (s.name||'') +'"'
            +     ' viewTradingName="'+ (s.trading_name||'') +'"'
            +     ' viewRegistrationNumber="'+ (s.registration_number||'') +'"'
            +     ' viewContactPerson="'+ (s.contact_person||'') +'"'
            +     ' viewPhone="'+ (s.phone||'') +'"'
            +     ' viewPhoneAlt="'+ (s.phone_alt||'') +'"'
            +     ' viewEmail="'+ (s.email||'') +'"'
            +     ' viewWebsite="'+ (s.website||'') +'"'
            +     ' viewBankName="'+ (s.bank_name||'') +'"'
            +     ' viewBankAccountName="'+ (s.bank_account_name||'') +'"'
            +     ' viewBankAccountNumber="'+ (s.bank_account_number||'') +'"'
            +     ' viewBankBranch="'+ (s.bank_branch||'') +'"'
            +     ' viewBankSwiftCode="'+ (s.bank_swift_code||'') +'"'
            +     ' viewPaymentTerms="'+ (s.payment_terms||'') +'"'
            +     ' viewCurrency="'+ (s.currency||'') +'"'
            +     ' viewStatus="'+ (s.status||'') +'"'
            +     ' viewAddress="'+ (s.address||'') +'"'
            +     ' viewCity="'+ (s.city||'') +'"'
            +     ' viewCountry="'+ (s.country||'') +'"'
            +     ' viewCategoryName="'+ (s.category_name||'') +'"'
            +     ' viewSector="'+ (s.sector||'') +'"'
            +     ' viewNotes="'+ (s.notes||'') +'"'
            +     ' title="View"><i class="ri-eye-line text-secondary" style="font-size:16px"></i></a>'
            +   '<a href="#" class="editDataBtn"'
            +     ' editId="'+ s.id +'"'
            +     ' editRow="'+ row +'"'
            +     ' editName="'+ (s.name||'') +'"'
            +     ' editTradingName="'+ (s.trading_name||'') +'"'
            +     ' editRegistrationNumber="'+ (s.registration_number||'') +'"'
            +     ' editContactPerson="'+ (s.contact_person||'') +'"'
            +     ' editPhone="'+ (s.phone||'') +'"'
            +     ' editPhoneAlt="'+ (s.phone_alt||'') +'"'
            +     ' editEmail="'+ (s.email||'') +'"'
            +     ' editBankName="'+ (s.bank_name||'') +'"'
            +     ' editBankAccountName="'+ (s.bank_account_name||'') +'"'
            +     ' editBankAccountNumber="'+ (s.bank_account_number||'') +'"'
            +     ' editBankBranch="'+ (s.bank_branch||'') +'"'
            +     ' editAddress="'+ (s.address||'') +'"'
            +     ' editCity="'+ (s.city||'') +'"'
            +     ' editCountry="'+ (s.country||'') +'"'
            +     ' editCategory="'+ (s.category||'') +'"'
            +     ' editSector="'+ (s.sector||'') +'"'
            +     ' editWebsite="'+ (s.website||'') +'"'
            +     ' editNotes="'+ (s.notes||'') +'"'
            +     ' title="Edit"><i class="ri-edit-box-line text-info" style="font-size:16px"></i></a>'
            +   '<a href="#" class="deleteDataBtn"'
            +     ' deleteLabel="'+ (s.name||'') +'"'
            +     ' deleteId="'+ s.id +'"'
            +     ' deleteRow="'+ row +'"'
            +     ' title="Delete"><i class="ri-delete-bin-line text-danger" style="font-size:16px"></i></a>'
            + '</td></tr>';
    }

    function handleAjaxError(xhr, status) {
        if (status === 'timeout') {
            toastr.error('The request timed out. Please check your connection and try again.', 'Timeout Error');
        } else if (xhr.status === 0) {
            toastr.error('Unable to connect. Please check your connection and try again.', 'Connection Error');
        } else if (xhr.status === 422) {
            var msg = '';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                $.each(xhr.responseJSON.errors, function (k, v) { msg += v + '<br>'; });
            }
            toastr.error(msg || 'Validation failed.', 'Validation Errors');
        } else if (xhr.status === 500) {
            toastr.error('Server error. Please refresh and try again.', 'Server Error');
        } else {
            toastr.error('An unspecified error occurred. Try again later.', 'Error');
        }
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

    /* ── header buttons ──────────────────────────────────── */
    $('#infoBtn').click(function (e) {
        e.preventDefault();
        $('#infoModal').modal('show');
    });

    $('#newDataBtn').click(function (e) {
        e.preventDefault();
        $('#newDataForm')[0].reset();
        $('#add-main-tab').tab('show');
        $('#newDataModal').modal('show');
    });

    $('#cancelDataBtn').click(function (e) {
        e.preventDefault();
        $('#newDataForm')[0].reset();
        $('#newDataModal').modal('hide');
    });

    $('#cancelEditDataBtn').click(function (e) {
        e.preventDefault();
        $('#editDataModal').modal('hide');
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
        $('#viewCategory').html(orDash(t.attr('viewCategoryName')));   // resolved name, not raw ID
        $('#viewSector').html(orDash(t.attr('viewSector')));
        $('#viewNotes').html(orDash(t.attr('viewNotes')));
        $('#viewDataModal').modal('show');
    });

    /* ── ADD ─────────────────────────────────────────────── */
    $('#submitDataBtn').click(function (e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);

        // Client-side required check for category and sector
        var category = $('#category').val();
        var sector   = $('#sector').val();
        if (!category || !sector) {
            toastr.error('Category and Sector are required fields.', 'Validation Error');
            $('#add-main-tab').tab('show');
            $('#category').toggleClass('is-invalid', !category);
            $('#sector').toggleClass('is-invalid', !sector);
            self.prop('disabled', false);
            return;
        }
        $('#category').removeClass('is-invalid');
        $('#sector').removeClass('is-invalid');

        $.ajax({
            type: 'POST',
            url: '{{ route("tenant.admin.supplier.insert") }}',
            data: $('#newDataForm').serialize(),
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var row = 'row' + data.supplier.id;
                    table.row.add($(buildRow(data.supplier, row))).draw(false);
                    $('#newDataModal').modal('hide');
                } else if (data.status === 422) {
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else {
                    toastr.info('Unspecified error occurred.', 'Error');
                }
            },
            error: function (xhr, status) { handleAjaxError(xhr, status); }
        });
    });

    // Clear is-invalid on change
    $('#category').on('change', function () { $(this).removeClass('is-invalid'); });
    $('#sector').on('change',   function () { $(this).removeClass('is-invalid'); });

    /* ── EDIT open ───────────────────────────────────────── */
    $('#tbody').on('click', '.editDataBtn', function (e) {
        e.preventDefault();
        var t = $(this);
        $('#editId').val(t.attr('editId'));
        $('#editRow').val(t.attr('editRow'));
        $('#editName').val(t.attr('editName'));
        $('#editTradingName').val(t.attr('editTradingName'));
        $('#editRegistrationNumber').val(t.attr('editRegistrationNumber'));
        $('#editContactPerson').val(t.attr('editContactPerson'));
        $('#editPhone').val(t.attr('editPhone'));
        $('#editPhoneAlt').val(t.attr('editPhoneAlt'));
        $('#editEmail').val(t.attr('editEmail'));
        $('#editBankName').val(t.attr('editBankName'));
        $('#editBankAccountName').val(t.attr('editBankAccountName'));
        $('#editBankAccountNumber').val(t.attr('editBankAccountNumber'));
        $('#editBankBranch').val(t.attr('editBankBranch'));
        $('#editAddress').val(t.attr('editAddress'));
        $('#editCity').val(t.attr('editCity'));
        $('#editCountry').val(t.attr('editCountry'));
        $('#editCategory').val(t.attr('editCategory'));
        $('#editSector').val(t.attr('editSector'));
        $('#editWebsite').val(t.attr('editWebsite'));
        $('#editNotes').val(t.attr('editNotes'));
        $('#editCategory').removeClass('is-invalid');
        $('#editSector').removeClass('is-invalid');
        $('#edit-main-tab').tab('show');
        $('#editDataModal').modal('show');
    });

    // Clear is-invalid on change (edit form)
    $('#editCategory').on('change', function () { $(this).removeClass('is-invalid'); });
    $('#editSector').on('change',   function () { $(this).removeClass('is-invalid'); });

    /* ── EDIT submit ─────────────────────────────────────── */
    $('#submitUpdateDataBtn').click(function (e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row  = $('#editRow').val();

        // Client-side required check for category and sector
        var category = $('#editCategory').val();
        var sector   = $('#editSector').val();
        if (!category || !sector) {
            toastr.error('Category and Sector are required fields.', 'Validation Error');
            $('#edit-main-tab').tab('show');
            $('#editCategory').toggleClass('is-invalid', !category);
            $('#editSector').toggleClass('is-invalid', !sector);
            self.prop('disabled', false);
            return;
        }
        $('#editCategory').removeClass('is-invalid');
        $('#editSector').removeClass('is-invalid');

        $.ajax({
            type: 'POST',
            url: '{{ route("tenant.admin.supplier.update") }}',
            data: $('#editDataForm').serialize(),
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#' + row).remove();
                    table.row.add($(buildRow(data.supplier, row))).draw(false);
                    $('#editDataModal').modal('hide');
                } else if (data.status === 409) {
                    toastr.warning(data.error || 'No changes detected.', 'No Changes');
                } else if (data.status === 422) {
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else {
                    toastr.info('Unspecified error occurred.', 'Error');
                }
            },
            error: function (xhr, status) { handleAjaxError(xhr, status); }
        });
    });

    /* ── DELETE open ─────────────────────────────────────── */
    $('#tbody').on('click', '.deleteDataBtn', function (e) {
        e.preventDefault();
        $('#singleDisplayDeleteLabel').text($(this).attr('deleteLabel'));
        $('#singleDeleteRow').val($(this).attr('deleteRow'));
        $('#singleDeleteId').val($(this).attr('deleteId'));
        $('#singleDeleteDataModal').modal('show');
    });

    $('#keepSingleDataBtn').click(function (e) {
        e.preventDefault();
        toastr.info('Your supplier is safe.', 'Cancelled');
        $('#singleDeleteDataModal').modal('hide');
    });

    /* ── DELETE submit ───────────────────────────────────── */
    $('#submitSingleDeleteDataBtn').click(function (e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row  = $('#singleDeleteRow').val();

        $.ajax({
            type: 'POST',
            url: '{{ route("tenant.admin.supplier.delete") }}',
            data: { id: $('#singleDeleteId').val(), _token: '{{ csrf_token() }}' },
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#' + row).remove().draw(false);
                    $('#singleDeleteDataModal').modal('hide');
                } else if (data.status === 422) {
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else {
                    toastr.info('Unspecified error occurred.', 'Error');
                }
            },
            error: function (xhr, status) { handleAjaxError(xhr, status); }
        });
    });

});
</script>
@endsection