<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">Property</li>
                    <li class="breadcrumb-item active" aria-current="page">Service Requests</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Service Requests</h1>
            <p class="lead">
                Utility connect and disconnect requests, from request through approval to completion
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('property.service-request.table')
            </div>
        </div>
    </div>
</x-app-layout>
