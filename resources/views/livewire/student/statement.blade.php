{{-- Student view → Statement. Styled by the parent .svx system (components/student/view-premium). --}}
@php
    $types = [
        'student_topup' => ['topup', 'fa-arrow-down'],
        'student_topup_refund' => ['refund', 'fa-undo'],
        'sale' => ['purchase', 'fa-shopping-cart'],
        'saleReturn' => ['return', 'fa-undo'],
        'sale_return' => ['return', 'fa-undo'],
    ];
    $presets = ['month' => 'This month', 'last_month' => 'Last month', 'three_months' => '3 months', 'year' => 'This year'];
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

    <div class="eq mb-3">
        <div class="c">
            <div class="k">Brought forward</div>
            <div class="v">{{ currency($statement['opening']) }}</div>
        </div>
        <span class="op">+</span>
        <div class="c in">
            <div class="k"><i class="fa fa-arrow-down me-1"></i>Money in</div>
            <div class="v">{{ currency($statement['credit']) }}</div>
        </div>
        <span class="op">−</span>
        <div class="c out">
            <div class="k"><i class="fa fa-arrow-up me-1"></i>Money out</div>
            <div class="v">{{ currency($statement['debit']) }}</div>
        </div>
        <span class="op">=</span>
        <div class="c close">
            <div class="k">Closing balance</div>
            <div class="v">{{ currency($statement['closing']) }}</div>
        </div>
    </div>

    <div class="tblw">
        <div class="table-responsive">
            <table class="table tbl">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Details</th>
                        <th class="text-end">In</th>
                        <th class="text-end">Out</th>
                        <th class="text-end">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bf">
                        <td colspan="5">Balance brought forward</td>
                        <td class="text-end fw-semibold">{{ currency($statement['opening']) }}</td>
                    </tr>
                    @forelse ($statement['rows'] as $row)
                        @php
                            [$tone, $icon] = $types[$row['source']] ?? ['plain', null];
                        @endphp
                        <tr wire:key="stmt-{{ $row['id'] }}">
                            <td class="text-nowrap">{{ systemDate($row['date']) }}</td>
                            <td><span class="tc {{ $tone }}">@if ($icon)<i class="fa {{ $icon }}"></i>@endif{{ $row['type'] }}</span></td>
                            <td>
                                @if ($row['model'] === 'Sale' && $row['model_id'])
                                    <a href="{{ route('sale::view', $row['model_id']) }}" class="inv">{{ $row['description'] }}</a>
                                @else
                                    {{ $row['description'] }}
                                @endif
                            </td>
                            <td class="text-end text-success-emphasis">{{ $row['credit'] > 0 ? currency($row['credit']) : '' }}</td>
                            <td class="text-end text-danger-emphasis">{{ $row['debit'] > 0 ? currency($row['debit']) : '' }}</td>
                            <td @class(['text-end fw-semibold', 'text-danger-emphasis' => $row['balance'] < 0])>{{ currency($row['balance']) }}</td>
                        </tr>
                    @empty
                        <tr class="none">
                            <td colspan="6"><i class="fa fa-list-alt me-1"></i>No card activity in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if (count($statement['rows']))
                    <tfoot>
                        <tr>
                            <td colspan="3">Closing balance</td>
                            <td class="text-end text-success-emphasis">{{ currency($statement['credit']) }}</td>
                            <td class="text-end text-danger-emphasis">{{ currency($statement['debit']) }}</td>
                            <td class="text-end">{{ currency($statement['closing']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
