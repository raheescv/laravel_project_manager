<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('grn::index') }}">GRN</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View GRN - {{ $grn->grn_no }}</li>
                </ol>
            </nav>
            <h1 class="mt-2 mb-0 page-title">View GRN</h1>
            <p class="lead">
                Review the details of the goods received note and its approval status.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('grn.view', ['grn_id' => $grn->id])
        </div>
    </div>
</x-app-layout>
