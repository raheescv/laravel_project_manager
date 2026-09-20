{{-- Student view → Purchases. Styled by the parent .svx system (components/student/view-premium). --}}
@php
    $presets = ['month' => 'This month', 'last_month' => 'Last month', 'three_months' => '3 months', 'all' => 'All time'];
    $statusTone = ['completed' => 'success', 'draft' => 'pending', 'cancelled' => 'failed'];
@endphp
<div>
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <div class="presets" role="group" aria-label="Period">
            @foreach ($presets as $key => $label)
                <button type="button" @class(['on' => $preset === $key]) wire:click="applyPreset('{{ $key }}')">{{ $label }}</button>
            @endforeach
        </div>
        <div class="range">
            <input type="date" class="form-control form-control-sm" wire:model.live="from_date" aria-label="From date">
            <span class="text-body-secondary">→</span>
            <input type="date" class="form-control form-control-sm" wire:model.live="to_date" aria-label="To date">
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="mini">
                <div class="k">Purchases</div>
                <div class="v">{{ $bills }} <small>{{ $bills === 1 ? 'bill' : 'bills' }}</small></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini">
                <div class="k">Spent</div>
                <div class="v">{{ currency($total) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini">
                <div class="k">Average bill</div>
                <div class="v">{{ currency($bills ? $total / $bills : 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini">
                <div class="k">Buys most at</div>
                <div class="v">{{ $topBranch ?: '-' }} @if ($topBranch)<small>· {{ $topBranchBills }}</small>@endif</div>
            </div>
        </div>
    </div>

    <div class="tblw">
        <div class="table-responsive">
            <table class="table tbl">
                <thead>
                    <tr>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="invoice_no" label="Invoice" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branch" label="Branch" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="items" label="Items" /></th>
                        <th>Paid with</th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="Status" /></th>
                        <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="grand_total" label="Amount" /></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr wire:key="purchase-{{ $sale->id }}">
                            <td class="text-nowrap">{{ systemDate($sale->date) }}</td>
                            <td class="text-nowrap">
                                @can('sale.view')
                                    <a href="{{ route('sale::view', $sale->id) }}" class="inv mono">{{ $sale->invoice_no }}</a>
                                @else
                                    <span class="mono">{{ $sale->invoice_no }}</span>
                                @endcan
                            </td>
                            <td>{{ $sale->branch?->name }}</td>
                            <td class="text-nowrap">{{ $sale->items_count }} {{ $sale->items_count === 1 ? 'item' : 'items' }}</td>
                            <td>
                                @foreach ($sale->payments->pluck('paymentMethod.name')->filter()->unique() as $method)
                                    <span class="tc plain">{{ $method }}</span>
                                @endforeach
                            </td>
                            <td><span class="sp {{ $statusTone[$sale->status] ?? '' }}">{{ ucfirst($sale->status) }}</span></td>
                            <td class="text-end fw-semibold">{{ currency($sale->grand_total) }}</td>
                        </tr>
                    @empty
                        <tr class="none">
                            <td colspan="7"><i class="fa fa-shopping-cart me-1"></i>No purchases in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $sales->links() }}
</div>
