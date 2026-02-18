<?php
namespace App\Http\Controllers\Master;
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
use Stancl\Tenancy\Facades\Tenancy;
use App\Services\CpanelService;
use App\Models\Tenant;
use Carbon\Carbon;
use DB;
use Auth;
use Mail;

class MasterTenantController extends Controller
{

    public function showTenantsView() { 
        return view('master.tenants');
     }

    public function showTenantDetailsView() { 
        return view('master.tenant-details'); 
    }
  
    public function approveTenantLocal(Request $request)
    {
        $tenantId = $request->id;
        $clientUrl = $request->client_url;
        $databaseName = "netacube_" . $request->client_url;

        $tenantModel = Tenant::find($tenantId);
        if (!$tenantModel) {
            return response()->json(['errors' => ['Tenant not found.'], 'status' => 404]);
        }

        if ($tenantModel->status === 'Approved') {
            return response()->json(['errors' => ['Tenant is already approved.'], 'status' => 203]);
        }

        $validator = Validator::make($request->all(), [
            'client_url' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[a-zA-Z0-9-]+$/',
                'unique:tenants,client_url,' . $tenantId,
                function ($attribute, $value, $fail) use ($databaseName, $tenantId) {
                    if (DB::table('tenants')->where('data', $databaseName)->where('id', '!=', $tenantId)->exists()) {
                        $fail('Database name ' . $databaseName . ' is already taken.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }

        $data = [
            'client_url' => $clientUrl,
            'data' => $databaseName,
            'approved_by' => $request->user_id,
            'approved_at' => Carbon::today()->toDateString(),
            'next_payment_date' => Carbon::today()->addDays(7)->toDateString(),
            'status' => 'Approved',
        ];

        try {
            $escapedDbName = DB::connection('mysql')->getPdo()->quote($databaseName);
            $dbExists = DB::connection('mysql')->select("SHOW DATABASES LIKE $escapedDbName");
            if (!empty($dbExists)) {
                throw new \Exception('Database ' . $databaseName . ' already exists.');
            }

            $dbCreated = DB::connection('mysql')->statement("CREATE DATABASE `$databaseName`");
            if (!$dbCreated) {
                throw new \Exception('Failed to create tenant database.');
            }

            DB::beginTransaction();

            config(['database.connections.tenant.database' => $databaseName]);
            DB::purge('tenant');

            $tenantModel->data = $databaseName;
            $tenantModel->save();

            tenancy()->initialize($tenantModel);
            Artisan::call('migrate', ['--database' => 'tenant', '--path' => 'database/migrations/tenant', '--force' => true]);
            tenancy()->end();

            $updated = DB::table('tenants')->where('id', $tenantId)->update($data);
            if (!$updated) {
                throw new \Exception('Failed to update tenant record.');
            }

            DB::commit();

            return response()->json(['success' => 'Tenant approved successfully.', 'status' => 201]);
        } catch (\Exception $e) {
            DB::rollBack();
            DB::connection('mysql')->statement("DROP DATABASE IF EXISTS `$databaseName`");
            return response()->json(['errors' => [$e->getMessage()], 'status' => 500]);
        }
    }

    public function approveTenantRemote(Request $request, CpanelService $cpanel)
    {
        $tenantId = $request->id;
        $clientUrl = $request->client_url;
        $databaseName = "premiate_netacube_" . $request->client_url;
        $dbUser = $databaseName;
        $dbPassword = "binto2020";

        $tenantModel = Tenant::find($tenantId);
        if (!$tenantModel) {
            return response()->json(['errors' => ['Tenant not found.'], 'status' => 404]);
        }

        if ($tenantModel->status === 'Approved') {
            return response()->json(['errors' => ['Tenant is already approved.'], 'status' => 203]);
        }

        $validator = Validator::make($request->all(), [
            'client_url' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[a-zA-Z0-9-]+$/',
                'unique:tenants,client_url,' . $tenantId,
                function ($attribute, $value, $fail) use ($databaseName, $tenantId) {
                    if (DB::table('tenants')->where('data', $databaseName)->where('id', '!=', $tenantId)->exists()) {
                        $fail('Database name ' . $databaseName . ' is already taken.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }

        $data = [
            'client_url' => $clientUrl,
            'data' => $databaseName,
            'approved_by' => $request->user_id,
            'approved_at' => Carbon::today()->toDateString(),
            'next_payment_date' => Carbon::today()->addDays(7)->toDateString(),
            'status' => 'Approved',
        ];

        try {
            if ($cpanel->databaseExists($databaseName)) {
                throw new \Exception('Database already exists.');
            }

            $userCreated = $cpanel->createUser($dbUser, $dbPassword);
            if (!$userCreated['success']) throw new \Exception($userCreated['message']);

            $dbCreated = $cpanel->createDatabase($databaseName);
            if (!$dbCreated['success']) throw new \Exception($dbCreated['message']);

            $privilegesSet = $cpanel->setPrivileges($dbUser, $databaseName);
            if (!$privilegesSet['success']) throw new \Exception($privilegesSet['message']);

            DB::beginTransaction();

            config([
                'database.connections.tenant.database' => $databaseName,
                'database.connections.tenant.username' => $dbUser,
                'database.connections.tenant.password' => $dbPassword,
            ]);
            DB::purge('tenant');

            $tenantModel->data = $databaseName;
            $tenantModel->save();

            tenancy()->initialize($tenantModel);
            Artisan::call('migrate', ['--database' => 'tenant', '--path' => 'database/migrations/tenant', '--force' => true]);
            tenancy()->end();

            $updated = DB::table('tenants')->where('id', $tenantId)->update($data);
            if (!$updated) throw new \Exception('Failed to update tenant.');

            DB::commit();

            return response()->json(['success' => 'Tenant approved successfully.', 'status' => 201]);
        } catch (\Exception $e) {
            DB::rollBack();
            $cpanel->deleteDatabase($databaseName);
            $cpanel->deleteUser($dbUser);
            return response()->json(['errors' => [$e->getMessage()], 'status' => 500]);
        }
    }
    public function masterAddTenant(Request $request)
    {
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

        $validator = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:tenants,email',
            'phone_number' => 'required|string|max:20|unique:tenants,phone_number',
            'business_name' => 'required|string|max:255',
            'subscription_plan' => 'required|integer|exists:subscription_plans,id',
        ]);

        if ($validator) {
            $insertId = DB::table('tenants')->insertGetId($data);
            if ($insertId) {
                $tenant = DB::table('tenants')->where('id', $insertId)->first();
                $plan = DB::table('subscription_plans')->where('id', $tenant->subscription_plan)->first();

                return response()->json([
                    'success' => 'Tenant added successfully.',
                    'status' => 201,
                    'tenant' => [
                        'id' => $tenant->id,
                        'full_name' => $tenant->full_name,
                        'business_name' => $tenant->business_name,
                        'email' => $tenant->email,
                        'phone_number' => $tenant->phone_number,
                        'status' => $tenant->status,
                        'next_payment_date' => $tenant->next_payment_date ?? 'NA',
                        'plan_name' => $plan->plan_name ?? '',
                        'plan_period' => $plan->plan_period ?? '',
                        'plan_amount' => $plan->plan_amount ?? '',
                    ],
                ]);
            } else {
                return response()->json(['error' => 'Failed to create tenant.', 'status' => 500]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }
    public function updateTenantDetails(Request $request)
    {
        $data = [
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'business_name' => $request->business_name,
            'physical_address' => $request->physical_address,
            'postal_address' => $request->postal_address,
        ];

        $validator = $request->validate([
            'id' => 'required|integer|exists:tenants,id',
            'full_name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:tenants,email,' . $request->id,
            'phone_number' => 'required|max:255|unique:tenants,phone_number,' . $request->id,
            'business_name' => 'required|max:255',
        ]);

        if ($validator) {
            $updated = DB::table('tenants')->where('id', $request->id)->update($data);
            if ($updated) {
                return response()->json(['success' => 'Data updated successfully.', 'status' => 201]);
            } else {
                return response()->json(['error' => 'An error occurred no data change detected', 'status' => 409]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }



}
