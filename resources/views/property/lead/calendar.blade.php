<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::lead::list') }}">Leads</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Calendar</li>
                </ol>
            </nav>
            <div class="d-md-flex align-items-md-center justify-content-md-between">
                <div>
                    <h1 class="page-title mb-0 mt-2">
                        <i class="fa fa-calendar text-white me-2"></i>Lead Calendar
                    </h1>
                    <p class="lead mb-0">Schedule visits, follow ups and call backs at a glance.</p>
                </div>
                <a href="{{ route('property::lead::list') }}" class="btn btn-light shadow-sm mt-3 mt-md-0">
                    <i class="fa fa-list me-1"></i> List View
                </a>
                {{-- Lead Dashboard is integrated into the main dashboard --}}
            </div>
        </div>
    </div>

    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('property.property-lead.calendar')
        </div>
    </div>
</x-app-layout>
