{{-- One signature cell per signatory role: the stored signature image when the
     block has been signed, otherwise blank space of the same height so every
     ruled line still lands on the same row.

     Printed twice against the same signatures — under each acknowledgment, and
     again at the foot of the handover terms annex, which is signed against them.

     Expects $phaseKey, plus $ro, $roles, $sigSrc and $nameFor from the parent. --}}
<table class="sign-table">
    <tr>
        @foreach ($roles as $roleKey => $roleLabel)
            @php $sig = $ro->checklistSignatureFor($phaseKey, $roleKey); $src = $sigSrc($sig); @endphp
            <td class="sign-cell">
                @if ($src)
                    <img class="sign-img" src="{{ $src }}" alt="signature">
                @else
                    <div style="height:46px;"></div>
                @endif
                <div class="sign-line">
                    <span class="sign-name">{{ $sig?->signer_name ?: ($nameFor($roleKey) ?: '________________') }}</span><br>
                    {{ $roleLabel != 'Lessee' ? $roleLabel : '' }}
                    @if ($sig?->signed_at)
                        <br><span class="muted">{{ $sig->signed_at->format('d M Y') }}</span>
                    @endif
                </div>
            </td>
        @endforeach
    </tr>
</table>
