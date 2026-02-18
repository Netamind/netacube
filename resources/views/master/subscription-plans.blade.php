@extends('master.dashboard')
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
   <i class="ri-money-dollar-circle-line"></i> Subscription Plans
</h4>
<div class="d-flex align-items-center">
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info"><i class="ri-information-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download options"><i class="ri-download-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn" title="Add new plan"><i class="ri-add-circle-line"></i></a>
</div>
<?php $maintableTitle = "Subscription Plans"; $plans = DB::table('subscription_plans')->get(); ?>
</div>

<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
    <thead style="background-color:#e2e2e9">
    <tr>
        <th>Plan</th>
        <th style="text-align:center">Period</th>
        <th style="text-align:center">Days</th>
        <th style="text-align:center">Amount</th>
        <th style="text-align:center">Currency</th>
        <th style="text-align:center">Description</th>
        <th style="text-align:center">Action</th>
    </tr>
    </thead>
    <tbody id="tbody">
    @foreach($plans as $plan)
        <?php $row = "row".$plan->id ?>
        <tr id="{{ $row }}">
            <td>{{ $plan->plan_name }}</td>
            <td style="text-align:center">
                {{ $plan->plan_period }}
                @if($plan->plan_period_name)
                 <small class="text-muted">({{ $plan->plan_period_name }})</small>
                @endif
            </td>
            <td style="text-align:center">{{ $plan->plan_days }}</td>
            <td style="text-align:center">{{ $plan->plan_amount }}</td>
            <td style="text-align:center">
                {{ $plan->plan_currency }}
                @if($plan->plan_currency_name)
                    <small class="text-muted">({{ $plan->plan_currency_name }})</small>
                @endif
            </td>
            <td style="text-align:center">{{ $plan->plan_description ?? 'Not available' }}</td>
            <td style="text-align:center">
                <a href="#" class="editDataBtn"
                   editId="{{ $plan->id }}"
                   editRow="{{ $row }}"
                   editPlanName="{{ $plan->plan_name }}"
                   editPlanPeriod="{{ $plan->plan_period }}"
                   editPlanPeriodName="{{ $plan->plan_period_name ?? '' }}"
                   editPlanDays="{{ $plan->plan_days }}"
                   editPlanAmount="{{ $plan->plan_amount }}"
                   editPlanCurrency="{{ $plan->plan_currency ?? 'USD' }}"
                   editPlanCurrencyName="{{ $plan->plan_currency_name ?? '' }}"
                   editPlanDescription="{{ $plan->plan_description ?? '' }}">
                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;color:blue2"></i>
                </a>
                <a href="#" class="deleteDataBtn"
                   deleteLabel="{{ $plan->plan_name }}"
                   deleteId="{{ $plan->id }}"
                   deleteRow="{{ $row }}">
                   <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;color:red2"></i>
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

<!-- Modals -->
<section>
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Click on respective button to download subscription plans data</p>
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
                <h5 class="modal-title">Subscription Plans Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Manage your subscription plans by adding, editing, or deleting them.
            </div>
        </div>
    </div>
</section>

<!-- Add New Plan Modal -->
<section>
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Subscription Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="newDataForm">
                    @csrf
                    <div class="mb-3">
                        <label class="control-label form-label">Plan Name </label>
                        <input class="form-control" placeholder="Enter plan name" type="text" name="plan_name" id="plan-name" autocomplete="off" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Plan Period </label>
                        <input class="form-control" placeholder="e.g. 1 Month, 3 Months" type="text" name="plan_period" id="plan-period" autocomplete="off" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Plan Period Display Name <small class="text-muted">(optional)</small></label>
                        <input class="form-control" placeholder="e.g. Monthly, Yearly" type="text" name="plan_period_name" id="plan-period-name" autocomplete="off"/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Plan Days </label>
                        <input class="form-control" placeholder="e.g. 30 for monthly, 365 for yearly" type="number" min="1" name="plan_days" id="plan-days" autocomplete="off" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Amount </label>
                        <input class="form-control" placeholder="e.g. 9.99" type="number" step="0.01" name="plan_amount" id="plan-amount" autocomplete="off" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Currency </label>
                        <input class="form-control" placeholder="e.g. USD, EUR, GBP" type="text" name="plan_currency" id="plan-currency" value="USD" autocomplete="off" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Currency Display Name <small class="text-muted">(optional)</small></label>
                        <input class="form-control" placeholder="e.g. US Dollar, Euro" type="text" name="plan_currency_name" id="plan-currency-name" value="US Dollar" autocomplete="off"/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Description </label>
                        <textarea class="form-control" placeholder="Enter plan description (optional)" name="plan_description" id="plan-description" rows="3"></textarea>
                    </div>
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelDataBtn">Clear</a>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Delete Confirmation Modal -->
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

