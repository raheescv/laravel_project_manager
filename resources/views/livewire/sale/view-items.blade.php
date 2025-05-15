<div class="card shadow-sm">
    <div class="card-body">
        <form wire:submit="submit">
            <div class="modal-body px-4">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-striped table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th width="30%">Product</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Tax %</th>
                                <th width="10%" class="text-end">Total</th>
                                <th>Action</th>
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
                                @php
                                    $first = array_values($groupedItems)[0];
                                @endphp
                                <tr class="table-light">
                                    <th colspan="8" class="text-capitalize">
                                        <i class="fa fa-user me-2"></i>{{ $first['employee_name'] }}
                                    </th>
                                </tr>
                                @foreach ($groupedItems as $item)
                                    <tr wire:key="item-{{ $item['key'] }}" class="border-bottom">
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-bold">{{ $item['name'] }}</td>
                                        <td>
                                            {{ html()->number('unit_price')->value($item['unit_price'])->class('number select_on_focus form-control form-control-sm text-end')->attribute('wire:model.live', 'items.' . $item['key'] . '.unit_price') }}
                                        </td>
                                        <td>
                                            {{ html()->number('quantity')->value($item['quantity'])->attribute('min', 1)->class('number select_on_focus form-control form-control-sm text-end')->attribute('wire:model.live', 'items.' . $item['key'] . '.quantity') }}
                                        </td>
                                        <td>
                                            {{ html()->number('discount')->value($item['discount'])->class('number select_on_focus form-control form-control-sm text-end')->attribute('wire:model.live', 'items.' . $item['key'] . '.discount') }}
                                        </td>
                                        <td>
                                            {{ html()->number('tax')->value($item['tax'])->attribute('max', '50')->class('number select_on_focus form-control form-control-sm text-end')->attribute('wire:model.live', 'items.' . $item['key'] . '.tax') }}
                                        </td>
                                        <td class="text-end fw-bold"> {{ currency($item['total']) }} </td>
                                        <td>
                                            <button type="button" wire:click="removeItem('{{ $item['key'] }}')" wire:confirm="Are your sure?" class="btn btn-sm btn-link text-danger">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            @php
                                $items = collect($items);
                            @endphp
                            <tr class="fw-bold">
                                <td colspan="3" class="text-end">Total</td>
                                <td class="text-end">{{ currency($items->sum('quantity')) }}</td>
                                <td class="text-end">{{ currency($items->sum('discount')) }}</td>
                                <td class="text-end">{{ currency($items->sum('tax_amount')) }}</td>
                                <td class="text-end">{{ currency($items->sum('total')) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i>Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-check me-1"></i>Submit
                </button>
            </div>
        </form>
    </div>
</div>
