<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransfer;

class InventoryTransferController extends Controller
{
    public function index()
    {
        return view('inventory-transfer.index');
    }

    public function page($id = null)
    {
        return view('inventory-transfer.page', compact('id'));
    }

    public function view($id)
    {
        return view('inventory-transfer.view', compact('id'));
    }

    public function print($id)
    {
        $model = InventoryTransfer::find($id);

        return view('inventory-transfer.print', compact('model'));
    }
}