<!-- Edit Plan Modal -->
<section>
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Subscription Plan</h5>
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
                        <label class="control-label form-label">Plan Name </label>
                        <input class="form-control" type="text" name="plan_name" id="editPlanName" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Plan Period </label>
                        <input class="form-control" type="text" name="plan_period" id="editPlanPeriod" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Plan Period Display Name</label>
                        <input class="form-control" type="text" name="plan_period_name" id="editPlanPeriodName"/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Plan Days </label>
                        <input class="form-control" type="number" min="1" name="plan_days" id="editPlanDays" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Amount </label>
                        <input class="form-control" type="number" step="0.01" name="plan_amount" id="editPlanAmount" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Currency </label>
                        <input class="form-control" type="text" name="plan_currency" id="editPlanCurrency" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Currency Display Name</label>
                        <input class="form-control" type="text" name="plan_currency_name" id="editPlanCurrencyName"/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Description </label>
                        <textarea class="form-control" name="plan_description" id="editPlanDescription" rows="3"></textarea>
                    </div>
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitUpdateDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelEditDataBtn">Clear</a>
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

    function initDataTable() {
        var table = $('#maintable').DataTable({
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

        $('#newDataBtn').click(function (e) { 
            e.preventDefault(); 
            $('#newDataForm')[0].reset(); 
            $('#plan-currency').val('USD');
            $('#plan-currency-name').val('US Dollar');
            $('#newDataModal').modal('show'); 
        });
        $('#infoBtn').click(function (e) { e.preventDefault(); $('#infoModal').modal('show'); });
        $('#tableButtonsBtn').click(function (e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

        // ADD NEW PLAN
        $('#submitDataBtn').click(function (e) {
            e.preventDefault();
            var self = $(this); self.prop('disabled', true);
            var formData = $('#newDataForm').serialize();

            $.ajax({
                type: 'POST',
                url: '{{ route("master.subscription.plan.insert") }}',
                data: formData + '&_token={{ csrf_token() }}',
                timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete: function () { $('#progressBar').hide(); self.prop('disabled', false); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success');
                        var p = data.plan;
                        var newRow = `<tr id="row${p.id}">
                            <td>${p.plan_name}</td>
                            <td style="text-align:center">
                                ${p.plan_period}
                                ${p.plan_period_name ? `<small class="text-muted">(${p.plan_period_name})</small>` : ''}
                            </td>
                            <td style="text-align:center">${p.plan_days}</td>
                            <td style="text-align:center">${p.plan_amount}</td>
                            <td style="text-align:center">
                                ${p.plan_currency}
                                ${p.plan_currency_name ? `<small class="text-muted">(${p.plan_currency_name})</small>` : ''}
                            </td>
                            <td style="text-align:center">${p.plan_description || 'Not available'}</td>
                            <td style="text-align:center">
                                <a href="#" class="editDataBtn"
                                   editId="${p.id}" editRow="row${p.id}"
                                   editPlanName="${p.plan_name}"
                                   editPlanPeriod="${p.plan_period}"
                                   editPlanPeriodName="${p.plan_period_name || ''}"
                                   editPlanDays="${p.plan_days}"
                                   editPlanAmount="${p.plan_amount}"
                                   editPlanCurrency="${p.plan_currency || 'USD'}"
                                   editPlanCurrencyName="${p.plan_currency_name || ''}"
                                   editPlanDescription="${p.plan_description || ''}">
                                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
                                </a>
                                <a href="#" class="deleteDataBtn"
                                   deleteLabel="${p.plan_name}" deleteId="${p.id}" deleteRow="row${p.id}">
                                   <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
                                </a>
                            </td>
                        </tr>`;
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
                        var msg = ''; $.each(xhr.responseJSON.errors, function(k,v){ msg += v + '\n'; });
                        toastr.error(msg, 'Validation Errors');
                    } else if (xhr.status === 500) toastr.error('Server error.', 'Server Error');
                    else toastr.error('Error occurred.', 'Error');
                }
            });
        });

        $('#cancelDataBtn').click(function (e) { 
            e.preventDefault(); 
            $('#newDataForm')[0].reset(); 
            $('#plan-currency').val('USD');
            $('#plan-currency-name').val('US Dollar');
            $('#newDataModal').modal('hide'); 
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
            toastr.info('Your plan is safe', 'Great!');
            $('#singleDeleteDataModal').modal('hide');
        });

        $('#submitSingleDeleteDataBtn').click(function (e) {
            e.preventDefault();
            var self = $(this); self.prop('disabled', true);
            var row = $('#singleDeleteRow').val();
            var planId = $('#singleDeleteId').val();

            $.ajax({
                type: 'POST',
                url: '{{ route("master.subscription.plan.delete") }}',
                data: { id: planId, _token: '{{ csrf_token() }}' },
                timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete: function () { $('#progressBar').hide(); self.prop('disabled', false); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success');
                        table.row('#' + row).remove().draw(false);
                        $('#singleDeleteDataModal').modal('hide');
                    }
                },
                error: function (xhr) {
                    toastr.error('Delete failed.', 'Error');
                }
            });
        });

        // EDIT
        $('#tbody').on('click', '.editDataBtn', function () {
            $('#editId').val($(this).attr('editId'));
            $('#editRow').val($(this).attr('editRow'));
            $('#editPlanName').val($(this).attr('editPlanName'));
            $('#editPlanPeriod').val($(this).attr('editPlanPeriod'));
            $('#editPlanPeriodName').val($(this).attr('editPlanPeriodName'));
            $('#editPlanDays').val($(this).attr('editPlanDays'));
            $('#editPlanAmount').val($(this).attr('editPlanAmount'));
            $('#editPlanCurrency').val($(this).attr('editPlanCurrency'));
            $('#editPlanCurrencyName').val($(this).attr('editPlanCurrencyName'));
            $('#editPlanDescription').val($(this).attr('editPlanDescription'));
            $('#editDataModal').modal('show');
        });

        $('#submitUpdateDataBtn').click(function (e) {
            e.preventDefault();
            var self = $(this); self.prop('disabled', true);
            var formData = $('#editDataForm').serialize();
            var row = $('#editRow').val();

            $.ajax({
                type: 'POST',
                url: '{{ route("master.subscription.plan.update") }}',
                data: formData + '&_token={{ csrf_token() }}',
                timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete: function () { $('#progressBar').hide(); self.prop('disabled', false); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success');
                        var p = data.plan;
                        var updatedRow = `<tr id="${row}">
                            <td>${p.plan_name}</td>
                            <td style="text-align:center">
                                ${p.plan_period}
                                ${p.plan_period_name ? `<small class="text-muted">(${p.plan_period_name})</small>` : ''}
                            </td>
                            <td style="text-align:center">${p.plan_days}</td>
                            <td style="text-align:center">${p.plan_amount}</td>
                            <td style="text-align:center">
                                ${p.plan_currency}
                                ${p.plan_currency_name ? `<small class="text-muted">(${p.plan_currency_name})</small>` : ''}
                            </td>
                            <td style="text-align:center">${p.plan_description || 'Not available'}</td>
                            <td style="text-align:center">
                                <a href="#" class="editDataBtn"
                                   editId="${p.id}" editRow="${row}"
                                   editPlanName="${p.plan_name}"
                                   editPlanPeriod="${p.plan_period}"
                                   editPlanPeriodName="${p.plan_period_name || ''}"
                                   editPlanDays="${p.plan_days}"
                                   editPlanAmount="${p.plan_amount}"
                                   editPlanCurrency="${p.plan_currency || 'USD'}"
                                   editPlanCurrencyName="${p.plan_currency_name || ''}"
                                   editPlanDescription="${p.plan_description || ''}">
                                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
                                </a>
                                <a href="#" class="deleteDataBtn"
                                   deleteLabel="${p.plan_name}" deleteId="${p.id}" deleteRow="${row}">
                                   <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
                                </a>
                            </td>
                        </tr>`;
                        table.row('#' + row).remove();
                        table.row.add($(updatedRow)).draw(false);
                        $('#editDataModal').modal('hide');
                    }
                },
                error: function (xhr) {
                    toastr.error('Update failed.', 'Error');
                }
            });
        });

        $('#cancelEditDataBtn').click(function (e) { 
            e.preventDefault(); 
            $('#editDataForm')[0].reset(); 
            $('#editDataModal').modal('hide'); 
        });
    }

    initDataTable();
});
</script>
@endsection