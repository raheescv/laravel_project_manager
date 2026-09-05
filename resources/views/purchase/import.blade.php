<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('purchase::index') }}">Purchase</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Invoice Upload</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Invoice Upload</h1>
            <p class="lead">
                Turn a vendor's item sheet into a draft purchase
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('purchase.import')
        </div>
    </div>
</x-app-layout>
