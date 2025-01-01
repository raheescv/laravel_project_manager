<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Audit History</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Audit History For {{ $model }}</h1>
            <p class="lead">
                A table is an arrangement of Audit History
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('audit.table', ['model' => $model, 'table_id' => $id])
            </div>
        </div>
    </div>
</x-app-layout>
