<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sale::index') }}">Sale</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Page</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Sale</h1>
            <p class="lead">
                A sale Form
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('sale.page', ['table_id' => $id])
        </div>
    </div>
    <x-account.customer-modal />
    @push('scripts')
        @include('components.select.customerSelect')
        @include('components.select.employeeSelect')
        @include('components.select.inventoryProductSelect')
        @include('components.select.paymentMethodSelect')
        <script>
            $(document).ready(function() {
                // $('#root').attr('class', 'root tm--expanded-hd mn--sticky mn--min');
            })
        </script>
    @endpush
</x-app-layout>
