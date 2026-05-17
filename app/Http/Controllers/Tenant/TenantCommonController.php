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

class TenantCommonController extends Controller
{
    public function showTenantLoginPageByUrl()
    {
        return view('tenants.common.login');
    }

    public function submitUrlBasedLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->with(['message' => implode(' | ', $validator->errors()->all()), 'alert-type' => 'error'])
                ->withInput();
        }

        $tenantCode = $request->route('tenantName');

        // tenancy middleware already configured the tenant connection for this route
        $user = DB::connection('tenant')
                   ->table('users')
                   ->where('email', $request->email)
                   ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return redirect()->back()->with([
                'message'    => 'Wrong login details.',
                'alert-type' => 'error'
            ]);
        }

        return $this->startSessionAndRedirect($user, $tenantCode);
    }

    public function loginByCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'     => 'required|string|min:3|max:20|regex:/^[a-zA-Z0-9-]+$/',
            'email'    => 'required|email',
            'password' => 'required|string|min:4',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->with(['message' => implode(' | ', $validator->errors()->all()), 'alert-type' => 'error'])
                ->withInput();
        }

        $clientCode = strtolower(trim($request->code));

        $tenant = DB::table('tenants')
            ->where('client_url', $clientCode)
            ->where('status', 'Approved')
            ->where('put_on_hold', '!=', 'Yes')
            ->first();

        if (!$tenant) {
            return redirect()->back()->with([
                'message'    => 'Invalid client code or tenant account is not active / suspended.',
                'alert-type' => 'error'
            ]);
        }

        // loginByCode has no tenancy middleware so we configure the connection manually
        $databaseName = $tenant->data;
        config(['database.connections.tenant.database' => $databaseName]);
        DB::purge('tenant');

        $user = DB::connection('tenant')
                   ->table('users')
                   ->where('email', $request->email)
                   ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return redirect()->back()->with([
                'message'    => 'Wrong email or password.',
                'alert-type' => 'error'
            ]);
        }

        return $this->startSessionAndRedirect($user, $clientCode);
    }

    /**
     * Flush any old session, write all user fields into the session,
     * then redirect based on role. We do not use Auth::loginUsingId()
     * because Laravel Auth reads from the central DB. Our users only
     * exist in tenant databases, so we own the session ourselves and
     * AppServiceProvider rebuilds Auth::user() from it on every request.
     */
    private function startSessionAndRedirect($user, string $tenantCode)
    {
        session()->flush();

        session([
            'tenant_code'                        => $tenantCode,

            // Identity
            'auth_user_id'                       => $user->id,
            'auth_user_name'                     => $user->name,
            'auth_user_phone'                    => $user->phone,
            'auth_user_email'                    => $user->email,
            'auth_user_profile_picture'          => $user->profile_picture,
            'auth_user_dob'                      => $user->dob,
            'auth_user_idtype'                   => $user->idtype,
            'auth_user_idnumber'                 => $user->idnumber,

            // Employment
            'auth_user_role'                     => $user->role,
            'auth_user_branch'                   => $user->branch,
            'auth_user_department'               => $user->department,
            'auth_user_position'                 => $user->position,
            'auth_user_gross_salary'             => $user->gross_salary,
            'auth_user_started_on'               => $user->started_on,
            'auth_user_entered_on'               => $user->entered_on,
            'auth_user_active'                   => $user->active,
            'auth_user_employment_type'          => $user->employment_type,
            'auth_user_contract_end_date'        => $user->contract_end_date,
            'auth_user_on_paye'                  => $user->on_paye,

            // Address
            'auth_user_home_address'             => $user->home_address,
            'auth_user_current_residence'        => $user->current_residence,

            // Banking
            'auth_user_bank_name'                => $user->bank_name,
            'auth_user_bank_account_name'        => $user->bank_account_name,
            'auth_user_bank_account_number'      => $user->bank_account_number,
            'auth_user_bank_branch'              => $user->bank_branch,
            'auth_user_bank_account_type'        => $user->bank_account_type,

            // Next of kin
            'auth_user_nextofkin_name'           => $user->nextofkin_name,
            'auth_user_nextofkin_relationship'   => $user->nextofkin_relationship,
            'auth_user_nextofkin_physical_address' => $user->nextofkin_physical_address,
            'auth_user_nextofkin_contact'        => $user->nextofkin_contact,
        ]);

        session()->regenerate(true);

        if ($user->role === 'Admin' || $user->role === 'Operations') {
            return redirect()->route('tenant.admin.dashboard', ['tenantName' => $tenantCode]);

        } elseif ($user->role === 'Sales') {
            return redirect()->route('retail.sales.dashboard', ['tenantName' => $tenantCode]);

        } else {
            session()->flush();
            return redirect()->back()->with([
                'message'    => 'Your role is not defined in the system.',
                'alert-type' => 'error'
            ]);
        }
    }

    private function getTenantDatabaseName(string $clientCode): string
    {
        $code = strtolower(trim($clientCode));
        $env  = app()->environment();

        if ($env === 'local') {
            return "netacube_{$code}";
        } elseif ($env === 'production') {
            return "premiate_netacube_{$code}";
        } else {
            return "netacube_{$code}";
        }
    }

    public function tenantLogout()
    {
        session()->flush();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('tenant.login.by.url');
    }
}