<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tenants::index') }}">Tenant Control</a></li>
                    <li class="breadcrumb-item active" aria-current="page">AMC Reminders</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">AMC Reminders</h1>
            <p class="lead">Tenants whose maintenance contract is overdue or renewing soon</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('tenant.amc-reminder')
        </div>
    </div>
</x-app-layout>
