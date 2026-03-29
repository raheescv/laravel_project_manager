{{-- Reusable balance sheet category section --}}
@php
    $categories = $categories ?? [];
    $end_date = $end_date ?? '';
    $color = $color ?? 'secondary';
@endphp

@foreach ($categories as $category)
    @if (isset($category['name']))
        {{-- Category Header --}}
        <div class="d-flex justify-content-between align-items-center py-1">
            <strong class="text-{{ $color }} small">{{ $category['name'] }}</strong>
            <strong class="text-{{ $color }} small text-nowrap">{{ currency(abs($category['total'] ?? 0)) }}</strong>
        </div>

        {{-- Direct Accounts --}}
        @if (!empty($category['directAccounts']))
            @foreach ($category['directAccounts'] as $account)
                <div class="d-flex justify-content-between py-1 ps-3">
                    <a href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}" target="_blank" class="text-decoration-none text-muted small">{{ $account['name'] }}</a>
                    <span class="text-nowrap small">{{ currency(abs($account['amount'])) }}</span>
                </div>
            @endforeach
        @endif

        {{-- Sub-Category Groups --}}
        @if (!empty($category['groups']))
            @foreach ($category['groups'] as $group)
                <div class="d-flex justify-content-between align-items-center py-1 ps-3">
                    <span class="fw-medium text-secondary small">{{ $group['name'] }}</span>
                    <span class="fw-medium text-secondary text-nowrap small">{{ currency(abs($group['total'] ?? 0)) }}</span>
                </div>
                @if (!empty($group['accounts']))
                    @foreach ($group['accounts'] as $account)
                        <div class="d-flex justify-content-between py-1 ps-4">
                            <a href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}" target="_blank" class="text-decoration-none text-muted small">{{ $account['name'] }}</a>
                            <span class="text-nowrap small">{{ currency(abs($account['amount'])) }}</span>
                        </div>
                    @endforeach
                @endif
            @endforeach
        @endif
    @endif
@endforeach
