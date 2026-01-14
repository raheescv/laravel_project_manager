<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Inventory;
use App\Models\ProductUnit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;

class BarcodeController extends Controller
{
    public function index()
    {
        return view('inventory.cart');
    }

    public function printOld($id = null)
    {
        if ($id) {
            $inventory = Inventory::with('product')->find($id);
        } else {
            $inventory = Inventory::with('product')->first();
        }

        $settings = Configuration::where('key', 'barcode_configurations')->value('value');
        $settings = json_decode($settings, true) ?? [];

        $html = view('inventory.barcode', compact('settings', 'inventory', 'company_name'))->render();

        // Configure PDF with custom size from settings (50mm x 30mm)
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper([0, 0, 142, 85], 'portrait');
        $pdf->setOption('margin-top', 0);
        $pdf->setOption('margin-right', 0);
        $pdf->setOption('margin-bottom', 0);
        $pdf->setOption('margin-left', 0);
        $pdf->setOption('page-size', 'custom');

        return $pdf->stream('barcode-'.time().'.pdf');
    }

    public function print($type = 'inventory', $id = null)
    {
        if ($type == 'inventory') {
            if ($id) {
                $inventory = Inventory::with('product')->find($id);
            } else {
                $inventory = Inventory::with('product')->first();
            }
            $product = $inventory->product;
            $barcode = $inventory->barcode;
        } elseif ($type == 'product_unit') {
            $productUnit = ProductUnit::find($id);
            $product = $productUnit->product;
            $barcode = $productUnit->barcode;
        }

        $settings = Configuration::where('key', 'barcode_configurations')->value('value');
        $settings = json_decode($settings, true) ?? [];
        $company_name = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');

        $html = view('inventory.barcode', compact('settings', 'product', 'barcode', 'company_name'))->render();
        // Configure Browsershot with optimized settings for faster rendering
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
            ->header('Content-Disposition', 'inline; filename="barcode-'.time().'.pdf"');

    }

    public function cartPrint(Request $request)
    {
        $cartItems = session('print_cart_items', []);

        if (empty($cartItems)) {
            return redirect()->route('inventory::barcode::cart::index')->with('error', 'No items to print. Please add products to cart first.');
        }
        // Load barcode settings
        $settings = Configuration::where('key', 'barcode_configurations')->value('value');
        $settings = json_decode($settings, true) ?? [];
        $company_name = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');

        // Generate HTML using Blade view
        $html = view('inventory.barcode-cart-print', compact('cartItems', 'settings', 'company_name'))->render();

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

    public function configuration()
    {
        return view('inventory.barcode-configuration');
    }
}
