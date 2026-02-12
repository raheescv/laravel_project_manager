<div>
    @if(!$user)
        <div class="card shadow-sm mb-4"><div class="card-body text-muted">User not found.</div></div>
    @else
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><i class="fa fa-telegram me-2"></i>Telegram Setup</h5>
            @if($user && $user->telegram_chat_id)
                <span class="badge bg-success">Connected</span>
            @else
                <span class="badge bg-secondary">Not connected</span>
            @endif
        </div>
        <div class="card-body">
            <h6 class="card-title mb-3">Telegram notifications</h6>
            <p class="text-muted small mb-3">
                Connect this user's Telegram account to receive notifications from the app. The user must open the bot and share their contact (mobile number) to link their account.
            </p>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <p class="text-muted mb-0">
                        <i class="fa fa-bell me-2"></i>Telegram notifications:
                        <span class="fw-bold">{{ $user && $user->is_telegram_enabled ? 'On' : 'Off' }}</span>
                    </p>
                    @if($user && $user->telegram_chat_id)
                        <p class="text-muted small mb-0 mt-1">Chat ID: <code>{{ $user->telegram_chat_id }}</code></p>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($user && $user->telegram_chat_id)
                        <button type="button" class="btn btn-outline-danger btn-sm" wire:click="disconnectTelegram" wire:loading.attr="disabled">
                            <span wire:loading.remove>Disconnect</span>
                            <span wire:loading>...</span>
                        </button>
                    @endif
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input h5 m-0 position-relative" id="telegramSwitch"
                            {{ $user && $user->is_telegram_enabled ? 'checked' : '' }}
                            wire:click="toggleTelegram"
                            wire:loading.attr="disabled">
                        <label class="form-check-label" for="telegramSwitch"></label>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3 bg-light">
                <h6 class="mb-2"><i class="fa fa-book me-2"></i>How to connect the Telegram bot</h6>
                <p class="small text-muted mb-2">Share these steps with the user so they can receive Telegram notifications:</p>
                <ol class="small mb-0 ps-3">
                    <li class="mb-1">Open Telegram (mobile or desktop).</li>
                    <li class="mb-1">Search for the bot or open this link: <a href="{{ $this->bot_link }}" target="_blank" rel="noopener noreferrer">{{ $this->bot_link }}</a></li>
                    <li class="mb-1">Start a chat with <strong>{{ '@' . $this->bot_username }}</strong> (tap Start or send any message).</li>
                    <li class="mb-1">When the bot asks, share your <strong>contact (phone number)</strong>. The number must match the mobile number saved for this user in the app (<code>{{ $user->mobile ?? '—' }}</code>).</li>
                    <li class="mb-1">After sharing contact, the bot will confirm that your account is connected.</li>
                    <li>Once connected, turn <strong>Telegram notifications</strong> on using the switch above.</li>
                </ol>
            </div>

            @if($user && !$user->telegram_chat_id)
                <div class="alert alert-info mt-3 mb-0 small py-2">
                    <i class="fa fa-info-circle me-2"></i>
                    User must complete the steps above first. Until they connect via the bot, notifications cannot be sent to Telegram.
                </div>
            @endif
        </div>
    </div>
    @endif
</div>
