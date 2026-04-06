<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">{{ ucfirst($breadcrumb) }}</li>
                    <li class="breadcrumb-item active" aria-current="page">Payments</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ $title }}</h1>
            <p class="lead">{{ $subtitle }}</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('rent-out.payment-table', ['agreementType' => $agreementType])
            </div>
        </div>
    </div>
    @push('scripts')
        <x-select.propertyGroupSelect />
        <x-select.propertyBuildingSelect />
        <x-select.propertySelect />
        <x-select.customerSelect />
    @endpush
</x-app-layout>
