<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="d-flex align-items-center mb-4">
                <i class="fa fa-whatsapp text-success fs-2 me-3"></i>
                <h4 class="mb-0">WhatsApp Configuration</h4>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        @if (!$isConnected)
                            <div class="card-header bg-light py-3">
                                <button class="btn btn-primary d-flex align-items-center" wire:click="getWhatsappQr">
                                    <i class="fa fa-qrcode me-2"></i>
                                    Refresh QR Code
                                </button>
                            </div>
                            <div class="card-body text-center p-4">
                                <div class="mb-4">
                                    <span class="badge bg-info fs-6">
                                        <i class="fa fa-info-circle me-2"></i>
                                        Scan QR Code to Connect
                                    </span>
                                </div>
                                <div class="qr-container p-3 bg-light rounded" wire:poll.keep-alive='getWhatsappQr'>
                                    <img src="{{ $whatsappQr }}" alt="WhatsApp QR Code" class="img-fluid rounded shadow-sm" style="max-width: 300px" />
                                </div>
                            </div>
                        @else
                            <div class="card-body text-center p-4">
                                <div class="mb-4">
                                    <div class="alert alert-success d-inline-flex align-items-center">
                                        <i class="fa fa-check-circle me-2"></i>
                                        WhatsApp Connected
                                    </div>
                                </div>
                                <button class="btn btn-danger btn-lg d-inline-flex align-items-center" wire:click="disconnect">
                                    <i class="fa fa-power-off me-2"></i>
                                    Disconnect Session
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light py-3">
                            <h5 class="mb-0">Test WhatsApp Message</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="form-group mb-4">
                                <label class="form-label fw-medium" for="number">
                                    <i class="fa fa-phone me-2"></i>Phone Number
                                </label>
                                <div class="input-group">
                                    {{ html()->text('number')->value('')->class('form-control')->placeholder('Enter phone number (e.g. +919633155669)')->attribute('wire:model', 'number') }}
                                    <button class="btn btn-primary d-flex align-items-center" type="button" wire:click="sendSampleSms" id="button-addon2">
                                        <i class="fa fa-paper-plane me-2"></i>
                                        Send Test Message
                                    </button>
                                </div>
                            </div>

                            @if ($this->message)
                                <div class="message-preview bg-light rounded p-3">
                                    <h6 class="text-muted mb-2">
                                        <i class="fa fa-envelope me-2"></i>Message Preview
                                    </h6>
                                    <div class="p-3 bg-white rounded shadow-sm">
                                        {{ $this->message }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
