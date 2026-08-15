<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Email Templates</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Email Templates</h1>
            <p class="lead">
                The wording your customers receive. Each module event uses whichever template you mark active.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('settings.email-template')
        </div>
    </div>
</x-app-layout>
