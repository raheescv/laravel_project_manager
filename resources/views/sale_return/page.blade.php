<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sale_return::index') }}">Sale Return</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Page</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('sale-return.page', ['table_id' => $id])
        </div>
    </div>
    <x-account.customer-modal />
    <x-account.customer-view-modal />
    <x-sale-return.edit-item-modal />
    <x-sale-return.view-items-modal />
    <x-sale-return.custom-payment-modal />
    @push('scripts')
        @include('components.select.customerSelect')
        @include('components.select.inventoryProductSelect')
        @include('components.select.customerSaleSelect')
        @include('components.select.paymentMethodSelect')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.addEventListener('print-invoice', function(event) {
                    if (event.detail[0].print) {
                        window.open(event.detail[0].link);
                    } else {
                        @if ($id)
                            window.location.href = "{{ route('sale_return::create') }}";
                        @endif
                    }
                });
            });
        </script>
        <script>
            $(document).ready(function() {
                // $('#root').attr('class', 'root mn--push');
            })
        </script>
    @endpush
</x-app-layout>
