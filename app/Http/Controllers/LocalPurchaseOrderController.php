<?php

namespace App\Http\Controllers;

use App\Models\LocalPurchaseOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

class LocalPurchaseOrderController extends BaseController
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(LocalPurchaseOrder::class, 'localPurchaseOrder');
    }

    public function index()
    {
        return view('local-purchase-order.index');
    }

    public function create()
    {
        $this->authorize('create', LocalPurchaseOrder::class);

        return view('local-purchase-order.create');
    }

    public function show(LocalPurchaseOrder $localPurchaseOrder)
    {
        return view('local-purchase-order.view', compact('localPurchaseOrder'));
    }

    public function decision(LocalPurchaseOrder $localPurchaseOrder)
    {
        $this->authorize('decide', $localPurchaseOrder);

        return view('local-purchase-order.decision', compact('localPurchaseOrder'));
    }
}
