@extends('tenants.admin.dashboard')
@section('content')

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height: 8px; transform: rotate(180deg);display:none">
<div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
</div>


<div class="content-page">
<div class="content">

<!-- start page title -->
<div class="row mb-3">

</div>
<!-- end page title -->


    <div class="row">

        <div class="col-xl-12 col-lg-7">

            <div class="card">
                <div class="card-body">

                
                    <ul class="nav nav-pills bg-nav-pills nav-justified mb-3">
                        <li class="nav-item">
                            <a href="#profile" data-bs-toggle="tab" aria-expanded="false" class="nav-link rounded-start rounded-0 active">
                            <i class="ri-account-circle-line fw-normal fs-18 align-middle me-1"></i>  
                            Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#leaves" data-bs-toggle="tab" aria-expanded="true" class="nav-link rounded-0">
                                Leaves
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#finances" data-bs-toggle="tab" aria-expanded="false" class="nav-link rounded-end rounded-0">
                                Finances
                            </a>
                        </li>
                          <li class="nav-item">
                            <a href="#password" data-bs-toggle="tab" aria-expanded="false" class="nav-link rounded-end rounded-0">
                                Change password
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">

                        <div class="tab-pane show active" id="profile">
                      
                           <?php 
                           $user = DB::table('users')->where('id',Auth::user()->id)->first();
                           ?>

                        <form action="#" class="form-horizontal" id="profileDataForm" method="post">
                            @csrf
                            <input type="hidden" name="id" value="{{Auth::user()->id}}">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row mb-3">
                                        <label for="name" class="col-3 col-form-label">Name</label>
                                        <div class="col-9">
                                            <input type="text" class="form-control" name="name" value="{{optional($user)->name}}">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="phone" class="col-3 col-form-label">Phone</label>
                                        <div class="col-9">
                                            <input type="text" class="form-control" name="phone" value="{{optional($user)->phone}}">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="email" class="col-3 col-form-label">Email</label>
                                        <div class="col-9">
                                            <input type="email" class="form-control" name="email" value="{{optional($user)->email}}">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="dob" class="col-3 col-form-label">Date of Birth</label>
                                        <div class="col-9">
                                            <input type="date" class="form-control" name="dob" value="{{optional($user)->dob}}">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="branch" class="col-3 col-form-label">Branch</label>
                                        <div class="col-9">
                                            <input type="text" class="form-control" name="branch" value="{{optional($user)->branch}}" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="idtype" class="col-3 col-form-label">ID Type</label>
                                        <div class="col-9">
                                            <input type="text" class="form-control" name="idtype" value="{{optional($user)->idtype}}">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="idnumber" class="col-3 col-form-label">ID Number</label>
                                        <div class="col-9">
                                            <input type="text" class="form-control" name="idnumber" value="{{optional($user)->idnumber}}">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="started_on" class="col-3 col-form-label">Started On</label>
                                        <div class="col-9">
                                            <input type="date" class="form-control" name="started_on" value="{{optional($user)->started_on}}" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="home_address" class="col-3 col-form-label">Home Address</label>
                                        <div class="col-9">
                                            <textarea name="home_address" class="form-control">{{optional($user)->home_address}}</textarea>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="current_residence" class="col-3 col-form-label">Current Residence</label>
                                        <div class="col-9">
                                            <textarea name="current_residence" class="form-control">{{optional($user)->current_residence}}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row mb-3">
                                        <label for="entered_on" class="col-3 col-form-label">Entered On</label>
                                        <div class="col-9">
                                            <input type="date" class="form-control" name="entered_on" value="{{optional($user)->entered_on}}" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="active" class="col-3 col-form-label">Active</label>
                                        <div class="col-9">
                                            <input type="text" class="form-control" name="active" value="{{optional($user)->active ?? 'Yes'}}" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="department" class="col-3 col-form-label">Department</label>
                                        <div class="col-9">
                                            <input type="text" class="form-control" name="department" value="{{optional($user)->department}}" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="position" class="col-3 col-form-label">Position</label>
                                        <div class="col-9">
                                            <input type="text" class="form-control" name="position" value="{{optional($user)->position}}" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="gross_salary" class="col-3 col-form-label">Gross Salary</label>
                                        <div class="col-9">
                                            <input type="number" class="form-control" name="gross_salary" value="{{optional($user)->gross_salary}}" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="role" class="col-3 col-form-label">Role</label>
                                        <div class="col-9">
                                            <input type="text" class="form-control" name="role" value="{{optional($user)->role}}" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="nextofkin_name" class="col-3 col-form-label">Next of Kin Name</label>
                                        <div class="col-9">
                                            <input type="text" class="form-control" name="nextofkin_name" value="{{optional($user)->nextofkin_name}}">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="nextofkin_relationship" class="col-3 col-form-label">Next of Kin Relationship</label>
                                        <div class="col-9">
                                            <input type="text" class="form-control" name="nextofkin_relationship" value="{{optional($user)->nextofkin_relationship}}">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="nextofkin_physical_address" class="col-3 col-form-label">Next of Kin Address</label>
                                        <div class="col-9">
                                            <textarea name="nextofkin_physical_address" class="form-control">{{optional($user)->nextofkin_physical_address}}</textarea>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="nextofkin_contact" class="col-3 col-form-label">Next of Kin Contact</label>
                                        <div class="col-9">
                                            <input type="text" class="form-control" name="nextofkin_contact" value="{{optional($user)->nextofkin_contact}}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="justify-content-end row">
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary" id="updateProfileInfoBtn">Update</button>
                                </div>
                            </div>
                        </form>

                       
                        </div> 
                        <!-- end tab-pane profile section -->
                    

                        <div class="tab-pane" id="leaves">

                          Leaves data not found

                        </div>
                        <!-- end tab-pane leaves section-->

                        <div class="tab-pane" id="finances">

                          Finances data not found
                            
                        </div>
                        <!-- end tab-pane finances section-->



            <div class="tab-pane" id="password">         
   
                <form class="form-horizontal" action="change-password" method="post" id="changePasswordForm">
                        @csrf
                    <div class="row mb-3">
                        <label for="#" class="col-3 col-form-label">Current password</label>
                        <div class="col-9">
                            <input type="password" class="form-control" name="currentpassword" placeholder="Enter current password">
                        </div>
                    </div>
                        <div class="row mb-3">
                        <label for="#" class="col-3 col-form-label">New password</label>
                        <div class="col-9">
                            <input type="password" class="form-control" name="newpassword" placeholder="Enter new password">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="#" class="col-3 col-form-label">Comfirm password</label>
                        <div class="col-9">
                            <input type="password" class="form-control" name="comfirmpassword" placeholder="Retype new password">
                        </div>
                    </div>
                    
                    <div class="justify-content-end row">
                        <div class="col-9 text-end">
                            <button type="submit" class="btn btn-primary" id="submitChangePasswordBtn">Submit</button>
                        </div>
                    </div>
                </form>   
              </div>
            <!-- end tab-pane password section-->



                    </div> <!-- end tab-content -->
                </div> <!-- end card body -->
            </div> <!-- end card -->
        </div> <!-- end col -->
    </div>
    <!-- end row-->

