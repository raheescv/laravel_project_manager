<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fa fa-telegram me-2"></i>Telegram integration</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">
            Configure the Telegram bot for notifications. Users link their account by opening the bot and sharing their phone number.
        </p>

        {{-- Credentials form (stored in configuration database) --}}
        <div class="mb-4">
            <h6 class="fw-bold mb-3">Bot credentials (saved in configuration database)</h6>
            <p class="text-muted small mb-3">
                Values saved here override <code>.env</code>. Leave blank to keep existing value.
            </p>
            <form wire:submit="saveCredentials" class="row g-3">
                <div class="col-12">
                    <label for="botToken" class="form-label">Bot Token</label>
                    <input type="password" id="botToken" class="form-control" wire:model="botToken"
                           placeholder="123456:ABC-DEF..." autocomplete="off">
                    <div class="form-text">From @BotFather after creating the bot. Leave blank to keep current.</div>
                    @error('botToken')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label for="botUsername" class="form-label">Bot username</label>
                    <input type="text" id="botUsername" class="form-control" wire:model="botUsername"
                           placeholder="YourAppBot">
                    <div class="form-text">Username without <code>@</code>. Leave blank to keep current.</div>
                    @error('botUsername')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveCredentials"><i class="fa fa-save me-1"></i> Save credentials</span>
                        <span wire:loading wire:target="saveCredentials">Saving…</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Documentation --}}
        <div class="mb-4">
            <h6 class="fw-bold mb-3">Initial setup</h6>
            <ol class="mb-0 ps-3">
                <li class="mb-2">
                    <strong>Create a bot</strong> — Open Telegram, search for <a href="https://t.me/BotFather" target="_blank" rel="noopener">@BotFather</a>, send <code>/newbot</code>, follow the prompts and get your <strong>Bot Token</strong>.
                </li>
                <li class="mb-2">
                    <strong>Bot username</strong> — BotFather gives you a username like <code>YourAppBot</code>. Note it (without <code>@</code>).
                </li>
                <li class="mb-2">
                    <strong>Store credentials</strong> — Enter <strong>Bot Token</strong> and <strong>Bot username</strong> above and click <strong>Save credentials</strong>. They are stored in the configuration database (and override <code>.env</code>). Alternatively, in <code>.env</code> add:
                    <pre class="bg-light p-3 rounded mt-1 mb-0 small"><code>TELEGRAM_BOT_TOKEN=123456:ABC-DEF...
TELEGRAM_BOT_USERNAME=YourAppBot</code></pre>
                </li>
                <li class="mb-2">
                    <strong>Webhook</strong> — Your app must be reachable at <code>APP_URL</code> over HTTPS. Webhook URL: <code>{{ $webhookUrl }}</code>
                </li>
                <li>
                    <strong>Register webhook</strong> — Click <strong>Set up webhook</strong> below (or run <code>php artisan telegram:setup</code>).
                </li>
            </ol>
        </div>

        {{-- Status (optional) --}}
        @if($this->webhookStatus)
            <div class="alert alert-info py-2 mb-4">
                <strong>Webhook:</strong> {{ $this->webhookStatus['url'] }}
                @if($this->webhookStatus['pending_updates'] > 0)
                    · Pending updates: {{ $this->webhookStatus['pending_updates'] }}
                @endif
                @if($this->webhookStatus['last_error'])
                    · <span class="text-danger">Last error: {{ $this->webhookStatus['last_error'] }}</span>
                @endif
            </div>
        @elseif($botUsername)
            <div class="alert alert-warning py-2 mb-4">
                Webhook not set or token invalid. Save your <strong>Bot Token</strong> above (or set <code>TELEGRAM_BOT_TOKEN</code> in <code>.env</code>), then run <strong>Set up webhook</strong>.
            </div>
        @endif

        {{-- Actions --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-primary" wire:click="runSetup" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="runSetup"><i class="fa fa-plug me-1"></i> Set up webhook</span>
                <span wire:loading wire:target="runSetup">Running…</span>
            </button>
            <button type="button" class="btn btn-outline-secondary" wire:click="runWebhookInfo" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="runWebhookInfo"><i class="fa fa-info-circle me-1"></i> Check webhook info</span>
                <span wire:loading wire:target="runWebhookInfo">Running…</span>
            </button>
        </div>
        <p class="text-muted small mb-0">
            Equivalent commands: <code>php artisan telegram:setup</code>, <code>php artisan telegram:webhook-info</code>, <code>php artisan telegram:send {mobile} {message}</code>
        </p>

        {{-- Send test message (telegram:send) --}}
        <div class="mt-4 pt-4 border-top">
            <h6 class="fw-bold mb-3"><i class="fa fa-paper-plane me-2"></i>Send test message</h6>
            <p class="text-muted small mb-3">
                Send a Telegram message to a user by their mobile number (with country code, e.g. 971501234567). The user must have connected the bot first.
            </p>
            <form wire:submit="sendTestMessage" class="row g-3">
                <div class="col-12 col-md-4">
                    <label for="testMobile" class="form-label">Mobile number</label>
                    <input type="text" id="testMobile" class="form-control" wire:model="testMobile"
                           placeholder="971501234567">
                    @error('testMobile')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="testMessage" class="form-label">Message</label>
                    <input type="text" id="testMessage" class="form-control" wire:model="testMessage"
                           placeholder="Test message from Settings">
                    @error('testMessage')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary w-100" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="sendTestMessage"><i class="fa fa-paper-plane me-1"></i> Send</span>
                        <span wire:loading wire:target="sendTestMessage">Sending…</span>
                    </button>
                </div>
            </form>
            @if($sendResult && $sendResult !== 'success')
                <div class="mt-2">
                    <pre class="bg-light p-2 rounded small text-danger mb-0">{{ $sendResult }}</pre>
                </div>
            @endif
        </div>

        {{-- Command output --}}
        @if($commandOutput !== null)
            <div class="mt-4">
                <h6 class="fw-bold mb-2">Command output</h6>
                <pre class="bg-dark text-light p-3 rounded small mb-0 {{ $commandExitCode !== 0 ? 'border border-danger' : '' }}">{{ $commandOutput ?: '(no output)' }}</pre>
            </div>
        @endif
    </div>
</div>
