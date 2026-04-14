<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::property::index') }}">Property</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Leads</li>
                </ol>
            </nav>
            <div class="d-md-flex align-items-md-center justify-content-md-between">
                <div>
                    <h1 class="page-title mb-0 mt-2">
                        <i class="fa fa-users text-white me-2"></i>Leads Management
                    </h1>
                    <p class="lead mb-0">Track, qualify and convert your property leads.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
                    @can('property lead.view')
                        <a href="{{ route('property::lead::calendar') }}" class="btn btn-light shadow-sm">
                            <i class="fa fa-calendar me-1"></i> Calendar
                        </a>
                    @endcan
                    @can('property lead.create')
                        <a href="{{ route('property::lead::create') }}" class="btn btn-info shadow-sm">
                            <i class="fa fa-plus-circle me-1"></i> New Lead
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('property.property-lead.table')
            </div>
        </div>
    </div>
</x-app-layout>
