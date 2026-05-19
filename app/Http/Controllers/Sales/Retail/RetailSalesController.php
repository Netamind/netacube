<?php

namespace App\Http\Controllers\Sales\Retail;

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

class RetailSalesController extends Controller
{
    public function showDashboardView()
    {
        return view('sales.retail.dashboard');
    }

    public function showProfileView()
    {
        return view('sales.retail.profile');
    }

    public function showSupportCenter()
    {
        return view('sales.retail.support-center');
    }

    public function showEventsView()
    {
        return view('sales.retail.events');
    }


    
    public function showProductsView()
    {
        return view('sales.retail.products');
    }

            public function showProductSearchView(){
                
            return view('sales.retail.product-search');

            }



    public function showEventsTable(Request $request)
    {
        $user = $this->getCurrentSalesUser();
        if (!$user) {
            $notification = [
                'message'    => 'Your session has expired. Please log in again.',
                'alert-type' => 'error',
            ];
            return redirect()->route('login')->with($notification);
        }

        $events = DB::connection('tenant')->table('events')
            ->where('user_id', $user->id)
            ->orderBy('start_date', 'asc')
            ->get();

        return view('sales.retail.events-table', compact('events'));
    }

    public function fetchEvents(Request $request)
    {
        $user = $this->getCurrentSalesUser();
        if (!$user) {
            return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
        }

        $query = DB::connection('tenant')->table('events')->where('user_id', $user->id);

        if ($request->has('upcoming')) {
            $query->where('start_date', '>=', now()->toDateString());
        }

        $events = $query->orderBy('start_date', 'asc')->get()->map(function ($event) {
            $start = $event->start_date . ($event->all_day ? '' : 'T' . $event->start_time);
            $end   = $event->all_day
                ? Carbon::parse($event->end_date)->addDay()->format('Y-m-d')
                : ($event->end_date . 'T' . $event->end_time);

            return [
                'id'            => $event->id,
                'title'         => $event->description,
                'start'         => $start,
                'end'           => $end,
                'allDay'        => (bool) $event->all_day,
                'classNames'    => [$event->bg_color],
                'extendedProps' => [
                    'start_time' => $event->start_time,
                    'end_time'   => $event->end_time,
                    'bg_color'   => $event->bg_color,
                ],
            ];
        });

        return response()->json([
            'success' => 'Events fetched successfully.',
            'status'  => 201,
            'events'  => $events,
        ]);
    }

    public function storeEvent(Request $request)
    {
        $user = $this->getCurrentSalesUser();
        if (!$user) {
            return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
        }

        try {
            $request->validate([
                'description' => 'required|string|max:255',
                'bg_color'    => 'required|in:bg-danger,bg-success,bg-primary,bg-info,bg-dark,bg-warning',
                'start_date'  => 'required|date',
                'end_date'    => 'required|date|after_or_equal:start_date',
                'all_day'     => 'boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->validator->errors()->all(),
                'status' => 422,
            ]);
        }

        $startTime = $request->all_day ? null : ($request->start_time ?? null);
        $endTime   = $request->all_day ? null : ($request->end_time ?? null);

        if (!$request->all_day && $startTime && $endTime && strtotime($endTime) < strtotime($startTime)) {
            $endTime = $startTime;
        }

        $data = [
            'description' => trim($request->description),
            'bg_color'    => $request->bg_color,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date ?: $request->start_date,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'all_day'     => $request->all_day ?? false,
            'user_id'     => $user->id,
        ];

        $insertId = DB::connection('tenant')->table('events')->insertGetId($data);

        if ($insertId) {
            $event = DB::connection('tenant')->table('events')->where('id', $insertId)->first();
            return response()->json([
                'success' => 'Event created successfully.',
                'status'  => 201,
                'event'   => $this->formatEventResponse($event),
            ]);
        }

        return response()->json(['error' => 'Failed to create event.', 'status' => 500]);
    }

