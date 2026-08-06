<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Support\BarcodeFonts;
use App\Support\BarcodeTemplateConfiguration;
use App\Traits\UsesBrowsershot;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    use UsesBrowsershot;

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

        $settings = BarcodeTemplateConfiguration::resolveSettings(request('template'))['settings'];
        $company_name = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');
        $company_logo = tenant_cache('logo', asset('assets/img/logo.svg'));

        $html = view('inventory.barcode', compact('settings', 'inventory', 'company_name', 'company_logo'))->render();

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
        $inventory = null;

        if ($type == 'inventory') {
            if ($id) {
                $inventory = Inventory::with('product.unit')->find($id);
            } else {
                $inventory = Inventory::with('product.unit')->first();
            }
            $product = $inventory->product;
            $conversionFactor = 1;
            $barcode = $inventory->barcode;
        } elseif ($type == 'product_unit') {
            $productUnit = ProductUnit::with('product.unit')->find($id);
            $product = $productUnit->product;
            $conversionFactor = $productUnit->conversion_factor;
            $barcode = $productUnit->barcode;
        }

        $settings = BarcodeTemplateConfiguration::resolveSettings(request('template'))['settings'];
        $company_name = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');
        $company_logo = tenant_cache('logo', asset('assets/img/logo.svg'));

        $html = view('inventory.barcode', compact('settings', 'product', 'inventory', 'conversionFactor', 'barcode', 'company_name', 'company_logo'))->render();
        putenv('HOME=/tmp');
        $pdf = $this->makeBrowsershot($html)
            ->paperSize($settings['width'], $settings['height'])
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
        $settings = BarcodeTemplateConfiguration::resolveSettings($request->query('template'))['settings'];
        $company_name = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');
        $company_logo = tenant_cache('logo', asset('assets/img/logo.svg'));

        // Generate HTML using Blade view
        $html = view('inventory.barcode-cart-print', compact('cartItems', 'settings', 'company_name', 'company_logo'))->render();
        putenv('HOME=/tmp');
        $pdf = $this->makeBrowsershot($html)
            ->paperSize($settings['width'], $settings['height'])
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
        return view('inventory.barcode-template-list');
    }

    public function configurationEdit(string $templateKey)
    {
        return view('inventory.barcode-configuration', compact('templateKey'));
    }

    public function configurationData(string $templateKey): JsonResponse
    {
        $configuration = BarcodeTemplateConfiguration::getConfiguration();

        abort_unless(isset($configuration['templates'][$templateKey]), 404);

        $sampleProduct = Product::orderBy('name')->select(['id', 'name', 'name_arabic', 'barcode', 'size', 'mrp'])->first();
        $settings = $configuration['templates'][$templateKey]['settings'];

        return response()->json([
            'templateKey' => $templateKey,
            'templateName' => $configuration['templates'][$templateKey]['name'],
            'defaultTemplateKey' => $configuration['default_template'],
            'type' => BarcodeTemplateConfiguration::resolveType($settings),
            'types' => BarcodeTemplateConfiguration::types(),
            'barcodeTypes' => $this->barcodeTypes(),
            'qtySources' => $this->qtySources(),
            'fonts' => BarcodeFonts::options(),
            'fontWeights' => BarcodeFonts::weights(),
            // Lets the designer render each font option in its own face.
            'fontFaceCss' => BarcodeFonts::faceCss(null, false),
            'settings' => $settings,
            'previewUrl' => route('inventory::barcode::preview').'?template='.$templateKey,
            'printUrl' => route('inventory::barcode::print').'?template='.$templateKey,
            'productSearchUrl' => route('product::list'),
            'sampleProduct' => $sampleProduct,
        ]);
    }

    public function preview(Request $request, $id = null)
    {
        if ($id) {
            $inventory = Inventory::with('product')->find($id);
        } else {
            $inventory = Inventory::with('product')->first();
        }

        $productId = $request->integer('product_id');
        $product = $productId ? Product::with('unit')->find($productId) : null;

        if (! $product && $inventory?->product) {
            $product = $inventory->product;
        }

        $product ??= Product::with('unit')->orderBy('name')->first();
        abort_unless($product, 404);

        $barcode = $product->barcode ?: ($inventory->barcode ?? '');
        $conversionFactor = 1;
        $settings = BarcodeTemplateConfiguration::resolveSettings($request->query('template'))['settings'];
        $company_name = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');
        $company_logo = tenant_cache('logo', asset('assets/img/logo.svg'));
        $isPreview = true;

        return view('inventory.barcode', compact('settings', 'product', 'inventory', 'conversionFactor', 'barcode', 'company_name', 'company_logo', 'isPreview'));
    }

    public function saveConfigurationTemplate(Request $request, string $templateKey): JsonResponse
    {
        $configuration = BarcodeTemplateConfiguration::getConfiguration();
        abort_unless(isset($configuration['templates'][$templateKey]), 404);

        $validated = $request->validate([
            'templateName' => ['required', 'string', 'max:255'],
            'settings' => ['required', 'array'],
        ]);

        // The type is fixed when the template is created: switching it would
        // leave the settings block describing a shape nothing can render.
        $type = BarcodeTemplateConfiguration::resolveType($configuration['templates'][$templateKey]['settings']);

        $configuration['templates'][$templateKey] = [
            'name' => trim($validated['templateName']),
            'type' => $type,
            'settings' => BarcodeTemplateConfiguration::normalizeSettings($validated['settings'], $type),
        ];

        BarcodeTemplateConfiguration::saveConfiguration($configuration);

        return response()->json([
            'message' => 'Barcode template saved successfully',
            'type' => $type,
            'settings' => $configuration['templates'][$templateKey]['settings'],
        ]);
    }

    public function resetConfigurationTemplate(string $templateKey): JsonResponse
    {
        $configuration = BarcodeTemplateConfiguration::getConfiguration();
        abort_unless(isset($configuration['templates'][$templateKey]), 404);

        $type = BarcodeTemplateConfiguration::resolveType($configuration['templates'][$templateKey]['settings']);

        $configuration['templates'][$templateKey]['settings'] = BarcodeTemplateConfiguration::defaultSettings($type);
        BarcodeTemplateConfiguration::saveConfiguration($configuration);

        return response()->json([
            'message' => 'Barcode template reset successfully',
            'type' => $type,
            'settings' => $configuration['templates'][$templateKey]['settings'],
        ]);
    }

    /**
     * Where the jewellery tag "Qty" line pulls its value from.
     */
    protected function qtySources(): array
    {
        return [
            'unit' => 'Unit quantity (1 Nos)',
            'stock' => 'Stock on hand',
            'custom' => 'Fixed text',
        ];
    }

    protected function barcodeTypes(): array
    {
        return [
            'C128' => 'Code 128',
            'C128A' => 'Code 128 A',
            'C128B' => 'Code 128 B',
            'C39' => 'Code 39',
            'C39+' => 'Code 39+',
            'C39E' => 'Code 39 Extended',
            'C39E+' => 'Code 39 Extended +',
            'C93' => 'Code 93',
            'PHARMA2T' => 'Pharmacode Two-Track',
            'CODABAR' => 'Codabar',
        ];
    }
}
