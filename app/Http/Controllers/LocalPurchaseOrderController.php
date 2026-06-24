<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\LocalPurchaseOrder;
use App\Services\CompanyLogoResolver;
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

        // Prefer the dedicated LPO header image, then fall back to the general
        // company logo. Browsershot embeds data URIs reliably.
        $companyLogo = CompanyLogoResolver::dataUri('lpo_header_image');

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
