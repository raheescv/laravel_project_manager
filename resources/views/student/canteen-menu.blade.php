<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('student::index') }}">Students</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Canteen Menu</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Canteen Menu</h1>
            <p class="lead">What each meal serves on each school day — parents see it when they pre-order</p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('student.canteen-menu')
        </div>
    </div>
</x-app-layout>
