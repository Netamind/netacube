@extends('tenants.admin.dashboard')
@section('content')
<style>
    .card-header { 
        padding:0.5rem 1.5rem !important; 
        background:linear-gradient(to right,#4B5EBD,#576CC0); 
        color:#fff; 
        border-top-left-radius:10px; 
        border-top-right-radius:10px; 
    }
    .card-header .btn-light { 
        height:28px; 
        padding:0 10px; 
        display:flex; 
        align-items:center; 
        justify-content:center; 
        line-height:1; 
        font-size: 1rem;
    }
    .card-header .btn-light:hover { 
        background-color:#f8f9fa; 
        transition:background-color .2s ease-in-out; 
    }
    .card { 
        border:none; 
        box-shadow:0 4px 8px rgba(0,0,0,.1); 
        border-radius:10px; 
        overflow: hidden;
    }
    .card-header h4 { 
        color:#fff; 
        font-weight:600; 
        margin-bottom:0; 
        display:flex; 
        align-items:center; 
    }
    .card-header h4 i { 
        margin-right:.25rem; 
    }
    .form-label { 
        font-weight:500; 
        color:#495057; 
    }
    .form-control:disabled, .form-select:disabled {
        background-color: #e9ecef;
        opacity: 1;
        color: #6c757d;
    }
</style>

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped"
     aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"
     style="height:8px; transform:rotate(180deg); display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <div class="row mb-3"></div>

            <?php
                $branchId = request('id');
                $branch   = DB::connection('tenant')->table('branches')->where('id', $branchId)->first();

                if (!$branch) {
                    abort(404, 'Branch not found');
                }
            ?>

            <div class="card">
                <!-- Blue gradient bar -->
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">
                      Branch Details
                    </h4>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('tenant.admin.branches') }}"
                           class="btn btn-light text-primary" title="Back to Branches">
                            <i class="ri-arrow-left-line"></i>
                        </a>
                    </div>
                </div>

                <!-- Form with all fields -->
                <div class="card-body pt-4">
                    <form action="#" class="form-horizontal" id="branchDataForm" method="post">
                        @csrf
                        <input type="hidden" name="id" value="{{ $branch->id }}">

                        <div class="row">
                            <!-- LEFT COLUMN -->
                            <div class="col-md-6">
                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Branch Name </label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" name="name" value="{{ $branch->name }}" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Business Number</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" name="business_number" value="{{ $branch->business_number }}">
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Sector</label>
                                    <div class="col-8">
                                        <select class="form-select" name="sector" readonly>
                                            <option value="{{$branch->sector}}">
                                            {{ DB::connection('tenant')->table('sectors')->where('id', $branch->sector)->value('sector') }}
                                            </option>
                                              @foreach(DB::connection('tenant')->table('sectors')->where('id','!=', $branch->sector)->get() as $sector)
                                                <option value="{{ $sector->id }}">{{ $sector->sector }}</option>
                                             @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Category </label>
                                    <div class="col-8">
                                        <select class="form-select" name="category" readonly>
                                            <option value="{{$branch->category}}">
                                                {{DB::connection('tenant')->table('categories')->where('id', $branch->category)->value('category') }}
                                            </option>
                                          @foreach(DB::connection('tenant')->table('categories')->where('id','!=',$branch->category)->get() as $cat)
                                              <option value="{{ $cat->id }}">{{ $cat->category }}</option>
                                           @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Status</label>
                                    <div class="col-8">
                                        <select class="form-select" name="status" required>
                                            <option value="active" {{ $branch->status == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ $branch->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            <option value="archived" {{ $branch->status == 'archived' ? 'selected' : '' }}>Archived</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT COLUMN -->
                            <div class="col-md-6">
                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Physical Address</label>
                                    <div class="col-8">
                                        <textarea class="form-control" name="address" rows="2">{{ $branch->address }}</textarea>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">City/District</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" name="city" value="{{ $branch->city }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Phone Number</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" name="phone" value="{{ $branch->phone }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Email</label>
                                    <div class="col-8">
                                        <input type="email" class="form-control" name="email" value="{{ $branch->email }}">
                                    </div>
                                </div>

                                <!-- Timestamps - disabled inputs -->
                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Created At</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" value="{{ $branch->created_at ? \Carbon\Carbon::parse($branch->created_at)->format('d M Y H:i') : '—' }}" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Updated At</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" value="{{ $branch->updated_at ? \Carbon\Carbon::parse($branch->updated_at)->format('d M Y H:i') : '—' }}" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ACTION BUTTONS -->
                        <div class="justify-content-end row mt-5">
                            <div class="col-12 text-end">
                               <!-- <a href="#" class="btn btn-danger" id="deleteBtn"
                                   data-id="{{ $branch->id }}" data-name="{{ $branch->name }}" title="Delete Branch">
                                    <i-->
                                <button type="submit" class="btn btn-primary" id="updateBranchInfoBtn">
                                    <i class="ri-save-line me-1"></i> Update
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
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
$(function () {
    toastr.options = { closeButton: true, progressBar: true, timeOut: 5000 };

    // Update branch info
    $('#updateBranchInfoBtn').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this).prop('disabled', true);
        const $form = $('#branchDataForm');

        $.ajax({
            url: '{{ route("tenant.admin.branch.update") }}',
            method: 'POST',
            data: $form.serialize(),
            timeout: 60000,
            beforeSend: () => $('#progressBar').show(),
            complete: () => { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: data => {
                if (data.success || data.status === 201) {
                    toastr.success(data.success || 'Branch updated successfully', 'Success');
                } else if (data.status === 422) {
                    let msg = ''; 
                    $.each(data.errors || {}, (k, v) => msg += v + '<br>');
                    toastr.error(msg, 'Validation Error');
                } else {
                    toastr.error(data.error || 'Update failed', 'Error');
                }
            },
            error: xhr => {
                let msg = 'Error';
                if (xhr.status === 422) {
                    $.each(xhr.responseJSON.errors || {}, (k, v) => msg += v + '<br>');
                    toastr.error(msg, 'Validation');
                } else {
                    toastr.error('Server error', 'Error');
                }
            }
        });
    });

    // Delete branch
    const delModal = new bootstrap.Modal('#singleDeleteDataModal');

    $('#deleteBtn').on('click', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');
        $('#singleDeleteId').val(id);
        $('#singleDisplayDeleteLabel').text(name);
        delModal.show();
    });

    $('#keepSingleDataBtn').on('click', function (e) {
        e.preventDefault();
        delModal.hide();
    });

    $('#submitSingleDeleteDataBtn').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this).prop('disabled', true);
        const $form = $('#singleDeleteDataForm');

        $.ajax({
            url: '{{ route("tenant.admin.branch.delete") }}',
            method: 'POST',
            data: $form.serialize() + '&_token={{ csrf_token() }}',
            beforeSend: () => $('#progressBar').show(),
            success: res => {
                if (res.success || res.status === 201) {
                    toastr.success(res.success || 'Branch deleted successfully', 'Success');
                    delModal.hide();
                    setTimeout(() => location.href = '{{ route("tenant.admin.branches") }}', 800);
                } else {
                    toastr.error(res.error || 'Delete failed', 'Error');
                }
            },
            error: xhr => {
                toastr.error(xhr.responseJSON?.message || 'Delete failed', 'Error');
            },
            complete: () => {
                $('#progressBar').hide();
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endsection