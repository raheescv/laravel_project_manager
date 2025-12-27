<div>
    <table class="table table-striped align-middle table-sm table-bordered">
        <tbody>
            @foreach ($sale_visible_column as $column => $visible)
                <tr>
                    <th>
                        <label class="me-3">
                            <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" @if ($visible) checked @endif>
                            &nbsp; {{ ucFirst(str_replace('_', ' ', $column)) }}
                        </label>
                    </th>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>
                    <div class="d-flex gap-2">
                        <a class="btn btn-info" href="{{ route('sale::index') }}">Apply</a>
                        <button type="button" class="btn btn-warning btn-sm ms-auto" wire:click="resetToDefaults">Reset to Defaults</button>
                    </div>
                </th>
            </tr>
        </tfoot>
    </table>
</div>
