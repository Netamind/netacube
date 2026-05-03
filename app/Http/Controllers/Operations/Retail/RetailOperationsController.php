<?php

namespace App\Http\Controllers\Operations\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Exception;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use DB;
use Auth;

class RetailOperationsController extends Controller
{
    public function showDashboardView()
    {
        return view('operations.retail.dashboard');
    }

    public function showProfileView()
    {
         return view('operations.retail.profile');
    }


public function updateUserFilters(Request $request)
{
    $validator = Validator::make(
        $request->all(),
        [
            'user_id' => 'required|integer|exists:tenant.users,id',
        ],
        [
            'user_id.required' => 'User ID is required.',
            'user_id.integer'  => 'User ID must be a valid number.',
            'user_id.exists'   => 'The selected user does not exist.',
        ]
    );

    if ($validator->fails()) {
        $notification = array(
            'message'    => implode(', ', $validator->errors()->all()),
            'alert-type' => 'error'
        );
        return Redirect()->back()->with($notification);
    }

    $data = $request->except('_token');

    try {
        $success = DB::connection('tenant')->table('user_filters')->updateOrInsert(
            ['user_id' => Auth::id()],
            $data
        );

        if ($success) {
            $notification = array(
                'message'    => 'User filters saved successfully!',
                'alert-type' => 'success'
            );
        } else {
            $notification = array(
                'message'    => 'Could not save user filters.',
                'alert-type' => 'error'
            );
        }

        return Redirect()->back()->with($notification);

    } catch (Exception $e) {
        $notification = array(
            'message'    => 'Something went wrong while saving user filters. Please try again later.',
            'alert-type' => 'error'
        );
        return Redirect()->back()->with($notification);
    }
}

        


    
}