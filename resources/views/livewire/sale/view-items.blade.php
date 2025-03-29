<div>
    <form wire:submit="submit">
        <div class="modal-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>SL No</th>
                            <th width="20%">Product</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Tax %</th>
                            <th class="text-end">Total</th>
                            @if ($status == 'draft')
                                <th>Action </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $result = [];
                            foreach ($items as $key => $value) {
                                [$parent, $sub] = explode('-', $key);
                                if (!isset($result[$parent])) {
                                    $result[$parent] = [];
                                }
                                $result[$parent][$sub] = $value;
                            }
                            $data = $result;
                        @endphp
                        @foreach ($data as $employee_id => $groupedItems)
                            <tr>
                                @php
                                    $first = array_values($groupedItems)[0];
                                @endphp
                                <th colspan="8">{{ $first['employee_name'] }}</th>
                            </tr>
                            @foreach ($groupedItems as $item)
                                <tr>
                                    @if ($status == 'draft')
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item['name'] }}</td>
                                        <td>
                                            {{ html()->number('unit_price')->value($item['unit_price'])->class('input-xs number select_on_focus transparent_border_input')->attribute('style', 'width:100%')->attribute('wire:model.live', 'items.' . $item['key'] . '.unit_price') }}
                                        </td>
                                        <td>
                                            {{ html()->number('quantity')->value($item['quantity'])->attribute('min', 1)->class('input-xs number select_on_focus transparent_border_input')->attribute('style', 'width:100%')->attribute('wire:model.live', 'items.' . $item['key'] . '.quantity') }}
                                        </td>
                                        <td>
                                            {{ html()->number('discount')->value($item['discount'])->class('input-xs number select_on_focus transparent_border_input')->attribute('style', 'width:100%')->attribute('wire:model.live', 'items.' . $item['key'] . '.discount') }}
                                        </td>
                                        <td>
                                            {{ html()->number('tax')->value($item['tax'])->attribute('max', '50')->class('input-xs number select_on_focus transparent_border_input')->attribute('style', 'width:100%')->attribute('wire:model.live', 'items.' . $item['key'] . '.tax') }}
                                        </td>
                                        <td class="text-end"> {{ currency($item['total']) }} </td>
                                    @else
                                        <td class="text-end">{{ currency($item['unit_price']) }}</td>
                                        <td class="text-end">{{ currency($item['quantity']) }}</td>
                                        <td class="text-end">{{ currency($item['discount']) }}</td>
                                        <td class="text-end">{{ currency($item['tax']) }}</td>
                                        <td class="text-end"> {{ currency($item['total']) }} </td>
                                    @endif
                                    @if ($status == 'draft')
                                        <td>
                                            <i wire:click="removeItem('{{ $item['key'] }}')" wire:confirm="Are your sure?" class="demo-pli-recycling fs-5 me-2 pointer"></i>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot>
                        @php
                            $items = collect($items);
                        @endphp
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end"><b>{{ currency($items->sum('quantity')) }}</b></th>
                            <th class="text-end"><b>{{ currency($items->sum('discount')) }}</b></th>
                            <th class="text-end"><b>{{ currency($items->sum('tax_amount')) }}</b></th>
                            <th class="text-end"><b>{{ currency($items->sum('total')) }}</b></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="modal-footer d-sm-flex justify-content-end">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </form>
</div>
