<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\LocalPurchaseOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Spatie\Browsershot\Browsershot;

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

    public function edit(LocalPurchaseOrder $localPurchaseOrder)
    {
        return view('local-purchase-order.edit', compact('localPurchaseOrder'));
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

    public function print(LocalPurchaseOrder $localPurchaseOrder)
    {
        $this->authorize('print', $localPurchaseOrder);

        $order = $localPurchaseOrder->load([
            'vendor',
            'items.product.unit',
            'branch',
            'creator',
            'decisionMaker',
            'tenant',
        ]);

        $companyLogo = null;
        $lpoImagePath = Configuration::where('key', 'lpo_header_image')->value('value');
        if ($lpoImagePath) {
            $fullPath = storage_path('app/public/'.$lpoImagePath);
            if (file_exists($fullPath)) {
                $companyLogo = 'data:image/'.pathinfo($fullPath, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($fullPath));
            }
        }

        $companyName = Configuration::where('key', 'company_name')->value('value') ?? cache('company_name', config('app.name'));
        $companyAddress = Configuration::where('key', 'company_address')->value('value') ?? '';
        $companyPhone = Configuration::where('key', 'company_phone')->value('value') ?? '';

        $html = view('local-purchase-order.print', compact(
            'order',
            'companyLogo',
            'companyName',
            'companyAddress',
            'companyPhone',
        ))->render();

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->noSandbox()
            ->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="LPO-'.$order->id.'-'.now()->format('Ymd').'.pdf"');
    }
}
