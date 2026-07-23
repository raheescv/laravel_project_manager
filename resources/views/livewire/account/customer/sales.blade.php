@php
    $billed = (float) ($totals->grand_total ?? 0);
    $paid = (float) ($totals->paid ?? 0);
    $balance = (float) ($totals->balance ?? 0);
    $paidPercent = $billed > 0 ? min(round(($paid / $billed) * 100), 100) : 0;
    $balancePercent = $billed > 0 ? min(round(($balance / $billed) * 100), 100) : 0;
    $presets = [7 => '7d', 30 => '30d', 90 => '90d', 365 => '1y', 'all' => 'All'];
@endphp

<div>
    <div class="panel">
        <div class="phead p-info">
            <span class="ic"><i class="fa fa-shopping-cart"></i></span>
            <div>
                <h4>Sales</h4>
                <span class="hint">Invoices raised against this customer</span>
            </div>
            <div class="right">
                @can('customer.view')
                    @if ($account_id)
                        <a href="{{ route('account::customer::statement', $account_id) }}@if ($from_date || $to_date)?from_date={{ $from_date }}&to_date={{ $to_date }}@endif"
                            target="_blank" class="btn sm" title="Generate Statement PDF">
                            <i class="fa fa-file-pdf-o"></i> Generate Statement
                        </a>
                    @endif
                @endcan
            </div>
        </div>

        <div class="pbody pb-0">
            <div class="row g-2">
                <div class="col-12 col-sm-4">
                    <div class="tile k1">
                        <div class="lab"><i class="fa fa-file-text-o"></i> Total Billed</div>
                        <div class="val">{{ currency($billed) }}</div>
                        <div class="bar"><i style="width: 100%"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="tile k2">
                        <div class="lab"><i class="fa fa-check"></i> Paid</div>
                        <div class="val">{{ currency($paid) }}</div>
                        <div class="bar"><i style="width: {{ $paidPercent }}%"></i></div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="tile k3">
                        <div class="lab"><i class="fa fa-clock-o"></i> Outstanding</div>
                        <div class="val">{{ currency($balance) }}</div>
                        <div class="bar"><i style="width: {{ $balancePercent }}%"></i></div>
                    </div>
                </div>
            </div>
            <p class="mt-2 mb-0 hint-text">
                Totals cover the selected period ({{ $totals->invoices ?? 0 }} {{ ($totals->invoices ?? 0) == 1 ? 'invoice' : 'invoices' }}).
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
                        <th>Invoice No</th>
                        <th class="r">Grand Total</th>
                        <th class="r">Paid</th>
                        <th class="r">Balance</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="num">{{ $sale->id }}</td>
                            <td class="num">{{ systemDate($sale->date) }}</td>
                            <td><a href="{{ route('sale::view', $sale->id) }}" target="_blank">{{ $sale->invoice_no }}</a></td>
                            <td class="r strong">{{ currency($sale->grand_total) }}</td>
                            <td class="r">{{ currency($sale->paid) }}</td>
                            <td class="r {{ $sale->balance > 0 ? 'v-warn' : 'v-ok' }}">{{ currency($sale->balance) }}</td>
                            <td>
                                <span class="stars">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="fa fa-star {{ $sale->rating >= $i ? 'f' : '' }}"></i>
                                    @endfor
                                </span>
                            </td>
                        </tr>
                        @if ($sale->feedback)
                            <tr>
                                <td colspan="7" class="pt-0 ps-4">
                                    <div class="fb"><i class="fa fa-comment-o"></i> {{ $sale->feedback }}</div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty"><i class="fa fa-file-text-o"></i> No sales found for the selected period.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($sales->count())
                    <tfoot>
                        <tr>
                            <td colspan="3">{{ $sales->count() }} of {{ $totals->invoices ?? 0 }} shown</td>
                            <td class="r">{{ currency($sales->sum('grand_total')) }}</td>
                            <td class="r">{{ currency($sales->sum('paid')) }}</td>
                            <td class="r">{{ currency($sales->sum('balance')) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
