<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
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
                <div class="col-md-2">
                    <label for="from_date">From Date</label>
                    {{ html()->date('from_date')->value('')->class('form-control')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                </div>
                <div class="col-md-2">
                    <label for="to_date">To Date</label>
                    {{ html()->date('to_date')->value('')->class('form-control')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                </div>
                <div class="col-md-4" wire:ignore>
                    <label for="account_id">Account</label>
                    {{ html()->select('account_id', [])->value('')->class('select-account_id-list')->id('account_id')->placeholder('Account') }}
                </div>
                <div class="col-md-4" wire:ignore>
                    <label for="branch_id">Branch</label>
                    {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr class="text-capitalize">
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="id" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="date" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_name" label="account name" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="description" label="description" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="reference_number" label="reference no" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="remarks" label="description" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="journal_remarks" label="journal remarks" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="debit" label="debit" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit" label="credit" /> </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ systemDate($item->date) }}</td>
                            <td>
                                <a href="{{ route('account::view', $item->account_id) }}?from_date={{ $from_date }}&to_date={{ $to_date }}" class="text-decoration-none"
                                    style="padding-left: 3rem !important; display: block;">{{ $item->account_name }}</a>
                            </td>
                            <td>
                                @switch($item->model)
                                    @case('Sale')
                                        <a href="{{ route('sale::view', $item->model_id) }}">{{ $item->description }}</a>
                                    @break

                                    @case('SaleReturn')
                                        <a href="{{ route('sale_return::view', $item->model_id) }}">{{ $item->description }}</a>
                                    @break

                                    @case('SalePayment')
                                        <a href="{{ route('sale::view', $item->journal?->model_id) }}">{{ $item->description }}</a>
                                    @break

                                    @default
                                        {{ $item->description }}
                                @endswitch
                            </td>
                            <td>{{ $item->reference_number }}</td>
                            <td>{{ $item->remarks }}</td>
                            <td>{{ $item->journal_remarks }}</td>
                            <td class="text-end">{{ currency($item->debit) }}</td>
                            <td class="text-end">{{ currency($item->credit) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th class="text-end" colspan="7">Total</th>
                        <th class="text-end">{{ currency($total['debit']) }}</th>
                        <th class="text-end">{{ currency($total['credit']) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('account_id', value);
                });
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
            });
        </script>
    @endpush
</div>
