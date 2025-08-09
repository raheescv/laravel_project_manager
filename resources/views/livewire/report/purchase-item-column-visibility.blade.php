<div>
    <table class="table table-striped align-middle table-sm table-bordered">
        <tbody>
            @foreach ($purchase_item_report_visible_column as $column => $visible)
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
                    <a class="btn btn-info" href="{{ route('report::purchase_item') }}">Apply</a>
                </th>
            </tr>
        </tfoot>
    </table>
</div>

