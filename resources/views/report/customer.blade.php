<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Report</li>
                    <li class="breadcrumb-item active" aria-current="page">Customer</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Customer Report</h1>
            <p class="lead">
                Report For Customer
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('report.customer-items')
            @livewire('report.customer-visit-history')
        </div>
    </div>
    @push('scripts')
        <x-select.customerSelect />
        <script>
            $(document).ready(function() {
                // $('#root').attr('class', 'root tm--expanded-hd mn--min');
            })
        </script>
    @endpush
</x-app-layout>
