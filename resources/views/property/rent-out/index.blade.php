<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $config->pluralLabel }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ $config->pluralLabel }}</h1>
            <p class="lead">
                {{ $config->subtitle }}
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                @livewire('rent-out.table', ['agreementType' => $config->typeKey])
            </div>
        </div>
    </div>
</x-app-layout>
