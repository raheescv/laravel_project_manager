<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sale::index') }}">Sale</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Coupons</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Coupons</h1>
            <p class="lead">Promo codes customers can use in the online store.</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div id="coupon-console"
                data-currency="{{ $currency }}"
                data-permissions='@json($permissions)'
                data-lookups='@json($lookups)'></div>
        </div>
    </div>
    @push('scripts')
        @vite('resources/js/coupon.js')
    @endpush
</x-app-layout>
