<x-app-layout>
    @if (cache('purchase_type') != 'pos')
        <div class="content__header content__boxed overlapping">
            <div class="content__wrap">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('purchase::index') }}">Purchase</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Page</li>
                    </ol>
                </nav>
                <h1 class="page-title mb-0 mt-2">Purchase</h1>
                <p class="lead">
                    Manage of purchased items to suppliers and track payments efficiently
                </p>
            </div>
        </div>
    @endif
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('purchase.page', ['table_id' => $id])
        </div>
    </div>
    <x-account.vendor-modal />
    @push('styles')
    @endpush
    @push('scripts')
        @include('components.select.vendorSelect')
        @include('components.select.productSelect')
        @include('components.select.paymentMethodSelect')
    @endpush
</x-app-layout>
