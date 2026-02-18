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

class TenantSuperAdminController extends Controller
{
    public function showTenantAdminDashboard()
    {
        return view('tenants.super-admin.dashboard');
    }

    public function showProfileView()
    {
        return view('tenants.super-admin.profile');
    }

    public function showEmployeesView()
    {
        return view('tenants.super-admin.employees');
    }

    public function showEmployeeDetailsView()
    {
        return view('tenants.super-admin.employee-details');
    }

    public function showCompanyInfoView()
    {
        return view('tenants.super-admin.company-info');
    }

    public function showSubscriptionPlansView()
    {
        return view('tenants.super-admin.subscription-plans');
    }

    public function showPaymentMethodsView()
    {
        return view('tenants.super-admin.payment-methods');
    }

    public function showRolesView()
    {
        return view('tenants.super-admin.roles');
    }

    public function showSupportCenter()
    {
        return view('tenants.super-admin.support-center');
    }

    public function showEventsView()
    {
        return view('tenants.super-admin.events');
    }

    public function showEventsTable(Request $request)
    {
        return view('tenants.super-admin.events-table');
    }

    public function showInvoiceTemplatesView()
    {
        return view('tenants.super-admin.invoice-templates');
    }

    public function showPointOfSalesView()
    {
        return view('tenants.super-admin.point-of-sales');
    }

