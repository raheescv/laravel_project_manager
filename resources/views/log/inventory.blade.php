<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Log</li>
                    <li class="breadcrumb-item active" aria-current="page">Inventory</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Inventory Log</h1>
            <p class="lead">
                Log For Inventory
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('log.inventory')
            </div>
        </div>
    </div>
    @push('scripts')
        @include('components.select.productSelect')
        @include('components.select.branchSelect')
        <script>
            $(document).ready(function() {
                $('#root').attr('class', 'root mn--push');
            })
        </script>
    @endpush
</x-app-layout>
