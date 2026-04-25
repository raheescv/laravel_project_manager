<?php

namespace App\Http\Controllers;

use App\Actions\Product\Inventory\SaveStockAdjustmentAction;
use App\Http\Requests\SaveStockAdjustmentRequest;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Support\Facades\Auth;

class InventoryStockAdjustmentController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        return view('inventory.stock-adjustment');
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
