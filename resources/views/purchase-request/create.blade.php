<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create Purchase Request</li>
                </ol>
            </nav>
            <h1 class="mt-2 mb-0 page-title">Create Purchase Request</h1>
            <p class="lead">
                Create a new purchase request.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('purchase-request.page')
        </div>
    </div>
</x-app-layout>
