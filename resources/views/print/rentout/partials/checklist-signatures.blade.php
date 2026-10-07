{{-- One signature cell per signatory role: the stored signature image when the
     block has been signed, otherwise blank space of the same height so every
     ruled line still lands on the same row.

     Printed twice against the same signatures — under each acknowledgment, and
     again at the foot of the handover terms annex, which is signed against them.

     On the sign page ($withPads) an unsigned box is a signature pad instead; the
     annex copy never carries pads, so each pad exists exactly once.

     Expects $phaseKey, $withPads, plus $ro, $roles, $sigSrc, $nameFor and $userIdFor
     from the parent. --}}
<table class="sign-table">
    <tr>
        @foreach ($roles as $roleKey => $roleLabel)
            @php $sig = $ro->checklistSignatureFor($phaseKey, $roleKey); $src = $sigSrc($sig); @endphp
            <td class="sign-cell">
                @if ($src)
                    <img class="sign-img zoomable" src="{{ $src }}" data-img="{{ $src }}" alt="signature">
                @elseif (! empty($withPads))
                    @livewire('rent-out.checklist.sign', [
                        'rentOut' => $ro,
                        'phase' => $phaseKey,
                        'role' => $roleKey,
                        'signerName' => $nameFor($roleKey),
                        'userId' => $userIdFor($roleKey),
                    ], key('sign-' . $phaseKey . '-' . $roleKey . '-' . $ro->id))
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
