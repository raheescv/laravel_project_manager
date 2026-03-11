<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::sale::index') }}">Sale Agreements</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Sale Agreement</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">View Sale Agreement</h1>
            <p class="lead">
                Sale agreement details and information
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.sale.view', ['id' => $id])
        </div>
    </div>
</x-app-layout>
