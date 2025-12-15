<div>
    <div class="card-header bg-light py-3">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="d-flex gap-2 align-items-center">
                    @can('account.create')
                        <button class="btn btn-primary d-inline-flex gap-2 align-items-center shadow-sm" id="AccountAdd">
                            <i class="demo-psi-add fs-5"></i>
                            <span class="vr my-1"></span>
                            Add New
                        </button>
                    @endcan
                    <div class="btn-group shadow-sm">
                        @can('account.export')
                            <button class="btn btn-success" title="Export as Excel" wire:click="export()">
                                <i class="demo-pli-file-excel fs-5"></i>
                            </button>
                        @endcan
                        @can('account.delete')
                            <button class="btn btn-danger" title="Delete selected items" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                                <i class="demo-pli-recycling fs-5"></i>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2 justify-content-md-end">
                    <select wire:model.live="limit" class="form-select w-auto shadow-sm">
                        <option value="10">10 rows</option>
                        <option value="100">100 rows</option>
                        <option value="500">500 rows</option>
                    </select>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="demo-pli-magnifi-glass"></i>
                        </span>
                        <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search..." autofocus autocomplete="off">
                    </div>
                    @can('account.import')
                        <button class="btn btn-light shadow-sm" data-bs-toggle="modal" data-bs-target="#AccountImportModal" title="Import Data">
                            <i class="demo-pli-download-from-cloud fs-5"></i>
                        </button>
                    @endcan
                </div>
            </div>
        </div>
        <hr class="my-3">
        <div class="row">
            <div class="col-md-4" wire:ignore>
                {{ html()->select('account_type', accountTypes())->value('')->class('tomSelect')->id('account_type')->placeholder('Select Account Type') }}
            </div>
            <div class="col-md-4" wire:ignore>
                {{ html()->select('account_category_id', [])->value('')->class('select-account_category_id')->id('account_category_id')->placeholder('Select account category') }}
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="text-capitalize">
                        <th width="5%" class="text-nowrap">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                            </div>
                        </th>
                        <th width="10%" class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_type" label="account type" /> </th>
                        <th width="10%" class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_category_id" label="account category" /> </th>
                        <th width="30%" class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="name" /> </th>
                        <th width="10%" class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="alias_name" label="alias name" /> </th>
                        <th width="40%" class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="description" label="description" /> </th>
                        <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="model" label="model" /> </th>
                        <th class="text-end px-3"> Action </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td class="px-3 text-nowrap">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected">
                                    <label class="form-check-label">{{ $item->id }}</label>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">{{ ucFirst($item->account_type) }}</span>
                            </td>
                            <td> {{ $item->accountCategory?->name }} </td>
                            <td>
                                <a href="{{ route('account::view', $item->id) }}" class="text-decoration-none">{{ $item->name }}</a>
                            </td>
                            <td>
                                <a href="{{ route('account::view', $item->id) }}" class="text-decoration-none">{{ $item->alias_name }}</a>
                            </td>
                            <td class="text-muted">{{ $item->description }}</td>
                            <td>
                                <span class="badge bg-light text-dark">{{ ucFirst($item->model) }}</span>
                            </td>
                            <td class="text-end px-3">
                                @can('account.edit')
                                    <button class="btn btn-light btn-sm edit" title="Edit" table_id="{{ $item->id }}">
                                        <i class="demo-psi-pencil fs-5"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">
            {{ $data->links() }}
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#account_type').on('change', function(e) {
                const value = $(this).val() || null;
                @this.set('account_type', value);
            });
            $('#account_category_id').on('change', function(e) {
                const value = $(this).val() || null;
                @this.set('account_category_id', value);
            });

            $(document).on('click', '.edit', function() {
                Livewire.dispatch("Account-Page-Update-Component", {
                    id: $(this).attr('table_id')
                });
            });

            $('#AccountAdd').click(function() {
                Livewire.dispatch("Account-Page-Create-Component");
            });

            window.addEventListener('RefreshAccountTable', event => {
                Livewire.dispatch("Account-Refresh-Component");
            });
        });
    </script>
@endpush
