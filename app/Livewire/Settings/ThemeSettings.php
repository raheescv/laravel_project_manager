<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class ThemeSettings extends Component
{
    public $theme = [
        'layout' => 'fluid', // fluid, boxed, centered
        'transition' => 'out-quart', // in-quart, out-quart, in-back, out-back, in-out-back, steps, jumping, rubber
        'header' => [
            'sticky' => false,
        ],
        'navigation' => [
            'sticky' => false,
            'profileWidget' => true,
            'mode' => 'maxi', // mini, maxi, push, slide, reveal
        ],
        'sidebar' => [
            'disableBackdrop' => false,
            'staticPosition' => false,
            'stuck' => false,
            'unite' => false,
            'pinned' => false,
        ],
        'color' => [
            'scheme' => 'gray', // gray, navy, ocean, lime, violet, orange, teal, corn, cherry, coffee, pear, night
            'mode' => '', // tm--expanded-hd, tm--fair-hd, tm--full-hd, tm--primary-mn, tm--primary-brand, tm--tall-hd
            'darkMode' => false,
        ],
        'misc' => [
            'fontSize' => 16,
            'bodyScrollbar' => false,
            'sidebarsScrollbar' => false,
        ],
    ];

    // Livewire v3 uses listeners differently
    // We now use #[On('themeUpdated')] attribute for the method

    public function mount()
    {
        $savedTheme = Configuration::where('key', 'theme_settings')->value('value');
        if ($savedTheme) {
            $this->theme = json_decode($savedTheme, true);
        }
    }

    #[On('themeUpdated')]
    public function receiveThemeUpdate($settings)
    {
        // Update the theme configuration with data from JavaScript
        foreach ($settings as $key => $value) {
            if (strpos($key, '.') !== false) {
                // Handle nested properties
                $parts = explode('.', $key);
                $this->theme[$parts[0]][$parts[1]] = $value;
            } else {
                $this->theme[$key] = $value;
            }
        }

        $this->saveThemeSettings();
    }

    public function saveThemeSettings()
    {
        Configuration::updateOrCreate(['key' => 'theme_settings'], ['value' => json_encode($this->theme)]);
        Cache::forget('theme_settings');
        $this->dispatch('themeSaved', themeData: $this->theme);
    }

    public function render()
    {
        return view('livewire.settings.theme-settings');
    }
}
