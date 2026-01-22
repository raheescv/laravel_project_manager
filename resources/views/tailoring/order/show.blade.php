<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tailoring::order::index') }}">Tailoring Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="view">View Order</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('tailoring.view', ['order_id' => $id])
        </div>
    </div>
</x-app-layout>
