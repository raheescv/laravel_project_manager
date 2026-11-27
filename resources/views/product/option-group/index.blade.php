<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('product::index') }}">Products</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Option Groups</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Option Groups</h1>
            <p class="lead">Choices the till asks for an item: a drink's base and flavour, tea add-ons, cup size. Build a group once, then attach it to products from their Options tab.</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('product.option-groups')
        </div>
    </div>
</x-app-layout>
