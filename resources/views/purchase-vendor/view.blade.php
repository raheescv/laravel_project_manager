<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('purchase-vendor::index') }}">Vendors</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $vendor->name }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Vendor Details</h1>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                <div class="card-body">
                    @livewire('purchase-vendor.view', ['vendor_id' => $vendor->id])
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        @include('components.select.paymentMethodSelect')
    @endpush
</x-app-layout>
