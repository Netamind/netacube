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

.dt-buttons .btn:hover {
  background: #5bc0de !important;
  color: #fff;
}

.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0);
  color: #fff;
}

.card-body {
  padding: 0 1.5rem 1.5rem 1.5rem !important;
}

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

#deleteSelectedBtn {
  height: 28px;
  padding: 0 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  position: relative;
}

#deleteSelectedBtn .badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background-color: #dc3545;
  color: #fff;
  font-size: 12px;
  padding: 2px 6px;
  border-radius: 50%;
  display: none;
}

#deleteSelectedBtn:hover {
  background-color: #f8f9fa;
}

.card {
  border: none;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  border-radius: 10px;
}

.card-header h4 {
  color: #fff;
  font-weight: 600;
  margin-bottom: 0;
  display: flex;
  align-items: center;
}

.card-header h4 i {
  margin-right: 0.25rem;
}

.card-body {
  padding: 0 1.5rem 1.5rem 1.5rem;
}

/* Beautified Status Badge */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-active {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.status-inactive {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.status-badge::before {
  content: '';
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.status-active::before {
  background-color: #28a745;
}

.status-inactive::before {
  background-color: #dc3545;
}
</style>

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height: 8px; transform: rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">

<!-- start page title -->
<div class="row mb-3">
</div>
<!-- end page title -->

<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<h4 class="header-title mb-0">
<i class="ri-team-line"></i> Tenant Management
</h4>
<div class="d-flex align-items-center">
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="deleteSelectedBtn" title="Selected Actions"><i class="ri-checkbox-circle-line"></i><span class="badge" id="selectedCount">0</span></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn" title="Add new tenant"><i class="ri-user-add-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info"><i class="ri-information-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download options"><i class="ri-download-line"></i></a>
</div>
 <?php
    $maintableTitle = "Tenants";
    $tenants = DB::table('tenants')->get();
   ?>
</div>
<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
        <thead style="background-color:#e2e2e9">
        <tr>
            <th><input type="checkbox" id="selectAll">&nbsp;&nbsp;Full Name</th>
            <th style="text-align:center">Business Name</th>
            <th style="text-align:center">Email</th>
            <th style="text-align:center">Phone Number</th>
            <th style="text-align:center">Subscription Plan</th>
            <th style="text-align:center">Payment Amount</th>
            <th style="text-align:center">Next Payment Date</th>
            <th style="text-align:center">Status</th>
            <th style="text-align:center">Action</th>
        </tr>
        </thead>
        <tbody id="tbody">
         @foreach($tenants as $tenant)
            <?php $row = "row".$tenant->id ?>
            <tr id="{{$row}}">
            <td><input type="checkbox" class="selectRow" value="{{ $tenant->id }}" data-row-id="{{ $row }}">&nbsp;{{ $tenant->full_name }}</td>
            <td style="text-align:center">{{ $tenant->business_name }}</td>
            <td style="text-align:center">{{ $tenant->email }}</td>
            <td style="text-align:center">{{ $tenant->phone_number }}</td>
            <?php $plan = DB::table('subscription_plans')->where('id',$tenant->subscription_plan)->first(); ?>
            <td style="text-align:center">
                {{ optional($plan)->plan_name }}
                <span class="text-muted">({{ optional($plan)->plan_period }})</span>
            </td>
            <td style="text-align:center">{{ optional($plan)->plan_amount }} {{ optional($plan)->plan_currency }}</td>
            <td style="text-align:center">{{ $tenant->next_payment_date ?? 'NA' }}</td>
            <td style="text-align:center">
                <span class="status-badge {{ $tenant->status == 'active' ? 'status-active' : 'status-inactive' }}">
                    {{ ucfirst($tenant->status) }}
                </span>
            </td>
            <td style="text-align:center">
                <a href="{{ route('master.tenant.details') }}?id={{ $tenant->id }}" class="btn btn-light text-primary"><i class="ri-settings-2-line"></i></a>
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

<!-- Download Buttons Modal -->
<section>
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                 Click on respective button to download tenants data
                </p>
                <div class="buttons"></div>
            </div>
        </div>
    </div>
</section>

<!-- Info Modal -->
<section>
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tenant Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Manage tenant accounts, subscription plans, payment schedules, and access details.
            </div>
        </div>
    </div>
</section>

<!-- Add New Tenant Modal -->
<section>
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Tenant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="newDataForm">
                    @csrf
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Full Name</label>
                        <input class="form-control" type="text" id="fullname" name="full_name"
                               placeholder="Enter your full name" required autocomplete>
                    </div>

                    <div class="mb-3">
                        <label for="emailaddress" class="form-label">Email address</label>
                        <input class="form-control" type="email" id="emailaddress" name="email"
                               required placeholder="Enter your email address" autocomplete>
                    </div>

                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone number</label>
                        <input class="form-control" type="tel" id="phone_number" name="phone_number"
                               required placeholder="Enter your phone number" autocomplete>
                    </div>

                    <div class="mb-3">
                        <label for="business_name" class="form-label">Business/Company name</label>
                        <input class="form-control" type="text" id="business_name" name="business_name"
                               required placeholder="Enter business name" autocomplete>
                    </div>

                    <div class="mb-3">
                        <label for="subscription_plan" class="form-label">Subscription Plan</label>
                        <select class="form-select" name="subscription_plan" id="subscription_plan" required>
                            <option value="">Select Plan</option>
                            @foreach(DB::table('subscription_plans')->get() as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->plan_name }} ({{ $plan->plan_period }}) - {{ $plan->plan_amount }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" id="cancelDataBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitDataBtn">Add Tenant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Action Modal -->
<section>
<div class="modal fade" id="actionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Selected Tenants</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 text-center">
                    <h5><span id="selectedCountDisplay">0</span> Tenant(s) Selected</h5>
                    <p class="text-muted">What would you like to do with the selected tenants?</p>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="#" class="btn btn-success" id="changeStatusBtn">Change Status</a>
                    <a href="#" class="btn btn-warning" id="putOnHoldBtn">Put on Hold</a>
                </div>
            </div>
        </div>
    </div>
</section>

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

        function initDataTable() {
            var table = $('#maintable').DataTable({
                dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
                lengthChange: true,
                lengthMenu: [
                    [100, 250, 500, -1],
                    [100, 250, 500, "All"]
                ],
                fixedColumns: {
                    leftColumns: 1
                },
                scrollX: true,
                buttons: [
                    {
                        extend: 'excelHtml5',
                        title: @json($maintableTitle),
                        exportOptions: {
                            columns: ':visible:not(:last-child)'
                        }
                    },
                    {
                        extend: 'csvHtml5',
                        title: @json($maintableTitle),
                        exportOptions: {
                            columns: ':visible:not(:last-child)'
                        }
                    },
                    {
                        extend: 'pdfHtml5',
                        title: @json($maintableTitle),
                        exportOptions: {
                            columns: ':visible:not(:last-child)'
                        },
                        customize: function (doc) {
                            doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                            doc.content[1].table.body.forEach(function (row) {
                                row[0].alignment = 'left';
                                for (var j = 1; j < row.length; j++) row[j].alignment = 'center';
                            });
                        }
                    },
                    {
                        extend: 'print',
                        title: @json($maintableTitle),
                        exportOptions: {
                            columns: ':visible:not(:last-child)'
                        }
                    }
                ]
            });
            table.buttons().container().appendTo($('#buttonsModal .buttons'));

            // Modals
            $('#tableButtonsBtn').click(function(e) {
                e.preventDefault();
                $('#buttonsModal').modal('show');
            });

            $('#infoBtn').click(function(e) {
                e.preventDefault();
                $('#infoModal').modal('show');
            });

            $('#newDataBtn').click(function(e) {
                e.preventDefault();
                $('#newDataForm')[0].reset();
                $('#newDataModal').modal('show');
            });

            // === ADD NEW TENANT ===
            $('#submitDataBtn').click(function(e) {
                var self = $(this);
                $(this).prop("disabled", true);
                var form = document.getElementById("newDataForm");
                e.preventDefault();

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    type: "POST",
                    url: "{{ route('master.add.tenant') }}",
                    data: $(form).serialize(),
                    timeout: 60000,
                    beforeSend: function() {
                        $('#progressBar').show();
                    },
                    complete: function() {
                        $('#progressBar').hide();
                        self.prop("disabled", false);
                    },
                    success: function(data) {
                        if (data.status === 201) {
                            toastr.success(data.success, 'Success', {
                                timeOut: 5000,
                                progressBar: true
                            });

                            var tenant = data.tenant;
                            var statusClass = tenant.status === 'active' ? 'status-active' : 'status-inactive';
                            var nextPayment = tenant.next_payment_date || 'NA';
                            var newRow = `
                                <tr id="row${tenant.id}">
                                    <td><input type="checkbox" class="selectRow" value="${tenant.id}" data-row-id="row${tenant.id}">&nbsp;${tenant.full_name}</td>
                                    <td style="text-align:center">${tenant.business_name}</td>
                                    <td style="text-align:center">${tenant.email}</td>
                                    <td style="text-align:center">${tenant.phone_number}</td>
                                    <td style="text-align:center">${tenant.plan_name} <span class="text-muted">(${tenant.plan_period})</span></td>
                                    <td style="text-align:center">${tenant.plan_amount}</td>
                                    <td style="text-align:center">${nextPayment}</td>
                                    <td style="text-align:center">
                                        <span class="status-badge ${statusClass}">${tenant.status.charAt(0).toUpperCase() + tenant.status.slice(1)}</span>
                                    </td>
                                    <td style="text-align:center">
                                        <a href="{{ route('master.tenant.details') }}?id=${tenant.id}" class="btn btn-light text-primary"><i class="ri-settings-2-line"></i></a>
                                    </td>
                                </tr>
                            `;
                            table.row.add($(newRow)).draw(false);
                            $('#newDataModal').modal('hide');
                        } else if (data.status === 422) {
                            toastr.error(data.error || 'Validation failed.', 'Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else {
                            toastr.info('Unspecified error occurred.', 'Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        if (status === 'timeout') {
                            toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 0) {
                            toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 422) {
                            var errorPassage = '';
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                errorPassage += value + '\n';
                            });
                            toastr.error(errorPassage, 'Validation Errors', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else if (xhr.status === 500) {
                            toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        } else {
                            toastr.error('Unspecified error occurred. Try again later.', 'Unspecified Error', {
                                timeOut: 5000,
                                progressBar: true
                            });
                        }
                    }
                });
            });



            
            $('#cancelDataBtn').click(function(e) {
                e.preventDefault();
                $('#newDataForm')[0].reset();
                $('#newDataModal').modal('hide');
            });

            // Select All & Count
            $('#selectAll').click(function() {
                $('.selectRow').prop('checked', this.checked);
                updateSelectedCount();
            });

            $('#tbody').on('click', '.selectRow', function() {
                updateSelectedCount();
            });

            // Show Action Modal
            $('#deleteSelectedBtn').click(function(e) {
                e.preventDefault();
                var count = $('.selectRow:checked').length;
                if (count === 0) {
                    toastr.warning('No tenants selected.', 'Warning');
                    return;
                }
                $('#selectedCountDisplay').text(count);
                $('#actionModal').modal('show');
            });

            $('#changeStatusBtn').click(function(e) {
                e.preventDefault();
                $('#actionModal').modal('hide');
                toastr.info('Change Status functionality will be implemented here.', 'Next Step');
            });

            $('#putOnHoldBtn').click(function(e) {
                e.preventDefault();
                $('#actionModal').modal('hide');
                toastr.info('Put on Hold functionality will be implemented here.', 'Next Step');
            });

            function updateSelectedCount() {
                var count = $('.selectRow:checked').length;
                var badge = $('#selectedCount');
                badge.text(count);
                badge.toggle(count > 0);
            }
        }

        initDataTable();
    });
</script>
@endsection