<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Customer Type</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Customer Type</h1>
            <p class="lead">
                A table is an arrangement of Customer Type
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('settings.customer-type.table')
            </div>
        </div>
    </div>
    <x-settings.customer-type.customer-type-modal />
    @push('scripts')
    @endpush
</x-app-layout>
