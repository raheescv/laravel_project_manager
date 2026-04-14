<div>
    @if ($rentOut)
        @php
            $data = [
                'indexRoute' => $config->indexRoute,
                'indexLabel' => $config->pluralLabel,
                'editPermission' => $config->editPermission,
                'editRoute' => $config->editRoute,
                'config' => $config,
            ];
        @endphp
        @include('livewire.rent-out.partials.rent-out-view', $data)
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa fa-exclamation-triangle text-warning fs-1 mb-3 d-block"></i>
                <p class="text-muted mb-0">{{ $config->notFoundMessage }}</p>
            </div>
        </div>
    @endif
</div>
