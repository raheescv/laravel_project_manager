<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Employee</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Employee</h1>
            <p class="lead">
                A table is an arrangement of Employee
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('user.employee.table')
            </div>
        </div>
    </div>
    <x-user.employee-modal />
    <x-settings.designation.designation-modal />
    @push('scripts')
        <x-select.designationSelect />
    @endpush
</x-app-layout>
