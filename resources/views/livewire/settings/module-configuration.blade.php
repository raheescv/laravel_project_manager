<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2 d-flex align-items-center gap-2">
        <i class="fa fa-cubes"></i>
        <h5 class="mb-0 text-white">Module Configuration</h5>
    </div>
    <form wire:submit="save">
        <div class="card-body p-3">
            <p class="text-muted small mb-3">
                Select the active system module. Role permissions will be filtered to show only what belongs to the selected module.
            </p>
            <div class="row g-3">
                @foreach ($systems as $systemName => $systemModules)
                    <div class="col-12 col-md-6">
                        <label class="d-block" style="cursor:pointer" for="module_{{ Str::slug($systemName) }}">
                            <div class="card border-2 h-100 {{ $active_module === $systemName ? 'border-primary bg-primary bg-opacity-10' : 'border' }}">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <input
                                        class="form-check-input mt-0 flex-shrink-0"
                                        type="radio"
                                        wire:model.live="active_module"
                                        value="{{ $systemName }}"
                                        id="module_{{ Str::slug($systemName) }}"
                                    >
                                    <div>
                                        <div class="fw-semibold">{{ $systemName }}</div>
                                        <div class="text-muted small">{{ count($systemModules) }} module groups</div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                @endforeach
            </div>

            @if ($active_module)
                <div class="mt-4 p-3 bg-light rounded-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa fa-check-circle text-success"></i>
                        <span class="fw-semibold small">Active: {{ $active_module }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach ($systems[$active_module] as $moduleKey)
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1 small">
                                {{ str_replace('_', ' ', $moduleKey) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div class="card-footer bg-light d-flex justify-content-end py-2 px-3">
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="fa fa-save me-1"></i>Save Changes
            </button>
        </div>
    </form>
</div>
