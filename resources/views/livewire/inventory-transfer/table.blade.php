<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                @can('inventory transfer.create')
                    <a class="btn btn-primary hstack gap-2 align-self-center" href="{{ route('inventory::transfer::create') }}">
                        <i class="demo-psi-add fs-5"></i>
                        <span class="vr"></span>
                        Add New
                    </a>
                @endcan
                <div class="btn-group">
                    @can('inventory transfer.delete')
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
                <div class="col-md-2">
                    <label for="from_date" class="form-label">From Date</label>
                    {{ html()->date('from_date')->value('')->class('form-control')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                </div>
                <div class="col-md-2">
                    <label for="to_date" class="form-label">To Date</label>
                    {{ html()->date('to_date')->value('')->class('form-control')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <label for="from_branch_id" class="form-label">From Branch</label>
                    {{ html()->select('from_branch_id', [session('branch_id') => session('branch_name')])->class('select-branch_id-list')->id('from_branch_id')->placeholder('All') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <label for="to_branch_id" class="form-label">To Branch</label>
                    {{ html()->select('to_branch_id', [])->value()->class('select-branch_id-list')->id('to_branch_id')->placeholder('All') }}
                </div>
                <div class="col-md-2" wire:ignore>
                    <label for="status" class="form-label">Status *</label>
                    {{ html()->select('status', pendingCompletedStatuses())->value('')->class('tomSelect')->placeholder('Select Status')->id('status')->attribute('wire:model.live', 'status') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle table-sm">
                <thead>
                    <tr class="text-capitalize">
                        <th>
                            <input type="checkbox" wire:model.live="selectAll" />
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="id" />
                        </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="date" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="from_branch_id" label="from branch" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="to_branch_id" label="to branch" /> </th>
                        <th width="30%"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="description" label="description" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="status" /> </th>
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
                            <td>
                                @if ($item->status == 'pending')
                                    <a href="{{ route('inventory::transfer::edit', $item->id) }}">{{ $item->fromBranch?->name }}</a>
                                @else
                                    <a href="{{ route('inventory::transfer::view', $item->id) }}">{{ $item->fromBranch?->name }}</a>
                                @endif
                            </td>
                            <td>{{ $item->toBranch?->name }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ ucFirst($item->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#from_branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('from_branch_id', value);
                });
                $('#to_branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('to_branch_id', value);
                });
                $('#status').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('status', value);
                });
                $('#based_on').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('based_on', value);
                });
            });
        </script>
    @endpush
</div>
