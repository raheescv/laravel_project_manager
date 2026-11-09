<x-app-layout>
    <x-student.registrations-premium />
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('student::index') }}">Students</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Registrations</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Registrations</h1>
            <p class="lead">Forms parents sent from the parent portal. Students are added when you approve.</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('student.registration-table')
        </div>
    </div>
</x-app-layout>
