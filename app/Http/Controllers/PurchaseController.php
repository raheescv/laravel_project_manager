<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;

class PurchaseController extends Controller
{
    public function index()
    {
        return view('purchase.index');
    }

   public function page($id = null)
{
    $defaultPurchaseBranchIds = Configuration::where('key', 'default_purchase_branch_id')->value('value');
    $defaultPurchaseBranchIds = json_decode($defaultPurchaseBranchIds, true);

    // Force array
    if (! is_array($defaultPurchaseBranchIds)) {
        $defaultPurchaseBranchIds = [$defaultPurchaseBranchIds];
    }

    if (! in_array(session('branch_id'), $defaultPurchaseBranchIds)) {
        return redirect()->route('purchase::index')->with('error', 'You are not in the default purchase branch');
    }

    return view('purchase.page', compact('id'));
    
}


    // public function barcodePrint($id)
    // {
    //     $purchaseItems = PurchaseItem::with('product')->where('purchase_id', $id)->get();

    //     if (empty($purchaseItems)) {
    //         return redirect()->route('purchase::index')->with('error', 'No items to print. Please add products to cart first.');
    //     }
    //     // Load barcode settings
    //     $settings = Configuration::where('key', 'barcode_configurations')->value('value');
    //     $settings = json_decode($settings, true) ?? [];
    //     $company_name = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');

    //     // Generate HTML using Blade view
    //     $html = view('purchase.barcode-print', compact('purchaseItems', 'settings', 'company_name'))->render();

    //     // Use the same PDF settings as the existing barcode print method
    //     $pdf = Browsershot::html($html)
    //         ->paperSize($settings['width'], $settings['height'])
    //         ->noSandbox()
    //         ->setNodeBinary('/usr/local/bin/node')
    //         ->setNpmBinary('/usr/local/bin/npm')
    //         ->ignoreHttpsErrors()
    //         ->disableJavascript()
    //         ->blockDomains(['*']) // Block external resource loading
    //         ->setOption('args', ['--disable-web-security', '--no-sandbox', '--disable-gpu'])
    //         ->margins(0, 0, 0, 0)
    //         ->deviceScaleFactor(1)
    //     // ->windowSize($settings['height'], $settings['width'])
    //         ->pdf([
    //             'printBackground' => false,
    //             'preferCSSPageSize' => true,
    //             'scale' => 1,
    //         ]);

    //     return response($pdf)
    //         ->header('Content-Type', 'application/pdf')
    //         ->header('Content-Disposition', 'inline; filename="cart-barcode-'.time().'.pdf"');
    // }
public function barcodePrint($id)
{
    $purchaseItems = PurchaseItem::with('product')->where('purchase_id', $id)->get();

    if ($purchaseItems->isEmpty()) {
        return redirect()->route('purchase::index')->with('error', 'No items to print. Please add products to cart first.');
    }

    // Load barcode settings
    $settings = Configuration::where('key', 'barcode_configurations')->value('value');
    $settings = json_decode($settings, true) ?? [];
    $company_name = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');

    // Generate HTML using Blade view
    $html = view('purchase.barcode-print', compact('purchaseItems', 'settings', 'company_name'))->render();

    // FIXED Browsershot settings (Windows Compatible)
    $pdf = Browsershot::html($html)
        ->paperSize($settings['width'], $settings['height'])
        ->noSandbox()
        ->ignoreHttpsErrors()
        ->disableJavascript()
        ->blockDomains(['*'])
        ->setOption('args', [
            '--disable-web-security',
            '--no-sandbox',
            '--disable-gpu',
        ])
        ->margins(0, 0, 0, 0)
        ->deviceScaleFactor(1)
        ->pdf([
            'printBackground' => true,
            'preferCSSPageSize' => true,
            'scale' => 1,
        ]);

    return response($pdf)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="barcode-'.time().'.pdf"');
}

    public function payments()
    {
        return view('purchase.payments');
    }

    public function get(Request $request)
    {
        $list = (new Purchase())->getDropDownList($request->all());

        return response()->json($list);
    }
}
