<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransfer;
use Spatie\Browsershot\Browsershot;

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
        $model = InventoryTransfer::with('items.inventory', 'fromBranch', 'toBranch')->findOrFail($id);
        $html = view('inventory-transfer.print', compact('model', 'id'));
        if (! $model->signature) {
            return $html;
        }
        $html = $html->render();
        $pdf = Browsershot::html($html)->transparentBackground()->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="inventory-transfer-'.time().'.pdf"');

    }
}
