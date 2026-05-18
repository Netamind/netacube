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

class MasterApproveTenantController extends Controller
{

    private function getTenantDatabaseName(string $clientCode): string
    {
        $code = strtolower(trim($clientCode));
        $env  = app()->environment();
        if ($env === 'local') {
            return "netacube_{$code}";
        } else if ($env === 'production') {
            return "premiate_netacube_{$code}";
        } else {
            throw new \Exception("Unresolved application environment. Only 'local' and 'production' are allowed.");
        }
    }

    public function approveTenant(Request $request, CpanelService $cpanel = null)
    {
        $tenantId = $request->id;
        $clientUrl = $request->client_url;
        $databaseName = $this->getTenantDatabaseName($clientUrl);

        if (str_contains($databaseName, "Failed to resolve")) {
            return response()->json(['errors' => [$databaseName], 'status' => 500]);
        }

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
            'number_of_tables' => 0,
            'migration_status' => 'not_started',
            'migrated_at' => null,
            'migration_error' => null,
        ];

        $isLocal = app()->environment('local');
        $dbUser = null;
        $dbPassword = null;

        try {
            if ($isLocal) {
                $escapedDbName = DB::connection('mysql')->getPdo()->quote($databaseName);
                $dbExists = DB::connection('mysql')->select("SHOW DATABASES LIKE $escapedDbName");
                if (!empty($dbExists)) {
                    throw new \Exception('Database ' . $databaseName . ' already exists.');
                }

                $dbCreated = DB::connection('mysql')->statement("CREATE DATABASE `$databaseName`");
                if (!$dbCreated) {
                    throw new \Exception('Failed to create tenant database.');
                }
            } else {
                if ($cpanel->databaseExists($databaseName)) {
                    throw new \Exception('Database already exists.');
                }

                $dbUser = $databaseName;
                $dbPassword = "binto2020";

                $userCreated = $cpanel->createUser($dbUser, $dbPassword);
                if (!$userCreated['success']) throw new \Exception($userCreated['message']);

                $dbCreated = $cpanel->createDatabase($databaseName);
                if (!$dbCreated['success']) throw new \Exception($dbCreated['message']);

                $privilegesSet = $cpanel->setPrivileges($dbUser, $databaseName);
                if (!$privilegesSet['success']) throw new \Exception($privilegesSet['message']);

          
                $data['db_user'] = $dbUser;
            }

            DB::beginTransaction();

            if ($isLocal) {
                config(['database.connections.tenant.database' => $databaseName]);
            } else {
                config([
                    'database.connections.tenant.database' => $databaseName,
                    'database.connections.tenant.username' => $dbUser,
                    'database.connections.tenant.password' => $dbPassword,
                ]);
            }
            DB::purge('tenant');

            $tenantModel->data = $databaseName;
            $tenantModel->save();

          
            $updated = DB::table('tenants')->where('id', $tenantId)->update($data);
            if (!$updated) {
                throw new \Exception('Failed to update tenant record.');
            }

            DB::commit();

            return response()->json(['success' => 'Tenant approved successfully.', 'status' => 201]);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($isLocal) {
                DB::connection('mysql')->statement("DROP DATABASE IF EXISTS `$databaseName`");
            } else {
                $cpanel->deleteDatabase($databaseName);
                if ($dbUser) {
                    $cpanel->deleteUser($dbUser);
                }
            }
            return response()->json(['errors' => [$e->getMessage()], 'status' => 500]);
        }
    }

}