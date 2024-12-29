<div>
    <h5>Whatsapp Qr</h5>
    <div class="row">
        <div class="col-md-6">
            <div class="card h-100">
                @if (!$isConnected)
                    <div class="card-header">
                        <div class="col-md-12 d-flex gap-1 align-items-center mb-3">
                            <button class="btn btn-primary hstack gap-2 align-self-center" wire:click="getWhatsappQr">
                                <i class="demo-psi-add fs-5"></i>
                                <span class="vr"></span>
                                Refresh QR
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">Scan this QR code with your device to connect</p>
                        <div wire:poll.keep-alive='getWhatsappQr'>
                            <img src="{{ $whatsappQr }}" alt="Scan the QR Code" />
                        </div>
                    </div>
                @else
                    <div class="card-body text-center">
                        <h5 class="card-title text-center">Logout from the current session</h5>
                        <button tabindex="0" class="btn btn-lg btn-danger" wire:click="disconnect">Terminate Session</button>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <label for="number">Number</label>
                    <div class="input-group mb-3">
                        {{ html()->text('number')->value('')->class('form-control')->placeholder('Enter Your Phone Number: +919633155669')->attribute('wire:model', 'number') }}
                        <button class="btn btn-info" type="button" wire:click="sendSampleSms" id="button-addon2">Send Sample</button>
                    </div>
                    <div class="form-group">
                        <h2>Message</h2>
                        <h3>{{ $this->message }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
