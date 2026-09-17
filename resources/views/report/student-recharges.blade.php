<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('student::index') }}">Students</a></li>
                    <li class="breadcrumb-item active" aria-current="page">QPay Recharges</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">QPay Recharge Report</h1>
            <p class="lead">Every online top-up and refund parents made through QPay, with what QPay said about each one</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('report.student.q-pay-recharge-report')
        </div>
    </div>
</x-app-layout>
