<?php

namespace App\Http\Controllers;

use App\Actions\Product\Inventory\StockCheck\CreateStockCheckAction;
use App\Actions\Product\Inventory\StockCheck\DeleteStockCheckAction;
use App\Actions\Product\Inventory\StockCheck\Item\GetStockCheckItemsAction;
use App\Actions\Product\Inventory\StockCheck\Item\ScanBarcodeAction;
use App\Actions\Product\Inventory\StockCheck\Item\UpdateStockCheckAction;
use App\Actions\Product\Inventory\StockCheck\UpdateStockCheckMetadataAction;
use App\Http\Requests\Inventory\StockCheck\CreateStockCheckRequest;
use App\Http\Requests\Inventory\StockCheck\UpdateStockCheckRequest;
use App\Models\StockCheck;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockCheckController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        return view('inventory.stock-check', ['branch_id' => session('branch_id')]);
    }

    public function get(Request $request)
    {
        try {
            $query = StockCheck::with(['branch:id,name', 'createdBy:id,name'])->orderBy('created_at', 'desc');
            $search = trim($request->search);
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $stockChecks = $query->get();

            return $this->sendSuccess($stockChecks, 'Stock checks retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), []);
        }
    }

    public function store(CreateStockCheckRequest $request, CreateStockCheckAction $action)
    {
        $userId = Auth::id();
        try {
            $response = $action->execute($request->validated(), $userId);
            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            return $this->sendSuccess($response['data'], $response['message']);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), $response['data'] ?? []);
        }
    }

    public function show($id, Request $request)
    {
        $stockCheck = StockCheck::with(['branch', 'createdBy'])->findOrFail($id);

        if ($request->expectsJson()) {
            return $this->sendSuccess($stockCheck, 'Stock check retrieved successfully');
        }

        // Categories and brands are now fetched via API endpoints in the Vue component
        return view('inventory.stock-check-show', compact('stockCheck'));
    }

    public function update($id, UpdateStockCheckRequest $request, UpdateStockCheckAction $action)
    {
        try {
            $userId = Auth::id();
            $response = $action->execute($id, $request->items, $userId);
            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            return $this->sendSuccess($response['data'], $response['message']);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), $response['data'] ?? []);
        }
    }

    public function delete(DeleteStockCheckAction $action, $id)
    {
        try {
            $response = $action->execute($id);
            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            return $this->sendSuccess($response['data'], $response['message']);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), $response['data'] ?? []);
        }
    }

    public function scanBarcode($id, Request $request, ScanBarcodeAction $action)
    {
        $request->validate([
            'barcode' => 'required|string',
        ]);

        try {
            $response = $action->execute($id, $request->barcode);
            if (! $response['success']) {
                throw new Exception($response['message'] ?? 'Barcode scan failed');
            }

            return $this->sendSuccess($response['data'], 'Barcode scanned successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), []);
        }
    }

    public function updateMetadata($id, CreateStockCheckRequest $request, UpdateStockCheckMetadataAction $action)
    {
        $userId = Auth::id();
        try {
            $response = $action->execute($id, $request->validated(), $userId);
            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            return $this->sendSuccess($response['data'], $response['message']);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), $response['data'] ?? []);
        }
    }

    public function getItems($id, Request $request, GetStockCheckItemsAction $action)
    {
        try {
            $response = $action->execute($id, $request);
            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            return $this->sendSuccess($response['data'], $response['message']);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), []);
        }
    }
}
