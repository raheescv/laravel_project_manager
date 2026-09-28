<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sale::index') }}">Sale</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Online Payments</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Online Payments</h1>
            <p class="lead">Every storefront checkout paid through Tap — including the failed, pending and unrecorded ones</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('sale.online-payments')
        </div>
    </div>
</x-app-layout>
