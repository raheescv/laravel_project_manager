<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Account</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Account : {{ $account?->name }}
                <p class="lead text-body-emphasis">
                    A table is an arrangement of this Account
                </p>
            </h1>

        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('account.view', ['account_id' => $id])
            </div>
        </div>
    </div>
    @push('scripts')
    @endpush
</x-app-layout>
