<?php

namespace App\Http\Controllers\Operations\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use DB;
use Auth;

class RetailExpenditureController extends Controller
{
    /**
     * All expenditure scoping is anchored to the Retail sector. branches.sector
     * stores the sector name as plain text (see branches migration), so we
     * filter against this constant rather than joining to the sectors table.
     */
    private const SECTOR = 'Retail';

    // ─────────────────────────────────────────────────────────────────────
    //  VIEWS
    // ─────────────────────────────────────────────────────────────────────

    public function showExpenditureTypesView()
    {
        return view('operations.retail.expenditure-types');
    }

    public function showExpendituresView()
    {
        return view('operations.retail.expenditures');
    }

    // ─────────────────────────────────────────────────────────────────────
    //  HELPERS — formatting
    // ─────────────────────────────────────────────────────────────────────

    private function formatExpenditureType($t): array
    {
        return [
            'id'          => $t->id,
            'row'         => 'etrow' . $t->id,
            'name'        => $t->name,
            'description' => $t->description,
            'status'      => $t->status,
        ];
    }

    /**
     * Expects $e to already carry type_name / category_name / branch_name
     * (joined in the query that produced it — see the listing query in
     * operations.retail.expenditures for the exact join shape).
     */
    private function formatExpenditure($e): array
    {
        return [
            'id'              => $e->id,
            'row'             => 'exrow' . $e->id,
            'expenditure_type_id' => $e->expenditure_type_id,
            'type_name'       => $e->type_name,
            'scope_type'      => $e->scope_type,
            'scope_label'     => $this->scopeLabel($e->scope_type, $e->category_name ?? null, $e->branch_name ?? null),
            'category_id'     => $e->category_id,
            'category_name'   => $e->category_name ?? null,
            'branch_id'       => $e->branch_id,
            'branch_name'     => $e->branch_name ?? null,
            'amount'          => $e->amount,
            'expenditure_date'=> $e->expenditure_date,
            'reference_no'    => $e->reference_no,
            'description'     => $e->description,
        ];
    }

    private function scopeLabel(string $scopeType, ?string $categoryName, ?string $branchName): string
    {
        if ($scopeType === 'category') return 'Category: ' . ($categoryName ?? '—');
        if ($scopeType === 'branch')   return 'Branch: '   . ($branchName   ?? '—');
        return 'All Retail';
    }

    /**
     * Re-fetches a single expenditure with the joins needed to format it for
     * the client (used after insert/update so the returned row always has
     * type_name / category_name / branch_name populated).
     */
    private function fetchExpenditureRow(int $id)
    {
        return DB::connection('tenant')
            ->table('retail_expenditures as e')
            ->join('retail_expenditure_types as t', 't.id', '=', 'e.expenditure_type_id')
            ->leftJoin('categories as c', 'c.id', '=', 'e.category_id')
            ->leftJoin('branches as b', 'b.id', '=', 'e.branch_id')
            ->where('e.id', $id)
            ->select('e.*', 't.name as type_name', 'c.category as category_name', 'b.name as branch_name')
            ->first();
    }

    /**
     * Confirms a category actually belongs to the Retail sector — i.e. some
     * Retail branch is tagged with that category's name. categories has no
     * sector column of its own, so Retail-scoping is derived through branches.
     */
    private function categoryBelongsToRetail(int $categoryId): bool
    {
        $category = DB::connection('tenant')->table('categories')->where('id', $categoryId)->first();
        if (!$category) return false;

        return DB::connection('tenant')
            ->table('branches')
            ->where('sector', self::SECTOR)
            ->where('category', $category->category)
            ->exists();
    }

    private function branchBelongsToRetail(int $branchId): bool
    {
        return DB::connection('tenant')
            ->table('branches')
            ->where('id', $branchId)
            ->where('sector', self::SECTOR)
            ->exists();
    }

    /**
     * Validates the scope combination server-side (belt-and-braces on top of
     * the required_if rules in each request). Returns an error string, or
     * null when the scope is valid.
     */
    private function validateScope(string $scopeType, ?int $categoryId, ?int $branchId): ?string
    {
        if ($scopeType === 'category') {
            if (!$categoryId) return 'A category is required for a category-scoped expenditure.';
            if (!$this->categoryBelongsToRetail($categoryId)) {
                return 'Selected category is not used by any branch in the Retail sector.';
            }
        }

        if ($scopeType === 'branch') {
            if (!$branchId) return 'A branch is required for a branch-scoped expenditure.';
            if (!$this->branchBelongsToRetail($branchId)) {
                return 'Selected branch does not belong to the Retail sector.';
            }
        }

        return null;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  EXPENDITURE TYPES — CRUD
    // ═════════════════════════════════════════════════════════════════════

    public function insertExpenditureType(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:tenant.retail_expenditure_types,name',
            'description' => 'nullable|string|max:2000',
            'status'      => 'nullable|in:active,inactive',
        ], [
            'name.unique' => 'An expenditure type with this name already exists.',
        ]);

