<div>
    @php $uid = $phase.'_'.$role.'_'.$rentOut->id; @endphp
    <style>
        .sigpad-container-{{ $uid }} {
            margin-top: 0.5rem;
            width: 100%;
            height: 150px;
            position: relative;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 0.5rem;
        }

        #sigpad_{{ $uid }} {
            width: 100%;
            height: 100%;
            display: block;
            background-color: white;
            border-radius: 8px;
        }

        .sigpad-actions-{{ $uid }} {
            margin-top: 0.5rem;
            display: flex;
            justify-content: end;
            gap: 0.5rem;
        }
    </style>
    <div class="sigpad-block">
        <p class="section-title mb-1">
            {{ $signerName ?: '-' }}
            <small class="text-muted">({{ \App\Enums\RentOut\ChecklistSignatoryRole::from($role)->label() }})</small>
        </p>
        <div class="sigpad-container-{{ $uid }}" wire:ignore>
            <canvas id="sigpad_{{ $uid }}"></canvas>
        </div>
        <div class="sigpad-actions-{{ $uid }}">
            <button type="button" class="btn btn-secondary btn-sm" id="sigclear_{{ $uid }}">Clear</button>
            <button type="button" class="btn btn-primary btn-sm" id="sigsubmit_{{ $uid }}">Sign &amp; Submit</button>
        </div>
        @if ($this->getErrorBag()->count())
            <ul class="mt-1">
                <?php foreach ($this->getErrorBag()->toArray() as $value) { ?>
                <li style="color:red">* {{ $value[0] }}</li>
                <?php } ?>
            </ul>
        @endif
        <input type="hidden" wire:model="signature" id="siginput_{{ $uid }}">
    </div>
    <script>
        (function () {
            var uid = @json($uid);
            var canvas = document.getElementById('sigpad_' + uid);
            if (!canvas) {
                return;
            }
            if (canvas.dataset.sigInit) {
                return;
            }
            canvas.dataset.sigInit = '1';
            var signaturePad = new SignaturePad(canvas);

            function resizeCanvas() {
                var ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
                signaturePad.clear();
            }
            window.addEventListener('resize', resizeCanvas);
            resizeCanvas();

            document.getElementById('sigclear_' + uid).addEventListener('click', function () {
                signaturePad.clear();
            });

            document.getElementById('sigsubmit_' + uid).addEventListener('click', function () {
                if (!signaturePad.isEmpty()) {
                    var data = signaturePad.toDataURL();
                    Promise.resolve(@this.set('signature', data)).then(function () {
                        @this.save();
                    });
                } else {
                    alert('Please provide a signature first.');
                }
            });
        })();
    </script>
</div>
