<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fa fa-home me-1"></i>Home</a></li>
                    <li class="breadcrumb-item">{{ $model }}</li>
                    <li class="breadcrumb-item active" aria-current="page">Audit History</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('audit.table', ['model' => $model, 'table_id' => $id])
        </div>
    </div>
</x-app-layout>
