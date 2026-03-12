<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route($config->indexRoute) }}">{{ $config->pluralLabel }}</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $id ? 'Edit' : 'Create' }}
                        {{ $config->singularLabel }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ $id ? 'Edit' : 'New' }} {{ $config->singularLabel }}</h1>
            <p class="lead">
                {{ $id ? 'Update ' . strtolower($config->singularLabel) . ' details' : 'Create a new ' . strtolower($config->singularLabel) }}
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('rent-out.page', ['type' => 'Rentout', 'table_id' => $id, 'agreementType' => $config->typeKey])
        </div>
    </div>
</x-app-layout>
