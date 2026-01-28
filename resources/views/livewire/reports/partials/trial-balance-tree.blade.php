@php
    $tree = $tree ?? [];
    $sectionName = $sectionName ?? 'section';
    $showCondition = $showCondition ?? 'true';
    $start_date = $start_date ?? '';
    $end_date = $end_date ?? '';
@endphp

@if (is_array($tree) && !empty($tree))
    @foreach ($tree as $index => $item)
        @if (isset($item['name']))
            {{-- Master Category --}}
            @php
                $itemBalance = $item['balance'] ?? ($item['debit'] - $item['credit']);
            @endphp
            <tr class="bg-light/20 {{ $itemBalance < 0 ? 'bg-red-100' : '' }}" x-show="{{ $showCondition }}">
                <td class="py-2 fw-semibold" style="padding-left: 2rem;">
                    <button @click="toggleCategory('{{ $sectionName }}', {{ $item['id'] }})" class="btn btn-link p-0 text-decoration-none text-dark fw-semibold d-inline-flex align-items-center">
                        <i class="pli-arrow-right me-2" :class="{ 'rotate-90': isCategoryExpanded('{{ $sectionName }}', {{ $item['id'] }}) }"
                            style="transition: transform 0.2s; font-size: 0.8rem; width: 1rem; text-align: center;"></i>
                        <span>{{ $item['name'] }}</span>
                    </button>
                </td>
                <td class="text-end py-2">
                    <span class="fw-semibold">{{ $item['debit'] > 0 ? number_format($item['debit'], 2) : '-' }}</span>
                </td>
                <td class="text-end py-2">
                    <span class="fw-semibold">{{ $item['credit'] > 0 ? number_format($item['credit'], 2) : '-' }}</span>
                </td>
                <td class="text-end pe-4 py-2">
                    <span class="fw-semibold">{{ number_format($itemBalance, 2) }}</span>
                </td>
            </tr>

            {{-- Direct Accounts under Master Category --}}
            @if (!empty($item['directAccounts']))
                @foreach ($item['directAccounts'] as $account)
                    @php
                        $accountBalance = $account['balance'] ?? ($account['debit'] - $account['credit']);
                    @endphp
                    <tr class="hover:bg-light/30 transition-colors {{ $accountBalance < 0 ? 'bg-red-100' : '' }}" x-show="{{ $showCondition }} && isCategoryExpanded('{{ $sectionName }}', {{ $item['id'] }})">
                        <td class="py-1" style="padding-left: 3rem;">
                            <i class="pli-dot me-2 text-muted" style="font-size: 0.6rem; width: 1rem; display: inline-block; text-align: center;"></i>
                            <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}">{{ $account['name'] }}</a>
                        </td>
                        <td class="text-end py-1">
                            {{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}
                        </td>
                        <td class="text-end py-1">
                            {{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}
                        </td>
                        <td class="text-end pe-4 py-1">
                            {{ number_format($accountBalance, 2) }}
                        </td>
                    </tr>
                @endforeach
            @endif

            {{-- Groups (Sub-Categories) under Master Category --}}
            @if (!empty($item['groups']))
                @foreach ($item['groups'] as $group)
                    @php
                        $groupBalance = $group['balance'] ?? ($group['debit'] - $group['credit']);
                    @endphp
                    <tr class="bg-light/10 {{ $groupBalance < 0 ? 'bg-red-100' : '' }}" x-show="{{ $showCondition }} && isCategoryExpanded('{{ $sectionName }}', {{ $item['id'] }})">
                        <td class="py-2 fw-medium" style="padding-left: 3rem;">
                            <button @click="toggleGroup('{{ $sectionName }}', {{ $group['id'] }})"
                                class="btn btn-link p-0 text-decoration-none text-dark fw-medium d-inline-flex align-items-center">
                                <i class="pli-arrow-right me-2" :class="{ 'rotate-90': isGroupExpanded('{{ $sectionName }}', {{ $group['id'] }}) }"
                                    style="transition: transform 0.2s; font-size: 0.75rem; width: 1rem; text-align: center;"></i>
                                <span>{{ $group['name'] }}</span>
                            </button>
                        </td>
                        <td class="text-end py-2">
                            <span class="fw-medium">{{ $group['debit'] > 0 ? number_format($group['debit'], 2) : '-' }}</span>
                        </td>
                        <td class="text-end py-2">
                            <span class="fw-medium">{{ $group['credit'] > 0 ? number_format($group['credit'], 2) : '-' }}</span>
                        </td>
                        <td class="text-end pe-4 py-2">
                            <span class="fw-medium">{{ number_format($groupBalance, 2) }}</span>
                        </td>
                    </tr>

                    {{-- Accounts under Group --}}
                    @if (!empty($group['accounts']))
                        @foreach ($group['accounts'] as $account)
                            @php
                                $accountBalance = $account['balance'] ?? ($account['debit'] - $account['credit']);
                            @endphp
                            <tr class="hover:bg-light/30 transition-colors {{ $accountBalance < 0 ? 'bg-red-100' : '' }}"
                                x-show="{{ $showCondition }} && isCategoryExpanded('{{ $sectionName }}', {{ $item['id'] }}) && isGroupExpanded('{{ $sectionName }}', {{ $group['id'] }})">
                                <td class="py-1" style="padding-left: 4.5rem;">
                                    <i class="pli-dot me-2 text-muted" style="font-size: 0.6rem; width: 1rem; display: inline-block; text-align: center;"></i>
                                    <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}">{{ $account['name'] }}</a>
                                </td>
                                <td class="text-end py-1">
                                    {{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}
                                </td>
                                <td class="text-end pe-4 py-1">
                                    {{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            @endif
        @elseif ($index === 'uncategorized' && is_array($item))
            {{-- Uncategorized Accounts --}}
            @foreach ($item as $account)
                @php
                    $accountBalance = $account['balance'] ?? ($account['debit'] - $account['credit']);
                @endphp
                <tr class="hover:bg-light/30 transition-colors {{ $accountBalance < 0 ? 'bg-red-100' : '' }}" x-show="{{ $showCondition }}">
                    <td class="py-1" style="padding-left: 2rem;">
                        <i class="pli-dot me-2 text-muted" style="font-size: 0.6rem; width: 1rem; display: inline-block; text-align: center;"></i>
                        <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}">{{ $account['name'] }}</a>
                    </td>
                    <td class="text-end py-1">
                        {{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}
                    </td>
                    <td class="text-end pe-4 py-1">
                        {{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}
                    </td>
                </tr>
            @endforeach
        @endif
    @endforeach
@endif

{{-- Section Total --}}
@php
    $sectionBalance = $totalDebit - $totalCredit;
@endphp
<tr class="border-top bg-light/40 {{ $sectionBalance < 0 ? 'bg-red-100' : '' }}" x-show="{{ $showCondition }}">
    <td class="py-2 fw-bold" style="padding-left: 1rem;">
        Total {{ ucfirst($sectionName) }}
    </td>
    <td class="text-end py-2 fw-bold">
        {{ number_format($totalDebit, 2) }}
    </td>
    <td class="text-end py-2 fw-bold">
        {{ number_format($totalCredit, 2) }}
    </td>
    <td class="text-end pe-4 py-2 fw-bold">
        {{ number_format($sectionBalance, 2) }}
    </td>
</tr>

<style>
    .rotate-90 {
        transform: rotate(90deg);
    }
</style>
