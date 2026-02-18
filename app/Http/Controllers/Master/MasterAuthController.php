<?php
namespace App\Http\Controllers\Master;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Mail; 
use DB;

class MasterAuthController extends Controller
{
    public function masterLogin(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            $notification = array(
                'message' => 'Both email and password fields are required.',
                'alert-type' => 'error'
            );
            return Redirect()->back()->with($notification);
        }

        // Find user by email using DB
        $user = DB::table('users')->where('email', $request->email)->first();

        // Check if user exists
        if (!$user) {
            $notification = array(
                'message' => 'Wrong login credentials.',
                'alert-type' => 'error'
            );
            return Redirect()->back()->with($notification);
        }

        // Check password
        if (!Hash::check($request->password, $user->password)) {
            $notification = array(
                'message' => 'Wrong login credentials.',
                'alert-type' => 'error'
            );
            return Redirect()->back()->with($notification);
        }

        // Check user role and redirect accordingly
        if ($user->role === 'Admin') {
            Auth::loginUsingId($user->id);
            return redirect()->route('master.dashboard');
        } elseif ($user->role === 'Sales') {
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
    }

    public function forgotPasswordView(){

        return view('master.forgot-password');
    }


    public function resetPasswordView(){

            return view('master.reset-password');
    }


        public function sendPasswordResetLink(Request $request)
        {
            $data = array();
            $messages = [
                'email.required' => 'Email is required.',
                'email.email' => 'Email must be valid.',
                'email.exists' => 'Email not found in our records.',
            ];

            $validator = $request->validate([
                'email' => 'required|email|exists:users',
            ], $messages);

            if ($validator) {
                $token = Str::random(64);
            
                $passwordResetData = [
                    'email' => $request->email,
                    'token' => $token,
                    'date' => Carbon::now(),
                ];
                DB::table('password_resets')->insert($passwordResetData);
                $data = ['token' => $token];
                try {

                    Mail::send('master.password-reset-link', ['data' => $data], function ($message) use ($request) {
                        $message->to($request->email);
                        $message->subject('Password Reset');
                    });

                    
                return response()->json(['success' => 'Password reset link sent successfully! If you dont receive the email, please check your spam folder', 'status' => 201]);
                } catch (\Exception $e) {
                    DB::table('password_resets')->where('email', $request->email)->where('token', $token)->delete();
                    return response()->json([
                        'error' => 'Failed to send password reset link. Refresh the page and  try again.',
                        'message' => $e->getMessage(),
                        'status' => 400
                    ]);
            }
            } else {
                return response()->json(['error' => 'Validation failed', 'status' => 422, 'errors' => $validator->errors()]);
            }
        }
 public function submitPasswordReset(Request $request)
    {
        $data = array();
        $data['password'] = Hash::make($request->password);
        $token = $request->token;

        $messages = [
            'password.required' => 'Password is required.',
            'password_confirmation.required' => 'Password confirmation is required.',
        ];

        $validator = $request->validate([
            'password' => 'required|min:4|confirmed',
            'password_confirmation' => 'required',
        ], $messages);

        if ($validator) {
            $tokenData = DB::table('password_resets')->where('token', $token)->first();
            if ($tokenData) {
                if ($tokenData->status == 1) {
                    $tokenDate = date('Y-m-d', strtotime($tokenData->date));
                    $currentDate = date('Y-m-d');
                    if ($tokenDate == $currentDate) {
                        $updateData = DB::table('users')->where('email', $tokenData->email)->update($data);
                        if ($updateData) {
                            DB::table('password_resets')->where('token', $token)->where('email', $tokenData->email)->update(['status' => 0]);
                            return response()->json(['success' => 'Password reset successfully', 'status' => 201]);
                        } else {
                            return response()->json(['error' => 'An error occurred try again later', 'status' => 422]);
                        }
                    } else {
                        return response()->json(['error' => 'Link has expired', 'status' => 400]);
                    }
                } else {
                    return response()->json(['error' => 'Link already used', 'status' => 400]);
                }
            } else {
                return response()->json([
                    'error' => 'Invalid token detected. Please restart the process by clicking "Forgot Password" on the login page.',
                    'status' => 400
                ]);
            }
        } else {
            return back()->withErrors($validator)->withInput();
        }
     }
public function updateProfileInfo(Request $request)
{
    $data = array();
       
    $validator = $request->validate([
        'name' => 'required|max:255',
        'email' => 'required|email|max:255|unique:users,email,'.$request->id,
        'phone' => 'required|max:255|unique:users,phone,'.$request->id,
        'dob' => 'nullable|date',
        'idtype' => 'nullable|max:255',
        'idnumber' => 'nullable|max:255',
        'home_address' => 'nullable|string',
        'current_residence' => 'nullable|string',
        'nextofkin_name' => 'nullable|max:255',
        'nextofkin_relationship' => 'nullable|max:255',
        'nextofkin_physical_address' => 'nullable|string',
        'nextofkin_contact' => 'nullable|max:255',
    ]);

    $data = [
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'dob' => $request->dob,
        'idtype' => $request->idtype,
        'idnumber' => $request->idnumber,
        'home_address' => $request->home_address,
        'current_residence' => $request->current_residence,
        'nextofkin_name' => $request->nextofkin_name,
        'nextofkin_relationship' => $request->nextofkin_relationship,
        'nextofkin_physical_address' => $request->nextofkin_physical_address,
        'nextofkin_contact' => $request->nextofkin_contact,
    ];

    if ($validator) {
        $updateData = DB::table('users')->where('id', $request->id)->update(array_filter($data));
        if ($updateData) {
            return response()->json(['success' => 'Data updated successfully.', 'status' => 201]);
        } else {
            return response()->json(['error' => 'User data not found or no changes made.', 'status' => 404]);
        }
    } else {
        return response()->json(['error' => $validator->errors()->all(), 'status' => 422]);
    }
}

public function profileChangePassword(request $request){

     $messages = [
        'currentpassword.required' => 'Current password is required.',
        'newpassword.required' => 'New password is required.',
        'newpassword.min' => 'New password must be at least 4 characters',
        'comfirmpassword.required' => 'Confirming new password is mandatory.',
        'comfirmpassword.same' => 'New password and confirm password do not match.',
    ];
    $validator = $request->validate([
        'currentpassword' => 'required',
        'newpassword' => 'required|min:4',
        'comfirmpassword' => 'required|same:newpassword',
    ], $messages);

    if ($validator) {
        $hashedPassword = DB::table('users')->where('id', Auth::user()->id)->value('password');
        if (Hash::check($request->currentpassword, $hashedPassword)) {
            $data = array();
            $data['password'] = Hash::make($request->newpassword);
            $updatePassword = DB::table('users')->where('id', Auth::user()->id)->update($data);
            if ($updatePassword) {
                return response()->json(['success' => 'Password changed successfully', 'status' => 201]);
            } else {
                return response()->json(['error' => 'An unexpected error occurred while updating your password', 'status' => 422]);
            }
        } else {
            return response()->json(['error' => 'The current password you entered is incorrect. Please try again', 'status' => 422]);
        }
    } else {
        return back()->withErrors($validator)->withInput();
    }


}

