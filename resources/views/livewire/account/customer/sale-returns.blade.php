@php
    $returned = (float) ($totals->grand_total ?? 0);
    $refunded = (float) ($totals->paid ?? 0);
    $pending = (float) ($totals->balance ?? 0);
    $refundedPercent = $returned > 0 ? min(round(($refunded / $returned) * 100), 100) : 0;
    $pendingPercent = $returned > 0 ? min(round(($pending / $returned) * 100), 100) : 0;
    $presets = [7 => '7d', 30 => '30d', 90 => '90d', 365 => '1y', 'all' => 'All'];
@endphp

<div>
    <div class="panel">
        <div class="phead p-warn">
            <span class="ic"><i class="fa fa-undo"></i></span>
            <div>
                <h4>Sales Return</h4>
                <span class="hint">Goods returned by this customer</span>
            </div>
        </div>

        <div class="pbody pb-0">
            <div class="row g-2">
                <div class="col-12 col-sm-4">
                    <div class="tile k1">
                        <div class="lab"><i class="fa fa-file-text-o"></i> Total Returned</div>
                        <div class="val">{{ currency($returned) }}</div>
                        <div class="bar"><i style="width: 100%"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="tile k2">
                        <div class="lab"><i class="fa fa-check"></i> Refunded</div>
                        <div class="val">{{ currency($refunded) }}</div>
                        <div class="bar"><i style="width: {{ $refundedPercent }}%"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="tile k3">
                        <div class="lab"><i class="fa fa-clock-o"></i> Pending</div>
                        <div class="val">{{ currency($pending) }}</div>
                        <div class="bar"><i style="width: {{ $pendingPercent }}%"></i></div>
                    </div>
                </div>
            </div>
            <p class="mt-2 mb-0 hint-text">
                Totals cover the selected period ({{ $totals->returns_count ?? 0 }} {{ ($totals->returns_count ?? 0) == 1 ? 'return' : 'returns' }}).
            </p>
        </div>

        <div class="filters mt-3 border-top">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3 fld">
                    <label>From Date</label>
                    <input type="date" wire:model.live="from_date">
                </div>
                <div class="col-6 col-md-3 fld">
                    <label>To Date</label>
                    <input type="date" wire:model.live="to_date">
                </div>
                <div class="col-6 col-md-2 fld">
                    <label>Rows</label>
                    <select wire:model.live="limit">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
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
                        <th>Reference No</th>
                        <th class="r">Grand Total</th>
                        <th class="r">Refunded</th>
                        <th class="r">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sale_returns as $return)
                        <tr>
                            <td class="num">{{ $return->id }}</td>
                            <td class="num">{{ systemDate($return->date) }}</td>
                            <td><a href="{{ route('sale_return::view', $return->id) }}" target="_blank">{{ $return->reference_no ?: $return->id }}</a></td>
                            <td class="r strong">{{ currency($return->grand_total) }}</td>
                            <td class="r">{{ currency($return->paid) }}</td>
                            <td class="r {{ $return->balance > 0 ? 'v-warn' : 'v-ok' }}">{{ currency($return->balance) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty"><i class="fa fa-undo"></i> No sales returns found for the selected period.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($sale_returns->count())
                    <tfoot>
                        <tr>
                            <td colspan="3">{{ $sale_returns->count() }} of {{ $totals->returns_count ?? 0 }} shown</td>
                            <td class="r">{{ currency($sale_returns->sum('grand_total')) }}</td>
                            <td class="r">{{ currency($sale_returns->sum('paid')) }}</td>
                            <td class="r">{{ currency($sale_returns->sum('balance')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
