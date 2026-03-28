<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Routing\Controller as BaseController;

class LpoPurchaseController extends BaseController
{
    public function index()
    {
        return view('lpo-purchase.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->can('lpo-purchase.create'), 403);

        return view('lpo-purchase.create');
    }

    public function edit(Purchase $purchase)
    {
        abort_unless(auth()->user()->can('lpo-purchase.create') && $purchase->status === 'pending', 403);

        return view('lpo-purchase.edit', compact('purchase'));
    }

    public function show(Purchase $purchase)
    {
        abort_unless(auth()->user()->can('lpo-purchase.view') || auth()->user()->can('lpo-purchase.view own'), 403);

        return view('lpo-purchase.view', compact('purchase'));
    }

    public function decision(Purchase $purchase)
    {
        abort_unless(auth()->user()->can('lpo-purchase.decide') && $purchase->status === 'pending', 403);

        return view('lpo-purchase.decision', compact('purchase'));
    }
}
