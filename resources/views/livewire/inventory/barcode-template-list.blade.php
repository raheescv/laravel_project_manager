<div>
    <div class="card">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fa fa-clone me-2"></i>
                    Barcode Design Templates
                </h5>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-lg-12">
                    <label class="form-label">Create New Template</label>
                    <div class="input-group">
                        <input type="text" class="form-control" wire:model="newTemplateName" placeholder="Ex: 50x30 shelf label">
                        <button type="button" class="btn btn-primary" wire:click="createTemplate">
                            <i class="fa fa-plus me-1"></i>Create
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Template</th>
                            <th>Label Size</th>
                            <th>Default</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($templates as $templateKey => $template)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $template['name'] }}</div>
                                    <small class="text-muted">{{ $templateKey }}</small>
                                </td>
                                <td>
                                    {{ $template['settings']['width'] ?? 0 }} x {{ $template['settings']['height'] ?? 0 }} mm
                                </td>
                                <td>
                                    @if ($defaultPrintTemplateKey === $templateKey)
                                        <span class="badge bg-success">Default</span>
                                    @else
                                        <span class="text-muted">No</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($defaultPrintTemplateKey === $templateKey)
                                        <span class="btn btn-sm btn-success disabled">
                                            <i class="fa fa-check me-1"></i>Default Print
                                        </span>
                                    @else
                                        <button type="button"
                                            class="btn btn-sm btn-outline-success"
                                            wire:click="$set('defaultPrintTemplateKey', '{{ $templateKey }}')">
                                            <i class="fa fa-check me-1"></i>Set Default
                                        </button>
                                    @endif
                                    <a href="{{ route('inventory::barcode::configuration.edit', $templateKey) }}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-pencil me-1"></i>Configure
                                    </a>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        wire:click="deleteTemplate('{{ $templateKey }}')"
                                        onclick="return confirm('Delete this barcode template?')"
                                        @if (count($templates) === 1) disabled @endif>
                                        <i class="fa fa-trash me-1"></i>Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
