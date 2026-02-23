<?php
namespace App\Http\Controllers\Tenant;
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
use Carbon\Carbon;
use DB;
use Auth;

class TenantCommonController extends Controller{

public function showTenantLoginPageByUrl() { return view('tenants.common.login'); }

public function submitUrlBasedLogin(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);
    if ($validator->fails()) {
        $messages = $validator->errors()->all();
        $message = implode(' | ', $messages);

        $notification = [
            'message'    => $message,
            'alert-type' => 'error'
        ];

        return redirect()->back()
            ->with($notification)
            ->withInput();
    }
    $user = DB::connection('tenant')->table('users')->where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        $notification = array(
            'message'    => 'Wrong login details.',
            'alert-type' => 'error'
        );
        return Redirect()->back()->with($notification);
    }

    // Get current tenant code from route (since login is under /noveta/...)
    $tenantCode = $request->route('tenantName');

    if ($user->role === 'SuperAdmin') {
        Auth::guard('web')->loginUsingId($user->id);
        session(['tenant_code' => $tenantCode]); 
        session()->regenerate(true);
        return redirect()->route('tenant.super.admin.dashboard');
    } 
     elseif ($user->role === 'RetailAdmin') {
        $notification = array(
            'message'    => 'Investor dashboard not implemented yet.',
            'alert-type' => 'info'
        );
        return Redirect()->back()->with($notification);
    } 
    elseif ($user->role === 'Investor') {
        $notification = array(
            'message'    => 'Investor dashboard not implemented yet.',
            'alert-type' => 'info'
        );
        return Redirect()->back()->with($notification);
    } 
    elseif ($user->role === 'Accounts') {
        $notification = array(
            'message'    => 'Accounts dashboard not implemented yet.',
            'alert-type' => 'info'
        );
        return Redirect()->back()->with($notification);
    } 
    elseif ($user->role === 'Sales') {
        $sector = DB::connection('tenant')
            ->table('branches')
            ->where('id', $user->branch)
            ->value('sector');

        if ($sector === 'Retail') {
            Auth::guard('web')->loginUsingId($user->id);
            session(['tenant_code' => $tenantCode]);
            session()->regenerate(true);
            return redirect('retail-sales-dashboard');
        } 
        elseif ($sector === 'Wholesale') {
            Auth::guard('web')->loginUsingId($user->id);
            session(['tenant_code' => $tenantCode]);
            session()->regenerate(true);
            return redirect('wholesale-sales-dashboard');
        } 
        else {
            $notification = array(
                'message'    => 'Dashboard for your sector is not available.',
                'alert-type' => 'info'
            );
            return Redirect()->back()->with($notification);
        }
    } 
    else {
        $notification = array(
            'message'    => 'Your role is not defined in the system.',
            'alert-type' => 'info'
        );
        return Redirect()->back()->with($notification);
    }
}


public function loginByCode(Request $request)
{
    $validator = Validator::make($request->all(), [
        'code'     => 'required|string|min:3|max:20|regex:/^[a-zA-Z0-9-]+$/',
        'email'    => 'required|email',
        'password' => 'required|string|min:4',
    ]);

    if ($validator->fails()) {
        $messages = $validator->errors()->all();
        $message = implode(' | ', $messages);

        $notification = [
            'message'    => $message,
            'alert-type' => 'error'
        ];

        return redirect()->back()
            ->with($notification)
            ->withInput();
    }

    $clientCode = strtolower(trim($request->code));

    $tenant = DB::table('tenants')
        ->where('client_url', $clientCode)
        ->where('status', 'Approved')
        ->where('put_on_hold', '!=', 'Yes')
        ->first();

    if (!$tenant) {
        $notification = [
            'message'    => 'Invalid client code or tenant account is not active / suspended.',
            'alert-type' => 'error'
        ];
        return redirect()->back()->with($notification);
    }

    $databaseName = $this->getTenantDatabaseName($clientCode);

    config(['database.connections.tenant.database' => $databaseName]);
    DB::purge('tenant');

    $user = DB::connection('tenant')
        ->table('users')
        ->where('email', $request->email)
        ->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        DB::purge('tenant');

        $notification = [
            'message'    => 'Wrong email or password.',
            'alert-type' => 'error'
        ];
        return redirect()->back()->with($notification);
    }

    $tenantCode = $clientCode;

    if ($user->role === 'Admin') {
        Auth::guard('web')->loginUsingId($user->id);
        session([
            'tenant_code'     => $tenantCode,
            'tenant_database' => $databaseName,
        ]);
        session()->regenerate(true);

       return redirect()->route('tenant.admin.dashboard', ['tenantName' => $tenantCode]);
        
    }

    if ($user->role === 'Sales') {
        $sector = DB::connection('tenant')
            ->table('branches')
            ->where('id', $user->branch)
            ->value('sector');

        if ($sector === 'Retail') {
            Auth::guard('web')->loginUsingId($user->id);
            session([
                'tenant_code'     => $tenantCode,
                'tenant_database' => $databaseName,
            ]);
            session()->regenerate(true);

             return redirect()->route('tenant.admin.dashboard', ['tenantName' => $tenantCode]);
       
        }

        if ($sector === 'Wholesale') {
            Auth::guard('web')->loginUsingId($user->id);
            session([
                'tenant_code'     => $tenantCode,
                'tenant_database' => $databaseName,
            ]);
            session()->regenerate(true);

           return redirect()->route('tenant.admin.dashboard', ['tenantName' => $tenantCode]);
           
        }

        $notification = [
            'message'    => 'No dashboard available for your sales sector.',
            'alert-type' => 'warning'
        ];
        return redirect()->back()->with($notification);
    }

    if ($user->role === 'Investor') {
        $notification = [
            'message'    => 'Investor dashboard is not yet available.',
            'alert-type' => 'info'
        ];
        return redirect()->back()->with($notification);
    }

    if ($user->role === 'Accounts') {
        $notification = [
            'message'    => 'Accounts dashboard is not yet available.',
            'alert-type' => 'info'
        ];
        return redirect()->back()->with($notification);
    }

    $notification = [
        'message'    => 'Your role is not recognized in the system.',
        'alert-type' => 'warning'
    ];
    return redirect()->back()->with($notification);
}

private function getTenantDatabaseName(string $clientCode): string
{
    $code = strtolower(trim($clientCode));

    $env  = app()->environment();

    if ($env === 'local') {
        return "netacube_{$code}";
    } else if($env === 'production'){

       return "premiate_netacube_{$code}";

    }
    else{

         return "Failed to resolve app environment";
    }

}



public function tenantLogout()
{
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('tenant.login.by.url');
}


}
