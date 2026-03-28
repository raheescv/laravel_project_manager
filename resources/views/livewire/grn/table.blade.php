<div x-data="{
    selected: @entangle('selected'),
    items: @js($this->grns->pluck('id')),
    selectAll: false,

    toggleSelectAll() {
        if (this.selectAll) {
            this.selected = this.items;
        } else {
            this.selected = [];
        }
    },

    init() {
        this.$watch('selected', (value) => {
            this.selectAll = value.length === this.items.length;
        });
    }
}">
    <div class="bg-white card-header">
        <div class="row g-3">
            <div class="col-md-4 d-flex align-items-center">
                <div class="btn-group">
                    @can('grn.create')
                        <a class="btn btn-primary d-flex align-items-center shadow-sm" href="{{ route('grn::create') }}">
                            <i class="demo-psi-add me-2"></i>
                            Create
                        </a>
                    @endcan
                    @can('grn.delete')
                        <button class="btn btn-sm btn-outline-danger" title="Delete selected items" wire:click="delete()"
                            wire:confirm="Are you sure you want to delete the selected items?" x-show="selected.length > 0">
                            <i class="demo-pli-recycling me-1"></i> Delete
                        </button>
                    @endcan
                </div>
            </div>
            <div class="col-md-8">
                <div class="gap-2 d-flex justify-content-md-end align-items-center">
                    <div class="form-group">
                        <select wire:model.live="limit" class="form-select form-select-sm">
                            <option value="10">10 rows</option>
                            <option value="100">100 rows</option>
                            <option value="500">500 rows</option>
                        </select>
                    </div>
                    <div class="form-group" style="width: 250px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-end-0">
                                <i class="demo-pli-magnifi-glass"></i>
                            </span>
                            <input type="text" wire:model.live.debounce.500ms="search" class="form-control border-start-0"
                                placeholder="Search GRNs..." autofocus>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $user = auth()->user();
        @endphp
        @use('App\Enums\Grn\GrnStatus')

        <hr class="mt-3 mb-0">
        <div class="mt-3 col-12">
            <div class="p-3 rounded bg-light">
                <div class="row g-3">
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label" for="branch_id">
                            <i class="demo-psi-home me-1"></i> Branch
                        </label>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All Branches') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label" for="vendor_id">
                            <i class="fa fa-building me-1"></i> Vendor
                        </label>
                        {{ html()->select('vendor_id', [])->value($this->vendor_id)->class('select-vendor_id-list')->id('vendor_id')->placeholder('All Vendors') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label" for="status">
                            <i class="fa fa-flag me-1"></i> Status
                        </label>
                        {{ html()->select('status', GrnStatus::values())->value($status)->class('form-control form-control-sm')->id('status')->placeholder('All Statuses') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label" for="local_purchase_order_id">
                            <i class="fa fa-shopping-cart me-1"></i> LPO
                        </label>
                        {{ html()->select('local_purchase_order_id', [])->value($this->local_purchase_order_id)->class('select-lpo_id-list')->id('local_purchase_order_id')->placeholder('All LPOs') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label" for="created_by">
                            <i class="fa fa-user me-1"></i> Created By
                        </label>
                        {{ html()->select('created_by', [])->value($this->created_by)->class('select-user_id-list')->id('created_by')->placeholder('All Users') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label" for="decision_by">
                            <i class="fa fa-user-check me-1"></i> Decision By
                        </label>
                        {{ html()->select('decision_by', [])->value($this->decision_by)->class('select-user_id-list')->id('decision_by')->placeholder('All Users') }}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="from_date">
                            <i class="demo-psi-calendar-4 me-1"></i> From Date
                        </label>
                        <input type="date" wire:model.live="from_date" class="form-control form-control-sm" id="from_date">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="to_date">
                            <i class="demo-psi-calendar-4 me-1"></i> To Date
                        </label>
                        <input type="date" wire:model.live="to_date" class="form-control form-control-sm" id="to_date">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="px-0 pb-0 card-body">
        <div class="table-responsive" style="overflow: visible;">
            <table class="table mb-0 align-middle table-striped table-hover table-sm border-bottom">
                <thead class="bg-light text-nowrap">
                    <tr>
                        <th class="ps-3">
                            <div class="d-flex align-items-center">
                                <div class="form-check me-2">
                                    <input type="checkbox" class="form-check-input" x-on:change="toggleSelectAll()" x-model="selectAll"
                                        id="selectAll">
                                    <label class="form-check-label" for="selectAll"></label>
                                </div>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                            </div>
                        </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="grn_no" label="GRN No" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" /> </th>
                        <th> LPO </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="vendor_id" label="Vendor" /> </th>
                        <th> Items </th>
                        <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="Status" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_by" label="Created By" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="decision_by" label="Decided By" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_at" label="Created At" /> </th>
                        <th> Actions </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->grns as $item)
                        <tr>
                            <td class="ps-3">
                                <div class="gap-2 d-flex align-items-center">
                                    <div class="mb-0 form-check">
                                        <input type="checkbox" class="form-check-input" value="{{ $item->id }}" x-model="selected"
                                            id="checkbox_{{ $item->id }}">
                                    </div>
                                    <span class="text-muted">#{{ $item->id }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('grn::view', $item->id) }}" class="fw-medium">
                                    {{ $item->grn_no }}
                                </a>
                            </td>
                            <td class="text-nowrap">
                                <div class="gap-2 d-flex align-items-center">
                                    <i class="demo-psi-calendar-4 fs-5 text-primary"></i>
                                    <span>{{ systemDate($item->date) }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                LPO #{{ $item->localPurchaseOrder?->id }}
                            </td>
                            <td class="text-nowrap">
                                {{ $item->vendor?->name ?? '-' }}
                            </td>
                            <td class="text-end"> {{ $item->items_count }} </td>
                            <td>
                                <div
                                    class="badge bg-{{ $item->status === GrnStatus::ACCEPTED ? 'success' : ($item->status === GrnStatus::PENDING ? 'warning' : 'danger') }} bg-opacity-10 text-{{ $item->status === GrnStatus::ACCEPTED ? 'success' : ($item->status === GrnStatus::PENDING ? 'warning' : 'danger') }}">
                                    {{ $item->status->label() }}
                                </div>
                            </td>
                            <td class="text-nowrap">
                                {{ $item->creator?->name }}
                            </td>
                            <td class="text-nowrap">
                                {{ $item->decisionMaker?->name }}
                            </td>
                            <td class="text-nowrap">
                                <div class="gap-2 d-flex align-items-center">
                                    <i class="demo-psi-calendar-4 fs-5 text-primary"></i>
                                    <span>{{ systemDateTime($item->created_at) }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @can('grn.view', $item)
                                            <li>
                                                <a class="dropdown-item" href="{{ route('grn::view', $item->id) }}">
                                                    <i class="demo-pli-magnifi-glass me-2"></i> View
                                                </a>
                                            </li>
                                        @endcan
                                        @if (!$item->decision_at)
                                            @can('grn.create', $item)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('grn::edit', $item->id) }}">
                                                        <i class="demo-pli-file-edit me-2"></i> Edit
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('grn.decide', $item)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('grn::decision', $item->id) }}">
                                                        <i class="demo-pli-check me-2"></i> Accept/Reject
                                                    </a>
                                                </li>
                                            @endcan
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $this->grns->links() }}
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
                $('#vendor_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('vendor_id', value);
                });
                $('#local_purchase_order_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('local_purchase_order_id', value);
                });
                $('#created_by').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('created_by', value);
                });
                $('#decision_by').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('decision_by', value);
                });
            });
        </script>
    @endpush
</div>
