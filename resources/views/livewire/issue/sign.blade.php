<div>
    <style>
        #signature-container {
            margin-top: 2rem;
            width: 100%;
            height: 400px;
            position: relative;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
        }

        #signature-pad {
            width: 100%;
            height: 100%;
            display: block;
            background-color: white;
            border-radius: 8px;
        }

        #signature-actions {
            margin-top: 1rem;
            display: flex;
            justify-content: end;
            gap: 0.5rem;
        }
    </style>
    <div class="col-12">
        <p class="section-title">Signature</p>
        <div id="signature-container" wire:ignore>
            <canvas id="signature-pad"></canvas>
        </div>
        <div id="signature-actions">
            <button class="btn btn-secondary btn-sm" id="clear">Clear</button>
            <button class="btn btn-primary btn-sm" wire:click="save" id="submit">Sign & Submit</button>
        </div>
        <form id="signature-form">
            @csrf
            <div class="col-md-12">
                @if ($this->getErrorBag()->count())
                    <ul>
                        <?php foreach ($this->getErrorBag()->toArray() as $value): ?>
                        <li style="color:red">* {{ $value[0] }}</li>
                        <?php endforeach; ?>
                    </ul>
                @endif
            </div>
            <input type="hidden" wire:model="signature" id="signature-input">
        </form>
    </div>
    <script>
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas);

        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear();
        }
        window.addEventListener("resize", resizeCanvas);
        resizeCanvas();

        document.getElementById('clear').addEventListener('click', () => {
            signaturePad.clear();
        });

        document.getElementById('submit').addEventListener('click', function() {
            if (!signaturePad.isEmpty()) {
                let data = signaturePad.toDataURL();
                console.log(data);
                @this.set('signature', data);
            } else {
                alert("Please provide a signature first.");
            }
        });
    </script>
</div>
