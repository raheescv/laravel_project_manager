<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Attendance</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Attendance Management</h1>
            <p class="lead">
                Track and manage employee attendance records
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('user.attendance.table')
            </div>
        </div>
    </div>
    <x-user.attendance-modal />
    @push('scripts')
        <x-select.employeeSelect />
        <script>
            $('#root').attr('class', 'root tm--expanded-hd mn--min');
        </script>
    @endpush
</x-app-layout>
