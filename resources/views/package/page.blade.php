<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('package::index') }}">Package</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $id ? 'Edit' : 'Create' }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ $id ? 'Edit Package' : 'Create Package' }}</h1>
            <p class="lead">
                {{ $id ? 'Update package details, items, and payments' : 'Create a new package with items and payments' }}
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('package.page', ['table_id' => $id])
        </div>
    </div>
    @push('scripts')
        @include('components.select.accountSelect')
    @endpush
</x-app-layout>

