{{--
    Reusable partial for P&L category detail rows (groups + accounts).
    Variables: $master (category array), $side ('left' or 'right')
--}}
@foreach ($master['groups'] as $group)
    @if ($group['total'])
        <tr>
            @if ($side === 'right')
                <td style="border-right: 2px solid #dee2e6;"></td>
                <td style="border-right: 2px solid #dee2e6;"></td>
            @endif
            <td class="ps-3" @if($side === 'left') style="border-right: 2px solid #dee2e6; position: relative;" @else style="position: relative;" @endif>
                <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                <span style="position: absolute; left: 0.75rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                <button type="button" wire:click="toggleGroup({{ $group['id'] }})" class="btn btn-sm btn-link p-0 text-start text-decoration-none" style="padding-left: 1.5rem !important;">
                    <i class="fa fa-{{ in_array($group['id'], $expandedGroups) ? 'minus' : 'plus' }} me-1"></i>
                    {{ $group['name'] }}
                </button>
            </td>
            <td class="text-end pe-3" @if($side === 'left') style="border-right: 2px solid #dee2e6; background-color: #f5f5f5;" @else style="background-color: #f5f5f5;" @endif>
                {{ currency($group['total']) }}
            </td>
            @if ($side === 'left')
                <td></td>
                <td></td>
            @endif
        </tr>
    @endif

    @if (in_array($group['id'], $expandedGroups))
        @foreach ($group['accounts'] as $account)
            @if ($account['amount'] > 0)
                <tr>
                    @if ($side === 'right')
                        <td style="border-right: 2px solid #dee2e6;"></td>
                        <td style="border-right: 2px solid #dee2e6;"></td>
                    @endif
                    <td class="ps-3" @if($side === 'left') style="border-right: 2px solid #dee2e6; position: relative;" @else style="position: relative;" @endif>
                        <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                        <span style="position: absolute; left: 2.25rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                        <span style="position: absolute; left: 2.25rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                        <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" class="text-decoration-none" style="padding-left: 3rem !important; display: block;">{{ $account['name'] }}</a>
                    </td>
                    <td class="text-end pe-3" @if($side === 'left') style="border-right: 2px solid #dee2e6; background-color: #ffffff;" @else style="background-color: #ffffff;" @endif>
                        {{ currency($account['amount']) }}
                    </td>
                    @if ($side === 'left')
                        <td></td>
                        <td></td>
                    @endif
                </tr>
            @endif
        @endforeach
    @endif
@endforeach

{{-- Direct accounts (not under any group) --}}
@if (!empty($master['directAccounts']))
    @foreach ($master['directAccounts'] as $account)
        @if ($account['amount'] > 0)
            <tr>
                @if ($side === 'right')
                    <td style="border-right: 2px solid #dee2e6;"></td>
                    <td style="border-right: 2px solid #dee2e6;"></td>
                @endif
                <td class="ps-3" @if($side === 'left') style="border-right: 2px solid #dee2e6; position: relative;" @else style="position: relative;" @endif>
                    <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                    <span style="position: absolute; left: 0.75rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                    <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" class="text-decoration-none" style="padding-left: 1.5rem !important; display: block;">{{ $account['name'] }}</a>
                </td>
                <td class="text-end pe-3" @if($side === 'left') style="border-right: 2px solid #dee2e6; background-color: #ffffff;" @else style="background-color: #ffffff;" @endif>
                    {{ currency($account['amount']) }}
                </td>
                @if ($side === 'left')
                    <td></td>
                    <td></td>
                @endif
            </tr>
        @endif
    @endforeach
@endif
