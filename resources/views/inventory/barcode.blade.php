{{--
    Single label: preview + print. Dispatches to the label type the template was
    created with; every type owns a folder under inventory/label-types.
    All parent variables ($settings, $product, $barcode, $conversionFactor,
    $company_name, $company_logo, $isPreview...) are inherited by the include.
--}}
@include(\App\Support\BarcodeLabel::viewPath($settings, 'single'))
