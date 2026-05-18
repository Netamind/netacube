<?php
namespace App\Http\Controllers\Website;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use DB;
use Auth;

class WebsiteController extends Controller
{
    public function showHomePage()
    { 
        return view('website.homepage');
    }

    public function showGetStartedView()
    {
        return view('website.getstarted');
    }


    
    public function showLoginByCodeView()
    {
        return view('website.login');
    }

    public function showAboutUsView(){
      return view('website.about');
    }

    

    public function showFeaturesView(){
      return view('website.features');
    }


    
    public function showContactView(){
      return view('website.contact');
    }


    
    public function showPricingView(){
      return view('website.pricing');
    }


    
    public function showHelpcenterView(){
      return view('website.helpcenter');
    }


    public function clientRegistration(Request $request)
    {

        $data = array();

        // ── Honeypot fields ── invisible inputs a real visitor never fills in.
        // Bots that auto-fill every input on the page will trip these.
        if (!empty($request->input('website')) || !empty($request->input('company_url'))) {
            return response()->json(['error' => 'Suspicious request detected. Please try again.', 'status' => 423]);
        }

        // ── Timing check ── reject submissions completed faster than a human
        // could realistically read and fill the form (front-end sends the
        // timestamp the form was loaded at, in milliseconds).
        $loadedAt = (int) $request->input('form_loaded_at');
        if ($loadedAt && (now()->getTimestampMs() - $loadedAt) < 3000) {
            return response()->json(['error' => 'Suspicious request detected. Please try again.', 'status' => 423]);
        }

        $validator = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:tenants,email',
            'phone_number' => 'required|string|max:20|unique:tenants,phone_number',
            'business_name' => 'required|string|max:255',
            'subscription_plan' => 'required|string|max:255',
        ]);

        $data = [
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'business_name' => $request->business_name,
            'client_url' => $request->email,
            'subscription_plan' => $request->subscription_plan,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($validator) {

            $insertData = DB::table('tenants')->insert($data);
            if ($insertData) {

                return response()->json(['success' => 'Registration successful! We will send login details via your email shortly.', 'status' => 201]);

            } else {

                return response()->json(['error' => 'Server error occured. Please try again.', 'status' => 500]);

            }

        } else {
            return response()->json(['error' => $validator->errors()->all(), 'status' => 422]);
        }
    }


}