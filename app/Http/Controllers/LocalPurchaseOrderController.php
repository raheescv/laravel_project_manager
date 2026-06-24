<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\LocalPurchaseOrder;
use App\Traits\UsesBrowsershot;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

class LocalPurchaseOrderController extends BaseController
{
    use AuthorizesRequests;
    use UsesBrowsershot;

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

    public function confirmation(LocalPurchaseOrder $localPurchaseOrder)
    {
        $this->authorize('confirm', $localPurchaseOrder);

        return view('local-purchase-order.confirmation', compact('localPurchaseOrder'));
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
            'confirmedBy',
            'tenant',
        ]);

        $companyLogo = null;

        // Prefer the dedicated LPO header image; fall back to the general company logo.
        $lpoImagePath = Configuration::where('key', 'lpo_header_image')->value('value');
        $candidatePaths = [];
        if ($lpoImagePath) {
            $candidatePaths[] = storage_path('app/public/'.ltrim($lpoImagePath, '/'));
        }

        $generalLogo = Configuration::where('key', 'logo')->value('value') ?? cache('logo');
        if ($generalLogo) {
            // Stored as an absolute URL (…/storage/<path>) or a relative disk path.
            $relative = $generalLogo;
            if (($pos = strpos($generalLogo, '/storage/')) !== false) {
                $relative = substr($generalLogo, $pos + strlen('/storage/'));
            }
            $candidatePaths[] = storage_path('app/public/'.ltrim($relative, '/'));
        }

        foreach ($candidatePaths as $fullPath) {
            if (is_file($fullPath)) {
                $companyLogo = 'data:image/'.pathinfo($fullPath, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($fullPath));
                break;
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

        $pdf = $this->makeBrowsershot($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="LPO-'.$order->id.'-'.now()->format('Ymd').'.pdf"');
    }
}
