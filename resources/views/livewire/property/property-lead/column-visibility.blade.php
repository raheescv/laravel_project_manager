<div>
    <p class="small text-muted mb-3">Tick the columns to show in the lead list. The list updates straight away; ID, name and actions are always shown.</p>
    <div class="list-group list-group-flush border rounded mb-3">
        @foreach ($definitions as $column => $definition)
            <label class="list-group-item d-flex align-items-center gap-2 py-2" for="leadColumn_{{ $column }}" style="cursor: pointer">
                <input class="form-check-input m-0" type="checkbox" id="leadColumn_{{ $column }}" wire:click="toggleColumn('{{ $column }}')" @checked($columns[$column] ?? false)>
                <span class="flex-grow-1">{{ $definition['label'] }}</span>
                @if ($definition['visible'])
                    <span class="badge bg-light text-muted border fw-normal">Default</span>
                @endif
            </label>
        @endforeach
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="offcanvas"><i class="fa fa-check me-1"></i> Done</button>
        <button type="button" class="btn btn-light btn-sm ms-auto" wire:click="resetToDefaults"><i class="fa fa-undo me-1"></i> Reset to defaults</button>
    </div>
</div>
