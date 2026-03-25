<div x-data="{
    selected: @entangle('selected'),
    items: @js($this->requests->pluck('id')),
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
                    @can('purchase request.delete-any')
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
                            <input type="text" wire:model.live.debounce.500ms="search"
                                class="form-control border-start-0" placeholder="Search purchase requests..." autofocus>
                        </div>
                    </div>
                    {{-- <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="demo-pli-layout-grid me-1"></i> Columns
                        </button>
                        <ul class="shadow-sm dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" data-bs-toggle="offcanvas"
                                    data-bs-target="#purchaseColumnVisibility" aria-controls="purchaseColumnVisibility">
                                    <i class="demo-pli-column-width me-2"></i>Column Visibility
                                </a>
                            </li>
                        </ul>
                    </div> --}}
                </div>
            </div>
        </div>

        @php
            $user = auth()->user();
        @endphp
        @use('App\Enums\PurchaseRequest\PurchaseRequestStatus')

        <hr class="mt-3 mb-0">
        <div class="mt-3 col-12">
            <div class="p-3 rounded bg-light">
                <div class="row g-3">
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label" for="branch_id">
                            <i class="demo-psi-home me-1"></i> Branch
                        </label>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->class('select-assigned-branch_id-list form-control form-control-s')->id('branch_id')->placeholder('All Branches') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label" for="status">
                            <i class="fa fa-flag me-1"></i> Status
                        </label>
                        {{ html()->select('status', PurchaseRequestStatus::values())->value($status)->class('form-control form-control-sm')->id('status')->placeholder('All Statuses') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="px-0 pb-0 card-body">
        <div class="table-responsive">
            <table class="table mb-0 align-middle table-striped table-hover table-sm border-bottom">
                <thead class="bg-light text-nowrap">
                    <tr>
                        <th class="ps-3">
                            <div class="d-flex align-items-center">
                                <div class="form-check me-2">
                                    <input type="checkbox" class="form-check-input" x-on:change="toggleSelectAll()"
                                        x-model="selectAll" id="selectAll">
                                    <label class="form-check-label" for="selectAll"></label>
                                </div>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                            </div>
                        </th>
                        <th>
                            Products
                        </th>
                        <th class="text-nowrap">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="Status" />
                        </th>
                        <th>
                            Approved/Rejected By
                        </th>
                        <th>
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="decision_at"
                                label="Approved/Rejected On" />
                        </th>
                        <th>
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_at"
                                label="Purchase Request Date" />
                        </th>
                        <th>
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->requests as $item)
                        <tr>
                            <td class="ps-3">
                                <div class="gap-2 d-flex align-items-center">
                                    <div class="mb-0 form-check">
                                        <input type="checkbox" class="form-check-input" value="{{ $item->id }}"
                                            x-model="selected" id="checkbox_{{ $item->id }}">
                                    </div>
                                    <span class="text-muted">#{{ $item->id }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="gap-2 d-flex align-items-center">
                                    <span>{{ $item->products_count }}</span>
                                </div>
                            </td>
                            <td>
                                <div
                                    class="badge bg-{{ $item->status === PurchaseRequestStatus::APPROVED ? 'success' : ($item->status === PurchaseRequestStatus::PENDING ? 'warning' : 'danger') }} bg-opacity-10 text-{{ $item->status === PurchaseRequestStatus::APPROVED ? 'success' : ($item->status === PurchaseRequestStatus::PENDING ? 'warning' : 'danger') }}">
                                    {{ $item->status->label() }}
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="gap-2 d-flex align-items-center">
                                    <span>{{ $item->decisionMaker?->name }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                @if ($item->decision_at)
                                    <div class="gap-2 d-flex align-items-center">
                                        <i class="demo-psi-calendar-4 fs-5 text-primary"></i>
                                        <span>{{ systemDateTime($item->decision_at) }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <div class="gap-2 d-flex align-items-center">
                                    <i class="demo-psi-calendar-4 fs-5 text-primary"></i>
                                    <span>{{ systemDateTime($item->created_at) }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="gap-2 d-flex align-items-center">
                                    @can('update', $item)
                                        <a href="{{ route('purchase-request::edit', $item->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="demo-pli-file-edit me-1"></i> Edit
                                        </a>
                                    @endcan

                                    @can('decide', $item)
                                        <a href="{{ route('purchase-request::decision', $item->id) }}"
                                            class="btn btn-sm btn-outline-success">
                                            <i class="demo-pli-check me-1"></i> Approve/Reject
                                        </a>
                                    @endcan

                                    @can('view', $item)
                                        <a href="{{ route('purchase-request::view', $item->id) }}"
                                            class="btn btn-sm btn-outline-info">
                                            <i class="demo-pli-magnifi-glass me-1"></i> View
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $this->requests->links() }}
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
            });
        </script>
    @endpush
</div>