    public function showCurrencyView()
    {
        return view('tenants.super-admin.currency');
    }

    
    public function showBranchesView()
    {
        return view('tenants.super-admin.branches');
    }


     
    public function  showBranchDetailsView()
    {
        return view('tenants.super-admin.branch-details');
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

    public function insertSubscriptionPlan(Request $request)
    {
        $data = [
            'plan_name'           => trim($request->plan_name),
            'plan_period'         => trim($request->plan_period),
            'plan_period_name'    => trim($request->plan_period_name ?? ''),
            'plan_days'           => $request->plan_days,
            'plan_amount'         => $request->plan_amount,
            'plan_currency'       => strtoupper(trim($request->plan_currency ?? 'USD')),
            'plan_currency_name'  => trim($request->plan_currency_name ?? ''),
            'plan_description'    => $request->plan_description,
        ];

        $validator = $request->validate([
            'plan_name'     => 'required|string|max:255|unique:subscription_plans,plan_name',
            'plan_period'   => 'required|string|max:255|unique:subscription_plans,plan_period',
            'plan_days'     => 'required|integer|min:1',
            'plan_amount'   => 'required|numeric|min:0',
            'plan_currency' => 'required|string|size:3',
        ]);

        if ($validator) {
            $insertId = DB::connection('tenant')->table('subscription_plans')->insertGetId($data);
            if ($insertId) {
                $plan = DB::connection('tenant')->table('subscription_plans')->where('id', $insertId)->first();

                return response()->json([
                    'success' => 'Plan created successfully.',
                    'status'  => 201,
                    'plan'    => [
                        'id'                  => $plan->id,
                        'plan_name'           => $plan->plan_name,
                        'plan_period'         => $plan->plan_period,
                        'plan_period_name'    => $plan->plan_period_name,
                        'plan_days'           => $plan->plan_days,
                        'plan_amount'         => $plan->plan_amount,
                        'plan_currency'       => $plan->plan_currency,
                        'plan_currency_name'  => $plan->plan_currency_name,
                        'plan_description'    => $plan->plan_description,
                    ],
                ]);
            } else {
                return response()->json(['error' => 'Failed to create plan.', 'status' => 500]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function updateSubscriptionPlan(Request $request)
    {
        $data = [
            'plan_name'           => trim($request->plan_name),
            'plan_period'         => trim($request->plan_period),
            'plan_period_name'    => trim($request->plan_period_name ?? ''),
            'plan_days'           => $request->plan_days,
            'plan_amount'         => $request->plan_amount,
            'plan_currency'       => strtoupper(trim($request->plan_currency ?? 'USD')),
            'plan_currency_name'  => trim($request->plan_currency_name ?? ''),
            'plan_description'    => $request->plan_description,
        ];

        $validator = $request->validate([
            'id'            => 'required|integer|exists:subscription_plans,id',
            'plan_name'     => 'required|string|max:255|unique:subscription_plans,plan_name,' . $request->id,
            'plan_period'   => 'required|string|max:255|unique:subscription_plans,plan_period,' . $request->id,
            'plan_days'     => 'required|integer|min:1',
            'plan_amount'   => 'required|numeric|min:0',
            'plan_currency' => 'required|string|size:3',
        ]);

        if ($validator) {
            $updated = DB::connection('tenant')->table('subscription_plans')->where('id', $request->id)->update($data);
            if ($updated) {
                $plan = DB::connection('tenant')->table('subscription_plans')->where('id', $request->id)->first();

                return response()->json([
                    'success' => 'Plan updated successfully.',
                    'status'  => 201,
                    'plan'    => [
                        'id'                  => $plan->id,
                        'plan_name'           => $plan->plan_name,
                        'plan_period'         => $plan->plan_period,
                        'plan_period_name'    => $plan->plan_period_name,
                        'plan_days'           => $plan->plan_days,
                        'plan_amount'         => $plan->plan_amount,
                        'plan_currency'       => $plan->plan_currency,
                        'plan_currency_name'  => $plan->plan_currency_name,
                        'plan_description'    => $plan->plan_description,
                    ],
                ]);
            } else {
                return response()->json(['error' => 'Plan not found or no changes made.', 'status' => 409]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function deleteSubscriptionPlan(Request $request)
    {
        $deleted = DB::connection('tenant')->table('subscription_plans')->where('id', $request->id)->delete();

        if ($deleted) {
            return response()->json(['success' => 'Plan deleted successfully.', 'status' => 201]);
        } else {
            return response()->json(['error' => 'Plan not found.', 'status' => 404]);
        }
    }

    public function fetchEvents(Request $request)
    {
        $query = DB::connection('tenant')->table('events');

        if ($request->has('user_id')) {
            $query->where('user_id', Auth::user()->id);
        }

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
            'user_id' => Auth::user()->id,
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
    }

    public function updateEvent(Request $request, $id)
    {
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
            $updated = DB::connection('tenant')->table('events')->where('id', $id)->where('user_id', Auth::user()->id)->update($data);
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

    public function deleteEvent($id)
    {
        $deleted = DB::connection('tenant')->table('events')->where('id', $id)->where('user_id', Auth::user()->id)->delete();
        if ($deleted) {
            return response()->json(['success' => 'Event deleted successfully.', 'status' => 201]);
        } else {
            return response()->json(['error' => 'Event not found or permission denied.', 'status' => 404]);
        }
    }

    public function bulkDeleteEvents(Request $request)
    {
        $validator = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:events,id',
        ]);

        if ($validator) {
            $deleted = DB::connection('tenant')->table('events')
                ->whereIn('id', $request->ids)
                ->where('user_id', Auth::id())
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
            'user_id' => Auth::user()->id,
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

    public function uploadDocument(Request $request)
    {
        $validator = $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $file = $request->file('file');
        $fileSize = $file->getSize(); 
        $extension = $file->getClientOriginalExtension();
        $firstFive = substr($request->name, 0, 5);
        $filename = $firstFive . '_' . Str::random(6) . '.' . $extension;
        $path = public_path('files/master/company');

        $data = [
            'name' => $request->name,
            'filename' => $filename,
            'path' => 'files/master/company/' . $filename,
            'size' => $fileSize,
            'created_at' => now(),
            'updated_at' => now()
        ];

        if ($validator) {
            $file->move($path, $filename);
            $inserted = DB::connection('tenant')->table('company_files')->insert($data);
            if ($inserted) {
                return response()->json(['success' => 'Document uploaded successfully', 'status' => 201]);
            } else {
                return response()->json(['error' => 'Failed to upload document', 'status' => 500]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }
    
    public function uploadImage(Request $request)
    {
        $blob = $request->file('file');
        $firstFive = substr($request->name, 0, 5);
        $filename =   $firstFive . '_' . Str::random(8) . '.jpg';
        $fullPath = public_path('files/master/company/' . $filename);
        file_put_contents($fullPath, file_get_contents($blob->getRealPath()));
        $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;

        $data = [
            'name' => $request->name,
            'filename' => $filename,
            'path' => 'files/master/company/' . $filename,
            'size' => $fileSize,
            'created_at' => now(),
            'updated_at' => now()
        ];

        $validator = $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|image|max:5120',
        ]);

        if ($validator) {
            $inserted = DB::connection('tenant')->table('company_files')->insert($data);
            if ($inserted) {
                return response()->json(['success' => 'Image uploaded successfully', 'status' => 201]);
            } else {
                return response()->json(['error' => 'Failed to upload image', 'status' => 500]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function updateName(Request $request, $id)
    {
        $data = ['name' => $request->name];

        $validator = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        if ($validator) {
            $updated = DB::connection('tenant')->table('company_files')->where('id', $id)->update($data);
            if ($updated) {
                return response()->json(['success' => 'File name updated successfully', 'status' => 201]);
            } else {
                return response()->json(['error' => 'No changes made or file not found', 'status' => 409]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function deleteFile($id)
    {
        $file = DB::connection('tenant')->table('company_files')->find($id);
        if (!$file) {
            return response()->json(['error' => 'File not found', 'status' => 404]);
        }

        File::delete(public_path('files/master/company/' . $file->filename));
        $deleted = DB::connection('tenant')->table('company_files')->where('id', $id)->delete();

        return $deleted
            ? response()->json(['success' => 'File deleted successfully', 'status' => 201])
            : response()->json(['error' => 'Failed to delete file', 'status' => 500]);
    }

    public function downloadFile($id)
    {
        $file = DB::connection('tenant')->table('company_files')->find($id);
        if (!$file) {
            return response()->json(['error' => 'File record not found', 'status' => 404]);
        }
        $path = public_path('files/master/company/' . $file->filename);
        if (!File::exists($path)) {
            return response()->json(['error' => 'File not found on server', 'status' => 404]);
        }
        $ext = strtolower(pathinfo($file->filename, PATHINFO_EXTENSION));
        $downloadName = $file->name . '.' . $ext;
        return response()->download($path, $downloadName);
    }

    public function bulkDeleteFiles(Request $request)
    {
        $validator = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:company_files,id',
        ]);

        if ($validator) {
            $files = DB::connection('tenant')->table('company_files')->whereIn('id', $request->ids)->get();
            foreach ($files as $file) {
                File::delete(public_path('files/master/company/' . $file->filename));
            }
            $deleted = DB::connection('tenant')->table('company_files')->whereIn('id', $request->ids)->delete();
            if ($deleted > 0) {
                return response()->json(['success' => 'Selected files deleted', 'status' => 201]);
            } else {
                return response()->json(['error' => 'No files found or permission denied.', 'status' => 404]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function insertEmployee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'required|email|unique:users,email',
            'role' => 'nullable|string',
            'branch' => 'nullable|string',
            'department' => 'nullable|string',
            'position' => 'nullable|string',
            'gross_salary' => 'nullable|integer',
            'dob' => 'nullable|date',
            'started_on' => 'nullable|date',
            'idtype' => 'nullable|string',
            'idnumber' => 'nullable|string',
            'home_address' => 'nullable|string',
            'current_residence' => 'nullable|string',
            'nextofkin_name' => 'nullable|string',
            'nextofkin_relationship' => 'nullable|string',
            'nextofkin_physical_address' => 'nullable|string',
            'nextofkin_contact' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        $id = DB::connection('tenant')->table('users')->insertGetId([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'role' => $request->role,
            'branch' => $request->branch,
            'department' => $request->department,
            'position' => $request->position,
            'gross_salary' => $request->gross_salary,
            'dob' => $request->dob,
            'started_on' => $request->started_on,
            'idtype' => $request->idtype,
            'idnumber' => $request->idnumber,
            'home_address' => $request->home_address,
            'current_residence' => $request->current_residence,
            'nextofkin_name' => $request->nextofkin_name,
            'nextofkin_relationship' => $request->nextofkin_relationship,
            'nextofkin_physical_address' => $request->nextofkin_physical_address,
            'nextofkin_contact' => $request->nextofkin_contact,
            'password' => Hash::make('default123'),
            'active' => 'Yes',
            'entered_on' => now(),
        ]);

        $employee = DB::connection('tenant')->table('users')->where('id', $id)->first();

        return response()->json([
            'status' => 201,
            'success' => 'Employee added successfully!',
            'employee' => $employee
        ], 201);
    }

    public function updateEmployee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone,' . $request->id,
            'email' => 'required|email|unique:users,email,' . $request->id,
            'role' => 'nullable|string',
            'branch' => 'nullable|string',
            'department' => 'nullable|string',
            'position' => 'nullable|string',
            'gross_salary' => 'nullable|integer',
            'dob' => 'nullable|date',
            'started_on' => 'nullable|date',
            'idtype' => 'nullable|string',
            'idnumber' => 'nullable|string',
            'home_address' => 'nullable|string',
            'current_residence' => 'nullable|string',
            'nextofkin_name' => 'nullable|string',
            'nextofkin_relationship' => 'nullable|string',
            'nextofkin_physical_address' => 'nullable|string',
            'nextofkin_contact' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::connection('tenant')->table('users')->where('id', $request->id)->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'role' => $request->role,
            'branch' => $request->branch,
            'department' => $request->department,
            'position' => $request->position,
            'gross_salary' => $request->gross_salary,
            'dob' => $request->dob,
            'started_on' => $request->started_on,
            'idtype' => $request->idtype,
            'idnumber' => $request->idnumber,
            'home_address' => $request->home_address,
            'current_residence' => $request->current_residence,
            'nextofkin_name' => $request->nextofkin_name,
            'nextofkin_relationship' => $request->nextofkin_relationship,
            'nextofkin_physical_address' => $request->nextofkin_physical_address,
            'nextofkin_contact' => $request->nextofkin_contact,
        ]);

        $employee = DB::connection('tenant')->table('users')->where('id', $request->id)->first();

        return response()->json([
            'status' => 201,
            'success' => 'Employee updated successfully!',
            'employee' => $employee
        ], 201);
    }

    public function deleteEmployee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::connection('tenant')->table('users')->where('id', $request->id)->delete();

        return response()->json([
            'status' => 201,
            'success' => 'Employee deleted successfully!'
        ], 201);
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

    public function insertInvoiceTemplate(Request $request)
    {
        $data = [
            'name'       => $request->name,
            'is_default' => $request->has('is_default') ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now()
        ];

        $validator = $request->validate([
            'name'       => 'required|string|max:255|unique:invoice_templates,name',
            'is_default' => 'sometimes|in:1',
        ]);

        if ($validator) {
            $filename = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $request->name);
            $filename = trim($filename, '_') . '.blade.php';
            $path = base_path('resources/views/common/invoices');

            $content = "";
            $tempPath = tempnam(sys_get_temp_dir(), 'tmp');
            file_put_contents($tempPath, $content);
            $uploadedFile = new UploadedFile($tempPath, $filename, 'application/octet-stream', null, true);
            $uploadedFile->move($path, $filename);

            $insertId = DB::connection('tenant')->table('invoice_templates')->insertGetId($data);
            if ($insertId) {
                $template = DB::connection('tenant')->table('invoice_templates')->where('id', $insertId)->first();

                if ($data['is_default']) {
                    DB::connection('tenant')->table('invoice_templates')->where('id', '!=', $insertId)->update(['is_default' => 0]);
                }

                return response()->json([
                    'success'  => 'Invoice template created successfully.',
                    'status'   => 201,
                    'template' => [
                        'id'         => $template->id,
                        'name'       => $template->name,
                        'is_default' => $template->is_default,
                    ],
                ]);
            } else {
                return response()->json(['error' => 'Failed to create template.', 'status' => 500]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function updateInvoiceTemplate(Request $request)
    {
        $data = [
            'name'       => $request->name,
            'is_default' => $request->has('is_default') ? 1 : 0,
            'updated_at' => now()
        ];

        $validator = $request->validate([
            'id'         => 'required|integer|exists:invoice_templates,id',
            'name'       => 'required|string|max:255|unique:invoice_templates,name,' . $request->id,
            'is_default' => 'sometimes|in:1',
        ]);

        if ($validator) {
            $old = DB::connection('tenant')->table('invoice_templates')->where('id', $request->id)->first();
            $path = base_path('resources/views/common/invoices');

            $newFilename = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $request->name);
            $newFilename = trim($newFilename, '_') . '.blade.php';
            $oldFilename = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $old->name);
            $oldFilename = trim($oldFilename, '_') . '.blade.php';

            if ($data['is_default']) {
                DB::connection('tenant')->table('invoice_templates')->where('id', '!=', $request->id)->update(['is_default' => 0]);
            }

            if ($old->name !== $request->name) {
                $oldFilePath = $path . '/' . $oldFilename;
                $newFilePath = $path . '/' . $newFilename;

                if (file_exists($oldFilePath)) {
                    if (!rename($oldFilePath, $newFilePath)) {
                        \Log::error("RENAME FAILED: {$oldFilePath} → {$newFilePath}");
                        return response()->json(['error' => 'Failed to rename file.', 'status' => 500]);
                    }
                } else {
                    $tempPath = tempnam(sys_get_temp_dir(), 'tmp');
                    file_put_contents($tempPath, "");
                    $uploadedFile = new UploadedFile($tempPath, $newFilename, 'application/octet-stream', null, true);
                    $uploadedFile->move($path, $newFilename);
                }
            }

            $updated = DB::connection('tenant')->table('invoice_templates')->where('id', $request->id)->update($data);
            if ($updated || $old->name === $request->name) {
                $template = DB::connection('tenant')->table('invoice_templates')->where('id', $request->id)->first();
                return response()->json([
                    'success'  => 'Invoice template updated successfully.',
                    'status'   => 201,
                    'template' => [
                        'id'         => $template->id,
                        'name'       => $template->name,
                        'is_default' => $template->is_default,
                    ],
                ]);
            } else {
                return response()->json(['error' => 'No changes made.', 'status' => 409]);
            }
        } else {
            return response()->json(['errors' => $validator->errors()->all(), 'status' => 422]);
        }
    }

    public function deleteInvoiceTemplate(Request $request)
    {
        $template = DB::connection('tenant')->table('invoice_templates')->where('id', $request->id)->first();
        if (!$template) {
            return response()->json(['error' => 'Template not found.', 'status' => 404]);
        }

        $filename = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $template->name);
        $filename = trim($filename, '_') . '.blade.php';
        $filePath = base_path('resources/views/common/invoices') . '/' . $filename;

        if (file_exists($filePath)) {
            if (!@unlink($filePath)) {
                \Log::error("DELETE FAILED: {$filePath}");
                return response()->json(['error' => 'Failed to delete file.', 'status' => 500]);
            }
        }

        $deleted = DB::connection('tenant')->table('invoice_templates')->where('id', $request->id)->delete();

        if ($deleted) {
            return response()->json(['success' => 'Invoice template deleted successfully.', 'status' => 201]);
        } else {
            return response()->json(['error' => 'Failed to delete from database.', 'status' => 500]);
        }
    }

    public function previewInvoiceTemplate($filename)
    {
        $cleanName = pathinfo($filename, PATHINFO_FILENAME);
        $cleanName = preg_replace('/\.blade$/i', '', $cleanName);
        $viewName = "common.invoices.{$cleanName}";

        $path = resource_path("views/common/invoices/{$cleanName}.blade.php");

        if (!file_exists($path)) {
            abort(404, "Template view '{$viewName}' not found.");
        }

        return view($viewName);
    }

    public function previewInvoiceTemplatePdf($filename)
    {
        $cleanName = pathinfo($filename, PATHINFO_FILENAME);
        $cleanName = preg_replace('/\.blade$/i', '', $cleanName);
        $viewName  = "common.invoices.{$cleanName}";

        $path = resource_path("views/common/invoices/{$cleanName}.blade.php");

        if (!file_exists($path)) {
            abort(404, "Template view '{$viewName}' not found.");
        }

        $data = [
            'invoice_number' => 'INV-001',
            'date' => now()->format('d/m/Y'),
            'customer' => 'John Doe',
            'items' => [
                ['description' => 'Product A', 'qty' => 2, 'price' => 50],
                ['description' => 'Product B', 'qty' => 1, 'price' => 100],
            ],
            'total' => 200,
        ];

        $pdf = Pdf::loadView($viewName, $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('invoice-preview.pdf');
    }

    public function insertCurrency(Request $request)
    {
        $data = [
            'name' => trim($request->name),
            'code' => strtoupper(trim($request->code)),
        ];

        $validator = $request->validate([
            'name' => 'required|string|max:255|unique:currency,name',
            'code' => 'required|size:3|unique:currency,code',
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
            'id'   => 'required|integer|exists:currency,id',
            'name' => 'required|string|max:255|unique:currency,name,' . $request->id,
            'code' => 'required|size:3|unique:currency,code,' . $request->id,
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
    $data = [
        'name'      => trim($request->name),
        'address'   => trim($request->address ?? ''),
        'city'      => trim($request->city ?? ''),
        'phone'     => trim($request->phone ?? ''),
        'email'     => trim($request->email ?? ''),
        'sector'    => trim($request->sector),
        'category'  => trim($request->category),
        'status'    => $request->status ?? 'active',
    ];

    $validator = $request->validate([
        'name'     => 'required|string|max:255',
        'sector'   => 'required|string|max:255',
        'category' => 'required|string|max:255',
        'address'  => 'nullable|string|max:1000',
        'city'     => 'nullable|string|max:100',
        'phone'    => 'nullable|string|max:50',
        'email'    => 'nullable|email|max:255',
        'status'   => 'nullable|in:active,inactive,archived',
    ]);

    // Manual uniqueness check using tenant connection
    $exists = DB::connection('tenant')
        ->table('branches')
        ->where('name', $data['name'])
        ->where('sector', $data['sector'])
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
                'category' => $branch->category,
                'status'   => $branch->status,
            ],
        ]);
    }

    return response()->json(['error' => 'Failed to create branch.', 'status' => 500]);
}

public function updateBranch(Request $request)
{
    $data = [
        'name'      => trim($request->name),
        'address'   => trim($request->address ?? ''),
        'city'      => trim($request->city ?? ''),
        'phone'     => trim($request->phone ?? ''),
        'email'     => trim($request->email ?? ''),
        'sector'    => trim($request->sector),
        'category'  => trim($request->category),
        'status'    => $request->status ?? 'active',
    ];

    $validator = $request->validate([
        'name'     => 'required|string|max:255',
        'sector'   => 'required|string|max:255',
        'category' => 'required|string|max:255',
        'address'  => 'nullable|string|max:1000',
        'city'     => 'nullable|string|max:100',
        'phone'    => 'nullable|string|max:50',
        'email'    => 'nullable|email|max:255',
        'status'   => 'nullable|in:active,inactive,archived',
    ]);

    // Manual uniqueness check (ignore current record)
    $exists = DB::connection('tenant')
        ->table('branches')
        ->where('name', $data['name'])
        ->where('sector', $data['sector'])
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
                'category' => $branch->category,
                'status'   => $branch->status,
            ],
        ]);
    }

    return response()->json(['error' => 'Branch not found or no changes made.', 'status' => 409]);
}

public function deleteBranch(Request $request)
{
    $validator = $request->validate([
        'id' => 'required|integer|exists:branches,id',
    ]);

    $deleted = DB::connection('tenant')
        ->table('branches')
        ->where('id', $request->id)
        ->delete();

    if ($deleted) {
        return response()->json(['success' => 'Branch deleted successfully.', 'status' => 201]);
    }

    return response()->json(['error' => 'Branch not found.', 'status' => 404]);
}






    
}