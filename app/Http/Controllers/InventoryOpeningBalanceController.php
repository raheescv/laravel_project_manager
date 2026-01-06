<?php

namespace App\Http\Controllers;

use App\Actions\Product\Inventory\SaveOpeningBalanceAction;
use App\Http\Requests\SaveOpeningBalanceRequest;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Support\Facades\Auth;

class InventoryOpeningBalanceController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        return view('inventory.opening-balance');
    }

    public function save(SaveOpeningBalanceRequest $request, SaveOpeningBalanceAction $action)
    {
        $userId = Auth::id();
        try {
            $response = $action->execute($request->items, $userId, $request->remarks);
            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            return $this->sendSuccess($response, $response['message']);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), $response['data'] ?? []);
        }
    }
}
