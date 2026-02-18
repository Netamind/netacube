<?php

namespace App\Http\Controllers\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class TenantAuthController extends Controller
{
    public function loginByCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'     => 'required|string|min:3|max:20|regex:/^[a-zA-Z0-9-]+$/',
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            $notification = array(
                'message'    => 'All fields are required and client code must be valid.',
                'alert-type' => 'error'
            );
            return Redirect()->back()->with($notification);
        }

        $clientCode = strtolower(trim($request->code));

        // Find tenant (central database)
        $tenant = DB::table('tenants')
            ->where('client_url', $clientCode)
            ->where('status', 'Approved')
            ->first();

        if (!$tenant) {
            $notification = array(
                'message'    => 'Invalid client code or account not approved yet contact system administrator.',
                'alert-type' => 'error'
            );
            return Redirect()->back()->with($notification);
        }

        // Generate correct database name
        $databaseName = $this->getTenantDatabaseName($clientCode);

        // Switch to tenant database
        Config::set('database.connections.tenant.database', $databaseName);
        DB::purge('tenant');

        // Find user in tenant database
        $user = DB::connection('tenant')
            ->table('users')
            ->where('email', $request->email)
            ->first();

        if (!$user) {
            DB::purge('tenant');
            $notification = array(
                'message'    => 'Wrong login credentials.',
                'alert-type' => 'error'
            );
            return Redirect()->back()->with($notification);
        }

        if (!Hash::check($request->password, $user->password)) {
            DB::purge('tenant');
            $notification = array(
                'message'    => 'Wrong login credentials.',
                'alert-type' => 'error'
            );
            return Redirect()->back()->with($notification);
        }

        // Check user role and redirect accordingly
        if ($user->role === 'Admin') {
           Auth::loginUsingId($user->id);
           //return redirect()->route('tenant.admin.dashboard', ['tenantName' => $clientCode]);

           $notification = array(
                'message' => 'Testing.',
                'alert-type' => 'info'
            );
            return Redirect()->back()->with($notification);
        } 
        elseif ($user->role === 'Sales') {
            $sector = DB::table('branches')->where('id', $user->branch)->value('sector');

            if ($sector === 'Retail') {
                Auth::loginUsingId($user->id);
                return redirect('retail-sales-dashboard');
            } elseif ($sector === 'Wholesale') {
                Auth::loginUsingId($user->id);
                return redirect('wholesale-sales-dashboard');
            } else {
                $notification = array(
                    'message' => 'Dashboard for your role is not available.',
                    'alert-type' => 'info'
                );
                return Redirect()->back()->with($notification);
            }
        } else {
            $notification = array(
                'message' => 'Your role is not defined in the system.',
                'alert-type' => 'info'
            );
            return Redirect()->back()->with($notification);
        }

        // Keep tenant connection for the session
        session([
            'tenant_database' => $databaseName,
            'tenant_code'     => $clientCode,
        ]);

        
    }

    private function getTenantDatabaseName($clientCode)
    {
        $code = strtolower(trim($clientCode));

        if (app()->environment('local', 'testing')) {
            return "netacube_" . $code;
        }

        return "premiate_netacube_" . $code;
    }

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
            'email' => 'required|email|exists:users,email',
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
            'email' => $request->email,
            'token' => $token,
            'date'  => Carbon::now(),
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

        $tokenDate = date('Y-m-d', strtotime($tokenData->date));
        $currentDate = date('Y-m-d');

        if ($tokenData->status != 1) {
            return response()->json(['error' => 'Link already used', 'status' => 400]);
        }

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

    public function updateProfileInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $request->id,
            'phone' => 'required|max:255|unique:users,phone,' . $request->id,
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
            ->where('id', $request->id)
            ->update($data);

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

        $hashedPassword = DB::connection('tenant')
            ->table('users')
            ->where('id', Auth::user()->id)
            ->value('password');

        if (!Hash::check($request->currentpassword, $hashedPassword)) {
            return response()->json([
                'error'  => 'The current password you entered is incorrect.',
                'status' => 422
            ]);
        }

        DB::connection('tenant')
            ->table('users')
            ->where('id', Auth::user()->id)
            ->update(['password' => Hash::make($request->newpassword)]);

        return response()->json(['success' => 'Password changed successfully', 'status' => 201]);
    }

    public function signout()
    {
        Auth::logout();

        // Clean tenant connection
        session()->forget(['tenant_database', 'tenant_code']);
        DB::purge('tenant');
        Config::set('database.connections.tenant.database', null);

        return redirect('/');
    }

    


}