<?php
// TenantCommonController.php (overwrite existing file)

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
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;
use App\SectorDashboards;
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
     * the HydrateAuthFromSession middleware rebuilds Auth::user() from
     * it at the start of every subsequent request.
     */
    private function startSessionAndRedirect($user, string $tenantCode)
    {
        session()->flush();

        // Fresh token for this login. Written to the session below and to
        // user_session_tokens so EnforceIdleTimeout can spot a second,
        // later login to the same account when enforce_single_session is on.
        $sessionToken = Str::random(64);

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

            // Session-lock token (see EnforceIdleTimeout)
            'auth_session_token'                 => $sessionToken,
        ]);

        session()->regenerate(true);

        // Overwrite any previous token for this user — this login is now
        // the "latest" one, so an older session elsewhere will fail the
        // token check on its next request if enforce_single_session is on.
        DB::connection('tenant')->table('user_session_tokens')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'session_token' => $sessionToken,
                'last_seen_at'  => now(),
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );

        if ($user->role === 'Admin') {
            return $this->redirectAdmin($user, $tenantCode);

        } elseif ($user->role === 'Operations') {
            return $this->redirectOperations($user, $tenantCode);

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

    /**
     * Admin landing: settings-driven. Defaults to the general admin
     * dashboard unless this admin has configured a specific sector
     * dashboard in their own admin_dashboard_settings row (now per-user,
     * same as operations_dashboard_settings — see
     * AdminDashboardSettingsController). A first-time admin with no row
     * yet simply has no override, same as if the field were blank.
     */
    private function redirectAdmin($user, string $tenantCode)
    {
        $settings     = DB::connection('tenant')->table('admin_dashboard_settings')->where('user_id', $user->id)->first();
        $sectorRoutes = SectorDashboards::routes();

        if ($settings && $settings->default_landing_sector && isset($sectorRoutes[$settings->default_landing_sector])) {
            return redirect()->route($sectorRoutes[$settings->default_landing_sector], ['tenantName' => $tenantCode]);
        }

        return redirect()->route('tenant.admin.dashboard', ['tenantName' => $tenantCode]);
    }

    /**
     * Operations landing: always the Operations dashboard, never the admin
     * area. All the actual landing logic (default_landing_sector, sector
     * access via employee_access, the "no access at all" bounce) lives in
     * OperationsSectorSwitcherController::show so there's a single source
     * of truth for it — this just sends the user there.
     */
    private function redirectOperations($user, string $tenantCode)
    {
        return redirect()->route('tenant.operations.hub.dashboard', ['tenantName' => $tenantCode]);
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
        Auth::logout();
        session()->flush();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('tenant.login.by.url');
    }


    /*
    |==========================================================================
      PASSWORD RESET (brought over from TenantAuthController, fixed to use
      the tenant connection throughout — both the validators and the writes)
    |==========================================================================
    */

    public function forgotPasswordView()
    {
        return view('auth.forgot-password'); // tenant version
    }

    public function resetPasswordView()
    {
        return view('auth.reset-password');
    }

    public function sendPasswordResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:tenant.users,email',
        ], [
            'email.required' => 'Email is required.',
            'email.email'    => 'Email must be valid.',
            'email.exists'   => 'Email not found in our records.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'  => 'Validation failed',
                'status' => 422,
                'errors' => $validator->errors()->all()
            ]);
        }

        $token = Str::random(64);

        DB::connection('tenant')->table('password_resets')->insert([
            'email'  => $request->email,
            'token'  => $token,
            'date'   => Carbon::now(),
            'status' => 1
        ]);

        try {
            Mail::send('tenant.password-reset-link', ['token' => $token], function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('Password Reset - Your Tenant Account');
            });

            return response()->json([
                'success' => 'Password reset link sent successfully! Check your email (including spam).',
                'status'  => 201
            ]);
        } catch (\Exception $e) {
            DB::connection('tenant')
                ->table('password_resets')
                ->where('email', $request->email)
                ->where('token', $token)
                ->delete();

            return response()->json([
                'error'   => 'Failed to send reset link. Try again later.',
                'message' => $e->getMessage(),
                'status'  => 400
            ]);
        }
    }

    public function submitPasswordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password'              => 'required|min:4|confirmed',
            'password_confirmation' => 'required',
            'token'                 => 'required',
        ], [
            'password.required'              => 'Password is required.',
            'password.min'                   => 'Password must be at least 4 characters',
            'password_confirmation.required' => 'Password confirmation is required.',
            'password.confirmed'             => 'Passwords do not match.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $tokenData = DB::connection('tenant')
            ->table('password_resets')
            ->where('token', $request->token)
            ->first();

        if (!$tokenData) {
            return response()->json([
                'error'  => 'Invalid or expired token.',
                'status' => 400
            ]);
        }

        if ($tokenData->status != 1) {
            return response()->json(['error' => 'Link already used', 'status' => 400]);
        }

        $tokenDate   = date('Y-m-d', strtotime($tokenData->date));
        $currentDate = date('Y-m-d');

        if ($tokenDate !== $currentDate) {
            return response()->json(['error' => 'Link has expired', 'status' => 400]);
        }

        DB::connection('tenant')
            ->table('users')
            ->where('email', $tokenData->email)
            ->update(['password' => Hash::make($request->password)]);

        DB::connection('tenant')
            ->table('password_resets')
            ->where('token', $request->token)
            ->delete();

        return response()->json([
            'success' => 'Password reset successfully!',
            'status'  => 201
        ]);
    }


    /*
    |==========================================================================
      PROFILE  (brought over from TenantAuthController)

      Fixed to:
      - validate against tenant.users, not the central users table
      - use the logged-in user's id via Auth::id() instead of trusting a
        posted `id` field (the original let any caller pass ?id=anything
        and edit someone else's record / change someone else's password)
    |==========================================================================
    */

    public function updateProfileInfo(Request $request)
    {
        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'name'  => 'required|max:255',
            'email' => 'required|email|max:255|unique:tenant.users,email,' . $userId,
            'phone' => 'required|max:255|unique:tenant.users,phone,' . $userId,
            // add your other fields as needed...
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'  => $validator->errors()->all(),
                'status' => 422
            ]);
        }

        $data = [
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            // ... your other fields
        ];

        $updated = DB::connection('tenant')
            ->table('users')
            ->where('id', $userId)
            ->update($data);

        if ($updated) {
            // keep the session in sync with what we just saved — this is the
            // source HydrateAuthFromSession reads from on the next request
            session([
                'auth_user_name'  => $data['name'],
                'auth_user_email' => $data['email'],
                'auth_user_phone' => $data['phone'],
            ]);
        }

        return $updated
            ? response()->json(['success' => 'Profile updated successfully.', 'status' => 201])
            : response()->json(['error' => 'No changes or user not found.', 'status' => 404]);
    }

    public function profileChangePassword(Request $request)
    {
        $messages = [
            'currentpassword.required' => 'Current password is required.',
            'newpassword.required'     => 'New password is required.',
            'newpassword.min'          => 'New password must be at least 4 characters',
            'comfirmpassword.required' => 'Confirming new password is mandatory.',
            'comfirmpassword.same'     => 'New password and confirm password do not match.',
        ];

        $validator = Validator::make($request->all(), [
            'currentpassword' => 'required',
            'newpassword'     => 'required|min:4',
            'comfirmpassword' => 'required|same:newpassword',
        ], $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $userId = Auth::id();

        $hashedPassword = DB::connection('tenant')
            ->table('users')
            ->where('id', $userId)
            ->value('password');

        if (!Hash::check($request->currentpassword, $hashedPassword)) {
            return response()->json([
                'error'  => 'The current password you entered is incorrect.',
                'status' => 422
            ]);
        }

        DB::connection('tenant')
            ->table('users')
            ->where('id', $userId)
            ->update(['password' => Hash::make($request->newpassword)]);

        return response()->json(['success' => 'Password changed successfully', 'status' => 201]);
    }
}