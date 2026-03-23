<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

class PurchaseRequestController extends BaseController
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(PurchaseRequest::class, 'purchaseRequest');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('purchase-request.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('purchase-request.create');
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseRequest $purchaseRequest)
    {
        return view('purchase-request.view', compact('purchaseRequest'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseRequest $purchaseRequest)
    {
        return view('purchase-request.update', compact('purchaseRequest'));
    }

    public function decision(PurchaseRequest $purchaseRequest)
    {
        $this->authorize('decide', $purchaseRequest);

        return view('purchase-request.decision', compact('purchaseRequest'));
    }
}
