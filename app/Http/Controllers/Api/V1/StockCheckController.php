<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Product\Inventory\StockCheck\CreateStockCheckAction;
use App\Actions\Product\Inventory\StockCheck\DeleteStockCheckAction;
use App\Actions\Product\Inventory\StockCheck\Item\GetStockCheckItemsAction;
use App\Actions\Product\Inventory\StockCheck\Item\UpdateStockCheckAction;
use App\Actions\V1\StockCheck\GetAction;
use App\Actions\V1\StockCheck\ListAction;
use App\Actions\V1\StockCheck\ScanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockCheck\CreateStockCheckRequest;
use App\Http\Requests\Inventory\StockCheck\UpdateStockCheckRequest;
use App\Http\Requests\V1\StockCheck\ScanRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

#[Group('Mobile - Stock Check')]
class StockCheckController extends Controller
{
    use ApiResponseTrait;

    /**
     * List stock checks.
     *
     * Paginated list scoped to the active branch (the app sends `branch_id`),
     * with per-check progress: counted / total items, variance count and net
     * difference. Optional `status` and `search` filters.
     */
    public function index(Request $request, ListAction $action): JsonResponse
    {
        try {
            return $this->sendSuccess($action->execute($request), 'Stock checks retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve stock checks: '.$e->getMessage());
        }
    }

    /**
     * Create stock check.
     *
     * Creates the count and snapshots every product currently in the branch's
     * inventory into it (recorded = system qty, physical = 0). Returns the new
     * id so the app can jump straight into counting.
     */
    public function store(CreateStockCheckRequest $request, CreateStockCheckAction $action): JsonResponse
    {
        $response = $action->execute($request->validated(), Auth::id());

        return $response['success']
            ? $this->sendSuccess($response['data'], 'Stock check created successfully', 201)
            : $this->sendError('Failed to create stock check: '.$response['message'], [], 422);
    }

    /**
     * View stock check.
     *
     * The header + progress aggregates used by the counting screen.
     */
    public function show(int $id, GetAction $action): JsonResponse
    {
        try {
            return $this->sendSuccess($action->execute($id), 'Stock check retrieved successfully');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Stock check not found.');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve stock check: '.$e->getMessage());
        }
    }

    /**
     * Stock check items.
     *
     * Paginated, filterable list of the items to count (product, system qty,
     * physical qty, difference, status). Filters: `status`, `search`,
     * `difference_condition` (positive|negative|zero), `category_id`, `brand_id`.
     */
    public function items(int $id, Request $request, GetStockCheckItemsAction $action): JsonResponse
    {
        $response = $action->execute($id, $request);

        return $response['success']
            ? $this->sendSuccess($response['data'], 'Items retrieved successfully')
            : $this->sendServerError('Failed to retrieve items: '.$response['message']);
    }

    /**
     * Scan barcode.
     *
     * Increments the matching item's physical quantity by 1 (rapid physical
     * counting). Returns the updated item enriched with product name/code so the
     * app can show a scan confirmation.
     */
    public function scan(int $id, ScanRequest $request, ScanAction $action): JsonResponse
    {
        try {
            return $this->sendSuccess($action->execute($id, $request->validated()['barcode']), 'Barcode scanned successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to scan barcode: '.$e->getMessage(), [], 422);
        }
    }

    /**
     * Save counts.
     *
     * Bulk-saves the counted physical quantities and per-item status. Items set
     * to `completed` reconcile the real branch inventory to the counted qty.
     */
    public function update(int $id, UpdateStockCheckRequest $request, UpdateStockCheckAction $action): JsonResponse
    {
        $response = $action->execute($id, $request->items, Auth::id());

        return $response['success']
            ? $this->sendSuccess($response['data'], 'Stock check updated successfully')
            : $this->sendError('Failed to save counts: '.$response['message'], [], 422);
    }

    /**
     * Delete stock check.
     */
    public function destroy(int $id, DeleteStockCheckAction $action): JsonResponse
    {
        $response = $action->execute($id);

        return $response['success']
            ? $this->sendSuccess($response['data'], 'Stock check deleted successfully')
            : $this->sendServerError('Failed to delete stock check: '.$response['message']);
    }
}
