{{-- Company header and document title shared by the project-contract vouchers. Expects $title, optional $stamp. --}}
<table class="b-head">
    <tr>
        <td>
            <div class="b-eyebrow">Accounts Payable</div>
            <div class="co-name">{{ $companyName }}</div>
            <div class="co-meta">
                @if ($companyAddress){{ $companyAddress }}<br>@endif
                @if ($companyPhone)Tel: {{ $companyPhone }}@endif
                @if ($companyEmail) &bull; {{ $companyEmail }}@endif
                @if (! empty($companyWebsite))<br>{{ $companyWebsite }}@endif
            </div>
        </td>
        <td class="logo-cell">
            @if ($companyLogo)
                <img src="{{ $companyLogo }}" alt="Logo">
            @endif
        </td>
    </tr>
</table>

<div class="b-title">
    <div class="t">{{ $title }}</div>
    <div class="rule"></div>
    @if (! empty($stamp))
        <div class="stamp">{{ $stamp }}</div>
    @endif
</div>
