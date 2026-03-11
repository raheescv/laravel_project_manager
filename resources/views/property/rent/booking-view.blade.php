<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::rent::booking') }}">Rental Booking</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Rental Booking</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">View Rental Booking</h1>
            <p class="lead">
                Rental booking details and information
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.rent.view', ['id' => $id])
        </div>
    </div>
</x-app-layout>
