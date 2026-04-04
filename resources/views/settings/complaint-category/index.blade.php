<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings::index') }}">Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Complaint Categories</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Complaint Categories</h1>
            <p class="lead">
                Manage complaint categories for maintenance requests
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('settings.complaint-category.table')
            </div>
        </div>
    </div>
    <x-settings.complaint-category.complaint-category-modal />
</x-app-layout>
