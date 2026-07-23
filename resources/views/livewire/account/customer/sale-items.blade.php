@php
    $presets = [7 => '7d', 30 => '30d', 90 => '90d', 365 => '1y', 'all' => 'All'];
@endphp

<div>
    <div class="panel">
        <div class="phead p-plum">
            <span class="ic"><i class="fa fa-cube"></i></span>
            <div>
                <h4>Sale Items</h4>
                <span class="hint">Line-level detail of everything sold</span>
            </div>
            <div class="right">
                <span class="tag mute">{{ $total_lines }} {{ $total_lines == 1 ? 'line' : 'lines' }}</span>
            </div>
        </div>

        <div class="filters">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3 col-xl-2 fld">
                    <label>From Date</label>
                    <input type="date" wire:model.live="from_date">
                </div>
                <div class="col-6 col-md-3 col-xl-2 fld">
                    <label>To Date</label>
                    <input type="date" wire:model.live="to_date">
                </div>
                <div class="col-6 col-md-2 col-xl-1 fld">
                    <label>Rows</label>
                    <select wire:model.live="limit">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Product</label>
                    <input type="text" wire:model.live.debounce.400ms="search" placeholder="Filter by product name…">
                </div>
                <div class="col-12 col-xl-4">
                    <div class="quick">
                        @foreach ($presets as $days => $label)
                            <button type="button" class="@if ((string) $preset === (string) $days) active @endif"
                                wire:click="applyPreset('{{ $days }}')">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="tw">
            <table class="t">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Invoice</th>
                        <th>Employee</th>
                        <th>Product</th>
                        <th>Unit</th>
                        <th class="r">Quantity</th>
                        <th class="r">Base Qty</th>
                        <th class="r">Total</th>
                        <th class="r">Effective</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sale_items as $item)
                        <tr>
                            <td class="num">{{ $item->id }}</td>
                            <td class="num">{{ $item->sale?->date ? systemDate($item->sale->date) : '—' }}</td>
                            <td><a href="{{ route('sale::view', $item->sale_id) }}" target="_blank">{{ $item->sale?->invoice_no }}</a></td>
                            <td>{{ $item->employee?->name ?: '—' }}</td>
                            <td>{{ $item->product?->name ?: '—' }}</td>
                            <td>{{ $item->unit?->name ?: '—' }}</td>
                            <td class="r">{{ currency($item->quantity) }}</td>
                            <td class="r">{{ currency($item->base_unit_quantity) }}</td>
                            <td class="r strong">{{ currency($item->total) }}</td>
                            <td class="r">{{ currency($item->effective_total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="empty"><i class="fa fa-cube"></i> No sale items found for the selected filters.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($sale_items->count())
                    <tfoot>
                        <tr>
                            <td colspan="8">{{ $sale_items->count() }} of {{ $total_lines }} lines</td>
                            <td class="r">{{ currency($sale_items->sum('total')) }}</td>
                            <td class="r">{{ currency($sale_items->sum('effective_total')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
