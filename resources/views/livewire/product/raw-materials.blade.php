<div>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="card-title mb-0">Raw Materials</h5>
        <button type="button" class="btn btn-primary btn-sm hstack gap-2" wire:click="create">
            <i class="fa fa-plus"></i>
            <span class="vr"></span>
            Add Raw Material
        </button>
    </div>

    @if (count($rawMaterials) > 0)
        <div class="card mb-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Raw Material</th>
                                <th>Code</th>
                                <th>Unit</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rawMaterials as $index => $item)
                                <tr wire:key="rm-{{ $item['id'] }}">
                                    <td class="text-muted small">{{ $index + 1 }}</td>
                                    <td class="fw-semibold">{{ $item['raw_material']['name'] ?? '-' }}</td>
                                    <td>
                                        @if (!empty($item['raw_material']['code']))
                                            <code class="bg-light rounded px-2 py-1 small">{{ $item['raw_material']['code'] }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $item['raw_material']['unit']['name'] ?? '-' }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($item['quantity'], 4) }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                            wire:click="edit({{ $item['id'] }})" title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            wire:click="delete({{ $item['id'] }})"
                                            wire:confirm="Remove this raw material?" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-5">
            <i class="fa fa-cubes fs-1 text-muted opacity-25 mb-3"></i>
            <p class="text-muted mb-0">No raw materials added yet.</p>
            <p class="text-muted small">Click <strong>Add Raw Material</strong> to get started.</p>
        </div>
    @endif
</div>