    public function updateEvent(Request $request, $tenantName, $id)
    {
        $user = $this->getCurrentSalesUser();
        if (!$user) {
            return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
        }

        $exists = DB::connection('tenant')->table('events')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$exists) {
            return response()->json(['error' => 'Event not found or permission denied.', 'status' => 404]);
        }

        try {
            $request->validate([
                'description' => 'required|string|max:255',
                'bg_color'    => 'required|in:bg-danger,bg-success,bg-primary,bg-info,bg-dark,bg-warning',
                'start_date'  => 'required|date',
                'end_date'    => 'required|date|after_or_equal:start_date',
                'all_day'     => 'boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->validator->errors()->all(),
                'status' => 422,
            ]);
        }

        $startTime = $request->all_day ? null : ($request->start_time ?? null);
        $endTime   = $request->all_day ? null : ($request->end_time ?? null);

        if (!$request->all_day && $startTime && $endTime && strtotime($endTime) < strtotime($startTime)) {
            $endTime = $startTime;
        }

        $data = [
            'description' => trim($request->description),
            'bg_color'    => $request->bg_color,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date ?: $request->start_date,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'all_day'     => $request->all_day ?? false,
        ];

        // Always update — 0 rows affected (no field changed) is still a success
        DB::connection('tenant')->table('events')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->update($data);

        $event = DB::connection('tenant')->table('events')->where('id', $id)->first();

