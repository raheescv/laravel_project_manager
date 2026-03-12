<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route($config->bookingRoute) }}">{{ $config->bookingLabel }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $id ? 'Edit' : 'Create' }}
                        {{ $config->bookingLabel }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ $id ? 'Edit' : 'New' }} {{ $config->bookingLabel }}</h1>
            <p class="lead">
                {{ $id ? 'Update ' . strtolower($config->bookingLabel) . ' details' : 'Create a new ' . strtolower($config->bookingLabel) }}
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.page', ['type' => 'Booking', 'table_id' => $id, 'agreementType' => $config->typeKey])
        </div>
    </div>
</x-app-layout>
