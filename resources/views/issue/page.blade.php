<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('issue::index') }}">Issue</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $id ? 'Edit' : 'Create' }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ $id ? 'Edit' : 'Create' }} {{ $type === 'return' ? 'Return' : 'Issue' }}</h1>
            <p class="lead">
                {{ $type === 'return'
                    ? 'Select customer, set one date, and add products with quantity in.'
                    : 'Select customer, set one date, and add products with quantity out.' }}
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('issue.page', ['id' => $id, 'type' => $type])
        </div>
    </div>
    <x-account.customer-modal />
    @push('scripts')
        @include('components.select.customerSelect')
        @include('components.select.productSelect')
    @endpush
</x-app-layout>
