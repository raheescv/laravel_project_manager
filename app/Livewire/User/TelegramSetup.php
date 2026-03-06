<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Component;

class TelegramSetup extends Component
{
    public $userId;

    public $user;

    public function mount($userId)
    {
        $this->userId = $userId;
        $this->user = User::find($userId);
    }

    public function toggleTelegram()
    {
        if (! $this->user) {
            return;
        }
        $this->user->update([
            'is_telegram_enabled' => ! $this->user->is_telegram_enabled,
        ]);
        $this->user->refresh();
        $this->dispatch('success', ['message' => $this->user->is_telegram_enabled ? 'Telegram notifications enabled.' : 'Telegram notifications disabled.']);
    }

    public function disconnectTelegram()
    {
        if (! $this->user) {
            return;
        }
        $this->user->update([
            'telegram_chat_id' => null,
            'is_telegram_enabled' => false,
        ]);
        $this->user->refresh();
        $this->dispatch('success', ['message' => 'Telegram account disconnected. User can connect again via the bot.']);
    }

    public function getBotUsernameProperty()
    {
        return config('services.telegram.bot_username') ?: 'YourBot';
    }

    public function getBotLinkProperty()
    {
        $username = $this->bot_username;

        return "https://t.me/{$username}";
    }

    public function render()
    {
        return view('livewire.user.telegram-setup');
    }
}
