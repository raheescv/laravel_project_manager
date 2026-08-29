<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('purchase::index') }}">Purchase</a></li>
                    <li class="breadcrumb-item active" aria-current="view">View</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('purchase.view', ['table_id' => $id])
        </div>
    </div>
    @push('styles')
        <x-document-view.styles />
    @endpush
</x-app-layout>
