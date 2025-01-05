<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                @can('account.create')
                    <button class="btn btn-primary hstack gap-2 align-self-center" id="AccountAdd">
                        <i class="demo-psi-add fs-5"></i>
                        <span class="vr"></span>
                        Add New
                    </button>
                @endcan
                <div class="btn-group">
                    @can('account.export')
                        <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
                    @endcan
                    @can('account.delete')
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
                <div class="btn-group">
                    @can('account.import')
                        <button class="btn btn-icon btn-outline-light" data-bs-toggle="modal" data-bs-target="#AccountImportModal">
                            <i class="demo-pli-download-from-cloud fs-5"></i>
                        </button>
                    @endcan
                </div>
            </div>
        </div>
        <hr>
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-3" wire:ignore>
                    {{ html()->select('account_type', accountTypes())->value('')->class('tomSelect')->id('account_type')->placeholder('Account Type') }}
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
                            <a href="#" wire:click.prevent="sortBy('id')">
                                #
                                @if ($sortField === 'id')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('account_type')">
                                Account Type
                                @if ($sortField === 'account_type')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('name')">
                                Name
                                @if ($sortField === 'name')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th class="text-end">
                            Action
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
                            <td>{{ ucFirst($item->account_type) }}</td>
                            <td>{{ $item->name }}</td>
                            <td class="text-end">
                                @can('account.edit')
                                    <i table_id="{{ $item->id }}" class="demo-psi-pencil fs-5 me-2 pointer edit"></i>
                                @endcan
                            </td>
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
                $('#account_type').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('account_type', value);
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
</div>
