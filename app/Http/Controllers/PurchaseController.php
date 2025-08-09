<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Browsershot\Browsershot;
use App\Models\Configuration;
use App\Models\PurchaseItem;

class PurchaseController extends Controller
{
    public function index()
    {
        return view('purchase.index');
    }

    public function page($id = null)
    {
        return view('purchase.page', compact('id'));
    }

    public function barcodePrint($id)
    {
        $purchaseItems = PurchaseItem::with('product')->where('purchase_id',$id)->get();

        if (empty($purchaseItems)) {
            return redirect()->route('purchase::index')->with('error', 'No items to print. Please add products to cart first.');
        }
        // Load barcode settings
        $settings = Configuration::where('key', 'barcode_configurations')->value('value');
        $settings = json_decode($settings, true) ?? [];

        // Generate HTML using Blade view
        $html = view('purchase.barcode-print', compact('purchaseItems', 'settings'))->render();

        // Use the same PDF settings as the existing barcode print method
        $pdf = Browsershot::html($html)
            ->paperSize($settings['width'], $settings['height'])
            ->noSandbox()
            ->setNodeBinary('/usr/local/bin/node')
            ->setNpmBinary('/usr/local/bin/npm')
            ->ignoreHttpsErrors()
            ->disableJavascript()
            ->blockDomains(['*']) // Block external resource loading
            ->setOption('args', ['--disable-web-security', '--no-sandbox', '--disable-gpu'])
            ->margins(0, 0, 0, 0)
            ->deviceScaleFactor(1)
        // ->windowSize($settings['height'], $settings['width'])
            ->pdf([
                'printBackground' => false,
                'preferCSSPageSize' => true,
                'scale' => 1,
            ]);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="cart-barcode-'.time().'.pdf"');
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
