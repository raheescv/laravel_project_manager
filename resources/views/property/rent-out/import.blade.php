<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route($config->indexRoute) }}">{{ $config->pluralLabel }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Import</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Import {{ $config->pluralLabel }}</h1>
            <p class="lead">
                Bulk import {{ strtolower($config->pluralLabel) }} from a spreadsheet with column mapping and live progress.
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.import', ['agreementType' => $config->typeKey])
        </div>
    </div>
</x-app-layout>
