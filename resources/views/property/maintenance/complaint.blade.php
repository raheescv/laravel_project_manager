<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::maintenance::index') }}">Maintenance</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Complaint Details</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('maintenance.complaint', ['id' => $id])
        </div>
    </div>
    @push('scripts')
        @include('components.select.branchSelect')
        @include('components.select.productSelect')
    @endpush
</x-app-layout>
