<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::rent::booking') }}">Rental Booking</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $id ? 'Edit' : 'Create' }} Rental Booking</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ $id ? 'Edit' : 'New' }} Rental Booking</h1>
            <p class="lead">
                {{ $id ? 'Update rental booking details' : 'Create a new rental booking' }}
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.rent.page', ['type' => 'Booking', 'table_id' => $id])
        </div>
    </div>
</x-app-layout>
