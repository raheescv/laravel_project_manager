<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationPreferences extends Component
{
    public bool $is_browser_notification_enabled = true;

    public function mount(): void
    {
        $this->is_browser_notification_enabled = (bool) Auth::user()->is_browser_notification_enabled;
    }

    public function save(): void
    {
        Auth::user()->update([
            'is_browser_notification_enabled' => $this->is_browser_notification_enabled,
        ]);

        $this->dispatch('success', message: 'Notification preferences saved.');
    }

    public function render()
    {
        return view('livewire.settings.notification-preferences');
    }
}