        return response()->json([
            'success' => 'Event updated successfully.',
            'status'  => 201,
            'event'   => $this->formatEventResponse($event),
        ]);
    }

    public function deleteEvent(Request $request, $tenantName, $id)
    {
        $user = $this->getCurrentSalesUser();
        if (!$user) {
            return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
        }

        $deleted = DB::connection('tenant')->table('events')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted) {
            return response()->json(['success' => 'Event deleted successfully.', 'status' => 201]);
        }

        return response()->json(['error' => 'Event not found or permission denied.', 'status' => 404]);
    }

    public function bulkDeleteEvents(Request $request)
    {
        $user = $this->getCurrentSalesUser();
        if (!$user) {
            return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
        }

        try {
            $request->validate([
                'ids'   => 'required|array',
                'ids.*' => 'required|integer',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->validator->errors()->all(),
                'status' => 422,
            ]);
        }

        $deleted = DB::connection('tenant')->table('events')
            ->whereIn('id', $request->ids)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted > 0) {
            return response()->json(['success' => 'Selected events deleted successfully.', 'status' => 201]);
        }

        return response()->json(['error' => 'No events found or permission denied.', 'status' => 404]);
    }

    public function addEventForTableView(Request $request)
    {
        $user = $this->getCurrentSalesUser();
        if (!$user) {
            return response()->json(['error' => 'Your session has expired. Please log in again.', 'status' => 401]);
        }

        try {
            $request->validate([
                'description' => 'required|string|max:255',
                'bg_color'    => 'required|in:bg-danger,bg-success,bg-primary,bg-info,bg-dark,bg-warning',
                'start_date'  => 'required|date',
                'end_date'    => 'required|date|after_or_equal:start_date',
                'all_day'     => 'boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->validator->errors()->all(),
                'status' => 422,
            ]);
        }

        $startTime = $request->all_day ? null : ($request->start_time ?? null);
        $endTime   = $request->all_day ? null : ($request->end_time ?? null);

        if (!$request->all_day && $startTime && $endTime && strtotime($endTime) < strtotime($startTime)) {
            $endTime = $startTime;
        }

        $data = [
            'description' => trim($request->description),
            'bg_color'    => $request->bg_color,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date ?: $request->start_date,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'all_day'     => $request->all_day ?? false,
            'user_id'     => $user->id,
        ];

        $insertId = DB::connection('tenant')->table('events')->insertGetId($data);

        if ($insertId) {
            $event = DB::connection('tenant')->table('events')->where('id', $insertId)->first();
            return response()->json([
                'success' => 'Event created successfully.',
                'status'  => 201,
                'event'   => $this->formatEventResponse($event),
            ]);
        }

        return response()->json(['error' => 'Failed to create event.', 'status' => 500]);
    }

    public function showSystemSettingsView()
    {
        return view('sales.retail.settings');
    }

    public function updateUserFilters(Request $request)
    {
        $user = $this->getCurrentSalesUser();
        if (!$user) {
            $notification = [
                'message'    => 'Your session has expired. Please log in again.',
                'alert-type' => 'error',
            ];
            return Redirect()->back()->with($notification);
        }

        $validator = Validator::make(
            $request->all(),
            ['user_id' => 'required|integer|exists:tenant.users,id'],
            [
                'user_id.required' => 'User ID is required.',
                'user_id.integer'  => 'User ID must be a valid number.',
                'user_id.exists'   => 'The selected user does not exist.',
            ]
        );

        if ($validator->fails()) {
            $notification = [
                'message'    => implode(', ', $validator->errors()->all()),
                'alert-type' => 'error',
            ];
            return Redirect()->back()->with($notification);
        }

        $data = $request->except('_token');

        try {
            $success = DB::connection('tenant')->table('user_filters')->updateOrInsert(
                ['user_id' => $user->id],
                $data
            );

            $notification = $success
                ? ['message' => 'User filters saved successfully!', 'alert-type' => 'success']
                : ['message' => 'Could not save user filters.',     'alert-type' => 'error'];

            return Redirect()->back()->with($notification);

        } catch (\Exception $e) {
            return Redirect()->back()->with([
                'message'    => 'Something went wrong while saving user filters. Please try again later.',
                'alert-type' => 'error',
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function getCurrentSalesUser()
    {
        return Auth::check() ? Auth::user() : null;
    }

    private function formatEventResponse($event): array
    {
        $start = $event->start_date . ($event->all_day ? '' : 'T' . $event->start_time);
        $end   = $event->all_day
            ? Carbon::parse($event->end_date)->addDay()->format('Y-m-d')
            : ($event->end_date . 'T' . $event->end_time);

        return [
            'id'            => $event->id,
            'title'         => $event->description,
            'start'         => $start,
            'end'           => $end,
            'allDay'        => (bool) $event->all_day,
            'classNames'    => [$event->bg_color],
            'extendedProps' => [
                'start_time' => $event->start_time,
                'end_time'   => $event->end_time,
                'bg_color'   => $event->bg_color,
            ],
        ];
    }


    public function searchProduct(Request $request)
{
    $q = trim($request->get('q', ''));
    if ($q === '') {
        return response()->json(['products' => []]);
    }

    $myBranchId = Auth::user()->branch;
    $myBranch   = DB::connection('tenant')->table('branches')->find($myBranchId);

    if (!$myBranch || !$myBranch->category) {
        return response()->json(['products' => []]);
    }

    $branchIds = DB::connection('tenant')
        ->table('branches')
        ->where('category', $myBranch->category)
        ->pluck('id');

    $baseProducts = DB::connection('tenant')
        ->table('retail_base_products')
        ->where('is_product', 1)
        ->where(function ($w) use ($q) {
            $w->where('name', 'like', "%{$q}%")
              ->orWhere('code', 'like', "%{$q}%");
        })
        ->limit(20)
        ->get();

    $results = $baseProducts->map(function ($bp) use ($branchIds) {
        $branchRows = DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('branches as br', 'br.id', '=', 'rbp.branch_id')
            ->whereIn('rbp.branch_id', $branchIds)
            ->where('rbp.base_product_id', $bp->id)
            ->select(
                'rbp.branch_id', 'br.name as branch_name',
                'rbp.stock_quantity', 'rbp.reorder_point',
                'rbp.selling_price', 'rbp.is_active'
            )
            ->orderBy('br.name')
            ->get();

        return [
            'id'       => $bp->id,
            'name'     => $bp->name,
            'code'     => $bp->code,
            'unit'     => $bp->unit,
            'supplier' => $bp->supplier,
            'branches' => $branchRows,
        ];
    });

    return response()->json(['products' => $results]);
}





}