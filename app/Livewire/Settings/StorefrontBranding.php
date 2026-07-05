<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class StorefrontBranding extends Component
{
    /** Brand accent color for the public storefront (showcase website), hex. */
    public string $primary_color = self::DEFAULT_COLOR;

    /** SIZE RUN electric blue — the storefront default when nothing is saved. */
    private const DEFAULT_COLOR = '#1F35E5';

    public function mount(): void
    {
        $saved = Configuration::where('key', 'storefront_primary_color')->value('value');
        if ($saved) {
            $this->primary_color = $saved;
        }
    }

    public function resetToDefault(): void
    {
        $this->primary_color = self::DEFAULT_COLOR;
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);

        $color = strtoupper(trim($this->primary_color));
        if (! preg_match('/^#([0-9A-F]{6}|[0-9A-F]{3})$/', $color)) {
            $this->dispatch('error', ['message' => 'Enter a valid hex color, e.g. #1F35E5']);

            return;
        }

        // Normalize shorthand (#abc) to full form so the storefront always gets #rrggbb.
        if (strlen($color) === 4) {
            $color = '#'.$color[1].$color[1].$color[2].$color[2].$color[3].$color[3];
        }

        Configuration::updateOrCreate(['key' => 'storefront_primary_color'], ['value' => $color]);
        Cache::forget('storefront_primary_color');

        $this->primary_color = $color;
        $this->dispatch('success', ['message' => 'Storefront color updated']);
    }

    public function render()
    {
        return view('livewire.settings.storefront-branding');
    }
}
