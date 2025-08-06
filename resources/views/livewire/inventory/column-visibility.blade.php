<div>
    <table class="table table-striped align-middle table-sm table-bordered">
        <tbody>
            @if($inventory_visible_column)
                @foreach ($inventory_visible_column as $column => $visible)
                    <tr>
                        <th>
                            <label class="me-3">
                                <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" @if ($visible) checked @endif>
                                &nbsp; {{ ucFirst(str_replace('_', ' ', $column)) }}
                            </label>
                        </th>
                    </tr>
                @endforeach
            @else
                <tr>
                    <th class="text-center text-muted">No columns available</th>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <th> <a class="btn btn-info" href="{{ route('inventory::index') }}">Apply</a> </th>
            </tr>
        </tfoot>
    </table>
</div>
