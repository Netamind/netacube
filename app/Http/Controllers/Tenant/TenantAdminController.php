<?php
// File: app/Http/Controllers/Tenant/TenantAdminController.php
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
use Illuminate\Validation\Rule;
use DB;
use Auth;
use Mail;

class TenantAdminController extends Controller
{
    public function showTenantAdminDashboard()
    {
        return view('tenants.admin.dashboard');
    }


    public function showEmployeesView()
{
    return view('tenants.admin.employees');
}

public function showEmployeeDetailsView(Request $request)
{
    $user = DB::connection('tenant')->table('users')->where('id', $request->id)->first();

    if (!$user) {
        abort(404, 'Employee not found.');
    }

    $branches = DB::connection('tenant')->table('branches')->get();
    $roles    = DB::connection('tenant')->table('roles')->get();

    return view('tenants.admin.employee-details', compact('user', 'branches', 'roles'));
}

    public function showProfileView()
    {
        return view('tenants.admin.profile');
    }


    public function showCompanyInfoView()
    {
        return view('tenants.admin.company-info');
    }


    public function showPaymentMethodsView()
    {
        return view('tenants.admin.payment-methods');
    }

    public function showRolesView()
    {
        return view('tenants.admin.roles');
    }

    public function showSupportCenter()
    {
        return view('tenants.admin.support-center');
    }

    public function showEventsView()
    {
        return view('tenants.admin.events');
    }



    public function showEventsTable(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return redirect()->route('tenant.admin.dashboard')
                ->with('notification', [
                    'message'    => 'Your session has expired. Please log in again.',
                    'alert-type' => 'error',
                ]);
        }

        $events = DB::connection('tenant')->table('events')
            ->where('user_id', $authUser->id)
            ->orderBy('start_date', 'asc')
            ->get();

