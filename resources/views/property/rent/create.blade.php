<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::rent::index') }}">Rental Agreements</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $id ? 'Edit' : 'Create' }} Rental Agreement</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ $id ? 'Edit' : 'New' }} Rental Agreement</h1>
            <p class="lead">
                {{ $id ? 'Update rental agreement details' : 'Create a new rental agreement' }}
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.rent.page', ['type' => 'Rentout', 'table_id' => $id])
        </div>
    </div>
</x-app-layout>
