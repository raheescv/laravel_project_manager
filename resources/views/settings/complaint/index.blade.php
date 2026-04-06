<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings::index') }}">Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Complaints</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Complaints</h1>
            <p class="lead">
                Manage complaint types for maintenance requests
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('settings.complaint.table')
            </div>
        </div>
    </div>
    <x-settings.complaint.complaint-modal />
</x-app-layout>