        return view('tenants.admin.events-table', compact('events'));
    }


    public function showCurrencyView()
    {
        return view('tenants.admin.currency');
    }

    
    public function showBranchesView()
    {
        return view('tenants.admin.branches');
    }


     
    public function  showBranchDetailsView()
    {
        return view('tenants.admin.branch-details');
    }

    public function  showCategoriesView()
    {
        return view('tenants.admin.categories');
    }


    public function  showSectorsView()
    {
        return view('tenants.admin.sectors');
    }

   

    public function updateCompanyGeneralInfo(Request $request)
    {
        $data = [
            'business_name' => $request->business_name,
            'business_license_number' => $request->business_license_number,
            'tin_number' => $request->tin_number,
            'business_description' => $request->business_description,
            'business_mission' => $request->business_mission,
            'business_vision' => $request->business_vision,
        ];

        $validator = $request->validate([
            'business_name' => 'required|max:100',
            'business_license_number' => 'nullable|max:100',
            'tin_number' => 'nullable|max:100',
            'business_description' => 'nullable|max:2000',
            'business_mission' => 'nullable|max:2000',
            'business_vision' => 'nullable|max:2000',
        ]);

        if ($validator) {
            $updated = DB::connection('tenant')->table('company_info')->where('id', 1)->update($data);
            if ($updated) {
                return response()->json(['success' => 'Data updated successfully.', 'status' => 201]);
            } else {
                return response()->json(['error' => 'No changes made or record not found.', 'status' => 409]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function updateCompanyContactInfo(Request $request)
    {
        $data = [
            'primary_number' => $request->primary_number,
            'secondary_number' => $request->secondary_number,
            'email_address' => $request->email_address,
            'physical_address' => $request->physical_address,
            'postal_address' => $request->postal_address,
        ];

        $validator = $request->validate([
            'primary_number' => 'nullable|max:11',
            'secondary_number' => 'nullable|max:11',
            'email_address' => 'nullable|email|max:100',
            'physical_address' => 'nullable|max:2000',
            'postal_address' => 'nullable|max:2000',
        ]);

        if ($validator) {
            $updated = DB::connection('tenant')->table('company_info')->where('id', 1)->update($data);
            if ($updated) {
                return response()->json(['success' => 'Data updated successfully.', 'status' => 201]);
            } else {
                return response()->json(['error' => 'No changes made or record not found.', 'status' => 409]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }


public function uploadDocument(Request $request)
{
    $file = $request->file('file');
    $fileSize = $file ? $file->getSize() : 0;
    $extension = $file ? $file->getClientOriginalExtension() : '';

    $firstFive = substr($request->name ?? '', 0, 5);
    $filename = $firstFive . '_' . Str::random(8) . '.' . $extension;

    $data = [
        'name'       => $request->name,
        'filename'   => $filename,
        'path'       => 'files/tenants/company/' . $filename,
        'size'       => $fileSize,
        'created_at' => now(),
        'updated_at' => now()
    ];

    $validator = $request->validate([
        'name' => 'required|string|max:255',
        'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
    ]);

    if ($validator) {
        $path = public_path('files/tenants/company');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $file->move($path, $filename);

        $inserted = DB::connection('tenant')->table('company_files')->insert($data);

        if ($inserted) {
            return response()->json([
                'success' => 'Document uploaded successfully',
                'status'  => 201
            ]);
        } else {
            return response()->json([
                'error'  => 'Failed to upload document',
                'status' => 500
            ]);
        }
    } else {
        return response()->json([
            'errors' => $validator->errors()->all(),
            'status' => 422
        ]);
    }
}

public function uploadImage(Request $request)
{
    $blob = $request->file('file');
    $firstFive = substr($request->name ?? '', 0, 5);
    $filename = $firstFive . '_' . Str::random(10) . '.jpg';
    $fullPath = public_path('files/tenants/company/' . $filename);

    $fileSize = 0;

    $data = [
        'name'       => $request->name,
        'filename'   => $filename,
        'path'       => 'files/tenants/company/' . $filename,
        'size'       => $fileSize,
        'created_at' => now(),
        'updated_at' => now()
    ];

    $validator = $request->validate([
        'name' => 'required|string|max:255',
        'file' => 'required|image|max:5120',
    ]);

    if ($validator) {
        if (!File::exists(public_path('files/tenants/company'))) {
            File::makeDirectory(public_path('files/tenants/company'), 0755, true);
        }

        file_put_contents($fullPath, file_get_contents($blob->getRealPath()));
        $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;
        $data['size'] = $fileSize;

        $inserted = DB::connection('tenant')->table('company_files')->insert($data);

        if ($inserted) {
            return response()->json([
                'success' => 'Image uploaded successfully',
                'status'  => 201
            ]);
        } else {
            return response()->json([
                'error'  => 'Failed to upload image',
                'status' => 500
            ]);
        }
    } else {
        return response()->json([
            'errors' => $validator->errors()->all(),
            'status' => 422
        ]);
    }
}

public function updateName(Request $request)
{
    $data = ['name' => $request->name];

    $validator = $request->validate([
        'id'   => 'required|integer|exists:tenant.company_files,id',
        'name' => 'required|string|max:255',
    ]);

    if ($validator) {
        $updated = DB::connection('tenant')->table('company_files')
                    ->where('id', $request->id)
                    ->update($data);

        if ($updated) {
            return response()->json([
                'success' => 'File name updated successfully',
                'status'  => 201
            ]);
        } else {
            return response()->json([
                'error'  => 'No changes made or file not found',
                'status' => 409
            ]);
        }
    } else {
        return response()->json([
            'errors' => $validator->errors()->all(),
            'status' => 422
        ]);
    }
}

public function deleteFile(Request $request)
{
    $validator = $request->validate([
        'id' => 'required|integer|exists:tenant.company_files,id',
    ]);

    $file = DB::connection('tenant')->table('company_files')->find($request->id);

    if (!$file) {
        return response()->json([
            'error'  => 'File not found',
            'status' => 404
        ]);
    }

    File::delete(public_path('files/tenants/company/' . $file->filename));

    $deleted = DB::connection('tenant')->table('company_files')
                ->where('id', $request->id)
                ->delete();

    if ($deleted) {
        return response()->json([
            'success' => 'File deleted successfully',
            'status'  => 201
        ]);
    } else {
        return response()->json([
            'error'  => 'Failed to delete file',
            'status' => 500
        ]);
    }
}

public function bulkDeleteFiles(Request $request)
{
    $validator = $request->validate([
        'ids'   => 'required|array',
        'ids.*' => 'required|integer|exists:tenant.company_files,id',
    ]);

    if ($validator) {
        $files = DB::connection('tenant')->table('company_files')
                    ->whereIn('id', $request->ids)
                    ->get();

        foreach ($files as $file) {
            File::delete(public_path('files/tenants/company/' . $file->filename));
        }

        $deleted = DB::connection('tenant')->table('company_files')
                    ->whereIn('id', $request->ids)
                    ->delete();

        if ($deleted > 0) {
            return response()->json([
                'success' => 'Selected files deleted',
                'status'  => 201
            ]);
        } else {
            return response()->json([
                'error'  => 'No files found or permission denied.',
                'status' => 404
            ]);
        }
    } else {
        return response()->json([
            'errors' => $validator->errors()->all(),
            'status' => 422
        ]);
    }
}

public function downloadFile(Request $request)
{
    $validator = $request->validate([
        'id' => 'required|integer|exists:tenant.company_files,id',
    ]);

    $file = DB::connection('tenant')->table('company_files')->find($request->id);

    if (!$file) {
        return response()->json([
            'error'  => 'File record not found',
            'status' => 404
        ]);
    }

    $path = public_path('files/tenants/company/' . $file->filename);

    if (!File::exists($path)) {
        return response()->json([
            'error'  => 'File not found on server',
            'status' => 404
        ]);
    }

    $ext = strtolower(pathinfo($file->filename, PATHINFO_EXTENSION));
    $downloadName = $file->name . '.' . $ext;

    return response()->download($path, $downloadName);
}

   public function fetchEvents(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
        }

        $query = DB::connection('tenant')->table('events')
            ->where('user_id', $authUser->id);

        if ($request->has('upcoming')) {
            $query->where('start_date', '>=', now()->toDateString());
        }

        $events = $query->orderBy('start_date', 'asc')->get()->map(function ($event) {
            $start = $event->start_date . ($event->all_day ? '' : 'T' . $event->start_time);
            $end = $event->all_day
                ? Carbon::parse($event->end_date)->addDay()->format('Y-m-d')
                : ($event->end_date . 'T' . $event->end_time);
            return [
                'id' => $event->id,
                'title' => $event->description,
                'start' => $start,
                'end' => $end,
                'allDay' => (bool) $event->all_day,
                'classNames' => [$event->bg_color],
                'extendedProps' => [
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'bg_color' => $event->bg_color
                ],
            ];
        });

        return response()->json([
            'success' => 'Events fetched successfully.',
            'status' => 201,
            'events' => $events
        ]);
    }
    public function storeEvent(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
        }

        $startTime = $request->all_day ? null : ($request->start_time ?? null);
        $endTime = $request->all_day ? null : ($request->end_time ?? null);

        if (!$request->all_day && $startTime && $endTime && strtotime($endTime) < strtotime($startTime)) {
            $endTime = $startTime;
        }

        $data = [
            'description' => trim($request->description),
            'bg_color' => $request->bg_color,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date ?: $request->start_date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'all_day' => $request->all_day ?? false,
            'user_id' => $authUser->id,
        ];

        $validator = $request->validate([
            'description' => 'required|string|max:255',
            'bg_color' => 'required|in:bg-danger,bg-success,bg-primary,bg-info,bg-dark,bg-warning',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'all_day' => 'boolean',
        ]);

        if ($validator) {
            $insertId = DB::connection('tenant')->table('events')->insertGetId($data);
            if ($insertId) {
                $event = DB::connection('tenant')->table('events')->where('id', $insertId)->first();
                $start = $event->start_date . ($event->all_day ? '' : 'T' . $event->start_time);
                $end = $event->all_day
                    ? Carbon::parse($event->end_date)->addDay()->format('Y-m-d')
                    : ($event->end_date . 'T' . $event->end_time);

                return response()->json([
                    'success' => 'Event created successfully.',
                    'status' => 201,
                    'event' => [
                        'id' => $event->id,
                        'title' => $event->description,
                        'start' => $start,
                        'end' => $end,
                        'allDay' => (bool) $event->all_day,
                        'classNames' => [$event->bg_color],
                        'extendedProps' => [
                            'start_time' => $event->start_time,
                            'end_time' => $event->end_time,
                            'bg_color' => $event->bg_color
                        ],
                    ],
                ]);
            } else {
                return response()->json(['error' => 'Failed to create event.', 'status' => 500]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }public function updateEvent(Request $request, $tenantName, $id)
{
    $authUser = Auth::user();
    if (!$authUser) {
        return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
    }

    $startTime = $request->all_day ? null : ($request->start_time ?? null);
    $endTime = $request->all_day ? null : ($request->end_time ?? null);

    if (!$request->all_day && $startTime && $endTime && strtotime($endTime) < strtotime($startTime)) {
        $endTime = $startTime;
    }

    $data = [
        'description' => trim($request->description),
        'bg_color' => $request->bg_color,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date ?: $request->start_date,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'all_day' => $request->all_day ?? false,
    ];

    $validator = $request->validate([
        'description' => 'required|string|max:255',
        'bg_color' => 'required|in:bg-danger,bg-success,bg-primary,bg-info,bg-dark,bg-warning',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'all_day' => 'boolean',
    ]);

    if ($validator) {
        $updated = DB::connection('tenant')->table('events')->where('id', $id)->where('user_id', $authUser->id)->update($data);
        if ($updated) {
            $event = DB::connection('tenant')->table('events')->where('id', $id)->first();
            $start = $event->start_date . ($event->all_day ? '' : 'T' . $event->start_time);
            $end = $event->all_day
                ? Carbon::parse($event->end_date)->addDay()->format('Y-m-d')
                : ($event->end_date . 'T' . $event->end_time);

            return response()->json([
                'success' => 'Event updated successfully.',
                'status' => 201,
                'event' => [
                    'id' => $event->id,
                    'title' => $event->description,
                    'start' => $start,
                    'end' => $end,
                    'allDay' => (bool) $event->all_day,
                    'classNames' => [$event->bg_color],
                    'extendedProps' => [
                        'start_time' => $event->start_time,
                        'end_time' => $event->end_time,
                        'bg_color' => $event->bg_color
                    ]
                ]
            ]);
        } else {
            return response()->json(['error' => 'Event not found or no changes made.', 'status' => 409]);
        }
    } else {
        return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
    }
}
public function deleteEvent(Request $request, $tenantName, $id)
{
    $authUser = Auth::user();
    if (!$authUser) {
        return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
    }

    $deleted = DB::connection('tenant')->table('events')->where('id', $id)->where('user_id', $authUser->id)->delete();
    if ($deleted) {
        return response()->json(['success' => 'Event deleted successfully.', 'status' => 201]);
    } else {
        return response()->json(['error' => 'Event not found or permission denied.', 'status' => 404]);
    }
}

public function bulkDeleteEvents(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
        }

        $validator = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:tenant.events,id',
        ]);

        if ($validator) {
            $deleted = DB::connection('tenant')->table('events')
                ->whereIn('id', $request->ids)
                ->where('user_id', $authUser->id)
                ->delete();

            if ($deleted > 0) {
                return response()->json(['success' => 'Selected events deleted successfully.', 'status' => 201]);
            } else {
                return response()->json(['error' => 'No events found or permission denied.', 'status' => 404]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function addEventForTableView(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
        }

        $startTime = $request->all_day ? null : ($request->start_time ?? null);
        $endTime = $request->all_day ? null : ($request->end_time ?? null);

        if (!$request->all_day && $startTime && $endTime && strtotime($endTime) < strtotime($startTime)) {
            $endTime = $startTime;
        }

        $data = [
            'description' => trim($request->description),
            'bg_color' => $request->bg_color,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date ?: $request->start_date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'all_day' => $request->all_day ?? false,
            'user_id' => $authUser->id,
        ];

        $validator = $request->validate([
            'description' => 'required|string|max:255',
            'bg_color' => 'required|in:bg-danger,bg-success,bg-primary,bg-info,bg-dark,bg-warning',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'all_day' => 'boolean',
        ]);

        if ($validator) {
            $insertId = DB::connection('tenant')->table('events')->insertGetId($data);
            if ($insertId) {
                $event = DB::connection('tenant')->table('events')->where('id', $insertId)->first();
                $start = $event->start_date . ($event->all_day ? '' : 'T' . $event->start_time);
                $end = $event->all_day
                    ? Carbon::parse($event->end_date)->addDay()->format('Y-m-d')
                    : ($event->end_date . 'T' . $event->end_time);

                return response()->json([
                    'success' => 'Event created successfully.',
                    'status' => 201,
                    'event' => [
                        'id' => $event->id,
                        'title' => $event->description,
                        'start' => $start,
                        'end' => $end,
                        'allDay' => (bool) $event->all_day,
                        'classNames' => [$event->bg_color],
                        'extendedProps' => [
                            'start_time' => $event->start_time,
                            'end_time' => $event->end_time,
                            'bg_color' => $event->bg_color
                        ]
                    ],
                ]);
            } else {
                return response()->json(['error' => 'Failed to create event.', 'status' => 500]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }


 
private function formatEmployee($data): array
{
    return [
        'id'                           => $data->id,
        'name'                         => $data->name,
        'phone'                        => $data->phone,
        'email'                        => $data->email,
        'role'                         => $data->role,
        'branch'                       => DB::connection('tenant')->table('branches')->where('id', $data->branch)->value('name'),
        'department'                   => $data->department,
        'position'                     => $data->position,
        'gross_salary'                 => $data->gross_salary,
        'dob'                          => $data->dob,
        'started_on'                   => $data->started_on,
        'idtype'                       => $data->idtype,
        'idnumber'                     => $data->idnumber,
        'home_address'                 => $data->home_address,
        'current_residence'            => $data->current_residence,
        'nextofkin_name'               => $data->nextofkin_name,
        'nextofkin_relationship'       => $data->nextofkin_relationship,
        'nextofkin_physical_address'   => $data->nextofkin_physical_address,
        'nextofkin_contact'            => $data->nextofkin_contact,
        'employment_type'              => $data->employment_type,
        'contract_end_date'            => $data->contract_end_date,
        'bank_name'                    => $data->bank_name,
        'bank_account_name'            => $data->bank_account_name,
        'bank_account_number'          => $data->bank_account_number,
        'bank_branch'                  => $data->bank_branch,
        'bank_account_type'            => $data->bank_account_type,
        'on_paye'                      => (bool) ($data->on_paye ?? false),
    ];
}
 
private function employeeRules(?int $id = null): array
{
    $uniquePhone = 'required|string|unique:tenant.users,phone';
    $uniqueEmail = 'required|email|unique:tenant.users,email';
 
    if ($id) {
        $uniquePhone .= ',' . $id;
        $uniqueEmail .= ',' . $id;
    }
 
    return [
        'name'                         => 'required|string|max:255',
        'phone'                        => $uniquePhone,
        'email'                        => $uniqueEmail,
        'role'                         => 'nullable|string',
        'branch'                       => 'nullable|string',
        'department'                   => 'nullable|string',
        'position'                     => 'nullable|string',
        'gross_salary'                 => 'nullable|integer',
        'dob'                          => 'nullable|date',
        'started_on'                   => 'nullable|date',
        'idtype'                       => 'nullable|string',
        'idnumber'                     => 'nullable|string',
        'home_address'                 => 'nullable|string',
        'current_residence'            => 'nullable|string',
        'nextofkin_name'               => 'nullable|string',
        'nextofkin_relationship'       => 'nullable|string',
        'nextofkin_physical_address'   => 'nullable|string',
        'nextofkin_contact'            => 'nullable|string',
        'employment_type'              => 'nullable|in:Full-time,Part-time,Contract,Casual',
        'contract_end_date'            => 'nullable|date|after_or_equal:started_on',
        'bank_name'                    => 'nullable|string|max:255',
        'bank_account_name'            => 'nullable|string|max:255',
        'bank_account_number'          => 'nullable|string|max:100',
        'bank_branch'                  => 'nullable|string|max:255',
        'bank_account_type'            => 'nullable|in:Savings,Current,Cheque',
        'on_paye'                      => 'nullable|boolean',
    ];
}
 
private function employeeDataFromRequest(Request $request): array
{
    return [
        'name'                         => $request->name,
        'phone'                        => $request->phone,
        'email'                        => $request->email,
        'role'                         => $request->role,
        'branch'                       => $request->branch,
        'department'                   => $request->department,
        'position'                     => $request->position,
        'gross_salary'                 => $request->gross_salary,
        'dob'                          => $request->dob,
        'started_on'                   => $request->started_on,
        'idtype'                       => $request->idtype,
        'idnumber'                     => $request->idnumber,
        'home_address'                 => $request->home_address,
        'current_residence'            => $request->current_residence,
        'nextofkin_name'               => $request->nextofkin_name,
        'nextofkin_relationship'       => $request->nextofkin_relationship,
        'nextofkin_physical_address'   => $request->nextofkin_physical_address,
        'nextofkin_contact'            => $request->nextofkin_contact,
        'employment_type'              => $request->employment_type ?? 'Full-time',
        'contract_end_date'            => $request->contract_end_date,
        'bank_name'                    => $request->bank_name,
        'bank_account_name'            => $request->bank_account_name,
        'bank_account_number'          => $request->bank_account_number,
        'bank_branch'                  => $request->bank_branch,
        'bank_account_type'            => $request->bank_account_type ?? 'Savings',
        'on_paye'                      => $request->boolean('on_paye', false),
    ];
}
 
public function insertEmployee(Request $request)
{
    $validator = Validator::make($request->all(), $this->employeeRules());
 
    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'errors' => $validator->errors()
        ], 422);
    }
 
    $data = $this->employeeDataFromRequest($request);
 
    // system fields only on insert
    $data['password']   = Hash::make('1234');
    $data['active']     = 'Yes';
    $data['entered_on'] = now();
 
    $id = DB::connection('tenant')->table('users')->insertGetId($data);
 
    $employee = DB::connection('tenant')->table('users')->where('id', $id)->first();
 
    return response()->json([
        'status'   => 201,
        'success'  => 'Employee added successfully!',
        'employee' => $this->formatEmployee($employee),
    ], 201);
}
 
public function updateEmployee(Request $request)
{
    $validator = Validator::make($request->all(), $this->employeeRules((int) $request->id));
 
    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'errors' => $validator->errors()
        ], 422);
    }
 
    DB::connection('tenant')->table('users')
        ->where('id', $request->id)
        ->update($this->employeeDataFromRequest($request));
 
    $employee = DB::connection('tenant')->table('users')->where('id', $request->id)->first();
 
    return response()->json([
        'status'   => 201,
        'success'  => 'Employee updated successfully!',
        'employee' => $this->formatEmployee($employee),
    ], 201);
}
 
public function updateEmployeeDetails(Request $request)
{
    $validator = Validator::make($request->all(), $this->employeeRules((int) $request->id));
 
    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'errors' => $validator->errors()
        ], 422);
    }
 
    $updateData = DB::connection('tenant')->table('users')
        ->where('id', $request->id)
        ->update($this->employeeDataFromRequest($request));
 
    if ($updateData) {
        return response()->json([
            'status'  => 201,
            'success' => 'Employee updated successfully!',
        ], 201);
    } else {
        return response()->json([
            'status' => 203,
            'error'  => 'Record not found or no data change detected',
        ], 203);
    }
}


public function deleteEmployee(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id' => 'required|exists:tenant.users,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'error'  => 'The employee could not be found. Please refresh the page and try again.',
        ], 422);
    }

    // Prevent the logged-in user from deleting their own account.
    if ((int) $request->id === (int) Auth::id()) {
        return response()->json([
            'status' => 403,
            'error'  => 'You cannot delete your own account.',
        ], 403);
    }

    try {
        DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=0');
        DB::connection('tenant')->table('users')->where('id', $request->id)->delete();

        return response()->json([
            'status'  => 201,
            'success' => 'The employee record has been removed successfully.',
        ], 201);

    } catch (\Exception $e) {
        \Log::error('Employee delete failed for ID ' . $request->id . ': ' . $e->getMessage());

        return response()->json([
            'status' => 500,
            'error'  => 'Something went wrong while removing the employee. Please try again or contact support.',
        ], 500);

    } finally {
        DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=1');
    }
}

    public function insertPaymentMethod(Request $request)
    {
        $methodType = $request->input('method_type', 'Bank');

        $data = [
            'method_type' => $methodType,
        ];

        $rules = [
            'method_type' => 'required|in:Bank,Mobile,Paypal',
        ];

        if ($methodType === 'Bank') {
            $rules['bank_name']          = 'required|string|max:255';
            $rules['account_name']       = 'required|string|max:255';
            $rules['account_number']     = 'required|string|max:255';
            $rules['account_swift_code'] = 'nullable|string|max:255';
            $rules['account_type']       = 'nullable|string|max:255';
            $rules['account_branch']     = 'nullable|string|max:255';

            $data['bank_name']           = trim($request->bank_name);
            $data['account_name']        = trim($request->account_name);
            $data['account_number']      = $request->account_number;
            $data['account_swift_code']  = $request->account_swift_code;
            $data['account_type']        = $request->account_type;
            $data['account_branch']      = $request->account_branch;
        } elseif ($methodType === 'Mobile') {
            $rules['mobile_operator']     = 'required|string|max:255';
            $rules['mobile_number']       = 'required|string|max:255';
            $rules['mobile_number_name']  = 'required|string|max:255';

            $data['mobile_operator']     = trim($request->mobile_operator);
            $data['mobile_number']       = $request->mobile_number;
            $data['mobile_number_name']  = trim($request->mobile_number_name);
        } elseif ($methodType === 'Paypal') {
            $rules['paypal_name']     = 'required|string|max:255';
            $rules['paypal_email']    = 'required|email|max:255';
            $rules['paypal_me_link']  = 'nullable|url|max:255';

            $data['paypal_name']     = trim($request->paypal_name);
            $data['paypal_email']    = $request->paypal_email;
            $data['paypal_me_link']  = $request->paypal_me_link;
        }

        $validator = $request->validate($rules);

        if ($validator) {
            $insertId = DB::connection('tenant')->table('payment_methods')->insertGetId($data);
            if ($insertId) {
                $method = DB::connection('tenant')->table('payment_methods')->where('id', $insertId)->first();

                return response()->json([
                    'success' => 'Method created successfully.',
                    'status'  => 201,
                    'method'  => [
                        'id'               => $method->id,
                        'method_type'      => $method->method_type,
                        'bank_name'        => $method->bank_name,
                        'account_name'     => $method->account_name,
                        'account_number'   => $method->account_number,
                        'account_swift_code' => $method->account_swift_code,
                        'account_type'     => $method->account_type,
                        'account_branch'   => $method->account_branch,
                        'mobile_operator'  => $method->mobile_operator,
                        'mobile_number'    => $method->mobile_number,
                        'mobile_number_name' => $method->mobile_number_name,
                        'paypal_name'      => $method->paypal_name,
                        'paypal_email'     => $method->paypal_email,
                        'paypal_me_link'   => $method->paypal_me_link,
                    ],
                ]);
            } else {
                return response()->json(['error' => 'Failed to create method.', 'status' => 500]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function updatePaymentMethod(Request $request)
    {
        $current = DB::connection('tenant')->table('payment_methods')->where('id', $request->id)->first();
        $methodType = $request->method_type ?? $current->method_type ?? 'Bank';

        $data = [
            'method_type' => $methodType,
        ];

        $rules = [
            'id'          => 'required|integer|exists:payment_methods,id',
            'method_type' => 'sometimes|in:Bank,Mobile,Paypal',
        ];

        if ($methodType === 'Bank') {
            $rules['bank_name']          = 'required|string|max:255';
            $rules['account_name']       = 'required|string|max:255';
            $rules['account_number']     = 'required|string|max:255';
            $rules['account_swift_code'] = 'nullable|string|max:255';
            $rules['account_type']       = 'nullable|string|max:255';
            $rules['account_branch']     = 'nullable|string|max:255';

            $data['bank_name']           = trim($request->bank_name);
            $data['account_name']        = trim($request->account_name);
            $data['account_number']      = $request->account_number;
            $data['account_swift_code']  = $request->account_swift_code;
            $data['account_type']        = $request->account_type;
            $data['account_branch']      = $request->account_branch;

            $data['mobile_operator']     = null;
            $data['mobile_number']       = null;
            $data['mobile_number_name']  = null;
            $data['paypal_name']         = null;
            $data['paypal_email']        = null;
            $data['paypal_me_link']      = null;
        } elseif ($methodType === 'Mobile') {
            $rules['mobile_operator']     = 'required|string|max:255';
            $rules['mobile_number']       = 'required|string|max:255';
            $rules['mobile_number_name']  = 'required|string|max:255';

            $data['mobile_operator']     = trim($request->mobile_operator);
            $data['mobile_number']       = $request->mobile_number;
            $data['mobile_number_name']  = trim($request->mobile_number_name);

            $data['bank_name']           = null;
            $data['account_name']        = null;
            $data['account_number']      = null;
            $data['account_swift_code']  = null;
            $data['account_type']        = null;
            $data['account_branch']      = null;
            $data['paypal_name']         = null;
            $data['paypal_email']        = null;
            $data['paypal_me_link']      = null;
        } elseif ($methodType === 'Paypal') {
            $rules['paypal_name']     = 'required|string|max:255';
            $rules['paypal_email']    = 'required|email|max:255';
            $rules['paypal_me_link']  = 'nullable|url|max:255';

            $data['paypal_name']     = trim($request->paypal_name);
            $data['paypal_email']    = $request->paypal_email;
            $data['paypal_me_link']  = $request->paypal_me_link;

            $data['bank_name']           = null;
            $data['account_name']        = null;
            $data['account_number']      = null;
            $data['account_swift_code']  = null;
            $data['account_type']        = null;
            $data['account_branch']      = null;
            $data['mobile_operator']     = null;
            $data['mobile_number']       = null;
            $data['mobile_number_name']  = null;
        }

        $validator = $request->validate($rules);

        if ($validator) {
            $updated = DB::connection('tenant')->table('payment_methods')->where('id', $request->id)->update($data);
            if ($updated || $updated === 0) {
                $method = DB::connection('tenant')->table('payment_methods')->where('id', $request->id)->first();

                return response()->json([
                    'success' => 'Method updated successfully.',
                    'status'  => 201,
                    'method'  => [
                        'id'               => $method->id,
                        'method_type'      => $method->method_type,
                        'bank_name'        => $method->bank_name,
                        'account_name'     => $method->account_name,
                        'account_number'   => $method->account_number,
                        'account_swift_code' => $method->account_swift_code,
                        'account_type'     => $method->account_type,
                        'account_branch'   => $method->account_branch,
                        'mobile_operator'  => $method->mobile_operator,
                        'mobile_number'    => $method->mobile_number,
                        'mobile_number_name' => $method->mobile_number_name,
                        'paypal_name'      => $method->paypal_name,
                        'paypal_email'     => $method->paypal_email,
                        'paypal_me_link'   => $method->paypal_me_link,
                    ],
                ]);
            } else {
                return response()->json(['error' => 'Method not found or no changes made.', 'status' => 409]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function deletePaymentMethod(Request $request)
    {
        $deleted = DB::connection('tenant')->table('payment_methods')->where('id', $request->id)->delete();

        if ($deleted) {
            return response()->json(['success' => 'Method deleted successfully.', 'status' => 201]);
        } else {
            return response()->json(['error' => 'Method not found.', 'status' => 404]);
        }
    }


    public function insertCurrency(Request $request)
    {
        $data = [
            'name' => trim($request->name),
            'code' => strtoupper(trim($request->code)),
        ];

        $validator = $request->validate([
            'name' => 'required|string|max:255|unique:tenant.currency,name',
            'code' => 'required|size:3|unique:tenant.currency,code',
        ]);

        if ($validator) {
            $insertId = DB::connection('tenant')->table('currency')->insertGetId($data);
            if ($insertId) {
                $currency = DB::connection('tenant')->table('currency')->where('id', $insertId)->first();

                return response()->json([
                    'success' => 'Currency created successfully.',
                    'status'  => 201,
                    'currency'  => [
                        'id'   => $currency->id,
                        'name'  => $currency->name,
                        'code'  => $currency->code,
                    ],
                ]);
            } else {
                return response()->json(['error' => 'Failed to create currency.', 'status' => 500]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function updateCurrency(Request $request)
    {
        $data = [
            'name' => trim($request->name),
            'code' => strtoupper(trim($request->code)),
        ];

        $validator = $request->validate([
            'id'   => 'required|integer|exists:tenant.currency,id',
            'name' => 'required|string|max:255|unique:tenant.currency,name,' . $request->id,
            'code' => 'required|size:3|unique:tenant.currency,code,' . $request->id,
        ]);

        if ($validator) {
            $updated = DB::connection('tenant')->table('currency')->where('id', $request->id)->update($data);
            if ($updated) {
                $currency = DB::connection('tenant')->table('currency')->where('id', $request->id)->first();

                return response()->json([
                    'success' => 'Currency updated successfully.',
                    'status'  => 201,
                    'currency'  => [
                        'id'   => $currency->id,
                        'name'  => $currency->name,
                        'code'  => $currency->code,
                    ],
                ]);
            } else {
                return response()->json(['error' => 'Currency not found or no changes made.', 'status' => 409]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function deleteCurrency(Request $request)
    {
        $deleted = DB::connection('tenant')->table('currency')->where('id', $request->id)->delete();

        if ($deleted) {
            return response()->json(['success' => 'Currency deleted successfully.', 'status' => 201]);
        } else {
            return response()->json(['error' => 'Currency not found.', 'status' => 404]);
        }
    }


    public function insertBranch(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'sector'   => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'address'  => 'nullable|string|max:1000',
            'city'     => 'nullable|string|max:100',
            'phone'    => 'nullable|string|max:50',
            'email'    => 'nullable|email|max:255',
            'status'   => 'nullable|in:active,inactive,archived',
        ]);

        $data = [
            'name'            => trim($request->name),
            'business_number' => trim($request->business_number),
            'address'         => trim($request->address),
            'city'            => trim($request->city),
            'phone'           => trim($request->phone),
            'email'           => trim($request->email),
            'sector'          => trim($request->sector),
            'category'        => trim($request->category),
            'status'          => $request->status ?? 'active',
            'created_at'      => Carbon::today()->toDateString(),
            'updated_at'      => Carbon::today()->toDateString(),
        ];

        $exists = DB::connection('tenant')
            ->table('branches')
            ->where('name',     $data['name'])
            ->where('sector',   $data['sector'])
            ->where('category', $data['category'])
            ->exists();

        if ($exists) {
            return response()->json([
                'errors' => ['The combination of branch name, sector and category has already been taken.'],
                'status' => 422
            ], 422);
        }

        $insertId = DB::connection('tenant')->table('branches')->insertGetId($data);

        if ($insertId) {
            $branch = DB::connection('tenant')->table('branches')->where('id', $insertId)->first();

            $categoryName = DB::connection('tenant')
                ->table('categories')
                ->where('id', $branch->category)
                ->value('category');

            return response()->json([
                'success' => 'Branch created successfully.',
                'status'  => 201,
                'branch'  => [
                    'id'       => $branch->id,
                    'name'     => $branch->name,
                    'address'  => $branch->address,
                    'city'     => $branch->city,
                    'phone'    => $branch->phone,
                    'email'    => $branch->email,
                    'sector'   => $branch->sector,
                    'category' => $categoryName,
                    'status'   => $branch->status,
                ],
            ]);
        }

        return response()->json(['error' => 'Failed to create branch.', 'status' => 500]);
    }

    public function updateBranch(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'address'  => 'nullable|string|max:1000',
            'city'     => 'nullable|string|max:100',
            'phone'    => 'nullable|string|max:50',
            'email'    => 'nullable|email|max:255',
            'status'   => 'nullable|in:active,inactive,archived',
        ]);

        $data = [
            'name'            => trim($request->name),
            'address'         => trim($request->address),
            'city'            => trim($request->city),
            'phone'           => trim($request->phone),
            'email'           => trim($request->email),
            'sector'          => trim($request->sector),
            'category'        => trim($request->category),
            'business_number' => trim($request->business_number),
            'status'          => trim($request->status),
            'updated_at'      => Carbon::today()->toDateString(),
        ];

        $exists = DB::connection('tenant')
            ->table('branches')
            ->where('name',     $data['name'])
            ->where('sector',   $data['sector'])
            ->where('category', $data['category'])
            ->where('id', '!=', $request->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'errors' => ['The combination of branch name, sector and category has already been taken.'],
                'status' => 422
            ], 422);
        }

        $updated = DB::connection('tenant')
            ->table('branches')
            ->where('id', $request->id)
            ->update($data);

        if ($updated) {
            $branch = DB::connection('tenant')->table('branches')->where('id', $request->id)->first();

            $categoryName = DB::connection('tenant')
                ->table('categories')
                ->where('id', $branch->category)
                ->value('category');

            return response()->json([
                'success' => 'Branch updated successfully.',
                'status'  => 201,
                'branch'  => [
                    'id'       => $branch->id,
                    'name'     => $branch->name,
                    'address'  => $branch->address,
                    'city'     => $branch->city,
                    'phone'    => $branch->phone,
                    'email'    => $branch->email,
                    'sector'   => $branch->sector,
                    'category' => $categoryName,
                    'status'   => $branch->status,
                ],
            ]);
        }

        return response()->json(['error' => 'No changes made.', 'status' => 409]);
    }

    public function deleteBranch(Request $request)
    {
        $deleted = DB::connection('tenant')
            ->table('branches')
            ->where('id', $request->id)
            ->delete();

        if ($deleted) {
            return response()->json(['success' => 'Branch deleted successfully.', 'status' => 201]);
        }

        return response()->json(['error' => 'Branch not found.', 'status' => 404]);
    }


    /* ════════════════════════════════════════════════════════════════════
       BRANCH SALES SETTINGS
       One row per branch (branch_id unique). The view queries its own
       defaults directly (same self-querying pattern as branches.blade.php
       and branch-details.blade.php), so this controller only needs to
       serve the page and handle the save.
       ════════════════════════════════════════════════════════════════════ */

    public function showBranchSalesSettingsListView()
    {
        // branches.category is a foreign key -> categories.id (categories.category
        // is the display label), so join it once here instead of resolving it
        // per-row in the view. branches is the source of truth for this list —
        // branch_sales_settings is only consulted below to flag which branches
        // already have a settings row.
        $branches = DB::connection('tenant')->table('branches')
            ->leftJoin('categories', 'branches.category', '=', 'categories.id')
            ->orderBy('branches.name')
            ->select('branches.*', 'categories.category as category_name')
            ->get();

        // Just the branch_ids that already have a settings row — enough to
        // flag "Configured" vs "Default" per branch without pulling every
        // column for every branch.
        $configuredBranchIds = DB::connection('tenant')->table('branch_sales_settings')
            ->pluck('branch_id')
            ->flip();

        return view('tenants.admin.branch-sales-settings-list', compact('branches', 'configuredBranchIds'));
    }

    public function showBranchSalesSettingsView()
    {
        return view('tenants.admin.branch-sales-settings');
    }

    public function updateBranchSalesSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id'                                => 'required|integer|exists:tenant.branches,id',
            'auto_upload_cloud_sales_interval_minutes'  => 'nullable|integer|min:1|max:1440',
            'auto_refresh_interval_minutes'             => 'nullable|integer|min:1|max:1440',
            'idle_timeout_minutes'                      => 'nullable|integer|min:1|max:1440',
            'session_lifetime_minutes'                  => 'nullable|integer|min:1|max:1440',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $branchId = (int) $request->branch_id;

        // Toggle + interval pairs — the interval only means something while
        // its switch is on, so force it back to null whenever the switch is
        // off rather than trusting whatever the client happened to send.
        $autoUploadCloudSales = $request->boolean('auto_upload_cloud_sales');
        $autoRefreshPage      = $request->boolean('auto_refresh_page');

        $data = [
            'auto_upload_cloud_sales'                  => $autoUploadCloudSales,
            'auto_upload_cloud_sales_interval_minutes' => $autoUploadCloudSales
                ? ($request->auto_upload_cloud_sales_interval_minutes ?: 2)
                : null,
            'allow_to_clear_cloud_sales' => $request->boolean('allow_to_clear_cloud_sales'),

            'display_yesterdays_sales'          => $request->boolean('display_yesterdays_sales'),
            'display_price_changes'             => $request->boolean('display_price_changes'),
            'display_deliverynotes_this_month'  => $request->boolean('display_deliverynotes_this_month'),
            'display_sales_this_month'          => $request->boolean('display_sales_this_month'),
            'display_number_of_customers_today' => $request->boolean('display_number_of_customers_today'),
            'display_regular_orders_short_cut'  => $request->boolean('display_regular_orders_short_cut'),
            'display_emergency_order_short_cut' => $request->boolean('display_emergency_order_short_cut'),
            'display_low_stock_alerts'          => $request->boolean('display_low_stock_alerts'),

            'auto_refresh_page' => $autoRefreshPage,
            'auto_refresh_interval_minutes' => $autoRefreshPage
                ? ($request->auto_refresh_interval_minutes ?: 5)
                : null,

            // Null = disabled, a number = enabled — taken as-is, no separate flag.
            'idle_timeout_minutes'     => $request->idle_timeout_minutes ?: null,
            'session_lifetime_minutes' => $request->session_lifetime_minutes ?: null,

            'updated_at' => now(),
        ];

        $exists = DB::connection('tenant')->table('branch_sales_settings')->where('branch_id', $branchId)->exists();

        if ($exists) {
            DB::connection('tenant')->table('branch_sales_settings')->where('branch_id', $branchId)->update($data);
        } else {
            $data['branch_id']  = $branchId;
            $data['created_at'] = now();
            DB::connection('tenant')->table('branch_sales_settings')->insert($data);
        }

        return response()->json(['status' => 201, 'success' => 'Sales settings updated successfully.']);
    }

    public function insertCategory(Request $request)
    {
        $data = [
            'category' => trim($request->category),
            'description' => trim($request->description),
        ];

        $validator = $request->validate([
            'category'   => 'required|string|max:255|unique:tenant.categories,category',
            'description' => 'required|string|max:5000',
        ]);

        if ($validator) {
            $insertId = DB::connection('tenant')->table('categories')->insertGetId($data);
            if ($insertId) {
                $category = DB::connection('tenant')->table('categories')->where('id', $insertId)->first();

                return response()->json([
                    'success'   => 'Category created successfully.',
                    'status'    => 201,
                    'category' => [
                        'id'        => $category->id,
                        'category' => $category->category,
                        'description' => $category->description,
                    ],
                ]);
            } else {
                return response()->json(['error' => 'Failed to create category.', 'status' => 500]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }


    public function updateCategory(Request $request)
    {
        $data = [
            'category' => trim($request->category),
            'description' => trim($request->description)
        ];

        $validator = $request->validate([
            'id'        => 'required|integer|exists:tenant.categories,id',
            'category' => 'required|string|max:255|unique:tenant.categories,category,' . $request->id,
        ]);

        if ($validator) {
            $updated = DB::connection('tenant')->table('categories')->where('id', $request->id)->update($data);
            if ($updated) {
                $category = DB::connection('tenant')->table('categories')->where('id', $request->id)->first();

                return response()->json([
                    'success'   => 'Category updated successfully.',
                    'status'    => 201,
                    'category' => [
                        'id'        => $category->id,
                        'category' => $category->category,
                        'description' => $category->description,
                    ],
                ]);
            } else {
                return response()->json(['error' => 'Failed to update category.', 'status' => 500]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }


    public function deleteCategory(Request $request)
    {
        $validator = $request->validate([
            'id' => 'required|integer',
        ]);

        if ($validator) {
            $deleted = DB::connection('tenant')->table('categories')->where('id', $request->id)->delete();
            if ($deleted) {
                return response()->json([
                    'success' => 'Category deleted successfully.',
                    'status'  => 201,
                ]);
            } else {
                return response()->json(['error' => 'Failed to delete category.', 'status' => 500]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    
    public function showPermissionsView()
    {
        return view('tenants.admin.employee_access');
    }


    public function addPermission(Request $request)
    {
        $data = [
            'employee_id' => trim($request->employee_id),
            'sector_id'   => trim($request->sector_id),
        ];

        $validator = $request->validate([
            'employee_id' => 'required|integer|exists:tenant.users,id',
            'sector_id'   => 'required|integer|exists:tenant.sectors,id',
        ]);

        $exists = DB::connection('tenant')->table('employee_access')
            ->where('employee_id', $data['employee_id'])
            ->where('sector_id', $data['sector_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'error'  => 'This sector is already assigned to the employee.',
                'status' => 422
            ]);
        }

        $insertId = DB::connection('tenant')->table('employee_access')->insertGetId([
            'employee_id' => $data['employee_id'],
            'sector_id'   => $data['sector_id'],
        ]);

        if ($insertId) {
            $access = DB::connection('tenant')->table('employee_access')->where('id', $insertId)->first();
            $sectorName = DB::connection('tenant')->table('sectors')->where('id', $access->sector_id)->value('sector');

            return response()->json([
                'success' => 'Sector assigned successfully.',
                'status'  => 201,
                'access'  => [
                    'id'          => $access->id,
                    'employee_id' => $access->employee_id,
                    'sector'      => $sectorName,
                ],
            ]);
        }

        return response()->json(['error' => 'Failed to assign sector.', 'status' => 500]);
    }

    public function removePermission(Request $request)
    {
        $validator = $request->validate([
            'id' => 'required|integer|exists:tenant.employee_access,id',
        ]);

        if ($validator) {
            $deleted = DB::connection('tenant')->table('employee_access')->where('id', $request->id)->delete();
            if ($deleted) {
                return response()->json([
                    'success' => 'Sector access removed successfully.',
                    'status'  => 201,
                ]);
            } else {
                return response()->json(['error' => 'Failed to remove sector access.', 'status' => 500]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function showSystemSettingsView()
    {
        return view('tenants.admin.settings');
    }

    public function showSystemHelpcenterView()
    {
        return view('tenants.admin.helpcenter');
    }

    public function showSystemSubscriptionView()
    {
        return view('tenants.admin.subscription');
    }

    public function downloadEployeeProfile($tenantName, $id)
    {
        $user = DB::connection('tenant')->table('users')->where('id', $id)->first();

        if (!$user) {
            abort(404, 'Employee not found');
        }

        $companyName = DB::connection('tenant')->table('company_info')->where('id', 1)->value('business_name');

        $pdf = Pdf::loadView('tenants.common.employee-pdf', compact('user', 'companyName'))
                  ->setPaper('a4', 'portrait');

        $filename = 'employee_' . str_replace(' ', '_', strtolower($user->name)) . '_' . $id . '.pdf';

        return $pdf->download($filename);
    }


    public function updateUserFilters(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            $notification = array(
                'message'    => 'Your session has expired. Please log in again.',
                'alert-type' => 'error'
            );
            return Redirect()->back()->with($notification);
        }

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
                ['user_id' => $authUser->id],
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


    public function showSuppliersView()
    {
        return view('tenants.admin.suppliers');
    }

    public function insertSupplier(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:255|unique:tenant.suppliers,name',
            'trading_name'        => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'contact_person'      => 'nullable|string|max:255',
            'phone'               => 'nullable|string|max:50',
            'phone_alt'           => 'nullable|string|max:50',
            'email'               => 'nullable|email|max:255',
            'website'             => 'nullable|url|max:255',
            'bank_name'           => 'nullable|string|max:255',
            'bank_account_name'   => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_branch'         => 'nullable|string|max:255',
            'bank_swift_code'     => 'nullable|string|max:50',
            'payment_terms'       => 'nullable|string|max:255',
            'currency'            => 'nullable|string|max:10',
            'address'             => 'nullable|string|max:1000',
            'city'                => 'nullable|string|max:100',
            'country'             => 'nullable|string|max:100',
            'category'            => 'required|integer|exists:tenant.categories,id',
            'sector'              => 'required|string|exists:tenant.sectors,sector',
            'status'              => 'nullable|in:active,inactive,blacklisted',
            'notes'               => 'nullable|string|max:5000',
        ]);

        $data = [
            'name'                => trim($request->name),
            'trading_name'        => trim($request->trading_name),
            'registration_number' => trim($request->registration_number),
            'contact_person'      => trim($request->contact_person),
            'phone'               => trim($request->phone),
            'phone_alt'           => trim($request->phone_alt),
            'email'               => trim($request->email),
            'website'             => trim($request->website),
            'bank_name'           => trim($request->bank_name),
            'bank_account_name'   => trim($request->bank_account_name),
            'bank_account_number' => trim($request->bank_account_number),
            'bank_branch'         => trim($request->bank_branch),
            'bank_swift_code'     => trim($request->bank_swift_code),
            'payment_terms'       => trim($request->payment_terms),
            'currency'            => trim($request->currency) ?: 'MWK',
            'address'             => trim($request->address),
            'city'                => trim($request->city),
            'country'             => trim($request->country) ?: 'Malawi',
            'category'            => $request->category,
            'sector'              => trim($request->sector),
            'status'              => $request->status ?? 'active',
            'notes'               => trim($request->notes),
        ];

        $insertId = DB::connection('tenant')->table('suppliers')->insertGetId($data);

        if ($insertId) {
            $supplier = DB::connection('tenant')->table('suppliers')
                ->leftJoin('categories', 'suppliers.category', '=', 'categories.id')
                ->select('suppliers.*', 'categories.category as category_name')
                ->where('suppliers.id', $insertId)
                ->first();

            return response()->json([
                'success'  => 'Supplier created successfully.',
                'status'   => 201,
                'supplier' => self::formatSupplier($supplier),
            ]);
        }

        return response()->json(['error' => 'Failed to create supplier.', 'status' => 500]);
    }

    public function updateSupplier(Request $request)
    {
        $request->validate([
            'id'                  => 'required|integer|exists:tenant.suppliers,id',
            'name'                => 'required|string|max:255|unique:tenant.suppliers,name,' . $request->id,
            'trading_name'        => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'contact_person'      => 'nullable|string|max:255',
            'phone'               => 'nullable|string|max:50',
            'phone_alt'           => 'nullable|string|max:50',
            'email'               => 'nullable|email|max:255',
            'website'             => 'nullable|url|max:255',
            'bank_name'           => 'nullable|string|max:255',
            'bank_account_name'   => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_branch'         => 'nullable|string|max:255',
            'bank_swift_code'     => 'nullable|string|max:50',
            'payment_terms'       => 'nullable|string|max:255',
            'currency'            => 'nullable|string|max:10',
            'address'             => 'nullable|string|max:1000',
            'city'                => 'nullable|string|max:100',
            'country'             => 'nullable|string|max:100',
            'category'            => 'required|integer|exists:tenant.categories,id',
            'sector'              => 'required|string|exists:tenant.sectors,sector',
            'status'              => 'nullable|in:active,inactive,blacklisted',
            'notes'               => 'nullable|string|max:5000',
        ]);

        $data = [
            'name'                => trim($request->name),
            'trading_name'        => trim($request->trading_name),
            'registration_number' => trim($request->registration_number),
            'contact_person'      => trim($request->contact_person),
            'phone'               => trim($request->phone),
            'phone_alt'           => trim($request->phone_alt),
            'email'               => trim($request->email),
            'website'             => trim($request->website),
            'bank_name'           => trim($request->bank_name),
            'bank_account_name'   => trim($request->bank_account_name),
            'bank_account_number' => trim($request->bank_account_number),
            'bank_branch'         => trim($request->bank_branch),
            'bank_swift_code'     => trim($request->bank_swift_code),
            'payment_terms'       => trim($request->payment_terms),
            'currency'            => trim($request->currency) ?: 'MWK',
            'address'             => trim($request->address),
            'city'                => trim($request->city),
            'country'             => trim($request->country) ?: 'Malawi',
            'category'            => $request->category,
            'sector'              => trim($request->sector),
            'status'              => $request->status ?? 'active',
            'notes'               => trim($request->notes),
        ];

        $existing = DB::connection('tenant')->table('suppliers')->where('id', $request->id)->first();

        $current = (array) $existing;
        unset($current['updated_at'], $current['created_at'], $current['id']);
        $incoming = $data;

        if ($current == $incoming) {
            return response()->json(['error' => 'No changes detected.', 'status' => 409]);
        }

        DB::connection('tenant')->table('suppliers')->where('id', $request->id)->update($data);

        $supplier = DB::connection('tenant')->table('suppliers')
            ->leftJoin('categories', 'suppliers.category', '=', 'categories.id')
            ->select('suppliers.*', 'categories.category as category_name')
            ->where('suppliers.id', $request->id)
            ->first();

        return response()->json([
            'success'  => 'Supplier updated successfully.',
            'status'   => 201,
            'supplier' => self::formatSupplier($supplier),
        ]);
    }

    public function deleteSupplier(Request $request)
    {
        $deleted = DB::connection('tenant')->table('suppliers')->where('id', $request->id)->delete();

        if ($deleted) {
            return response()->json(['success' => 'Supplier deleted successfully.', 'status' => 201]);
        }

        return response()->json(['error' => 'Supplier not found.', 'status' => 404]);
    }

    private static function formatSupplier($supplier): array
    {
        return [
            'id'                  => $supplier->id,
            'name'                => $supplier->name,
            'trading_name'        => $supplier->trading_name,
            'registration_number' => $supplier->registration_number,
            'contact_person'      => $supplier->contact_person,
            'phone'               => $supplier->phone,
            'phone_alt'           => $supplier->phone_alt,
            'email'               => $supplier->email,
            'website'             => $supplier->website,
            'bank_name'           => $supplier->bank_name,
            'bank_account_name'   => $supplier->bank_account_name,
            'bank_account_number' => $supplier->bank_account_number,
            'bank_branch'         => $supplier->bank_branch,
            'bank_swift_code'     => $supplier->bank_swift_code,
            'payment_terms'       => $supplier->payment_terms,
            'currency'            => $supplier->currency,
            'address'             => $supplier->address,
            'city'                => $supplier->city,
            'country'             => $supplier->country,
            'category'            => $supplier->category,
            'category_name'       => $supplier->category_name ?? null,
            'sector'              => $supplier->sector,
            'status'              => $supplier->status,
            'notes'               => $supplier->notes,
        ];
    }



/*
|==========================================================================
  PAYROLL PERIODS
|==========================================================================
*/

// ── Helper — summary counts returned with every response ──────────────────
private function payrollSummary(): array
{
    $rows = DB::connection('tenant')->table('payroll_periods')
        ->selectRaw("status, COUNT(*) as cnt")
        ->groupBy('status')
        ->pluck('cnt', 'status')
        ->toArray();

    return [
        'total'    => array_sum($rows),
        'paid'     => $rows['paid']       ?? 0,
        'approved' => $rows['approved']   ?? 0,
        'draft'    => ($rows['draft'] ?? 0) + ($rows['processing'] ?? 0),
    ];
}

// ── Helper — format a single period row for the frontend ──────────────────
private function formatPeriod($period): array
{
    return [
        'id'             => $period->id,
        'name'           => $period->name,
        'period_start'   => $period->period_start,
        'period_end'     => $period->period_end,
        'pay_date'       => $period->pay_date,
        'status'         => $period->status,
        'notes'          => $period->notes,
        'employee_count' => $period->employee_count ?? 0,
        'total_net_pay'  => $period->total_net_pay  ?? 0,
    ];
}

// ── VIEW ──────────────────────────────────────────────────────────────────
public function showPayrollPeriodsView()
{
    // Attach employee count + total net pay per period
    $periods = DB::connection('tenant')
        ->table('payroll_periods')
        ->leftJoinSub(
            DB::connection('tenant')
                ->table('payroll_entries')
                ->selectRaw('payroll_period_id, COUNT(*) as employee_count, SUM(net_pay) as total_net_pay')
                ->groupBy('payroll_period_id'),
            'sums',
            'sums.payroll_period_id',
            '=',
            'payroll_periods.id'
        )
        ->select('payroll_periods.*', 'sums.employee_count', 'sums.total_net_pay')
        ->orderBy('payroll_periods.period_start', 'desc')
        ->get();

    $summary = $this->payrollSummary();

    return view('tenants.admin.payroll-periods', [
        'periods'         => $periods,
        'totalPeriods'    => $summary['total'],
        'paidPeriods'     => $summary['paid'],
        'approvedPeriods' => $summary['approved'],
        'draftPeriods'    => $summary['draft'],
    ]);
}

// ── STORE (create new period) ─────────────────────────────────────────────
public function storePayrollPeriod(Request $request)
{
    $authUser = Auth::user();
    if (!$authUser) {
        return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
    }

    $request->validate([
        'name'         => 'required|string|max:100|unique:tenant.payroll_periods,name',
        'period_start' => 'required|date',
        'period_end'   => 'required|date|after_or_equal:period_start',
        'pay_date'     => 'required|date',
        'notes'        => 'nullable|string|max:2000',
    ]);

    $id = DB::connection('tenant')->table('payroll_periods')->insertGetId([
        'name'         => trim($request->name),
        'period_start' => $request->period_start,
        'period_end'   => $request->period_end,
        'pay_date'     => $request->pay_date,
        'status'       => 'draft',
        'created_by'   => $authUser->name,
        'notes'        => $request->notes,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    $period = DB::connection('tenant')->table('payroll_periods')->where('id', $id)->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Payroll period created successfully.',
        'period'  => $this->formatPeriod($period),
        'summary' => $this->payrollSummary(),
    ], 201);
}

// ── UPDATE ────────────────────────────────────────────────────────────────
public function updatePayrollPeriod(Request $request)
{
    $request->validate([
        'id'           => 'required|integer|exists:tenant.payroll_periods,id',
        'name'         => 'required|string|max:100|unique:tenant.payroll_periods,name,' . $request->id,
        'period_start' => 'required|date',
        'period_end'   => 'required|date|after_or_equal:period_start',
        'pay_date'     => 'required|date',
        'notes'        => 'nullable|string|max:2000',
    ]);

    // Only draft periods can be edited
    $period = DB::connection('tenant')->table('payroll_periods')->where('id', $request->id)->first();
    if (!$period || $period->status !== 'draft') {
        return response()->json(['error' => 'Only draft periods can be edited.', 'status' => 409], 409);
    }

    DB::connection('tenant')->table('payroll_periods')->where('id', $request->id)->update([
        'name'         => trim($request->name),
        'period_start' => $request->period_start,
        'period_end'   => $request->period_end,
        'pay_date'     => $request->pay_date,
        'notes'        => $request->notes,
        'updated_at'   => now(),
    ]);

    // Re-fetch with counts
    $updated = DB::connection('tenant')
        ->table('payroll_periods')
        ->leftJoinSub(
            DB::connection('tenant')
                ->table('payroll_entries')
                ->selectRaw('payroll_period_id, COUNT(*) as employee_count, SUM(net_pay) as total_net_pay')
                ->groupBy('payroll_period_id'),
            'sums', 'sums.payroll_period_id', '=', 'payroll_periods.id'
        )
        ->select('payroll_periods.*', 'sums.employee_count', 'sums.total_net_pay')
        ->where('payroll_periods.id', $request->id)
        ->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Payroll period updated successfully.',
        'period'  => $this->formatPeriod($updated),
        'summary' => $this->payrollSummary(),
    ], 201);
}


// ============================================================
//  FORMAT PAYROLL ENTRY HELPER
// ============================================================
private function formatPayrollEntry($entry): array
{
    return [
        'id'                              => $entry->id,
        'payroll_period_id'               => $entry->payroll_period_id,
        'employee_id'                     => $entry->employee_id,
        'employee_name'                   => $entry->employee_name      ?? '',
        'employee_number'                 => $entry->employee_number    ?? '',

        // Earnings
        'basic_salary'                    => $entry->basic_salary,
        'housing_allowance'               => $entry->housing_allowance,
        'transport_allowance'             => $entry->transport_allowance,
        'medical_allowance'               => $entry->medical_allowance,
        'meal_allowance'                  => $entry->meal_allowance,
        'other_recurring_allowance'       => $entry->other_recurring_allowance,
        'other_recurring_allowance_label' => $entry->other_recurring_allowance_label ?? 'Other Recurring',
        'acting_allowance'                => $entry->acting_allowance,
        'commissions'                     => $entry->commissions,
        'other_variable_allowance'        => $entry->other_variable_allowance,
        'other_variable_allowance_label'  => $entry->other_variable_allowance_label  ?? 'Other Variable',
        'overtime_amount'                 => $entry->overtime_amount,
        'gross_pay'                       => $entry->gross_pay,

        // Deductions
        'on_pension'                      => (bool) $entry->on_pension,
        'pension_employee'                => $entry->pension_employee,
        'pension_employer'                => $entry->pension_employer,
        'paye'                            => $entry->paye,
        'loan_deduction'                  => $entry->loan_deduction,
        'advance_deduction'               => $entry->advance_deduction,
        'other_deductions'                => $entry->other_deductions,
        'total_deductions'                => $entry->total_deductions,
        'net_pay'                         => $entry->net_pay,

        'notes'                           => $entry->notes,
        'status'                          => $entry->status,
    ];
}


// ============================================================
//  GENERATE PAYROLL ENTRIES
// ============================================================
public function generatePayrollEntries(Request $request)
{
    $request->validate([
        'id' => 'required|integer|exists:tenant.payroll_periods,id',
    ]);

    $period = DB::connection('tenant')->table('payroll_periods')->where('id', $request->id)->first();

    if (!$period || $period->status !== 'draft') {
        return response()->json(['error' => 'Only draft periods can be generated.', 'status' => 409], 409);
    }

    $employees = DB::connection('tenant')->table('users')
        ->where('active', 'Yes')
        ->get();

    $generated = 0;
    $skipped   = 0;

    foreach ($employees as $emp) {

        $exists = DB::connection('tenant')->table('payroll_entries')
            ->where('payroll_period_id', $period->id)
            ->where('employee_id', $emp->id)
            ->exists();

        if ($exists) { $skipped++; continue; }

        $basicSalary = (float) ($emp->gross_salary ?? 0);

        // ── Read allowance record ─────────────────────────────────────
        $ar = DB::connection('tenant')
            ->table('employee_allowances')
            ->where('employee_id', $emp->id)
            ->first();

        $housingAllowance    = (float) ($ar->housing_allowance             ?? 0);
        $transportAllowance  = (float) ($ar->transport_allowance           ?? 0);
        $medicalAllowance    = (float) ($ar->medical_allowance             ?? 0);
        $mealAllowance       = (float) ($ar->meal_allowance                ?? 0);
        $otherRecurring      = (float) ($ar->other_recurring_allowance     ?? 0);
        $otherRecurringLabel = $ar->other_recurring_allowance_label        ?? null;
        $actingAllowance     = (float) ($ar->acting_allowance              ?? 0);
        $commissions         = (float) ($ar->commissions                   ?? 0);
        $otherVariable       = (float) ($ar->other_variable_allowance      ?? 0);
        $otherVariableLabel  = $ar->other_variable_allowance_label         ?? null;
        $overtimeAmount      = 0; // filled by admin in wage bill edit if needed

        $grossPay = $basicSalary
                  + $housingAllowance + $transportAllowance
                  + $medicalAllowance + $mealAllowance + $otherRecurring
                  + $actingAllowance  + $commissions   + $otherVariable
                  + $overtimeAmount;

        // ── Pension ───────────────────────────────────────────────────
        $pension = DB::connection('tenant')->table('employee_pension')
            ->where('employee_id', $emp->id)
            ->where('status', 'active')
            ->first();

        $onPension       = (bool) $pension;
        $pensionEmployee = 0;
        $pensionEmployer = 0;

        if ($pension) {
            $pensionEmployee = round($basicSalary * ($pension->employee_rate / 100), 2);
            $pensionEmployer = round($basicSalary * ($pension->employer_rate / 100), 2);
        }

        $taxableIncome = $grossPay - $pensionEmployee;
        $paye = (bool) ($emp->on_paye ?? false)
            ? $this->calculatePAYE($taxableIncome)
            : 0.00;

        // ── Loan ──────────────────────────────────────────────────────
        $loan = DB::connection('tenant')->table('employee_loans')
            ->where('employee_id', $emp->id)
            ->where('status', 'active')
            ->orderBy('id', 'asc')
            ->first();

        $loanDeduction = $loan ? min($loan->monthly_deduction, $loan->balance_remaining) : 0;

        // ── Advance ───────────────────────────────────────────────────
        $advance = DB::connection('tenant')->table('employee_advances')
            ->where('employee_id', $emp->id)
            ->where('status', 'active')
            ->orderBy('id', 'asc')
            ->first();

        $advanceDeduction = $advance ? min($advance->monthly_deduction, $advance->balance_remaining) : 0;

        $totalDeductions = $pensionEmployee + $paye + $loanDeduction + $advanceDeduction;
        $netPay          = max(0, $grossPay - $totalDeductions);

        // ── Insert — frozen snapshot of every allowance column ────────
        DB::connection('tenant')->table('payroll_entries')->insert([
            'payroll_period_id'               => $period->id,
            'employee_id'                     => $emp->id,
            'basic_salary'                    => $basicSalary,
            'housing_allowance'               => $housingAllowance,
            'transport_allowance'             => $transportAllowance,
            'medical_allowance'               => $medicalAllowance,
            'meal_allowance'                  => $mealAllowance,
            'other_recurring_allowance'       => $otherRecurring,
            'other_recurring_allowance_label' => $otherRecurringLabel,
            'acting_allowance'                => $actingAllowance,
            'commissions'                     => $commissions,
            'other_variable_allowance'        => $otherVariable,          // correct column name
            'other_variable_allowance_label'  => $otherVariableLabel,
            'overtime_amount'                 => $overtimeAmount,
            'gross_pay'                       => $grossPay,
            'on_pension'                      => $onPension,
            'pension_employee'                => $pensionEmployee,
            'pension_employer'                => $pensionEmployer,
            'paye'                            => $paye,
            'loan_deduction'                  => $loanDeduction,
            'advance_deduction'                => $advanceDeduction,
            'other_deductions'                => 0,
            'total_deductions'                => $totalDeductions,
            'net_pay'                         => $netPay,
            'status'                          => 'draft',
            'created_at'                      => now(),
            'updated_at'                      => now(),
        ]);

        $generated++;

        // ── Reset variable tier if configured ─────────────────────────
        if ($ar && $ar->variable_reset_on_generate) {
            DB::connection('tenant')->table('employee_allowances')
                ->where('employee_id', $emp->id)
                ->update([
                    'acting_allowance'         => 0,
                    'commissions'              => 0,
                    'other_variable_allowance' => 0,
                    'updated_at'               => now(),
                ]);
        }
    }

    DB::connection('tenant')->table('payroll_periods')
        ->where('id', $period->id)
        ->update(['status' => 'processing', 'updated_at' => now()]);

    $updated = DB::connection('tenant')
        ->table('payroll_periods')
        ->leftJoinSub(
            DB::connection('tenant')
                ->table('payroll_entries')
                ->selectRaw('payroll_period_id, COUNT(*) as employee_count, SUM(net_pay) as total_net_pay')
                ->groupBy('payroll_period_id'),
            'sums', 'sums.payroll_period_id', '=', 'payroll_periods.id'
        )
        ->select('payroll_periods.*', 'sums.employee_count', 'sums.total_net_pay')
        ->where('payroll_periods.id', $period->id)
        ->first();

    return response()->json([
        'status'  => 201,
        'success' => "Wage bill generated for {$generated} employee(s). {$skipped} skipped (already existed).",
        'period'  => $this->formatPeriod($updated),
        'summary' => $this->payrollSummary(),
    ], 201);
}


// ============================================================
//  UPDATE PAYROLL ENTRY  (wage bill edit)
// ============================================================
public function updatePayrollEntry(Request $request)
{
    $request->validate([
        'id'                          => 'required|integer|exists:tenant.payroll_entries,id',
        'basic_salary'                => 'required|numeric|min:0',
        'housing_allowance'           => 'nullable|numeric|min:0',
        'transport_allowance'         => 'nullable|numeric|min:0',
        'medical_allowance'           => 'nullable|numeric|min:0',
        'meal_allowance'              => 'nullable|numeric|min:0',
        'other_recurring_allowance'   => 'nullable|numeric|min:0',
        'acting_allowance'            => 'nullable|numeric|min:0',
        'commissions'                 => 'nullable|numeric|min:0',
        'other_variable_allowance'    => 'nullable|numeric|min:0',
        'overtime_amount'             => 'nullable|numeric|min:0',
        'paye'                        => 'nullable|numeric|min:0',
        'pension_employee'            => 'nullable|numeric|min:0',
        'pension_employer'            => 'nullable|numeric|min:0',
        'loan_deduction'              => 'nullable|numeric|min:0',
        'advance_deduction'           => 'nullable|numeric|min:0',
        'other_deductions'            => 'nullable|numeric|min:0',
        'notes'                       => 'nullable|string|max:2000',
    ]);

    $entry = DB::connection('tenant')->table('payroll_entries')->where('id', $request->id)->first();

    if (!$entry) {
        return response()->json(['error' => 'Entry not found.', 'status' => 404], 404);
    }

    $period = DB::connection('tenant')->table('payroll_periods')
        ->where('id', $entry->payroll_period_id)
        ->first();

    if (!$period || !in_array($period->status, ['draft', 'processing'])) {
        return response()->json([
            'error'  => 'Entries can only be edited while the period is Draft or Processing.',
            'status' => 409,
        ], 409);
    }

    $employee = DB::connection('tenant')->table('users')->where('id', $entry->employee_id)->first();
    $onPaye   = (bool) ($employee->on_paye ?? false);

    $basic       = (float) ($request->basic_salary              ?? 0);
    $housing     = (float) ($request->housing_allowance         ?? 0);
    $transport   = (float) ($request->transport_allowance       ?? 0);
    $medical     = (float) ($request->medical_allowance         ?? 0);
    $meal        = (float) ($request->meal_allowance            ?? 0);
    $otherRecurr = (float) ($request->other_recurring_allowance ?? 0);
    $acting      = (float) ($request->acting_allowance          ?? 0);
    $commissions = (float) ($request->commissions               ?? 0);
    $otherVar    = (float) ($request->other_variable_allowance  ?? 0);
    $overtime    = (float) ($request->overtime_amount           ?? 0);

    $grossPay = $basic + $housing + $transport + $medical + $meal
              + $otherRecurr + $acting + $commissions + $otherVar + $overtime;

    $paye       = $onPaye ? (float) ($request->paye ?? 0) : 0.00;
    $pensionEe  = (float) ($request->pension_employee  ?? 0);
    $pensionEr  = (float) ($request->pension_employer  ?? 0);
    $loanDed    = (float) ($request->loan_deduction    ?? 0);
    $advanceDed = (float) ($request->advance_deduction ?? 0);
    $otherDed   = (float) ($request->other_deductions  ?? 0);

    $totalDeductions = $paye + $pensionEe + $loanDed + $advanceDed + $otherDed;
    $netPay          = max(0, $grossPay - $totalDeductions);

    DB::connection('tenant')->table('payroll_entries')->where('id', $request->id)->update([
        'basic_salary'                    => $basic,
        'housing_allowance'               => $housing,
        'transport_allowance'             => $transport,
        'medical_allowance'               => $medical,
        'meal_allowance'                  => $meal,
        'other_recurring_allowance'       => $otherRecurr,
        'acting_allowance'                => $acting,
        'commissions'                     => $commissions,
        'other_variable_allowance'        => $otherVar,
        'overtime_amount'                 => $overtime,
        'gross_pay'                       => $grossPay,
        'paye'                            => $paye,
        'pension_employee'                => $pensionEe,
        'pension_employer'                => $pensionEr,
        'loan_deduction'                  => $loanDed,
        'advance_deduction'               => $advanceDed,
        'other_deductions'                => $otherDed,
        'total_deductions'                => $totalDeductions,
        'net_pay'                         => $netPay,
        'notes'                           => $request->notes,
        'updated_at'                      => now(),
    ]);

    $updated = DB::connection('tenant')
        ->table('payroll_entries')
        ->join('users', 'users.id', '=', 'payroll_entries.employee_id')
        ->where('payroll_entries.id', $request->id)
        ->select(
            'payroll_entries.*',
            'users.name    as employee_name',
            'users.phone   as employee_number',
            'users.on_paye as on_paye'
        )
        ->first();

    $response = [
        'status'  => 201,
        'success' => 'Entry updated successfully.',
        'entry'   => $this->formatPayrollEntry($updated),
        'totals'  => $this->wageBillTotals($entry->payroll_period_id),
    ];

    if (!$onPaye && (float) ($request->paye ?? 0) > 0) {
        $response['warning'] = 'PAYE was set to 0 because this employee is not marked as On PAYE.';
    }

    return response()->json($response, 201);
}






















// ============================================================
//  CALCULATE PAYE  (reads live from paye_brackets table)
// ============================================================
 
private function calculatePAYE(float $taxableIncome): float
{
    if ($taxableIncome <= 0) return 0.00;
 
    $brackets = DB::connection('tenant')
        ->table('paye_brackets')
        ->whereNull('effective_to')       // active brackets only
        ->orderBy('income_from', 'asc')
        ->get();
 
    if ($brackets->isEmpty()) return 0.00;
 
    $paye      = 0.0;
    $remaining = $taxableIncome;
 
    foreach ($brackets as $bracket) {
        if ($remaining <= 0) break;
 
        // Tax-free band — consume the width, add nothing
        if ($bracket->rate == 0) {
            $bandWidth  = $bracket->income_to !== null
                            ? ($bracket->income_to - $bracket->income_from)
                            : $remaining;
            $remaining -= min($remaining, $bandWidth);
            continue;
        }
 
        // Top band has no ceiling — tax everything left
        $bandWidth = $bracket->income_to !== null
                        ? ($bracket->income_to - $bracket->income_from)
                        : $remaining;
 
        $taxable    = min($remaining, $bandWidth);
        $paye      += $taxable * ($bracket->rate / 100);
        $remaining -= $taxable;
    }
 
    return round($paye, 2);
}
 

// ── APPROVE ───────────────────────────────────────────────────────────────
public function approvePayrollPeriod(Request $request)
{
    $authUser = Auth::user();
    if (!$authUser) {
        return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
    }

    $request->validate([
        'id' => 'required|integer|exists:tenant.payroll_periods,id',
    ]);

    $period = DB::connection('tenant')->table('payroll_periods')->where('id', $request->id)->first();

    if (!$period || $period->status !== 'processing') {
        return response()->json(['error' => 'Only processing periods can be approved.', 'status' => 409], 409);
    }

    DB::connection('tenant')->table('payroll_periods')->where('id', $request->id)->update([
        'status'      => 'approved',
        'approved_by' => $authUser->name,
        'approved_at' => now(),
        'updated_at'  => now(),
    ]);

    // Also mark all entries as approved
    DB::connection('tenant')->table('payroll_entries')
        ->where('payroll_period_id', $request->id)
        ->update(['status' => 'approved', 'updated_at' => now()]);

    $updated = DB::connection('tenant')
        ->table('payroll_periods')
        ->leftJoinSub(
            DB::connection('tenant')
                ->table('payroll_entries')
                ->selectRaw('payroll_period_id, COUNT(*) as employee_count, SUM(net_pay) as total_net_pay')
                ->groupBy('payroll_period_id'),
            'sums', 'sums.payroll_period_id', '=', 'payroll_periods.id'
        )
        ->select('payroll_periods.*', 'sums.employee_count', 'sums.total_net_pay')
        ->where('payroll_periods.id', $request->id)
        ->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Payroll period approved successfully.',
        'period'  => $this->formatPeriod($updated),
        'summary' => $this->payrollSummary(),
    ], 201);
}

// ── MARK PAID ─────────────────────────────────────────────────────────────
public function markPayrollPeriodPaid(Request $request)
{
    $request->validate([
        'id' => 'required|integer|exists:tenant.payroll_periods,id',
    ]);

    $period = DB::connection('tenant')->table('payroll_periods')->where('id', $request->id)->first();

    if (!$period || $period->status !== 'approved') {
        return response()->json(['error' => 'Only approved periods can be marked as paid.', 'status' => 409], 409);
    }

    DB::connection('tenant')->table('payroll_periods')->where('id', $request->id)->update([
        'status'     => 'paid',
        'updated_at' => now(),
    ]);

    // Mark all entries as paid
    DB::connection('tenant')->table('payroll_entries')
        ->where('payroll_period_id', $request->id)
        ->update(['status' => 'paid', 'updated_at' => now()]);

    // ── Reduce loan balances ───────────────────────────────────────────────
    $entries = DB::connection('tenant')->table('payroll_entries')
        ->where('payroll_period_id', $request->id)
        ->where('loan_deduction', '>', 0)
        ->get();

    foreach ($entries as $entry) {
        $loan = DB::connection('tenant')->table('employee_loans')
            ->where('employee_id', $entry->employee_id)
            ->where('status', 'active')
            ->first();

        if ($loan) {
            $newBalance = max(0, $loan->balance_remaining - $entry->loan_deduction);
            $newStatus  = $newBalance <= 0 ? 'completed' : 'active';
            DB::connection('tenant')->table('employee_loans')->where('id', $loan->id)->update([
                'balance_remaining' => $newBalance,
                'status'            => $newStatus,
                'updated_at'        => now(),
            ]);
        }
    }

    // ── Reduce advance balances ────────────────────────────────────────────
    $advEntries = DB::connection('tenant')->table('payroll_entries')
        ->where('payroll_period_id', $request->id)
        ->where('advance_deduction', '>', 0)
        ->get();

    foreach ($advEntries as $entry) {
        $advance = DB::connection('tenant')->table('employee_advances')
            ->where('employee_id', $entry->employee_id)
            ->where('status', 'active')
            ->first();

        if ($advance) {
            $newBalance = max(0, $advance->balance_remaining - $entry->advance_deduction);
            $newStatus  = $newBalance <= 0 ? 'recovered' : 'active';
            DB::connection('tenant')->table('employee_advances')->where('id', $advance->id)->update([
                'balance_remaining' => $newBalance,
                'status'            => $newStatus,
                'updated_at'        => now(),
            ]);
        }
    }

    $updated = DB::connection('tenant')
        ->table('payroll_periods')
        ->leftJoinSub(
            DB::connection('tenant')
                ->table('payroll_entries')
                ->selectRaw('payroll_period_id, COUNT(*) as employee_count, SUM(net_pay) as total_net_pay')
                ->groupBy('payroll_period_id'),
            'sums', 'sums.payroll_period_id', '=', 'payroll_periods.id'
        )
        ->select('payroll_periods.*', 'sums.employee_count', 'sums.total_net_pay')
        ->where('payroll_periods.id', $request->id)
        ->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Period marked as paid. Loan and advance balances updated.',
        'period'  => $this->formatPeriod($updated),
        'summary' => $this->payrollSummary(),
    ], 201);
}

// ── DELETE ────────────────────────────────────────────────────────────────
public function deletePayrollPeriod(Request $request)
{
    $request->validate([
        'id' => 'required|integer|exists:tenant.payroll_periods,id',
    ]);

    $period = DB::connection('tenant')->table('payroll_periods')->where('id', $request->id)->first();

    if (!$period || $period->status !== 'draft') {
        return response()->json(['error' => 'Only draft periods can be deleted.', 'status' => 409], 409);
    }

    // Delete entries first
    DB::connection('tenant')->table('payroll_entries')
        ->where('payroll_period_id', $request->id)
        ->delete();

    DB::connection('tenant')->table('payroll_periods')->where('id', $request->id)->delete();

    return response()->json([
        'status'  => 201,
        'success' => 'Payroll period deleted successfully.',
        'summary' => $this->payrollSummary(),
    ], 201);
}















/*
    |==========================================================================
      WAGE BILL
    |==========================================================================
    */

    // ── Helper — wageBillTotals returned after every entry update ─────────────
    private function wageBillTotals(int $periodId): array
    {
        $sums = DB::connection('tenant')->table('payroll_entries')
            ->where('payroll_period_id', $periodId)
            ->selectRaw('
                SUM(gross_pay)          as gross_pay,
                SUM(paye)               as paye,
                SUM(pension_employee)   as pension_employee,
                SUM(pension_employer)   as pension_employer,
                SUM(loan_deduction)     as loan_deduction,
                SUM(advance_deduction)  as advance_deduction,
                SUM(other_deductions)   as other_deductions,
                SUM(total_deductions)   as total_deductions,
                SUM(net_pay)            as net_pay,
                COUNT(*)                as employee_count
            ')
            ->first();

        return [
            'gross_pay'        => $sums->gross_pay        ?? 0,
            'paye'             => $sums->paye             ?? 0,
            'pension_employee' => $sums->pension_employee ?? 0,
            'pension_employer' => $sums->pension_employer ?? 0,
            'loan_deduction'   => $sums->loan_deduction   ?? 0,
            'advance_deduction'=> $sums->advance_deduction?? 0,
            'other_deductions' => $sums->other_deductions ?? 0,
            'total_deductions' => $sums->total_deductions ?? 0,
            'net_pay'          => $sums->net_pay          ?? 0,
            'employee_count'   => $sums->employee_count   ?? 0,
        ];
    }


public function showWageBillView(Request $request)
{
  
    return view('tenants.admin.wagebill');
}
public function downloadPayslip(Request $request)
{
    $entryId = $request->query('entry_id');

    if (!$entryId) {
        abort(400, 'entry_id is required.');
    }

    $entry = DB::connection('tenant')
        ->table('payroll_entries')
        ->join('users', 'users.id', '=', 'payroll_entries.employee_id')
        ->where('payroll_entries.id', $entryId)
        ->select(
            'payroll_entries.*',          // all payroll columns incl. every allowance
            'users.name                   as employee_name',
            'users.phone                  as employee_number',
            'users.position               as position',
            'users.department             as department',
            'users.bank_name              as bank_name',
            'users.bank_account_number    as bank_account_number'
        )
        ->first();

    if (!$entry) {
        abort(404, 'Payroll entry not found.');
    }

    $period = DB::connection('tenant')
        ->table('payroll_periods')
        ->where('id', $entry->payroll_period_id)
        ->first();

    if (!$period) {
        abort(404, 'Payroll period not found.');
    }

    $company = DB::connection('tenant')
        ->table('company_info')
        ->where('id', 1)
        ->first();

    $pdf = Pdf::loadView('tenants.admin.payslip-pdf', compact('entry', 'period', 'company'))
              ->setPaper('a5', 'portrait')
              ->setOptions(['defaultFont' => 'DejaVu Sans']);

    $filename = 'payslip_'
        . str_replace(' ', '_', strtolower($entry->employee_name))
        . '_' . str_replace([' ', '/'], '_', $period->name)
        . '.pdf';

    return $pdf->download($filename);
}
// ============================================================
//  PAYSLIPS — Main view
//  GET /admin/hr/payroll/payslips
// ============================================================
public function showPayslipsView(Request $request)
{
    return view('tenants.admin.payslip-view');
}


// ============================================================
//  PAYSLIPS — Statistics (AJAX)
//  GET /admin/hr/payroll/payslips/stats
//  Returns summary cards + per-period breakdown.
// ============================================================
public function getPayslipStats(Request $request)
{
    try {
        $tenantDb = DB::connection('tenant');

        // ── Overall totals ────────────────────────────────────────────
        $totals = $tenantDb
            ->table('payroll_entries')
            ->join('payroll_periods', 'payroll_periods.id', '=', 'payroll_entries.payroll_period_id')
            ->whereIn('payroll_periods.status', ['approved', 'paid'])
            ->selectRaw('
                COUNT(*)                        as total_payslips,
                COUNT(DISTINCT employee_id)     as total_employees,
                SUM(gross_pay)                  as total_gross,
                SUM(total_deductions)           as total_deductions,
                SUM(net_pay)                    as total_net,
                SUM(paye)                       as total_paye,
                SUM(pension_employee)           as total_pension
            ')
            ->first();

        $stats = [
            ['label' => 'Total Payslips',    'value' => number_format($totals->total_payslips),                                          'css' => 'bg-sc1'],
            ['label' => 'Employees',         'value' => number_format($totals->total_employees),                                         'css' => 'bg-sc2'],
            ['label' => 'Total Gross Pay',   'value' => number_format($totals->total_gross, 2),                                          'css' => 'bg-sc3'],
            ['label' => 'Total Net Pay',     'value' => number_format($totals->total_net, 2),                                            'css' => 'bg-sc4'],
        ];

        // ── Per-period breakdown ──────────────────────────────────────
        $periodBreakdown = $tenantDb
            ->table('payroll_entries')
            ->join('payroll_periods', 'payroll_periods.id', '=', 'payroll_entries.payroll_period_id')
            ->whereIn('payroll_periods.status', ['approved', 'paid'])
            ->selectRaw('
                payroll_periods.id,
                payroll_periods.name,
                payroll_periods.status,
                payroll_periods.pay_date,
                COUNT(payroll_entries.id) as count,
                SUM(gross_pay)            as gross_pay,
                SUM(net_pay)              as net_pay
            ')
            ->groupBy(
                'payroll_periods.id',
                'payroll_periods.name',
                'payroll_periods.status',
                'payroll_periods.pay_date'
            )
            ->orderBy('payroll_periods.period_start', 'desc')
            ->get();

        return response()->json([
            'status'           => 200,
            'stats'            => $stats,
            'period_breakdown' => $periodBreakdown,
        ]);

    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'error' => $e->getMessage()]);
    }
}


// ============================================================
//  PAYSLIPS — Email single payslip
//  POST /admin/hr/payroll/payslips/email
//  Body: entry_id, note (optional)
// ============================================================
public function emailPayslip(Request $request)
{
    $authUser = Auth::user();
    if (!$authUser) {
        return response()->json(['status' => 401, 'error' => 'Your session has expired. Please log in again.']);
    }

    $request->validate([
        'entry_id' => 'required|integer|exists:tenant.payroll_entries,id',
    ]);

    $tenantDb = DB::connection('tenant');

    // ── Fetch entry with all joins the PDF + email view need ──────────
    $entry = $tenantDb
        ->table('payroll_entries')
        ->join('payroll_periods', 'payroll_periods.id', '=', 'payroll_entries.payroll_period_id')
        ->join('users',           'users.id',           '=', 'payroll_entries.employee_id')
        ->leftJoin('branches',    'branches.id',        '=', 'users.branch')
        ->where('payroll_entries.id', $request->entry_id)
        ->select(
            'payroll_entries.*',
            'payroll_periods.name          as period_name',
            'payroll_periods.period_start',
            'payroll_periods.period_end',
            'payroll_periods.pay_date',
            'payroll_periods.status        as period_status',
            'users.name                    as employee_name',
            'users.email                   as employee_email',
            'users.phone                   as employee_number',
            'users.position                as position',
            'users.department              as department',
            'users.bank_name               as bank_name',
            'users.bank_account_number     as bank_account_number',
            'branches.name                 as branch_name'
        )
        ->first();

    if (!$entry) {
        return response()->json(['status' => 404, 'error' => 'Payroll entry not found.']);
    }

    if (empty($entry->employee_email)) {
        return response()->json([
            'status' => 422,
            'error'  => 'Employee has no email address on file. Please update their profile first.',
        ]);
    }

    // ── Company info ──────────────────────────────────────────────────
    $company = $tenantDb->table('company_info')->where('id', 1)->first();

    // ── Plain period object for the view ──────────────────────────────
    $period = (object) [
        'name'         => $entry->period_name,
        'period_start' => $entry->period_start,
        'period_end'   => $entry->period_end,
        'pay_date'     => $entry->pay_date,
        'status'       => $entry->period_status,
    ];

    // ── Render PDF ────────────────────────────────────────────────────
    $pdf = Pdf::loadView('tenants.admin.payslip-pdf', [
                'entry'   => $entry,
                'period'  => $period,
                'company' => $company,
            ])
            ->setPaper('a5', 'portrait')
            ->setOptions(['defaultFont' => 'DejaVu Sans']);

    $filename = 'Payslip_'
              . str_replace(' ', '_', $entry->employee_name)
              . '_' . str_replace([' ', '/'], '_', $entry->period_name)
              . '.pdf';

    // ── Data array passed into the blade email view ───────────────────
    $emailData = [
        'entry'       => $entry,
        'period'      => $period,
        'company'     => $company,
        'note'        => $request->note ?? null,
        'sender_name' => $authUser->name ?? 'HR',
    ];

    // ── Send mail — inner try/catch mirrors masterSendInvoiceFromTenantDetails
    try {
        Mail::send(
            'tenants.admin.payslip-email',
            $emailData,
            function ($message) use ($entry, $pdf, $filename, $period) {
                $message->to($entry->employee_email, $entry->employee_name)
                        ->subject('Your Payslip — ' . $period->name)
                        ->attachData($pdf->output(), $filename, [
                            'mime' => 'application/pdf',
                        ]);
            }
        );
    } catch (\Exception $mailException) {
        \Log::error('Payslip email failed: ' . $mailException->getMessage());

        $tenantDb->table('payslip_email_logs')->insert([
            'payroll_entry_id'  => $entry->id,
            'employee_id'       => $entry->employee_id,
            'payroll_period_id' => $entry->payroll_period_id,
            'recipient_email'   => $entry->employee_email,
            'send_type'         => 'single',
            'status'            => 'failed',
            'note'              => $request->note ?? null,
            'sent_by'           => $authUser->name ?? null,
            'error_message'     => $mailException->getMessage(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return response()->json([
            'status' => 500,
            'error'  => 'Payslip record saved but email failed to send.',
        ]);
    }

    // ── Log success ───────────────────────────────────────────────────
    $tenantDb->table('payslip_email_logs')->insert([
        'payroll_entry_id'  => $entry->id,
        'employee_id'       => $entry->employee_id,
        'payroll_period_id' => $entry->payroll_period_id,
        'recipient_email'   => $entry->employee_email,
        'send_type'         => 'single',
        'status'            => 'sent',
        'note'              => $request->note ?? null,
        'sent_by'           => $authUser->name ?? null,
        'error_message'     => null,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    return response()->json([
        'status'  => 200,
        'success' => 'Payslip sent to ' . $entry->employee_email,
    ]);
}


// ============================================================
//  PAYSLIPS — Bulk email payslips
//  POST /admin/hr/payroll/payslips/bulkemail
//  Body: entry_ids[] (array of integers), note (optional)
// ============================================================
public function bulkEmailPayslips(Request $request)
{
    $authUser = Auth::user();
    if (!$authUser) {
        return response()->json(['status' => 401, 'error' => 'Your session has expired. Please log in again.']);
    }

    $request->validate([
        'entry_ids'   => 'required|array|min:1',
        'entry_ids.*' => 'integer|exists:tenant.payroll_entries,id',
    ]);

    $tenantDb   = DB::connection('tenant');
    $company    = $tenantDb->table('company_info')->where('id', 1)->first();
    $note       = $request->note ?? null;
    $senderName = $authUser->name ?? 'HR';
    $sent       = 0;
    $skipped    = 0;
    $failed     = 0;

    try {
        // ── Fetch all requested entries in one query ──────────────────
        $entries = $tenantDb
            ->table('payroll_entries')
            ->join('payroll_periods', 'payroll_periods.id', '=', 'payroll_entries.payroll_period_id')
            ->join('users',           'users.id',           '=', 'payroll_entries.employee_id')
            ->leftJoin('branches',    'branches.id',        '=', 'users.branch')
            ->whereIn('payroll_entries.id', $request->entry_ids)
            ->select(
                'payroll_entries.*',
                'payroll_periods.name          as period_name',
                'payroll_periods.period_start',
                'payroll_periods.period_end',
                'payroll_periods.pay_date',
                'payroll_periods.status        as period_status',
                'users.name                    as employee_name',
                'users.email                   as employee_email',
                'users.phone                   as employee_number',
                'users.position                as position',
                'users.department              as department',
                'users.bank_name               as bank_name',
                'users.bank_account_number     as bank_account_number',
                'branches.name                 as branch_name'
            )
            ->get();

        foreach ($entries as $entry) {

            // ── Skip: no email address ────────────────────────────────
            if (empty($entry->employee_email)) {
                $tenantDb->table('payslip_email_logs')->insert([
                    'payroll_entry_id'  => $entry->id,
                    'employee_id'       => $entry->employee_id,
                    'payroll_period_id' => $entry->payroll_period_id,
                    'recipient_email'   => 'none',
                    'send_type'         => 'bulk',
                    'status'            => 'skipped',
                    'note'              => $note,
                    'sent_by'           => $senderName,
                    'error_message'     => 'No email address on file.',
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
                $skipped++;
                continue;
            }

            $period = (object) [
                'name'         => $entry->period_name,
                'period_start' => $entry->period_start,
                'period_end'   => $entry->period_end,
                'pay_date'     => $entry->pay_date,
                'status'       => $entry->period_status,
            ];

            $emailData = [
                'entry'       => $entry,
                'period'      => $period,
                'company'     => $company,
                'note'        => $note,
                'sender_name' => $senderName,
            ];

            try {
                $pdf = Pdf::loadView('tenants.admin.payslip-pdf', [
                            'entry'   => $entry,
                            'period'  => $period,
                            'company' => $company,
                        ])
                        ->setPaper('a5', 'portrait')
                        ->setOptions(['defaultFont' => 'DejaVu Sans']);

                $filename = 'Payslip_'
                          . str_replace(' ', '_', $entry->employee_name)
                          . '_' . str_replace([' ', '/'], '_', $entry->period_name)
                          . '.pdf';

                Mail::send(
                    'tenants.admin.payslip-email',
                    $emailData,
                    function ($message) use ($entry, $pdf, $filename, $period) {
                        $message->to($entry->employee_email, $entry->employee_name)
                                ->subject('Your Payslip — ' . $period->name)
                                ->attachData($pdf->output(), $filename, [
                                    'mime' => 'application/pdf',
                                ]);
                    }
                );

                $tenantDb->table('payslip_email_logs')->insert([
                    'payroll_entry_id'  => $entry->id,
                    'employee_id'       => $entry->employee_id,
                    'payroll_period_id' => $entry->payroll_period_id,
                    'recipient_email'   => $entry->employee_email,
                    'send_type'         => 'bulk',
                    'status'            => 'sent',
                    'note'              => $note,
                    'sent_by'           => $senderName,
                    'error_message'     => null,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                $sent++;

            } catch (\Exception $e) {
                \Log::error('Payslip bulk email failed for entry ' . $entry->id . ': ' . $e->getMessage());

                $tenantDb->table('payslip_email_logs')->insert([
                    'payroll_entry_id'  => $entry->id,
                    'employee_id'       => $entry->employee_id,
                    'payroll_period_id' => $entry->payroll_period_id,
                    'recipient_email'   => $entry->employee_email,
                    'send_type'         => 'bulk',
                    'status'            => 'failed',
                    'note'              => $note,
                    'sent_by'           => $senderName,
                    'error_message'     => $e->getMessage(),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                $failed++;
            }
        }

        $summary = "Sent: {$sent}";
        if ($skipped) $summary .= ", Skipped (no email): {$skipped}";
        if ($failed)  $summary .= ", Failed: {$failed}";

        return response()->json([
            'status'  => 200,
            'success' => $summary,
            'sent'    => $sent,
            'skipped' => $skipped,
            'failed'  => $failed,
        ]);

    } catch (\Exception $e) {
        \Log::error('Payslip bulk email failed: ' . $e->getMessage());
        return response()->json(['status' => 500, 'error' => 'Failed to send payslips. Please try again.'], 500);
    }
}



// ── Helper — format a pension record for the frontend ─────────────────────
private function formatPension($p): array
{
    $employee = DB::connection('tenant')->table('users')->where('id', $p->employee_id)->first();

    return [
        'id'                    => $p->id,
        'employee_id'           => $p->employee_id,
        'employee_name'         => $employee->name       ?? '',
        'position'              => $employee->position   ?? '',
        'department'            => $employee->department ?? '',
        'pension_fund_name'     => $p->pension_fund_name,
        'pension_member_number' => $p->pension_member_number,
        'employee_rate'         => $p->employee_rate,
        'employer_rate'         => $p->employer_rate,
        'enrolled_on'           => $p->enrolled_on,
        'enrolled_on_fmt'       => \Carbon\Carbon::parse($p->enrolled_on)->format('d M Y'),
        'status'                => $p->status,
        'notes'                 => $p->notes,
    ];
}

// ── VIEW ──────────────────────────────────────────────────────────────────
public function showPensionView()
{
    return view('tenants.admin.pension');
}

// ── STORE ─────────────────────────────────────────────────────────────────
public function storePension(Request $request)
{
    $request->validate([
        'employee_id'           => 'required|integer|exists:tenant.users,id',
        'pension_fund_name'     => 'nullable|string|max:255',
        'pension_member_number' => 'nullable|string|max:100',
        'employee_rate'         => 'required|numeric|min:0|max:100',
        'employer_rate'         => 'required|numeric|min:0|max:100',
        'enrolled_on'           => 'required|date',
        'status'                => 'nullable|in:active,suspended,exited',
        'notes'                 => 'nullable|string|max:2000',
    ]);

    // One pension record per employee
    $exists = DB::connection('tenant')
        ->table('employee_pension')
        ->where('employee_id', $request->employee_id)
        ->exists();

    if ($exists) {
        return response()->json([
            'status' => 422,
            'errors' => ['This employee is already enrolled in pension. Edit the existing record instead.'],
        ], 422);
    }

    $id = DB::connection('tenant')->table('employee_pension')->insertGetId([
        'employee_id'           => $request->employee_id,
        'pension_fund_name'     => trim($request->pension_fund_name),
        'pension_member_number' => trim($request->pension_member_number),
        'employee_rate'         => $request->employee_rate,
        'employer_rate'         => $request->employer_rate,
        'enrolled_on'           => $request->enrolled_on,
        'status'                => $request->status ?? 'active',
        'notes'                 => $request->notes,
        'created_at'            => now(),
        'updated_at'            => now(),
    ]);

    $pension = DB::connection('tenant')->table('employee_pension')->where('id', $id)->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Employee enrolled in pension successfully.',
        'pension' => $this->formatPension($pension),
    ], 201);
}

// ── UPDATE ────────────────────────────────────────────────────────────────
public function updatePension(Request $request)
{
    $request->validate([
        'id'                    => 'required|integer|exists:tenant.employee_pension,id',
        'pension_fund_name'     => 'nullable|string|max:255',
        'pension_member_number' => 'nullable|string|max:100',
        'employee_rate'         => 'required|numeric|min:0|max:100',
        'employer_rate'         => 'required|numeric|min:0|max:100',
        'enrolled_on'           => 'required|date',
        'status'                => 'nullable|in:active,suspended,exited',
        'notes'                 => 'nullable|string|max:2000',
    ]);

    DB::connection('tenant')->table('employee_pension')->where('id', $request->id)->update([
        'pension_fund_name'     => trim($request->pension_fund_name),
        'pension_member_number' => trim($request->pension_member_number),
        'employee_rate'         => $request->employee_rate,
        'employer_rate'         => $request->employer_rate,
        'enrolled_on'           => $request->enrolled_on,
        'status'                => $request->status ?? 'active',
        'notes'                 => $request->notes,
        'updated_at'            => now(),
    ]);

    $pension = DB::connection('tenant')->table('employee_pension')->where('id', $request->id)->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Pension record updated successfully.',
        'pension' => $this->formatPension($pension),
    ], 201);
}

// ── DELETE ────────────────────────────────────────────────────────────────
public function deletePension(Request $request)
{
    $request->validate([
        'id' => 'required|integer|exists:tenant.employee_pension,id',
    ]);

    DB::connection('tenant')->table('employee_pension')->where('id', $request->id)->delete();

    return response()->json([
        'status'  => 201,
        'success' => 'Pension record deleted successfully.',
    ], 201);
}




/*
|==========================================================================
  LOANS
|==========================================================================
*/

private function formatLoan($l): array
{
    $employee = DB::connection('tenant')->table('users')->where('id', $l->employee_id)->first();

    return [
        'id'                    => $l->id,
        'employee_id'           => $l->employee_id,
        'employee_name'         => $employee->name       ?? '',
        'position'              => $employee->position   ?? '',
        'department'            => $employee->department ?? '',
        'loan_amount'           => $l->loan_amount,
        'balance_remaining'     => $l->balance_remaining,
        'monthly_deduction'     => $l->monthly_deduction,
        'start_date'            => $l->start_date,
        'start_date_fmt'        => \Carbon\Carbon::parse($l->start_date)->format('d M Y'),
        'expected_end_date'     => $l->expected_end_date,
        'expected_end_date_fmt' => $l->expected_end_date
                                    ? \Carbon\Carbon::parse($l->expected_end_date)->format('d M Y')
                                    : null,
        'purpose'               => $l->purpose,
        'approved_by'           => $l->approved_by,
        'status'                => $l->status,
        'notes'                 => $l->notes,
    ];
}

public function showLoansView()
{
    return view('tenants.admin.loans');
}

public function storeLoan(Request $request)
{
    $request->validate([
        'employee_id'       => 'required|integer|exists:tenant.users,id',
        'loan_amount'       => 'required|numeric|min:0',
        'monthly_deduction' => 'required|numeric|min:0',
        'start_date'        => 'required|date',
        'expected_end_date' => 'nullable|date|after_or_equal:start_date',
        'purpose'           => 'nullable|string|max:255',
        'approved_by'       => 'nullable|string|max:255',
        'status'            => 'required|in:active,completed,cancelled',
        'notes'             => 'nullable|string|max:2000',
    ]);

    $id = DB::connection('tenant')->table('employee_loans')->insertGetId([
        'employee_id'       => $request->employee_id,
        'loan_amount'       => $request->loan_amount,
        'balance_remaining' => $request->loan_amount, // starts equal to loan amount
        'monthly_deduction' => $request->monthly_deduction,
        'start_date'        => $request->start_date,
        'expected_end_date' => $request->expected_end_date,
        'purpose'           => trim($request->purpose),
        'approved_by'       => trim($request->approved_by),
        'status'            => $request->status,
        'notes'             => $request->notes,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $loan = DB::connection('tenant')->table('employee_loans')->where('id', $id)->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Loan added successfully.',
        'loan'    => $this->formatLoan($loan),
    ], 201);
}

public function updateLoan(Request $request)
{
    $request->validate([
        'id'                => 'required|integer|exists:tenant.employee_loans,id',
        'loan_amount'       => 'required|numeric|min:0',
        'balance_remaining' => 'required|numeric|min:0',
        'monthly_deduction' => 'required|numeric|min:0',
        'start_date'        => 'required|date',
        'expected_end_date' => 'nullable|date|after_or_equal:start_date',
        'purpose'           => 'nullable|string|max:255',
        'approved_by'       => 'nullable|string|max:255',
        'status'            => 'required|in:active,completed,cancelled',
        'notes'             => 'nullable|string|max:2000',
    ]);

    DB::connection('tenant')->table('employee_loans')->where('id', $request->id)->update([
        'loan_amount'       => $request->loan_amount,
        'balance_remaining' => $request->balance_remaining,
        'monthly_deduction' => $request->monthly_deduction,
        'start_date'        => $request->start_date,
        'expected_end_date' => $request->expected_end_date,
        'purpose'           => trim($request->purpose),
        'approved_by'       => trim($request->approved_by),
        'status'            => $request->status,
        'notes'             => $request->notes,
        'updated_at'        => now(),
    ]);

    $loan = DB::connection('tenant')->table('employee_loans')->where('id', $request->id)->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Loan updated successfully.',
        'loan'    => $this->formatLoan($loan),
    ], 201);
}

public function deleteLoan(Request $request)
{
    $request->validate([
        'id' => 'required|integer|exists:tenant.employee_loans,id',
    ]);

    DB::connection('tenant')->table('employee_loans')->where('id', $request->id)->delete();

    return response()->json([
        'status'  => 201,
        'success' => 'Loan deleted successfully.',
    ], 201);
}


/*
|==========================================================================
  ADVANCES
|==========================================================================
*/

private function formatAdvance($a): array
{
    $employee = DB::connection('tenant')->table('users')->where('id', $a->employee_id)->first();

    return [
        'id'                => $a->id,
        'employee_id'       => $a->employee_id,
        'employee_name'     => $employee->name       ?? '',
        'position'          => $employee->position   ?? '',
        'department'        => $employee->department ?? '',
        'advance_amount'    => $a->advance_amount,
        'balance_remaining' => $a->balance_remaining,
        'monthly_deduction' => $a->monthly_deduction,
        'advance_date'      => $a->advance_date,
        'advance_date_fmt'  => \Carbon\Carbon::parse($a->advance_date)->format('d M Y'),
        'approved_by'       => $a->approved_by,
        'status'            => $a->status,
        'notes'             => $a->notes,
    ];
}

public function showAdvancesView()
{
    return view('tenants.admin.advances');
}

public function storeAdvance(Request $request)
{
    $request->validate([
        'employee_id'       => 'required|integer|exists:tenant.users,id',
        'advance_amount'    => 'required|numeric|min:0',
        'monthly_deduction' => 'required|numeric|min:0',
        'advance_date'      => 'required|date',
        'approved_by'       => 'nullable|string|max:255',
        'status'            => 'required|in:active,recovered,cancelled',
        'notes'             => 'nullable|string|max:2000',
    ]);

    $id = DB::connection('tenant')->table('employee_advances')->insertGetId([
        'employee_id'       => $request->employee_id,
        'advance_amount'    => $request->advance_amount,
        'balance_remaining' => $request->advance_amount, // starts equal to advance amount
        'monthly_deduction' => $request->monthly_deduction,
        'advance_date'      => $request->advance_date,
        'approved_by'       => trim($request->approved_by),
        'status'            => $request->status,
        'notes'             => $request->notes,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $advance = DB::connection('tenant')->table('employee_advances')->where('id', $id)->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Advance added successfully.',
        'advance' => $this->formatAdvance($advance),
    ], 201);
}

public function updateAdvance(Request $request)
{
    $request->validate([
        'id'                => 'required|integer|exists:tenant.employee_advances,id',
        'advance_amount'    => 'required|numeric|min:0',
        'balance_remaining' => 'required|numeric|min:0',
        'monthly_deduction' => 'required|numeric|min:0',
        'advance_date'      => 'required|date',
        'approved_by'       => 'nullable|string|max:255',
        'status'            => 'required|in:active,recovered,cancelled',
        'notes'             => 'nullable|string|max:2000',
    ]);

    DB::connection('tenant')->table('employee_advances')->where('id', $request->id)->update([
        'advance_amount'    => $request->advance_amount,
        'balance_remaining' => $request->balance_remaining,
        'monthly_deduction' => $request->monthly_deduction,
        'advance_date'      => $request->advance_date,
        'approved_by'       => trim($request->approved_by),
        'status'            => $request->status,
        'notes'             => $request->notes,
        'updated_at'        => now(),
    ]);

    $advance = DB::connection('tenant')->table('employee_advances')->where('id', $request->id)->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Advance updated successfully.',
        'advance' => $this->formatAdvance($advance),
    ], 201);
}

public function deleteAdvance(Request $request)
{
    $request->validate([
        'id' => 'required|integer|exists:tenant.employee_advances,id',
    ]);

    DB::connection('tenant')->table('employee_advances')->where('id', $request->id)->delete();

    return response()->json([
        'status'  => 201,
        'success' => 'Advance deleted successfully.',
    ], 201);
}


/*
|==========================================================================
  OFFER LETTERS 
|==========================================================================
*/

// ── Helper — format a single offer letter row for the frontend ────────────
private function formatOfferLetter($l): array
{
    // Pull fresh employee data for display fields that are not on the letter
    $employee = DB::connection('tenant')->table('users')->where('id', $l->employee_id)->first();

    return [
        'id'                 => $l->id,
        'employee_id'        => $l->employee_id,
        'employee_name'      => $employee->name         ?? '',
        'current_position'   => $employee->position     ?? '',
        'department'         => $employee->department   ?? '',
        // current_salary is shown in the view as a reference hint only —
        // it is NOT used on the PDF; the letter's own offered_salary is used.
        'current_salary'     => $employee->gross_salary ?? 0,
        'letter_type'        => $l->letter_type,
        'issue_date'         => $l->issue_date,
        'issue_date_fmt'     => \Carbon\Carbon::parse($l->issue_date)->format('d M Y'),
        'start_date'         => $l->start_date,
        'start_date_fmt'     => $l->start_date
                                    ? \Carbon\Carbon::parse($l->start_date)->format('d M Y')
                                    : null,
        'offered_position'   => $l->offered_position,
        'offered_department' => $l->offered_department,
        'offered_salary'     => $l->offered_salary,     // historical snapshot — never null
        'custom_message'     => $l->custom_message,
        'file_path'          => $l->file_path,
        'generated_by'       => $l->generated_by,
        'notes'              => $l->notes,
    ];
}

// ── VIEW ──────────────────────────────────────────────────────────────────
public function showOfferLettersView()
{
    return view('tenants.admin.offer-letters');
}

// ── STORE ─────────────────────────────────────────────────────────────────
public function storeOfferLetter(Request $request)
{
    $authUser = Auth::user();
    if (!$authUser) {
        return response()->json(['status' => 401, 'error' => 'Your session has expired. Please log in again.']);
    }

    $request->validate([
        'employee_id'        => 'required|integer|exists:tenant.users,id',
        'letter_type'        => 'required|in:Offer,Confirmation,Promotion,Termination',
        'issue_date'         => 'required|date',
        'start_date'         => 'nullable|date',
        'offered_position'   => 'nullable|string|max:255',
        'offered_department' => 'nullable|string|max:255',
        // Always required — frontend shows current salary as a reference hint
        'offered_salary'     => 'required|numeric|min:0',
        'custom_message'     => 'nullable|string|max:2000',
        'notes'              => 'nullable|string|max:2000',
    ]);

    $id = DB::connection('tenant')->table('offer_letters')->insertGetId([
        'employee_id'        => $request->employee_id,
        'letter_type'        => $request->letter_type,
        'issue_date'         => $request->issue_date,
        'start_date'         => $request->start_date,
        'offered_position'   => trim($request->offered_position),
        'offered_department' => trim($request->offered_department),
        'offered_salary'     => $request->offered_salary,
        'custom_message'     => trim($request->custom_message),
        'generated_by'       => $authUser->name ?? 'System',
        'notes'              => $request->notes,
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);

    // Sync the employee's active salary for Offer and Promotion letters only.
    // This makes users.gross_salary always reflect the latest formal letter
    // while every old letter retains its own offered_salary snapshot.
    if (in_array($request->letter_type, ['Offer', 'Promotion'])) {
        DB::connection('tenant')
            ->table('users')
            ->where('id', $request->employee_id)
            ->update([
                'gross_salary' => $request->offered_salary,
            ]);
    }

    $letter = DB::connection('tenant')->table('offer_letters')->where('id', $id)->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Letter generated successfully.',
        'letter'  => $this->formatOfferLetter($letter),
    ], 201);
}

// ── UPDATE ────────────────────────────────────────────────────────────────
public function updateOfferLetter(Request $request)
{
    $request->validate([
        'id'                 => 'required|integer|exists:tenant.offer_letters,id',
        'letter_type'        => 'required|in:Offer,Confirmation,Promotion,Termination',
        'issue_date'         => 'required|date',
        'start_date'         => 'nullable|date',
        'offered_position'   => 'nullable|string|max:255',
        'offered_department' => 'nullable|string|max:255',
        'offered_salary'     => 'required|numeric|min:0',
        'custom_message'     => 'nullable|string|max:2000',
        'notes'              => 'nullable|string|max:2000',
    ]);

    DB::connection('tenant')->table('offer_letters')->where('id', $request->id)->update([
        'letter_type'        => $request->letter_type,
        'issue_date'         => $request->issue_date,
        'start_date'         => $request->start_date,
        'offered_position'   => trim($request->offered_position),
        'offered_department' => trim($request->offered_department),
        'offered_salary'     => $request->offered_salary,
        'custom_message'     => trim($request->custom_message),
        'notes'              => $request->notes,
        'updated_at'         => now(),
    ]);

    // Re-fetch the letter to get employee_id for the salary sync
    $letter = DB::connection('tenant')->table('offer_letters')->where('id', $request->id)->first();

    // Sync active salary for Offer and Promotion only
    if (in_array($request->letter_type, ['Offer', 'Promotion'])) {
        DB::connection('tenant')
            ->table('users')
            ->where('id', $letter->employee_id)
            ->update([
                'gross_salary' => $request->offered_salary,
            ]);
    }

    return response()->json([
        'status'  => 201,
        'success' => 'Letter updated successfully.',
        'letter'  => $this->formatOfferLetter($letter),
    ], 201);
}

// ── DELETE ────────────────────────────────────────────────────────────────
public function deleteOfferLetter(Request $request)
{
    $request->validate([
        'id' => 'required|integer|exists:tenant.offer_letters,id',
    ]);

    $letter = DB::connection('tenant')->table('offer_letters')->where('id', $request->id)->first();

    DB::connection('tenant')->table('offer_letters')->where('id', $request->id)->delete();

    return response()->json([
        'status'  => 201,
        'success' => 'Letter deleted successfully.',
    ], 201);
}

// ── DOWNLOAD PDF ──────────────────────────────────────────────────────────
public function downloadOfferLetter(Request $request)
{
    $request->validate([
        'id' => 'required|integer|exists:tenant.offer_letters,id',
    ]);

    $letter = DB::connection('tenant')
        ->table('offer_letters')
        ->join('users', 'users.id', '=', 'offer_letters.employee_id')
        ->select(
            'offer_letters.*',
            'users.name         as employee_name',
            'users.position     as current_position',
            'users.department   as department',
            'users.phone        as employee_phone',
            'users.email        as employee_email'
        )
        ->where('offer_letters.id', $request->id)
        ->first();

    if (!$letter) {
        abort(404, 'Letter not found.');
    }

    $company = DB::connection('tenant')->table('company_info')->where('id', 1)->first();

    $pdf = Pdf::loadView('tenants.admin.offer-letter-pdf', compact('letter', 'company'))
              ->setPaper('a4', 'portrait')
              ->setOptions(['defaultFont' => 'DejaVu Sans']);

    $filename = $letter->letter_type . '_'
              . str_replace(' ', '_', strtolower($letter->employee_name)) . '_'
              . $letter->issue_date . '.pdf';

    return $pdf->download($filename);
}

/*
|--------------------------------------------------------------------------
| VIEW
|--------------------------------------------------------------------------
*/
public function showPayeBracketsView()
{
    return view('tenants.admin.paye-brackets');
}


/*
|--------------------------------------------------------------------------
| FORMAT HELPER
|--------------------------------------------------------------------------
*/
private function formatBracket($b): array
{
    return [
        'id'             => $b->id,
        'income_from'    => $b->income_from,
        'income_to'      => $b->income_to,
        'rate'           => $b->rate,
        'effective_from' => $b->effective_from,
        'effective_to'   => $b->effective_to,
        'description'    => $b->description,
        'is_active'      => is_null($b->effective_to),
        'income_from_fmt'=> number_format($b->income_from, 2),
        'income_to_fmt'  => $b->income_to !== null ? number_format($b->income_to, 2) : 'No ceiling',
        'rate_fmt'       => $b->rate . '%',
    ];
}


/*
|--------------------------------------------------------------------------
| STORE
|--------------------------------------------------------------------------
*/
public function storePayeBracket(Request $request)
{
    $request->validate([
        'income_from'    => 'required|numeric|min:0',
        'income_to'      => 'nullable|numeric|gt:income_from',
        'rate'           => 'required|numeric|min:0|max:100',
        'effective_from' => 'required|date',
        'effective_to'   => 'nullable|date|after:effective_from',
        'description'    => 'nullable|string|max:255',
    ]);

    // Only one bracket can be the open-ended top band (income_to = null) per active set
    if (is_null($request->income_to)) {
        $topExists = DB::connection('tenant')
            ->table('paye_brackets')
            ->whereNull('income_to')
            ->whereNull('effective_to')
            ->exists();

        if ($topExists) {
            return response()->json([
                'status' => 422,
                'errors' => ['An active top band (no ceiling) already exists. Retire it first before adding a new one.'],
            ], 422);
        }
    }

    $id = DB::connection('tenant')->table('paye_brackets')->insertGetId([
        'income_from'    => $request->income_from,
        'income_to'      => $request->income_to,
        'rate'           => $request->rate,
        'effective_from' => $request->effective_from,
        'effective_to'   => $request->effective_to,
        'description'    => trim($request->description),
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    $bracket = DB::connection('tenant')->table('paye_brackets')->where('id', $id)->first();

    return response()->json([
        'status'  => 201,
        'success' => 'PAYE bracket added successfully.',
        'bracket' => $this->formatBracket($bracket),
    ], 201);
}


/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/
public function updatePayeBracket(Request $request)
{
    $request->validate([
        'id'             => 'required|integer|exists:tenant.paye_brackets,id',
        'income_from'    => 'required|numeric|min:0',
        'income_to'      => 'nullable|numeric|gt:income_from',
        'rate'           => 'required|numeric|min:0|max:100',
        'effective_from' => 'required|date',
        'effective_to'   => 'nullable|date|after:effective_from',
        'description'    => 'nullable|string|max:255',
    ]);

    // Top-band uniqueness check (exclude self)
    if (is_null($request->income_to)) {
        $topExists = DB::connection('tenant')
            ->table('paye_brackets')
            ->whereNull('income_to')
            ->whereNull('effective_to')
            ->where('id', '!=', $request->id)
            ->exists();

        if ($topExists) {
            return response()->json([
                'status' => 422,
                'errors' => ['An active top band (no ceiling) already exists.'],
            ], 422);
        }
    }

    DB::connection('tenant')->table('paye_brackets')->where('id', $request->id)->update([
        'income_from'    => $request->income_from,
        'income_to'      => $request->income_to,
        'rate'           => $request->rate,
        'effective_from' => $request->effective_from,
        'effective_to'   => $request->effective_to,
        'description'    => trim($request->description),
        'updated_at'     => now(),
    ]);

    $bracket = DB::connection('tenant')->table('paye_brackets')->where('id', $request->id)->first();

    return response()->json([
        'status'  => 201,
        'success' => 'PAYE bracket updated successfully.',
        'bracket' => $this->formatBracket($bracket),
    ], 201);
}


/*
|--------------------------------------------------------------------------
| RETIRE  (set effective_to without deleting — preserves audit trail)
| Called when MRA issues new rates; you retire old brackets and add new ones.
|--------------------------------------------------------------------------
*/
public function retirePayeBracket(Request $request)
{
    $request->validate([
        'id'           => 'required|integer|exists:tenant.paye_brackets,id',
        'effective_to' => 'required|date',
    ]);

    DB::connection('tenant')->table('paye_brackets')->where('id', $request->id)->update([
        'effective_to' => $request->effective_to,
        'updated_at'   => now(),
    ]);

    $bracket = DB::connection('tenant')->table('paye_brackets')->where('id', $request->id)->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Bracket retired successfully.',
        'bracket' => $this->formatBracket($bracket),
    ], 201);
}


/*
|--------------------------------------------------------------------------
| DELETE  (only safe to delete brackets never used in any payroll run)
|--------------------------------------------------------------------------
*/
public function deletePayeBracket(Request $request)
{
    $request->validate([
        'id' => 'required|integer|exists:tenant.paye_brackets,id',
    ]);

    DB::connection('tenant')->table('paye_brackets')->where('id', $request->id)->delete();

    return response()->json([
        'status'  => 201,
        'success' => 'PAYE bracket deleted successfully.',
    ], 201);
}





/*
|==========================================================================
  ALLOWANCES
|==========================================================================
*/

// ── Helper — format a single allowance row for the frontend ───────────────
private function formatAllowance($a): array
{
    $employee = DB::connection('tenant')->table('users')->where('id', $a->employee_id)->first();

    return [
        'id'                              => $a->id,
        'employee_id'                     => $a->employee_id,
        'employee_name'                   => $employee->name       ?? '',
        'position'                        => $employee->position   ?? '',
        'department'                      => $employee->department ?? '',

        // Recurring tier
        'housing_allowance'               => $a->housing_allowance,
        'transport_allowance'             => $a->transport_allowance,
        'medical_allowance'               => $a->medical_allowance,
        'meal_allowance'                  => $a->meal_allowance,
        'other_recurring_allowance'       => $a->other_recurring_allowance,
        'other_recurring_allowance_label' => $a->other_recurring_allowance_label,

        // Variable tier
        'acting_allowance'                => $a->acting_allowance,
        'commissions'                     => $a->commissions,
        'other_variable_allowance'        => $a->other_variable_allowance,
        'other_variable_allowance_label'  => $a->other_variable_allowance_label,

        // Control
        'variable_reset_on_generate'      => (bool) $a->variable_reset_on_generate,

        // Dates
        'effective_from'                  => $a->effective_from,
        'effective_from_fmt'              => \Carbon\Carbon::parse($a->effective_from)->format('d M Y'),

        'notes' => $a->notes,
    ];
}

// ── VIEW ──────────────────────────────────────────────────────────────────
public function showAllowancesView()
{
    return view('tenants.admin.allowances');
}

// ── STORE ─────────────────────────────────────────────────────────────────
public function storeAllowance(Request $request)
{
    $authUser = Auth::user();
    if (!$authUser) {
        return response()->json(['status' => 401, 'error' => 'Your session has expired. Please log in again.']);
    }

    $request->validate([
        'employee_id'                     => 'required|integer|exists:tenant.users,id',
        'housing_allowance'               => 'nullable|numeric|min:0',
        'transport_allowance'             => 'nullable|numeric|min:0',
        'medical_allowance'               => 'nullable|numeric|min:0',
        'meal_allowance'                  => 'nullable|numeric|min:0',
        'other_recurring_allowance'       => 'nullable|numeric|min:0',
        'other_recurring_allowance_label' => 'nullable|string|max:100',
        'acting_allowance'                => 'nullable|numeric|min:0',
        'commissions'                     => 'nullable|numeric|min:0',
        'other_variable_allowance'        => 'nullable|numeric|min:0',
        'other_variable_allowance_label'  => 'nullable|string|max:100',
        'effective_from'                  => 'required|date',
        'notes'                           => 'nullable|string|max:2000',
    ]);

    // One allowance record per employee
    $exists = DB::connection('tenant')
        ->table('employee_allowances')
        ->where('employee_id', $request->employee_id)
        ->exists();

    if ($exists) {
        return response()->json([
            'status' => 422,
            'errors' => ['This employee already has an allowance package. Edit the existing record instead.'],
        ], 422);
    }

    $id = DB::connection('tenant')->table('employee_allowances')->insertGetId([
        'employee_id'                     => $request->employee_id,
        'housing_allowance'               => $request->housing_allowance               ?? 0,
        'transport_allowance'             => $request->transport_allowance             ?? 0,
        'medical_allowance'               => $request->medical_allowance               ?? 0,
        'meal_allowance'                  => $request->meal_allowance                  ?? 0,
        'other_recurring_allowance'       => $request->other_recurring_allowance       ?? 0,
        'other_recurring_allowance_label' => trim($request->other_recurring_allowance_label ?? ''),
        'acting_allowance'                => $request->acting_allowance                ?? 0,
        'commissions'                     => $request->commissions                     ?? 0,
        'other_variable_allowance'        => $request->other_variable_allowance        ?? 0,
        'other_variable_allowance_label'  => trim($request->other_variable_allowance_label ?? ''),
        'variable_reset_on_generate'      => $request->has('variable_reset_on_generate') ? 1 : 0,
        'effective_from'                  => $request->effective_from,
        'notes'                           => $request->notes,
        'created_at'                      => now(),
        'updated_at'                      => now(),
    ]);

    // ── Write initial history snapshot ────────────────────────────────────
    $this->writeAllowanceHistory($id, 'Initial setup', $authUser->name ?? 'System');

    $allowance = DB::connection('tenant')->table('employee_allowances')->where('id', $id)->first();

    return response()->json([
        'status'    => 201,
        'success'   => 'Allowance package created successfully.',
        'allowance' => $this->formatAllowance($allowance),
    ], 201);
}

// ── UPDATE ────────────────────────────────────────────────────────────────
public function updateAllowance(Request $request)
{
    $authUser = Auth::user();
    if (!$authUser) {
        return response()->json(['status' => 401, 'error' => 'Your session has expired. Please log in again.']);
    }

    $request->validate([
        'id'                              => 'required|integer|exists:tenant.employee_allowances,id',
        'housing_allowance'               => 'nullable|numeric|min:0',
        'transport_allowance'             => 'nullable|numeric|min:0',
        'medical_allowance'               => 'nullable|numeric|min:0',
        'meal_allowance'                  => 'nullable|numeric|min:0',
        'other_recurring_allowance'       => 'nullable|numeric|min:0',
        'other_recurring_allowance_label' => 'nullable|string|max:100',
        'acting_allowance'                => 'nullable|numeric|min:0',
        'commissions'                     => 'nullable|numeric|min:0',
        'other_variable_allowance'        => 'nullable|numeric|min:0',
        'other_variable_allowance_label'  => 'nullable|string|max:100',
        'effective_from'                  => 'required|date',
        'notes'                           => 'nullable|string|max:2000',
        'change_reason'                   => 'nullable|string|max:255',
    ]);

    DB::connection('tenant')->table('employee_allowances')->where('id', $request->id)->update([
        'housing_allowance'               => $request->housing_allowance               ?? 0,
        'transport_allowance'             => $request->transport_allowance             ?? 0,
        'medical_allowance'               => $request->medical_allowance               ?? 0,
        'meal_allowance'                  => $request->meal_allowance                  ?? 0,
        'other_recurring_allowance'       => $request->other_recurring_allowance       ?? 0,
        'other_recurring_allowance_label' => trim($request->other_recurring_allowance_label ?? ''),
        'acting_allowance'                => $request->acting_allowance                ?? 0,
        'commissions'                     => $request->commissions                     ?? 0,
        'other_variable_allowance'        => $request->other_variable_allowance        ?? 0,
        'other_variable_allowance_label'  => trim($request->other_variable_allowance_label ?? ''),
        'variable_reset_on_generate'      => $request->has('variable_reset_on_generate') ? 1 : 0,
        'effective_from'                  => $request->effective_from,
        'notes'                           => $request->notes,
        'updated_at'                      => now(),
    ]);

    // ── Write history snapshot after every update ─────────────────────────
    $this->writeAllowanceHistory(
        $request->id,
        $request->change_reason ?? 'Manual update',
        $authUser->name ?? 'System'
    );

    $allowance = DB::connection('tenant')->table('employee_allowances')->where('id', $request->id)->first();

    return response()->json([
        'status'    => 201,
        'success'   => 'Allowance package updated successfully.',
        'allowance' => $this->formatAllowance($allowance),
    ], 201);
}

// ── DELETE ────────────────────────────────────────────────────────────────
public function deleteAllowance(Request $request)
{
    $request->validate([
        'id' => 'required|integer|exists:tenant.employee_allowances,id',
    ]);

    // History rows cascade via FK; but if you prefer explicit control:
    // DB::connection('tenant')->table('employee_allowance_history')->where('employee_id', ...)->delete();
    DB::connection('tenant')->table('employee_allowances')->where('id', $request->id)->delete();

    return response()->json([
        'status'  => 201,
        'success' => 'Allowance package deleted successfully.',
    ], 201);
}

// ── PRIVATE — write a history snapshot ───────────────────────────────────
private function writeAllowanceHistory(int $allowanceId, ?string $reason, ?string $changedBy): void
{
    $a = DB::connection('tenant')->table('employee_allowances')->where('id', $allowanceId)->first();

    if (!$a) return;

    DB::connection('tenant')->table('employee_allowance_history')->insert([
        'employee_id'                     => $a->employee_id,
        'housing_allowance'               => $a->housing_allowance,
        'transport_allowance'             => $a->transport_allowance,
        'medical_allowance'               => $a->medical_allowance,
        'meal_allowance'                  => $a->meal_allowance,
        'other_recurring_allowance'       => $a->other_recurring_allowance,
        'other_recurring_allowance_label' => $a->other_recurring_allowance_label,
        'acting_allowance'                => $a->acting_allowance,
        'commissions'                     => $a->commissions,
        'other_variable_allowance'        => $a->other_variable_allowance,
        'other_variable_allowance_label'  => $a->other_variable_allowance_label,
        'change_reason'                   => $reason,
        'changed_by'                      => $changedBy,
        'effective_from'                  => $a->effective_from,
        'created_at'                      => now(),
    ]);
}
public function showAllowanceHistoryView()
{
    return view('tenants.admin.allowance-history');
}

}