        $insertId = DB::connection('tenant')->table('retail_expenditure_types')->insertGetId([
            'name'        => trim($request->name),
            'description' => $request->description ? trim($request->description) : null,
            'status'      => $request->status ?? 'active',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        if ($insertId) {
            $type = DB::connection('tenant')->table('retail_expenditure_types')->where('id', $insertId)->first();
            return response()->json([
                'success' => 'Expenditure type created successfully.',
                'status'  => 201,
                'type'    => $this->formatExpenditureType($type),
            ]);
        }

        return response()->json(['error' => 'Failed to create expenditure type.', 'status' => 500]);
    }

    public function updateExpenditureType(Request $request)
    {
        $request->validate([
            'id'          => 'required|integer|exists:tenant.retail_expenditure_types,id',
            'name'        => 'required|string|max:255|unique:tenant.retail_expenditure_types,name,' . $request->id,
            'description' => 'nullable|string|max:2000',
            'status'      => 'nullable|in:active,inactive',
        ], [
            'name.unique' => 'An expenditure type with this name already exists.',
        ]);

        $updated = DB::connection('tenant')
            ->table('retail_expenditure_types')
            ->where('id', $request->id)
            ->update([
                'name'        => trim($request->name),
                'description' => $request->description ? trim($request->description) : null,
                'status'      => $request->status ?? 'active',
                'updated_at'  => now(),
            ]);

        if ($updated !== false) {
            $type = DB::connection('tenant')->table('retail_expenditure_types')->where('id', $request->id)->first();
            return response()->json([
                'success' => 'Expenditure type updated successfully.',
                'status'  => 201,
                'type'    => $this->formatExpenditureType($type),
            ]);
        }

        return response()->json(['error' => 'Expenditure type not found or no changes made.', 'status' => 409]);
    }

    /**
     * Blocked (skipped, not hard-refused) if any expenditure row still
     * references this type — mirrors the "skip if still has stock" pattern
     * used for base products.
     */
    public function deleteExpenditureType(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.retail_expenditure_types,id',
        ]);

        $inUse = DB::connection('tenant')
            ->table('retail_expenditures')
            ->where('expenditure_type_id', $request->id)
            ->exists();

        if ($inUse) {
            return response()->json([
                'success' => 'Type skipped — it is still used by one or more expenditures.',
                'status'  => 201,
                'skipped' => 1,
                'deleted' => 0,
            ]);
        }

        $deleted = DB::connection('tenant')->table('retail_expenditure_types')->where('id', $request->id)->delete();

        if ($deleted) {
            return response()->json([
                'success' => 'Expenditure type deleted successfully.',
                'status'  => 201,
                'skipped' => 0,
                'deleted' => 1,
            ]);
        }

