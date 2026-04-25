<?php

namespace App\Http\Controllers;

use App\Actions\Product\Inventory\SaveStockAdjustmentAction;
use App\Http\Requests\SaveStockAdjustmentRequest;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class InventoryStockAdjustmentController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $selectionToken = (string) $request->query('selection_token', '');
        $cacheKey = $this->getStockAdjustmentSelectionCacheKey($selectionToken);
        $selectedInventoryIds = collect(Cache::pull($cacheKey, []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        return view('inventory.stock-adjustment', [
            'selectedInventoryIds' => $selectedInventoryIds,
        ]);
    }

    protected function getStockAdjustmentSelectionCacheKey(string $token): string
    {
        if ($token === '') {
            return 'stock-adjustment-selection:invalid';
        }

        $userId = Auth::id() ?? 'guest';

        return "stock-adjustment-selection:{$userId}:{$token}";
    }

    public function save(SaveStockAdjustmentRequest $request, SaveStockAdjustmentAction $action)
    {
        $userId = Auth::id();
        $branchId = (int) $request->session()->get('branch_id');

        try {
            if (! $branchId) {
                throw new Exception('Active branch is required for stock adjustment');
            }

            $response = $action->execute($request->items, $userId, $branchId, $request->remarks);
            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            return $this->sendSuccess($response, $response['message']);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), $response['data'] ?? []);
        }
    }
}
