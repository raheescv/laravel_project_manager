<div>
    <div class="col-md-12 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <form wire:submit="save">
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <div wire:ignore>
                                <label for="from_branch_id" class="form-label">From Branch *</label>
                                {{ html()->select('from_branch_id', $fromBranch)->value($inventory_transfers['from_branch_id'])->class('select-branch_id-list selected_branch_id')->id('from_branch_id')->placeholder('Select Branch') }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div wire:ignore>
                                <label for="to_branch_id" class="form-label">To Branch *</label>
                                {{ html()->select('to_branch_id', $toBranch)->value($inventory_transfers['to_branch_id'])->class('select-branch_id-list')->id('to_branch_id')->placeholder('Select Branch') }}
                            </div>
                        </div>
                        <div class="col-md-3">
                        </div>
                        <div class="col-md-3">
                            <label for="date" class="form-label">Date *</label>
                            {{ html()->date('date')->value('')->class('form-control')->attribute('wire:model', 'inventory_transfers.date') }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="description" class="form-label">Description</label>
                            {{ html()->textarea('description')->value('')->class('form-control')->attribute('wire:model', 'inventory_transfers.description') }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">ITEM INFO </h5>
                                    <div class="row mb-3">
                                        <div class="col-md-2">
                                        </div>
                                        <div class="col-md-8">
                                            <div class="searchbox input-group" wire:ignore>
                                                {{ html()->select('inventory_id', [])->value('')->class('select-selected-branch-products-list')->id('inventory_id')->attribute('style', 'width:100%')->placeholder('Select Product') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-striped align-middle table-sm table-bordered">
                                            <thead style="background: #f8f8f8">
                                                <tr>
                                                    <th>SL No</th>
                                                    <th width="30%">Product</th>
                                                    <th>Batch</th>
                                                    <th>Barcode</th>
                                                    <th class="text-end">Current Stock</th>
                                                    <th class="text-end">Quantity</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($items as $item)
                                                    <tr wire:key="item-{{ $item['key'] }}">
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $item['name'] }}</td>
                                                        <td>{{ $item['batch'] }}</td>
                                                        <td>{{ $item['barcode'] }}</td>
                                                        <td class="text-end">{{ $item['current_stock'] }}</td>
                                                        <td>
                                                            {{ html()->number('quantity')->value($item['quantity'])->attribute('min', 1)->class('input-xs number select_on_focus transparent_border_input')->attribute('style', 'width:100%')->attribute('step', 'any')->attribute('wire:model.live', 'items.' . $item['key'] . '.quantity') }}
                                                        </td>
                                                        <td>
                                                            <i wire:click="removeItem('{{ $item['key'] }}')" wire:confirm="Are your sure?" class="demo-pli-recycling fs-5 me-2 pointer"></i>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                @php
                                                    $items = collect($items);
                                                @endphp
                                                <tr>
                                                    <th colspan="5" class="text-end">Total</th>
                                                    <th class="text-end"><b>{{ currency($items->sum('quantity')) }}</b></th>
                                                    <th></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            @if ($this->getErrorBag()->count())
                                <ul>
                                    <?php foreach ($this->getErrorBag()->toArray() as $value): ?>
                                    <li style="color:red">* {{ $value[0] }}</li>
                                    <?php endforeach; ?>
                                </ul>
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="d-flex justify-content-end gap-2 my-4 d-print-none">

                            @if ($inventory_transfers['status'] == 'completed')
                                <a target="_blank" href="{{ route('inventory::transfer::print', $inventory_transfers['id']) }}" class="btn btn-info btn-icon flex-fill">
                                    <i class="demo-pli-printer fs-4"></i>
                                </a>
                            @endif
                            &nbsp;
                            @if ($inventory_transfers['status'] == 'pending')
                                <button type="button" wire:click='save("pending")' class="btn btn-success btn-icon flex-fill">Save</button>
                            @endif
                            &nbsp;
                            <button type="submit" wire:confirm="Are you sure to submit this?" class="btn btn-primary btn-icon flex-fill">Transfer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#inventory_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('inventory_id', value);
                });
                $('#from_branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('inventory_transfers.from_branch_id', value);
                    document.querySelector('#to_branch_id').tomselect.open();
                });
                $('#to_branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('inventory_transfers.to_branch_id', value);
                    document.querySelector('#inventory_id').tomselect.open();
                });
                window.addEventListener('OpenProductBox', event => {
                    var tomSelectInstance = document.querySelector('#inventory_id').tomselect;
                    tomSelectInstance.clear();
                    @this.set('inventory_id', null);
                    document.querySelector('#inventory_id').tomselect.open();
                });
                window.addEventListener('ResetSelectBox', event => {
                    var tomSelectInstance = document.querySelector('#from_branch_id').tomselect;
                    tomSelectInstance.clear();
                    document.querySelector('#inventory_id').tomselect.open();
                });
            });
        </script>
    @endpush
</div>
