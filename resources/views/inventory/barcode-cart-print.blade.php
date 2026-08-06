{{--
    Cart / bulk print. Dispatches to the label type the template was created
    with. All parent variables ($cartItems, $settings, $company_name,
    $company_logo) are inherited by the include.
--}}
@include(\App\Support\BarcodeLabel::viewPath($settings, 'cart'))
