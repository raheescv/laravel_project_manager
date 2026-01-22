<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tailoring</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Tailoring</h1>
            <p class="lead">
                A table is an arrangement of Tailoring
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div id="orderList"> </div>
        </div>
    </div>
    @push('scripts')
        @routes
        @vite('resources/js/tailoring-order-index.js')
        <script>
            window.ordersData = @json($orders);
        </script>
    @endpush
</x-app-layout>
