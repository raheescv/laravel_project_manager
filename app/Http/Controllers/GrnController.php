<?php

namespace App\Http\Controllers;

use App\Models\Grn;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

class GrnController extends BaseController
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(Grn::class, 'grn');
    }

    public function index()
    {
        return view('grn.index');
    }

    public function create()
    {
        $this->authorize('create', Grn::class);

        return view('grn.create');
    }

    public function edit(Grn $grn)
    {
        return view('grn.edit', compact('grn'));
    }

    public function show(Grn $grn)
    {
        return view('grn.view', compact('grn'));
    }

    public function decision(Grn $grn)
    {
        $this->authorize('decide', $grn);

        return view('grn.decision', compact('grn'));
    }
}
