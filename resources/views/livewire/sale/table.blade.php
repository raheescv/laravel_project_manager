<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    @can('sale.export')
                        <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
                    @endcan
                    @can('sale.delete')
                        <button class="btn btn-icon btn-outline-light" title="To delete the selected items" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling fs-5"></i>
                        </button>
                    @endcan
                </div>
            </div>
            <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end mb-3">
                <div class="form-group">
                    <select wire:model.live="limit" class="form-control">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" wire:model.live="search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                </div>
            </div>
        </div>
        <hr>
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-3">
                    <b><label for="from_date">From Date</label></b>
                    {{ html()->date('from_date')->value('')->class('form-control')->id('unit_id')->attribute('wire:model.live', 'from_date') }}
                </div>
                <div class="col-md-3">
                    <b><label for="to_date">To Date</label></b>
                    {{ html()->date('to_date')->value('')->class('form-control')->id('unit_id')->attribute('wire:model.live', 'to_date') }}
                </div>
                <div class="col-md-6" wire:ignore>
                    <b><label for="customer_id">Customer</label></b>
                    {{ html()->select('customer_id', [])->value('')->class('select-customer_id-list')->id('customer_id')->placeholder('All') }}
                </div>
            </div>
            <div class="row my-2">
                <div class="col-md-3" wire:ignore>
                    <b><label for="branch_id">Branch</label></b>
                    {{ html()->select('branch_id', [])->value('')->class('select-branch_id-list')->id('branch_id')->placeholder('All') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <b><label for="status">Status</label></b>
                    {{ html()->select('status', saleStatuses())->value($status)->class('tomSelect')->id('status')->placeholder('All') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle table-sm table-bordered">
                <thead>
                    <tr class="text-capitalize">
                        <th>
                            <input type="checkbox" wire:model.live="selectAll" />
                            <a href="#" wire:click.prevent="sortBy('id')">
                                #
                                @if ($sortField === 'id')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('date')">
                                Date
                                @if ($sortField === 'date')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('invoice_no')">
                                Invoice No
                                @if ($sortField === 'invoice_no')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('reference_no')">
                                Reference No
                                @if ($sortField === 'reference_no')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('branch_id')">
                                Branch
                                @if ($sortField === 'branch_id')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('accounts.name')">
                                Customer
                                @if ($sortField === 'accounts.name')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('gross_amount')">
                                Gross Amount
                                @if ($sortField === 'gross_amount')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('item_discount')">
                                Item Discount
                                @if ($sortField === 'item_discount')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('tax_amount')">
                                Tax Amount
                                @if ($sortField === 'tax_amount')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('total')">
                                Total
                                @if ($sortField === 'total')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('other_discount')">
                                Other Discount
                                @if ($sortField === 'other_discount')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('freight')">
                                freight
                                @if ($sortField === 'freight')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('grand_total')">
                                Grand Total
                                @if ($sortField === 'grand_total')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('paid')">
                                Paid
                                @if ($sortField === 'paid')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('balance')">
                                Balance
                                @if ($sortField === 'balance')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('status')">
                                Status
                                @if ($sortField === 'status')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>
                                <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" />
                                {{ $item->id }}
                            </td>
                            <td>{{ systemDate($item->date) }}</td>
                            <td><a href="{{ route('sale::edit', $item->id) }}">{{ $item->invoice_no }} </a></td>
                            <td>{{ $item->reference_no }}</td>
                            <td>{{ $item->branch?->name }}</td>
                            <td>{{ $item->name }}</td>
                            <td class="text-end">{{ currency($item->gross_amount) }}</td>
                            <td class="text-end">{{ currency($item->item_discount) }}</td>
                            <td class="text-end">{{ currency($item->tax_amount) }}</td>
                            <td class="text-end">{{ currency($item->total) }}</td>
                            <td class="text-end">{{ currency($item->other_discount) }}</td>
                            <td class="text-end">{{ currency($item->freight) }}</td>
                            <td class="text-end">{{ currency($item->grand_total) }}</td>
                            <td class="text-end">{{ currency($item->paid) }}</td>
                            <td class="text-end">{{ currency($item->balance) }}</td>
                            <td>{{ ucFirst($item->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6">Total</th>
                        <th class="text-end">{{ currency($total['gross_amount']) }}</th>
                        <th class="text-end">{{ currency($total['item_discount']) }}</th>
                        <th class="text-end">{{ currency($total['tax_amount']) }}</th>
                        <th class="text-end">{{ currency($total['total']) }}</th>
                        <th class="text-end">{{ currency($total['other_discount']) }}</th>
                        <th class="text-end">{{ currency($total['freight']) }}</th>
                        <th class="text-end">{{ currency($total['grand_total']) }}</th>
                        <th class="text-end">{{ currency($total['paid']) }}</th>
                        <th class="text-end">{{ currency($total['balance']) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
                $('#status').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('status', value);
                });
                $('#customer_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('customer_id', value);
                });
            });
        </script>
    @endpush
</div>
