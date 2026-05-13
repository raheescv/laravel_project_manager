<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="card-title mb-0"><i class="fa fa-whatsapp text-success me-2"></i>WhatsApp integration</h5>
            <small class="text-body-secondary">Connect Project Manager to Meta, the legacy local server, or the WhatsApp gateway.</small>
        </div>
        <span class="badge {{ $isConnected ? 'text-bg-success' : 'text-bg-warning' }}">
            {{ $isConnected ? 'Connected / configured' : 'Needs attention' }}
        </span>
    </div>

    <div class="card-body">
        @if ($statusMessage)
            <div class="alert {{ $isConnected ? 'alert-success' : 'alert-warning' }} py-2">
                {{ $statusMessage }}
            </div>
        @endif

        <form wire:submit="saveIntegration" class="row g-3 mb-4">
            <div class="col-12 col-lg-4">
                <label class="form-label" for="whatsapp_driver">Driver</label>
                <select id="whatsapp_driver" class="form-select" wire:model.live="driver">
                    <option value="meta">Meta / wa-api.cloud</option>
                    <option value="core_connecta">Core Connecta</option>
                    <option value="localhost">Legacy localhost server</option>
                </select>
                <div class="form-text">
                    Use <strong>Core Connecta</strong> when {{ config('app.name') }} should call your own gateway site (the <strong>Gateway URL</strong> you configure below).
                </div>
                @error('driver')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            @if ($this->usesCoreConnectaDriver())
                <div class="col-12 col-lg-8">
                    <label class="form-label" for="gatewayUrl">Gateway URL</label>
                    <input id="gatewayUrl" type="url" class="form-control" wire:model="gatewayUrl" placeholder="http://localhost:3000">
                    <div class="form-text">Base URL of the Core Connecta gateway. Do not include <code>/api</code>.</div>
                    @error('gatewayUrl')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="gatewayApiKey">Tenant API key</label>
                    <input id="gatewayApiKey" type="password" class="form-control" wire:model="gatewayApiKey" autocomplete="off">
                    <div class="form-text">Use the tenant API key from your Core Connecta admin panel.</div>
                    @error('gatewayApiKey')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label" for="gatewaySessionId">Session ID</label>
                    <input id="gatewaySessionId" type="number" min="1" class="form-control" wire:model="gatewaySessionId" placeholder="Auto">
                    <div class="form-text">Optional. If blank, the first tenant session is used or created.</div>
                    @error('gatewaySessionId')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label" for="gatewaySessionName">Session name</label>
                    <input id="gatewaySessionName" type="text" class="form-control" wire:model="gatewaySessionName">
                    @error('gatewaySessionName')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            @endif

            @if ($driver === 'meta')
                <div class="col-12">
                    <label class="form-label" for="metaAccessToken">Meta / wa-api access token</label>
                    <input id="metaAccessToken" type="password" class="form-control" wire:model="metaAccessToken" autocomplete="off">
                    @error('metaAccessToken')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-lg-8">
                    <label class="form-label" for="metaBaseUrl">Meta / wa-api base URL</label>
                    <input id="metaBaseUrl" type="url" class="form-control" wire:model="metaBaseUrl" placeholder="https://wa-api.cloud/api/v1">
                    @error('metaBaseUrl')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="metaTemplateName">Invoice template name</label>
                    <input id="metaTemplateName" type="text" class="form-control" wire:model="metaTemplateName" placeholder="invoice_slip">
                    @error('metaTemplateName')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            @endif

            @if ($driver === 'localhost')
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        The legacy localhost driver uses <code>WHATSAPP_SERVER_URL</code> / <code>WHATSAPP_PORT</code>
                        and the server in <code>public/node</code>. Use <code>./whatsapp-server.sh start</code> to run it.
                    </div>
                </div>
            @endif

            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveIntegration">
                    <span wire:loading.remove wire:target="saveIntegration"><i class="fa fa-save me-1"></i> Save integration</span>
                    <span wire:loading wire:target="saveIntegration">Saving...</span>
                </button>
                <button type="button" class="btn btn-outline-secondary" wire:click="checkClientStatus" wire:loading.attr="disabled" wire:target="checkClientStatus">
                    <span wire:loading.remove wire:target="checkClientStatus"><i class="fa fa-plug me-1"></i> Check status</span>
                    <span wire:loading wire:target="checkClientStatus">Checking...</span>
                </button>
                @if ($driver !== 'meta')
                    <button type="button" class="btn btn-outline-success" wire:click="getWhatsappQr" wire:loading.attr="disabled" wire:target="getWhatsappQr">
                        <span wire:loading.remove wire:target="getWhatsappQr"><i class="fa fa-qrcode me-1"></i> Refresh QR</span>
                        <span wire:loading wire:target="getWhatsappQr">Loading QR...</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger" wire:click="disconnect" wire:loading.attr="disabled" wire:target="disconnect">
                        <i class="fa fa-power-off me-1"></i> Disconnect
                    </button>
                @endif
            </div>
        </form>

        @if ($driver !== 'meta')
            <div class="row g-4 mb-4">
                <div class="col-12 col-lg-5">
                    <div class="border rounded p-3 h-100 text-center">
                        <h6 class="fw-bold mb-3">QR pairing</h6>
                        @if ($whatsappQr)
                            <img src="{{ $whatsappQr }}" alt="WhatsApp QR Code" class="img-fluid rounded border" style="max-width: 300px">
                            <p class="text-muted small mt-3 mb-0">Scan this QR with WhatsApp on your phone.</p>
                        @elseif ($isConnected)
                            <div class="alert alert-success mb-0">The selected WhatsApp session is connected.</div>
                        @else
                            <div class="alert alert-light border mb-0">No QR code is available yet. Click <strong>Refresh QR</strong>.</div>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="border rounded p-3 h-100">
                        <h6 class="fw-bold mb-3">Gateway notes</h6>
                        @if ($this->usesCoreConnectaDriver())
                            <ol class="small text-muted mb-0 ps-3">
                                @if ($this->gatewayPublicInfo['has_url'])
                                    <li>
                                        Keep the Core Connecta process running on the host that serves
                                        <a href="{{ $this->gatewayPublicInfo['href'] }}" target="_blank" rel="noopener noreferrer" class="link-secondary fw-semibold text-break">{{ $this->gatewayPublicInfo['href'] }}</a>
                                        <span class="text-body-secondary">({{ $this->gatewayPublicInfo['label'] }})</span>.
                                        Open that base URL in a browser for the admin UI, tenants, WhatsApp sessions, and API keys.
                                    </li>
                                @else
                                    <li>
                                        Set <strong>Gateway URL</strong> above to your Core Connecta public base address (for example <code>https://wa.example.com</code> or <code>http://localhost:3000</code>), then save. {{ config('app.name') }} loads QR, status, and sends from that site.
                                    </li>
                                @endif
                                <li>Paste the tenant API key from Core Connecta into <strong>Tenant API key</strong>, then <strong>Save integration</strong>.</li>
                                <li>Use <strong>Check status</strong> and <strong>Refresh QR</strong>; scan the QR in WhatsApp when prompted.</li>
                                <li>Outgoing messages use a connected session: your saved session ID when it is connected, otherwise the first connected session for that tenant.</li>
                            </ol>
                        @else
                            <ol class="small text-muted mb-0 ps-3">
                                <li>Run the legacy WhatsApp server on your configured <code>WHATSAPP_SERVER_URL</code> and port (<code>public/node</code>).</li>
                                <li>Use <strong>Check status</strong> and <strong>Refresh QR</strong> against that server.</li>
                            </ol>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="border-top pt-4">
            <h6 class="fw-bold mb-3"><i class="fa fa-paper-plane me-2"></i>Send test message</h6>
            <form wire:submit="sendSampleSms" class="row g-3">
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="number">Phone number</label>
                    <input id="number" type="text" class="form-control" wire:model="number" placeholder="+919633155669">
                    @error('number')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="message">Message</label>
                    <input id="message" type="text" class="form-control" wire:model="message" placeholder="Test message">
                    @error('message')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-lg-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary w-100" wire:loading.attr="disabled" wire:target="sendSampleSms">
                        <span wire:loading.remove wire:target="sendSampleSms">Send</span>
                        <span wire:loading wire:target="sendSampleSms">Sending...</span>
                    </button>
                </div>
            </form>
            @if ($this->usesCoreConnectaDriver())
                <p class="text-muted small mt-2 mb-0">
                    Core Connecta sends plain text today. Invoice image or template sends are converted to a text message that includes the generated invoice URL.
                </p>
            @endif
        </div>
    </div>
</div>