    public function employeeChangePassword(Request $request) {
        $data = array();
        $data['password'] = Hash::make($request->newpassword);
        $messages = [
            'newpassword.required' => 'New password is required.',
            'newpassword.min' => 'New password must be at least 4 characters',
            'comfirmpassword.required' => 'Confirming new password is mandatory.',
            'comfirmpassword.same' => 'New password and confirm password do not match.',
        ];
        $validator = $request->validate([
            'newpassword' => 'required|min:4',
            'comfirmpassword' => 'required|same:newpassword',
        ], $messages);

        if ($validator) {
            $updatePassword = DB::table('users')->where('id', Auth::user()->id)->update($data);
            if ($updatePassword) {
                return response()->json(['success' => 'Password changed successfully', 'status' => 201]);
            } else {
                return response()->json(['error' => 'An unexpected error occurred while updating your password', 'status' => 422]);
            }
        } else {
            return back()->withErrors($validator)->withInput();
        }
    }

public function updateEmployeeInfo(Request $request)
{
    $data = array();
       
    $validator = $request->validate([
        'name' => 'required|max:255',
        'email' => 'required|email|max:255|unique:users,email,'.$request->id,
        'phone' => 'required|max:255|unique:users,phone,'.$request->id,
        'dob' => 'nullable|date',
        'branch' => 'nullable|max:255',
        'profile_picture' => 'nullable|max:255',
        'idtype' => 'nullable|max:255',
        'idnumber' => 'nullable|max:255',
        'started_on' => 'nullable|date',
        'home_address' => 'nullable|string',
        'current_residence' => 'nullable|string',
        'entered_on' => 'nullable|date',
        'active' => 'nullable|in:Yes,No',
        'department' => 'nullable|max:255',
        'position' => 'nullable|max:255',
        'gross_salary' => 'nullable|integer',
        'role' => 'nullable|max:255',
        'password' => 'nullable|min:4',
        'nextofkin_name' => 'nullable|max:255',
        'nextofkin_relationship' => 'nullable|max:255',
        'nextofkin_physical_address' => 'nullable|string',
        'nextofkin_contact' => 'nullable|max:255',
    ]);

    $data = [
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'dob' => $request->dob,
        'branch' => $request->branch,
        'profile_picture' => $request->profile_picture,
        'idtype' => $request->idtype,
        'idnumber' => $request->idnumber,
        'started_on' => $request->started_on,
        'home_address' => $request->home_address,
        'current_residence' => $request->current_residence,
        'entered_on' => $request->entered_on,
        'active' => $request->active,
        'department' => $request->department,
        'position' => $request->position,
        'gross_salary' => $request->gross_salary,
        'role' => $request->role,
        'password' => $request->password ? Hash::make($request->password) : null,
        'nextofkin_name' => $request->nextofkin_name,
        'nextofkin_relationship' => $request->nextofkin_relationship,
        'nextofkin_physical_address' => $request->nextofkin_physical_address,
        'nextofkin_contact' => $request->nextofkin_contact,
    ];

    if ($validator) {
        $updateData = DB::table('users')->where('id', $request->id)->update(array_filter($data));
        if ($updateData) {
            return response()->json(['success' => 'Data updated successfully.', 'status' => 201]);
        } else {
            return response()->json(['error' => 'User data not found or no changes made.', 'status' => 404]);
        }
    } else {
        return response()->json(['error' => $validator->errors()->all(), 'status' => 422]);
    }
}

public function masterLogout()
{
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('master.login.page');
}
}