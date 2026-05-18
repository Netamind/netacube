<?php

namespace App\Http\Controllers\Sales\Retail;

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

class RetailSalesController extends Controller
{
    public function showDashboardView()
    {
        return view('sales.retail.dashboard');
    }

    public function showProfileView()
    {
         return view('sales.retail.profile');
    }                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              



    
}