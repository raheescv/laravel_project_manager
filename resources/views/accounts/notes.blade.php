<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('account::index') }}">Account</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Notes</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Notes </h1>
            <p class="lead">
                A table is an arrangement of Notes
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('account.note.table', ['account_id' => $id])
            </div>
        </div>
    </div>
</x-app-layout>