        return response()->json(['error' => 'Expenditure type not found.', 'status' => 404]);
    }

    public function bulkDeleteExpenditureTypes(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:tenant.retail_expenditure_types,id',
        ]);

        $allIds = array_values(array_unique(array_map('intval', $request->ids)));

        $usedIds = DB::connection('tenant')
            ->table('retail_expenditures')
            ->whereIn('expenditure_type_id', $allIds)
            ->pluck('expenditure_type_id')
            ->unique()
            ->toArray();

        $safeIds = array_values(array_diff($allIds, $usedIds));
        $skipped = count($usedIds);

        $deleted = 0;
        if (!empty($safeIds)) {
            $deleted = DB::connection('tenant')->table('retail_expenditure_types')->whereIn('id', $safeIds)->delete();
        }

        if ($deleted === 0 && $skipped === 0) {
            return response()->json(['error' => 'No expenditure types found.', 'status' => 404]);
        }

        $message = $deleted . ' type' . ($deleted !== 1 ? 's' : '') . ' deleted.';
        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' type' . ($skipped !== 1 ? 's were' : ' was') .
                        ' skipped because ' . ($skipped !== 1 ? 'they are' : 'it is') .
                        ' still used by one or more expenditures.';
        }

        return response()->json([
            'success' => $message,
            'status'  => 201,
            'deleted' => $deleted,
            'skipped' => $skipped,
        ]);
    }

    public function bulkStatusExpenditureTypes(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array',
            'ids.*'  => 'required|integer|exists:tenant.retail_expenditure_types,id',
            'status' => 'required|in:active,inactive',
        ]);

        DB::connection('tenant')
            ->table('retail_expenditure_types')
            ->whereIn('id', $request->ids)
            ->update(['status' => $request->status, 'updated_at' => now()]);

        $types = DB::connection('tenant')
            ->table('retail_expenditure_types')
            ->whereIn('id', $request->ids)
            ->get()
            ->map(fn($t) => $this->formatExpenditureType($t));

        return response()->json([
            'success' => count($request->ids) . ' type(s) marked ' . $request->status . '.',
            'status'  => 201,
            'types'   => $types,
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  EXPENDITURES — CRUD (with Retail-sector scoping)
    // ═════════════════════════════════════════════════════════════════════

    public function insertExpenditure(Request $request)
    {
        $request->validate([
            'expenditure_type_id' => 'required|integer|exists:tenant.retail_expenditure_types,id',
            'scope_type'          => 'required|in:all,category,branch',
            'category_id'         => 'required_if:scope_type,category|nullable|integer|exists:tenant.categories,id',
            'branch_id'           => 'required_if:scope_type,branch|nullable|integer|exists:tenant.branches,id',
            'amount'              => 'required|numeric|min:0.01',
            'expenditure_date'    => 'required|date_format:Y-m-d',
            'reference_no'        => 'nullable|string|max:100',
            'description'         => 'nullable|string|max:2000',
        ]);

        $scopeType  = $request->scope_type;
        $categoryId = $scopeType === 'category' ? (int) $request->category_id : null;
        $branchId   = $scopeType === 'branch'   ? (int) $request->branch_id   : null;

        $scopeError = $this->validateScope($scopeType, $categoryId, $branchId);
        if ($scopeError) {
            return response()->json(['error' => $scopeError, 'status' => 422]);
        }

        $insertId = DB::connection('tenant')->table('retail_expenditures')->insertGetId([
            'expenditure_type_id' => $request->expenditure_type_id,
            'scope_type'          => $scopeType,
            'category_id'         => $categoryId,
            'branch_id'           => $branchId,
            'amount'              => round((float) $request->amount, 2),
            'expenditure_date'    => $request->expenditure_date,
            'reference_no'        => $request->reference_no ? trim($request->reference_no) : null,
            'description'         => $request->description ? trim($request->description) : null,
            'created_by'          => Auth::id(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        if ($insertId) {
            return response()->json([
                'success'     => 'Expenditure recorded successfully.',
                'status'      => 201,
                'expenditure' => $this->formatExpenditure($this->fetchExpenditureRow($insertId)),
            ]);
        }

        return response()->json(['error' => 'Failed to record expenditure.', 'status' => 500]);
    }

    public function updateExpenditure(Request $request)
    {
        $request->validate([
            'id'                  => 'required|integer|exists:tenant.retail_expenditures,id',
            'expenditure_type_id' => 'required|integer|exists:tenant.retail_expenditure_types,id',
            'scope_type'          => 'required|in:all,category,branch',
            'category_id'         => 'required_if:scope_type,category|nullable|integer|exists:tenant.categories,id',
            'branch_id'           => 'required_if:scope_type,branch|nullable|integer|exists:tenant.branches,id',
            'amount'              => 'required|numeric|min:0.01',
            'expenditure_date'    => 'required|date_format:Y-m-d',
            'reference_no'        => 'nullable|string|max:100',
            'description'         => 'nullable|string|max:2000',
        ]);

        $scopeType  = $request->scope_type;
        $categoryId = $scopeType === 'category' ? (int) $request->category_id : null;
        $branchId   = $scopeType === 'branch'   ? (int) $request->branch_id   : null;

        $scopeError = $this->validateScope($scopeType, $categoryId, $branchId);
        if ($scopeError) {
            return response()->json(['error' => $scopeError, 'status' => 422]);
        }

        $updated = DB::connection('tenant')
            ->table('retail_expenditures')
            ->where('id', $request->id)
            ->update([
                'expenditure_type_id' => $request->expenditure_type_id,
                'scope_type'          => $scopeType,
                'category_id'         => $categoryId,
                'branch_id'           => $branchId,
                'amount'              => round((float) $request->amount, 2),
                'expenditure_date'    => $request->expenditure_date,
                'reference_no'        => $request->reference_no ? trim($request->reference_no) : null,
                'description'         => $request->description ? trim($request->description) : null,
                'updated_at'          => now(),
            ]);

        if ($updated !== false) {
            return response()->json([
                'success'     => 'Expenditure updated successfully.',
                'status'      => 201,
                'expenditure' => $this->formatExpenditure($this->fetchExpenditureRow($request->id)),
            ]);
        }

        return response()->json(['error' => 'Expenditure not found or no changes made.', 'status' => 409]);
    }

    public function deleteExpenditure(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.retail_expenditures,id',
        ]);

        $deleted = DB::connection('tenant')->table('retail_expenditures')->where('id', $request->id)->delete();

        if ($deleted) {
            return response()->json([
                'success' => 'Expenditure deleted successfully.',
                'status'  => 201,
                'deleted' => 1,
            ]);
        }

        return response()->json(['error' => 'Expenditure not found.', 'status' => 404]);
    }

    public function bulkDeleteExpenditures(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:tenant.retail_expenditures,id',
        ]);

        $ids     = array_values(array_unique(array_map('intval', $request->ids)));
        $deleted = DB::connection('tenant')->table('retail_expenditures')->whereIn('id', $ids)->delete();

        if ($deleted === 0) {
            return response()->json(['error' => 'No expenditures found.', 'status' => 404]);
        }

        return response()->json([
            'success' => $deleted . ' expenditure' . ($deleted !== 1 ? 's' : '') . ' deleted.',
            'status'  => 201,
            'deleted' => $deleted,
        ]);
    }
}