<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="demo-psi-home"></i></a></li>
                    <li class="breadcrumb-item">Properties</li>
                    <li class="breadcrumb-item">{{ $breadcrumb }}</li>
                    <li class="breadcrumb-item active" aria-current="page">Payments</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ $title }}</h1>
            <p class="lead">{{ $subtitle }}</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.payment-table', ['agreementType' => $agreementType])
        </div>
    </div>
</x-app-layout>
