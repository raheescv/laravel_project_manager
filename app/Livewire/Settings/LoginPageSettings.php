<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use App\Support\LoginScreen;
use App\Support\TenantCache;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LoginPageSettings extends Component
{
    public string $layout = LoginScreen::RANDOM;

    public string $background = LoginScreen::RANDOM;

    public function mount(): void
    {
        $this->layout = LoginScreen::layoutSetting();
        $this->background = LoginScreen::backgroundSetting();
    }

    public function setLayout(string $layout): void
    {
        $this->layout = $this->store('login_layout', $layout, LoginScreen::LAYOUTS);
    }

    public function setBackground(string $background): void
    {
        $this->background = $this->store('login_background', $background, LoginScreen::BACKGROUNDS);
    }

    /**
     * @param  array<string, string>  $options
     */
    protected function store(string $key, string $value, array $options): string
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);

        $value = LoginScreen::sanitize($value, $options);

        Configuration::updateOrCreate(['key' => $key], ['value' => $value]);
        TenantCache::forget($key);

        $this->dispatch('success', ['message' => 'Login page updated']);

        return $value;
    }

    public function render(): View
    {
        return view('livewire.settings.login-page-settings', [
            'layouts' => LoginScreen::LAYOUTS,
            'backgrounds' => LoginScreen::BACKGROUNDS,
        ]);
    }
}
