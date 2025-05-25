<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                @can('expense.create')
                    <button class="btn btn-primary hstack gap-2 align-self-center" id="ExpenseAdd">
                        <i class="demo-psi-add fs-5"></i>
                        <span class="vr"></span>
                        Add New
                    </button>
                @endcan
                <div class="btn-group">
                    @can('expense.export')
                        <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
                    @endcan
                    @can('expense.delete')
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
                    <input type="text" wire:model.live="filter.search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                </div>
                <div class="btn-group">
                    @can('expense.import')
                        <button class="btn btn-icon btn-outline-light" data-bs-toggle="modal" data-bs-target="#ExpenseImportModal">
                            <i class="demo-pli-download-from-cloud fs-5"></i>
                        </button>
                    @endcan
                </div>
            </div>
        </div>
        <hr>
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-2">
                    <label for="from_date">From Date</label>
                    {{ html()->date('from_date')->value('')->class('form-control')->id('from_date')->attribute('wire:model.live', 'filter.from_date') }}
                </div>
                <div class="col-md-2">
                    <label for="to_date">To Date</label>
                    {{ html()->date('to_date')->value('')->class('form-control')->id('to_date')->attribute('wire:model.live', 'filter.to_date') }}
                </div>
                <div class="col-md-4" wire:ignore>
                    <label for="account_id">Account</label>
                    {{ html()->select('account_id', [])->value('')->class('select-account_id')->attribute('account_type', 'expense')->id('account_id')->attribute('wire:model', 'filter.account_id') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr class="text-capitalize">
                        <th>
                            <input type="checkbox" wire:model.live="selectAll" />
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="journal_id" label="id" />
                        </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="date" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_name" label="account name" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="person_name" label="payee" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="reference_number" label="reference number" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="description" label="description" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="debit" label="debit" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit" label="credit" /> </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>
                                <input type="checkbox" value="{{ $item->journal_id }}" wire:model.live="selected" />
                                {{ $item->journal_id }}
                            </td>
                            <td>{{ systemDate($item->date) }}</td>
                            <td>
                                <a href="{{ route('account::view', $item->account_id) }}">{{ $item->account_name }}</a>
                            </td>
                            <td>{{ $item->person_name }}</td>
                            <td>{{ $item->reference_number }}</td>
                            <td>
                                @switch($item->model)
                                    @case('Sale')
                                        <a href="{{ route('sale::view', $item->model_id) }}">{{ $item->description }}</a>
                                    @break

                                    @case('SaleReturn')
                                        <a href="{{ route('sale_return::view', $item->model_id) }}">{{ $item->description }}</a>
                                    @break

                                    @default
                                        {{ $item->description }}
                                @endswitch
                            </td>
                            <td class="text-end">{{ currency($item->debit) }}</td>
                            <td class="text-end">{{ currency($item->credit) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th class="text-end" colspan="6">Total</th>
                        <th class="text-end">{{ currency($total['debit']) }}</th>
                        <th class="text-end">{{ currency($total['credit']) }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $(document).on('click', '.edit', function() {
                    Livewire.dispatch("Expense-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('filter.account_id', value);
                });
                $('#ExpenseAdd').click(function() {
                    Livewire.dispatch("Expense-Page-Create-Component");
                });
                window.addEventListener('RefreshExpenseTable', event => {
                    Livewire.dispatch("Expense-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
