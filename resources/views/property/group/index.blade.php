<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Property Groups</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Property Groups</h1>
            <p class="lead">
                Manage property groups and their details
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('property.group.table')
            </div>
        </div>
    </div>
    <x-property.group.group-modal />
</x-app-layout>
