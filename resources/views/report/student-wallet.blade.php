<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('student::index') }}">Students</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Wallet Report</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Student Wallet Report</h1>
            <p class="lead">Every student's card balance, and what went on and off it over the period</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('report.student.wallet-report')
        </div>
    </div>
</x-app-layout>
