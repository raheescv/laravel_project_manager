<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::service_request::index') }}">Service Requests</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Report</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Service Request Report</h1>
            <p class="lead">
                Each connect paired with the disconnect that ended it
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('property.service-request.report')
            </div>
        </div>
    </div>
</x-app-layout>
