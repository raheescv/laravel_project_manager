<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tenant Details</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Tenant Details</h1>
            <p class="lead">
                Manage tenant details and information
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('property.tenant-detail.table')
            </div>
        </div>
    </div>
    <x-property.tenant-detail.tenant-detail-modal />
</x-app-layout>
