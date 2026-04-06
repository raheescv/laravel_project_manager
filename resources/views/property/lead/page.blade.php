<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::property::index') }}">Property</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::lead::list') }}">Leads</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $lead_id ? 'Edit Lead #'.$lead_id : 'New Lead' }}</li>
                </ol>
            </nav>
            <div class="d-md-flex align-items-md-center justify-content-md-between">
                <div>
                    <h1 class="page-title mb-0 mt-2">
                        <i class="fa fa-user-plus text-primary me-2"></i>{{ $lead_id ? 'Edit Lead' : 'Create New Lead' }}
                    </h1>
                    <p class="lead mb-0">Capture lead details, schedule meetings and track every interaction.</p>
                </div>
                <a href="{{ route('property::lead::list') }}" class="btn btn-light shadow-sm mt-3 mt-md-0">
                    <i class="fa fa-arrow-left me-1"></i> Back to list
                </a>
            </div>
        </div>
    </div>

    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('property.property-lead.page', ['lead_id' => $lead_id])
        </div>
    </div>
    
    @push('scripts')
        <x-select.propertyGroupSelect />
        <x-select.employeeSelect />
    @endpush
</x-app-layout>