</div>
<!-- container -->

</div>
<!-- content -->


@section('scripts')
<script>
$(document).ready(function() {


$('#updateProfileInfoBtn').click(function(e) {
    e.preventDefault();
    var self = $(this);
    self.prop("disabled", true);
    var form = document.getElementById("profileDataForm");
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.ajax({
        type: "POST",
        url: "{{ route('master.update.profile.info') }}",
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

            } else if(data.status === 404) {
                toastr.error(data.error, 'Error', {
                    timeOut: 5000,
                    progressBar: true
                });
            } else {
                toastr.info('Un specified error occured', 'Error', {
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
                toastr.error('Network error. Please check your internet connection and try again.', 'Connection Error', {
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
                    timeOut: 10000,
                    progressBar: true
                });
            } else if (xhr.status === 500) {
                toastr.error('Server error occured refresh the page and try again.', 'Server Error', {
                    timeOut: 5000,
                    progressBar: true
                });
            } else {
                toastr.error('An unexpected error occurred. Please try again.', 'Error', {
                    timeOut: 5000,
                    progressBar: true
                });
            }
          }
       });
   });

   $('#submitChangePasswordBtn').click(function(e) {
    var self = $(this);
    $(this).prop("disabled", true);
    var form = document.getElementById("changePasswordForm");
    e.preventDefault();
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.ajax({
        type: "post",
        url: "{{ route('master.profile.change.password') }}",
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
                form.reset();
            } else if (data.status === 422) {
                toastr.error(data.error, 'Error', {
                    timeOut: 5000,
                    progressBar: true
                })
            } else {
                toastr.info('Success!', 'Success', {
                    timeOut: 5000,
                    progressBar: true
                });
                form.reset();
            }
        },
        error: function(xhr, status, error) {
            if (xhr.status === 0 && xhr.readyState === 0) {
                toastr.error('Timeout check your internet connect and try again', 'Timeout Error', {
                    timeOut: 5000,
                    progressBar: true
                })
            } else if (xhr.status === 422) {
                var errorPassage = '';
                var errors = xhr.responseJSON.errors;
                $.each(errors, function(key, value) {
                    errorPassage += value + '\n'
                });
                toastr.error(errorPassage, 'Validation Errors', {
                    timeOut: 5000,
                    progressBar: true
                });
            } else if (xhr.status === 500) {
                var errorMessage = xhr.responseText;
                toastr.error('Internal server error occured try again later', 'Server Error', {
                    timeOut: 5000,
                    progressBar: true
                });
            } else {
                toastr.error('Unspecified error occured try again later', 'Unspecified Error', {
                    timeOut: 5000,
                    progressBar: true
                });
            }
        }
    });
});





})
</script> 
@endsection
@endsection