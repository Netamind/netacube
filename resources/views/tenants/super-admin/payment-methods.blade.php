@extends('tenants.super-admin.dashboard')
@section('content')
<style>
.dt-buttons .btn {
  background: transparent !important;
  background-image: none !important;
  box-shadow: none !important;
  border-color: #5bc0de;
  color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }
.card-header { padding: 0.5rem 1.5rem !important; background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; }
.card-body { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card-header .btn-light { height: 28px; padding: 0 10px; display: flex; align-items: center; justify-content: center; line-height: 1; }
.card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color 0.2s ease-in-out; }
.card { border: none; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-radius: 10px; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; }
.card-header h4 i { margin-right: 0.25rem; }
table.dataTable.fixedHeader-floating, table.dataTable.fixedHeader-locked { background: #fff !important; border-bottom: none !important; }
table.dataTable thead th.fixedHeader-floating { background: #e2e2e9 !important; }
</style>

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height: 8px; transform: rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">
<div class="row mb-3"></div>
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<h4 class="header-title mb-0">
   <i class="ri-money-dollar-circle-line"></i> Payment Methods
</h4>
<div class="d-flex align-items-center">
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info"><i class="ri-information-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download options"><i class="ri-download-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn" title="Add new method"><i class="ri-add-circle-line"></i></a>
</div>
<?php $maintableTitle = "Payment Methods"; $methods = DB::table('payment_methods')->get(); ?>
</div>
<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
    <thead style="background-color:#e2e2e9">
    <tr>
        <th style="width:180px">Type</th>
        <th style="text-align:center">Details</th>
        <th style="width:120px; text-align:center">Action</th>
    </tr>
    </thead>
    <tbody id="tbody">
    @foreach($methods as $method)
        <?php $row = "row".$method->id ?>
        <tr id="{{ $row }}">
            <td>
                <strong>
                @if($method->method_type === 'Bank')
                    Bank Transfer
                @elseif($method->method_type === 'Mobile')
                    Mobile Money
                @elseif($method->method_type === 'Paypal')
                    PayPal
                @endif
                </strong>
            </td>
            <td style="text-align:center; vertical-align:middle">
                @if($method->method_type === 'Bank')
                    <div><strong>{{ $method->account_name ?? '—' }}</strong></div>
                    @if($method->bank_name)
                        <small class="text-muted d-block">{{ $method->bank_name }}</small>
                    @endif
                    <small class="text-muted">
                        {{ $method->account_number ?? '—' }}
                        @if($method->account_swift_code)
                            • {{ $method->account_swift_code }}
                        @endif
                        @if($method->account_type || $method->account_branch)
                            • {{ $method->account_type ?? '' }} @if($method->account_branch) / {{ $method->account_branch }} @endif
                        @endif
                    </small>
                @elseif($method->method_type === 'Mobile')
                    <div><strong>{{ $method->mobile_number_name ?? '—' }}</strong></div>
                    <small class="text-muted">
                        {{ $method->mobile_number ?? '—' }}
                        @if($method->mobile_operator) • {{ $method->mobile_operator }} @endif
                    </small>
                @elseif($method->method_type === 'Paypal')
                    <div><strong>{{ $method->paypal_name ?? '—' }}</strong></div>
                    <small class="text-muted">
                        {{ $method->paypal_email ?? '—' }}
                        @if($method->paypal_me_link) • <a href="{{ $method->paypal_me_link }}" target="_blank" class="text-primary">PayPal.me</a> @endif
                    </small>
                @endif
            </td>
            <td style="text-align:center">
                <a href="#" class="editDataBtn"
                   editId="{{ $method->id }}"
                   editRow="{{ $row }}"
                   editMethodType="{{ $method->method_type }}"
                   editAccountName="{{ $method->account_name ?? '' }}"
                   editAccountNumber="{{ $method->account_number ?? '' }}"
                   editAccountType="{{ $method->account_type ?? '' }}"
                   editAccountBranch="{{ $method->account_branch ?? '' }}"
                   editBankName="{{ $method->bank_name ?? '' }}"
                   editAccountSwiftCode="{{ $method->account_swift_code ?? '' }}"
                   editMobileOperator="{{ $method->mobile_operator ?? '' }}"
                   editMobileNumber="{{ $method->mobile_number ?? '' }}"
                   editMobileNumberName="{{ $method->mobile_number_name ?? '' }}"
                   editPaypalName="{{ $method->paypal_name ?? '' }}"
                   editPaypalEmail="{{ $method->paypal_email ?? '' }}"
                   editPaypalMeLink="{{ $method->paypal_me_link ?? '' }}">
                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="deleteDataBtn"
                   deleteLabel="{{ $method->method_type }} Method"
                   deleteId="{{ $method->id }}"
                   deleteRow="{{ $row }}">
                   <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
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

<!-- MODALS -->
<section>
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Click on respective button to download payment methods data</p>
                <div class="buttons"></div>
            </div>
        </div>
    </div>
</section>

<section>
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Methods Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Manage your payment methods by adding, editing, or deleting them.
            </div>
        </div>
    </div>
</section>

<section>
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="newDataForm">
                    @csrf
                    <div class="mb-3">
                        <label class="control-label form-label">Method Type <span class="text-danger">*</span></label>
                        <select class="form-control" name="method_type" id="new-method-type" required>
                            <option value="Bank">Bank Transfer</option>
                            <option value="Mobile">Mobile Money</option>
                            <option value="Paypal">PayPal</option>
                        </select>
                    </div>
                    <div id="bank-fields">
                        <div class="mb-3">
                            <label class="control-label form-label">Bank Name <span class="text-danger">*</span></label>
                            <input class="form-control" placeholder="Enter bank name" type="text" name="bank_name" />
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">Account Name <span class="text-danger">*</span></label>
                            <input class="form-control" placeholder="Enter account name" type="text" name="account_name" />
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">Account Number <span class="text-danger">*</span></label>
                            <input class="form-control" placeholder="Enter account number" type="text" name="account_number" />
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">SWIFT Code (optional)</label>
                            <input class="form-control" placeholder="Enter SWIFT/BIC code" type="text" name="account_swift_code"/>
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">Account Type (optional)</label>
                            <input class="form-control" placeholder="Enter account type" type="text" name="account_type"/>
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">Branch (optional)</label>
                            <input class="form-control" placeholder="Enter branch" type="text" name="account_branch"/>
                        </div>
                    </div>
                    <div id="mobile-fields" style="display:none">
                        <div class="mb-3">
                            <label class="control-label form-label">Mobile Operator <span class="text-danger">*</span></label>
                            <input class="form-control" placeholder="e.g. MTN, Airtel" type="text" name="mobile_operator" />
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">Mobile Number <span class="text-danger">*</span></label>
                            <input class="form-control" placeholder="Enter mobile number" type="text" name="mobile_number" />
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">Registered Name <span class="text-danger">*</span></label>
                            <input class="form-control" placeholder="Name on the number" type="text" name="mobile_number_name" />
                        </div>
                    </div>
                    <div id="paypal-fields" style="display:none">
                        <div class="mb-3">
                            <label class="control-label form-label">PayPal Name <span class="text-danger">*</span></label>
                            <input class="form-control" placeholder="Full name on PayPal" type="text" name="paypal_name" />
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">PayPal Email <span class="text-danger">*</span></label>
                            <input class="form-control" placeholder="email@paypal.com" type="email" name="paypal_email" />
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">PayPal.me Link (optional)</label>
                            <input class="form-control" placeholder="https://paypal.me/yourname" type="text" name="paypal_me_link"/>
                        </div>
                    </div>
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelDataBtn">Clear</a>
                </form>
            </div>
        </div>
    </div>
</section>

<section>
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="editDataForm">
                    @csrf
                    <div class="form-group">
                        <input type="hidden" name="id" id="editId">
                        <input type="hidden" name="editrow" id="editRow">
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Method Type <span class="text-danger">*</span></label>
                        <select class="form-control" name="method_type" id="edit-method-type" required>
                            <option value="Bank">Bank Transfer</option>
                            <option value="Mobile">Mobile Money</option>
                            <option value="Paypal">PayPal</option>
                        </select>
                    </div>
                    <div id="edit-bank-fields">
                        <div class="mb-3">
                            <label class="control-label form-label">Bank Name <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" name="bank_name" id="editBankName"/>
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">Account Name <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" name="account_name" id="editAccountName"/>
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">Account Number <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" name="account_number" id="editAccountNumber"/>
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">SWIFT Code (optional)</label>
                            <input class="form-control" type="text" name="account_swift_code" id="editAccountSwiftCode"/>
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">Account Type (optional)</label>
                            <input class="form-control" type="text" name="account_type" id="editAccountType"/>
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">Branch (optional)</label>
                            <input class="form-control" type="text" name="account_branch" id="editAccountBranch"/>
                        </div>
                    </div>
                    <div id="edit-mobile-fields" style="display:none">
                        <div class="mb-3">
                            <label class="control-label form-label">Mobile Operator <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" name="mobile_operator" id="editMobileOperator"/>
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">Mobile Number <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" name="mobile_number" id="editMobileNumber"/>
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">Registered Name <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" name="mobile_number_name" id="editMobileNumberName"/>
                        </div>
                    </div>
                    <div id="edit-paypal-fields" style="display:none">
                        <div class="mb-3">
                            <label class="control-label form-label">PayPal Name <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" name="paypal_name" id="editPaypalName"/>
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">PayPal Email <span class="text-danger">*</span></label>
                            <input class="form-control" type="email" name="paypal_email" id="editPaypalEmail"/>
                        </div>
                        <div class="mb-3">
                            <label class="control-label form-label">PayPal.me Link (optional)</label>
                            <input class="form-control" type="text" name="paypal_me_link" id="editPaypalMeLink"/>
                        </div>
                    </div>
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitUpdateDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelEditDataBtn">Clear</a>
                </form>
            </div>
        </div>
    </div>
</section>

<section>
<div class="modal fade" id="singleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:350px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                <form action="#" method="post" id="singleDeleteDataForm">
                    @csrf
                    <div class="form-group">
                      <h4>Are you sure you want to delete <span id="singleDisplayDeleteLabel"></span>?</h4>
                    </div>
                    <div class="form-group">
                        <h5>You won't be able to revert this!</h5>
                    </div>
                    <div class="form-group">
                        <input type="hidden" id="singleDeleteId" name="id">
                        <input type="hidden" id="singleDeleteRow">
                    </div>
                    <div class="form-group">
                        <a href="#" class="btn btn-danger" id="submitSingleDeleteDataBtn" style="margin-top:10px;margin-bottom:10px;margin-right:5px">Yes, Delete it</a>
                        <a href="#" class="btn btn-info" id="keepSingleDataBtn" style="margin-top:10px;margin-bottom:10px;">No, Keep it</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    toastr.options = {
        closeButton: true,
        progressBar: true,
        showMethod: 'slideDown',
        timeOut: 5000,
        allowHtml: true
    };

    var table;

    function initDataTable() {
        table = $('#maintable').DataTable({
            dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
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
        table.buttons().container().appendTo($('#buttonsModal .buttons'));

        // Button clicks
        $('#infoBtn').click(function (e) { e.preventDefault(); $('#infoModal').modal('show'); });
        $('#tableButtonsBtn').click(function (e) { e.preventDefault(); $('#buttonsModal').modal('show'); });
    }

    // Field toggle for ADD modal
    $('#new-method-type').on('change', function() {
        $('#bank-fields').hide();
        $('#mobile-fields').hide();
        $('#paypal-fields').hide();
        
        $('input[name="bank_name"], input[name="account_name"], input[name="account_number"], input[name="mobile_operator"], input[name="mobile_number"], input[name="mobile_number_name"], input[name="paypal_name"], input[name="paypal_email"]').prop('required', false);
        
        var type = $(this).val();
        if(type === 'Bank') {
            $('#bank-fields').show();
            $('input[name="bank_name"], input[name="account_name"], input[name="account_number"]').prop('required', true);
        } else if(type === 'Mobile') {
            $('#mobile-fields').show();
            $('input[name="mobile_operator"], input[name="mobile_number"], input[name="mobile_number_name"]').prop('required', true);
        } else if(type === 'Paypal') {
            $('#paypal-fields').show();
            $('input[name="paypal_name"], input[name="paypal_email"]').prop('required', true);
        }
    });

    // Field toggle for EDIT modal
    $('#edit-method-type').on('change', function() {
        $('#edit-bank-fields').hide();
        $('#edit-mobile-fields').hide();
        $('#edit-paypal-fields').hide();
        
        $('#edit-bank-fields input, #edit-mobile-fields input, #edit-paypal-fields input').prop('required', false);
        
        var type = $(this).val();
        if(type === 'Bank') {
            $('#edit-bank-fields').show();
            $('#editBankName, #editAccountName, #editAccountNumber').prop('required', true);
        } else if(type === 'Mobile') {
            $('#edit-mobile-fields').show();
            $('#editMobileOperator, #editMobileNumber, #editMobileNumberName').prop('required', true);
        } else if(type === 'Paypal') {
            $('#edit-paypal-fields').show();
            $('#editPaypalName, #editPaypalEmail').prop('required', true);
        }
    });

    // ADD NEW METHOD
    $('#newDataBtn').click(function (e) { 
        e.preventDefault(); 
        $('#newDataForm')[0].reset(); 
        $('#new-method-type').val('Bank').trigger('change');
        $('#newDataModal').modal('show'); 
    });

    $('#submitDataBtn').click(function (e) {
        e.preventDefault();
        var self = $(this); 
        self.prop('disabled', true).html('Submitting...');
        var formData = $('#newDataForm').serialize();

        $.ajax({
            type: 'POST',
            url: '{{ route("master.payment.method.insert") }}',
            data: formData,
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete: function () { 
                $('#progressBar').hide(); 
                self.prop('disabled', false).html('Submit');
            },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var m = data.method;
                    var newRow = buildPaymentMethodRow(m);
                    table.row.add($(newRow)).draw(false);
                    $('#newDataModal').modal('hide');
                } else if (data.status === 422) {
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else {
                    toastr.info('Unspecified error occurred.', 'Error');
                }
            },
            error: function (xhr, status, error) {
                if (status === 'timeout') toastr.error('The request timed out.', 'Timeout Error');
                else if (xhr.status === 0) toastr.error('Unable to connect.', 'Connection Error');
                else if (xhr.status === 422) {
                    var msg = ''; 
                    if (xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(k,v){ msg += v[0] + '<br>'; });
                    }
                    toastr.error(msg, 'Validation Errors');
                } else if (xhr.status === 500) toastr.error('Server error.', 'Server Error');
                else toastr.error('Error occurred.', 'Error');
            }
        });
    });

    // UPDATE METHOD
    $('#tbody').on('click', '.editDataBtn', function () {
        var t = $(this);
        $('#editId').val(t.attr('editId'));
        $('#editRow').val(t.attr('editRow'));
        $('#edit-method-type').val(t.attr('editMethodType')).trigger('change');

        // Clear all fields first
        $('#edit-bank-fields input, #edit-mobile-fields input, #edit-paypal-fields input').val('');

        // Populate based on type
        if (t.attr('editMethodType') === 'Bank') {
            $('#editBankName').val(t.attr('editBankName'));
            $('#editAccountName').val(t.attr('editAccountName'));
            $('#editAccountNumber').val(t.attr('editAccountNumber'));
            $('#editAccountSwiftCode').val(t.attr('editAccountSwiftCode'));
            $('#editAccountType').val(t.attr('editAccountType'));
            $('#editAccountBranch').val(t.attr('editAccountBranch'));
        } else if (t.attr('editMethodType') === 'Mobile') {
            $('#editMobileOperator').val(t.attr('editMobileOperator'));
            $('#editMobileNumber').val(t.attr('editMobileNumber'));
            $('#editMobileNumberName').val(t.attr('editMobileNumberName'));
        } else if (t.attr('editMethodType') === 'Paypal') {
            $('#editPaypalName').val(t.attr('editPaypalName'));
            $('#editPaypalEmail').val(t.attr('editPaypalEmail'));
            $('#editPaypalMeLink').val(t.attr('editPaypalMeLink'));
        }

        $('#editDataModal').modal('show');
    });

    $('#submitUpdateDataBtn').click(function (e) {
        e.preventDefault();
        var self = $(this); 
        self.prop('disabled', true).html('Updating...');
        var formData = $('#editDataForm').serialize();
        var row = $('#editRow').val();

        $.ajax({
            type: 'POST',
            url: '{{ route("master.payment.method.update") }}',
            data: formData,
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete: function () { 
                $('#progressBar').hide(); 
                self.prop('disabled', false).html('Submit');
            },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var m = data.method;
                    var updatedRow = buildPaymentMethodRow(m);
                    table.row('#' + row).remove();
                    table.row.add($(updatedRow)).draw(false);
                    $('#editDataModal').modal('hide');
                }
            },
            error: function (xhr, status, error) {
                if (status === 'timeout') toastr.error('The request timed out.', 'Timeout Error');
                else if (xhr.status === 0) toastr.error('Unable to connect.', 'Connection Error');
                else if (xhr.status === 422) {
                    var msg = ''; 
                    if (xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(k,v){ msg += v[0] + '<br>'; });
                    }
                    toastr.error(msg, 'Validation Errors');
                } else if (xhr.status === 409 || xhr.status === 404) {
                    toastr.error(xhr.responseJSON.error || 'Method not found', 'Error');
                } else if (xhr.status === 500) toastr.error('Server error.', 'Server Error');
                else toastr.error('Error occurred.', 'Error');
            }
        });
    });

    // DELETE
    $('#tbody').on('click', '.deleteDataBtn', function () {
        $('#singleDisplayDeleteLabel').html($(this).attr('deleteLabel'));
        $('#singleDeleteRow').val($(this).attr('deleteRow'));
        $('#singleDeleteId').val($(this).attr('deleteId'));
        $('#singleDeleteDataModal').modal('show');
    });

    $('#keepSingleDataBtn').click(function (e) {
        e.preventDefault();
        toastr.info('Your method is safe!', 'Great!');
        $('#singleDeleteDataModal').modal('hide');
    });

    $('#submitSingleDeleteDataBtn').click(function (e) {
        e.preventDefault();
        var self = $(this); 
        self.prop('disabled', true).html('Deleting...');
        var row = $('#singleDeleteRow').val();
        var methodId = $('#singleDeleteId').val();

        $.ajax({
            type: 'POST',
            url: '{{ route("master.payment.method.delete") }}',
            data: { 
                id: methodId, 
                _token: '{{ csrf_token() }}' 
            },
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete: function () { 
                $('#progressBar').hide(); 
                self.prop('disabled', false).html('Yes, Delete it');
            },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#' + row).remove().draw(false);
                    $('#singleDeleteDataModal').modal('hide');
                }
            },
            error: function (xhr) {
                if (xhr.status === 404) {
                    toastr.error('Method not found.', 'Error');
                } else {
                    toastr.error('Delete failed.', 'Error');
                }
            }
        });
    });

    // CANCEL BUTTONS
    $('#cancelDataBtn').click(function (e) { 
        e.preventDefault(); 
        $('#newDataForm')[0].reset(); 
        $('#new-method-type').val('Bank').trigger('change');
        $('#newDataModal').modal('hide'); 
    });

    $('#cancelEditDataBtn').click(function (e) { 
        e.preventDefault(); 
        $('#editDataModal').modal('hide'); 
    });

    // BUILD ROW FUNCTION
    function buildPaymentMethodRow(m) {
        var typeText = '';
        var detailsHtml = '';
        
        if (m.method_type === 'Bank') {
            typeText = 'Bank Transfer';
            detailsHtml = `<div><strong>${m.account_name || '—'}</strong></div>`;
            if (m.bank_name) {
                detailsHtml += `<small class="text-muted d-block">${m.bank_name}</small>`;
            }
            detailsHtml += `<small class="text-muted">
                               ${m.account_number || '—'}
                               ${m.account_swift_code ? ' • ' + m.account_swift_code : ''}
                               ${m.account_type || m.account_branch ? ' • ' + (m.account_type || '') + (m.account_branch ? ' / ' + m.account_branch : '') : ''}
                           </small>`;
        } else if (m.method_type === 'Mobile') {
            typeText = 'Mobile Money';
            detailsHtml = `<div><strong>${m.mobile_number_name || '—'}</strong></div>
                           <small class="text-muted">
                               ${m.mobile_number || '—'}
                               ${m.mobile_operator ? ' • ' + m.mobile_operator : ''}
                           </small>`;
        } else if (m.method_type === 'Paypal') {
            typeText = 'PayPal';
            detailsHtml = `<div><strong>${m.paypal_name || '—'}</strong></div>
                           <small class="text-muted">
                               ${m.paypal_email || '—'}
                               ${m.paypal_me_link ? ' • <a href="' + m.paypal_me_link + '" target="_blank" class="text-primary">PayPal.me</a>' : ''}
                           </small>`;
        }

        return `<tr id="row${m.id}">
            <td><strong>${typeText}</strong></td>
            <td style="text-align:center; vertical-align:middle">${detailsHtml}</td>
            <td style="text-align:center">
                <a href="#" class="editDataBtn"
                   editId="${m.id}" editRow="row${m.id}"
                   editMethodType="${m.method_type}"
                   editBankName="${m.bank_name || ''}"
                   editAccountName="${m.account_name || ''}"
                   editAccountNumber="${m.account_number || ''}"
                   editAccountSwiftCode="${m.account_swift_code || ''}"
                   editAccountType="${m.account_type || ''}"
                   editAccountBranch="${m.account_branch || ''}"
                   editMobileOperator="${m.mobile_operator || ''}"
                   editMobileNumber="${m.mobile_number || ''}"
                   editMobileNumberName="${m.mobile_number_name || ''}"
                   editPaypalName="${m.paypal_name || ''}"
                   editPaypalEmail="${m.paypal_email || ''}"
                   editPaypalMeLink="${m.paypal_me_link || ''}">
                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="deleteDataBtn"
                   deleteLabel="${m.method_type} Method" deleteId="${m.id}" deleteRow="row${m.id}">
                   <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
                </a>
            </td>
        </tr>`;
    }

    initDataTable();
});
</script>
@endsection