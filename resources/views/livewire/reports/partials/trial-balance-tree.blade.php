@php
    $tree = $tree ?? [];
    $sectionName = $sectionName ?? 'section';
    $sectionColor = $sectionColor ?? 'secondary';
    $showCondition = $showCondition ?? 'true';
    $start_date = $start_date ?? '';
    $end_date = $end_date ?? '';
@endphp

@if (is_array($tree) && !empty($tree))
    @foreach ($tree as $index => $item)
        @if (isset($item['name']))
            {{-- Master Category Row --}}
            @php $itemBalance = $item['balance'] ?? ($item['debit'] - $item['credit']); @endphp
            <tr x-show="{{ $showCondition }}" x-cloak class="{{ $itemBalance < 0 ? 'table-danger bg-opacity-25' : '' }}">
                <td class="py-2 ps-4">
                    <button @click="toggleCategory('{{ $sectionName }}', {{ $item['id'] }})"
                        class="btn btn-link p-0 text-decoration-none text-dark fw-semibold d-inline-flex align-items-center">
                        <i class="pli-arrow-right me-2" :class="{ 'tb-rotate': isCategoryExpanded('{{ $sectionName }}', {{ $item['id'] }}) }"
                            style="transition: transform 0.2s; font-size: 0.7rem;"></i>
                        {{ $item['name'] }}
                    </button>
                </td>
                <td class="text-end py-2 fw-semibold text-nowrap">{{ $item['debit'] > 0 ? number_format($item['debit'], 2) : '-' }}</td>
                <td class="text-end py-2 fw-semibold text-nowrap">{{ $item['credit'] > 0 ? number_format($item['credit'], 2) : '-' }}</td>
                <td class="text-end pe-3 py-2 fw-semibold text-nowrap {{ $itemBalance < 0 ? 'text-danger' : '' }}">{{ number_format($itemBalance, 2) }}</td>
            </tr>

            {{-- Direct Accounts under Master Category --}}
            @if (!empty($item['directAccounts']))
                @foreach ($item['directAccounts'] as $account)
                    @php $acctBal = $account['balance'] ?? ($account['debit'] - $account['credit']); @endphp
                    <tr x-show="{{ $showCondition }} && isCategoryExpanded('{{ $sectionName }}', {{ $item['id'] }})" x-cloak
                        class="{{ $acctBal < 0 ? 'table-danger bg-opacity-25' : '' }}">
                        <td class="py-1" style="padding-left: 2.5rem;">
                            <span class="text-muted me-1" style="font-size: 0.5rem;">&bull;</span>
                            <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" target="_blank" class="text-decoration-none">{{ $account['name'] }}</a>
                        </td>
                        <td class="text-end py-1 text-nowrap">{{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}</td>
                        <td class="text-end py-1 text-nowrap">{{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}</td>
                        <td class="text-end pe-3 py-1 text-nowrap {{ $acctBal < 0 ? 'text-danger' : '' }}">{{ number_format($acctBal, 2) }}</td>
                    </tr>
                @endforeach
            @endif

            {{-- Groups (Sub-Categories) --}}
            @if (!empty($item['groups']))
                @foreach ($item['groups'] as $group)
                    @php $groupBal = $group['balance'] ?? ($group['debit'] - $group['credit']); @endphp
                    <tr x-show="{{ $showCondition }} && isCategoryExpanded('{{ $sectionName }}', {{ $item['id'] }})" x-cloak
                        class="{{ $groupBal < 0 ? 'table-danger bg-opacity-25' : '' }}">
                        <td class="py-1" style="padding-left: 2.5rem;">
                            <button @click="toggleGroup('{{ $sectionName }}', {{ $group['id'] }})"
                                class="btn btn-link p-0 text-decoration-none text-dark d-inline-flex align-items-center" style="font-weight: 500;">
                                <i class="pli-arrow-right me-2" :class="{ 'tb-rotate': isGroupExpanded('{{ $sectionName }}', {{ $group['id'] }}) }"
                                    style="transition: transform 0.2s; font-size: 0.65rem;"></i>
                                {{ $group['name'] }}
                            </button>
                        </td>
                        <td class="text-end py-1 text-nowrap" style="font-weight: 500;">{{ $group['debit'] > 0 ? number_format($group['debit'], 2) : '-' }}</td>
                        <td class="text-end py-1 text-nowrap" style="font-weight: 500;">{{ $group['credit'] > 0 ? number_format($group['credit'], 2) : '-' }}</td>
                        <td class="text-end pe-3 py-1 text-nowrap {{ $groupBal < 0 ? 'text-danger' : '' }}" style="font-weight: 500;">{{ number_format($groupBal, 2) }}</td>
                    </tr>

                    {{-- Accounts under Group --}}
                    @if (!empty($group['accounts']))
                        @foreach ($group['accounts'] as $account)
                            @php $acctBal = $account['balance'] ?? ($account['debit'] - $account['credit']); @endphp
                            <tr x-show="{{ $showCondition }} && isCategoryExpanded('{{ $sectionName }}', {{ $item['id'] }}) && isGroupExpanded('{{ $sectionName }}', {{ $group['id'] }})" x-cloak
                                class="{{ $acctBal < 0 ? 'table-danger bg-opacity-25' : '' }}">
                                <td class="py-1" style="padding-left: 4rem;">
                                    <span class="text-muted me-1" style="font-size: 0.5rem;">&bull;</span>
                                    <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" target="_blank" class="text-decoration-none">{{ $account['name'] }}</a>
                                </td>
                                <td class="text-end py-1 text-nowrap">{{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}</td>
                                <td class="text-end py-1 text-nowrap">{{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}</td>
                                <td class="text-end pe-3 py-1 text-nowrap {{ $acctBal < 0 ? 'text-danger' : '' }}">{{ number_format($acctBal, 2) }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            @endif

        @elseif ($index === 'uncategorized' && is_array($item))
            {{-- Uncategorized Accounts --}}
            @foreach ($item as $account)
                @php $acctBal = $account['balance'] ?? ($account['debit'] - $account['credit']); @endphp
                <tr x-show="{{ $showCondition }}" x-cloak class="{{ $acctBal < 0 ? 'table-danger bg-opacity-25' : '' }}">
                    <td class="py-1 ps-4">
                        <span class="text-muted me-1" style="font-size: 0.5rem;">&bull;</span>
                        <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" target="_blank" class="text-decoration-none">{{ $account['name'] }}</a>
                    </td>
                    <td class="text-end py-1 text-nowrap">{{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}</td>
                    <td class="text-end py-1 text-nowrap">{{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}</td>
                    <td class="text-end pe-3 py-1 text-nowrap {{ $acctBal < 0 ? 'text-danger' : '' }}">{{ number_format($acctBal, 2) }}</td>
                </tr>
            @endforeach
        @endif
    @endforeach
@endif

{{-- Section Total --}}
@php $sectionBalance = $totalDebit - $totalCredit; @endphp
<tr class="border-top {{ $sectionBalance < 0 ? 'table-danger bg-opacity-25' : '' }}" x-show="{{ $showCondition }}" x-cloak style="border-left: 3px solid var(--bs-{{ $sectionColor }});">
    <td class="py-2 ps-3 fw-bold small">Total {{ ucfirst($sectionName) }}</td>
    <td class="text-end py-2 fw-bold text-nowrap">{{ number_format($totalDebit, 2) }}</td>
    <td class="text-end py-2 fw-bold text-nowrap">{{ number_format($totalCredit, 2) }}</td>
    <td class="text-end pe-3 py-2 fw-bold text-nowrap {{ $sectionBalance < 0 ? 'text-danger' : '' }}">{{ number_format($sectionBalance, 2) }}</td>
</tr>
