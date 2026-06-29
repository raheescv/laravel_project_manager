<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class CurrencyConfiguration extends Component
{
    /**
     * The currency rows. Each row:
     * ['code', 'symbol', 'name', 'decimals', 'rate_to_base', 'active'].
     */
    public array $currencies = [];

    /** Index (in $currencies) of the base currency. */
    public int $base_index = 0;

    public function mount(): void
    {
        $stored = Configuration::where('key', 'currencies')->value('value');
        $stored = $stored ? json_decode($stored, true) : null;

        if (is_array($stored) && count($stored)) {
            $baseCode = Configuration::where('key', 'base_currency_code')->value('value');
            foreach ($stored as $i => $row) {
                $this->currencies[] = [
                    'code' => $row['code'] ?? '',
                    'symbol' => $row['symbol'] ?? '',
                    'name' => $row['name'] ?? '',
                    'decimals' => (int) ($row['decimals'] ?? 2),
                    'rate_to_base' => (float) ($row['rate_to_base'] ?? 1),
                    'active' => (bool) ($row['active'] ?? true),
                ];
                if ((! empty($row['is_base']) || ($baseCode && $row['code'] === $baseCode))) {
                    $this->base_index = $i;
                }
            }

            return;
        }

        // First-time setup: seed from the existing single-currency config so the
        // current behaviour carries over unchanged.
        $code = Configuration::where('key', 'currency_code')->value('value') ?: 'QAR';
        $symbol = Configuration::where('key', 'currency_symbol')->value('value') ?: $code;
        $this->currencies[] = [
            'code' => $code,
            'symbol' => $symbol,
            'name' => $code,
            'decimals' => 2,
            'rate_to_base' => 1.0,
            'active' => true,
        ];
        $this->base_index = 0;
    }

    public function addCurrency(): void
    {
        $this->currencies[] = [
            'code' => '',
            'symbol' => '',
            'name' => '',
            'decimals' => 2,
            'rate_to_base' => 1.0,
            'active' => true,
        ];
    }

    public function removeCurrency(int $index): void
    {
        if (! isset($this->currencies[$index])) {
            return;
        }
        unset($this->currencies[$index]);
        $this->currencies = array_values($this->currencies);

        // Keep the base pointer valid after the shift.
        if ($index === $this->base_index) {
            $this->base_index = 0;
        } elseif ($index < $this->base_index) {
            $this->base_index--;
        }
        $this->base_index = max(0, min($this->base_index, count($this->currencies) - 1));
    }

    public function setBase(int $index): void
    {
        if (isset($this->currencies[$index])) {
            $this->base_index = $index;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);

        $baseCode = strtoupper(trim($this->currencies[$this->base_index]['code'] ?? ''));

        $rows = [];
        $seen = [];
        foreach ($this->currencies as $c) {
            $code = strtoupper(trim($c['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            if (isset($seen[$code])) {
                $this->dispatch('error', ['message' => "Duplicate currency code: {$code}"]);

                return;
            }
            $seen[$code] = true;

            $isBase = $code === $baseCode;
            $decimals = (int) ($c['decimals'] ?? 2);
            $rate = $isBase ? 1.0 : (float) ($c['rate_to_base'] ?? 0);

            if ($decimals < 0 || $decimals > 6) {
                $this->dispatch('error', ['message' => "Decimals for {$code} must be between 0 and 6"]);

                return;
            }
            if (! $isBase && $rate <= 0) {
                $this->dispatch('error', ['message' => "Exchange rate for {$code} must be greater than 0"]);

                return;
            }

            $rows[] = [
                'code' => $code,
                'symbol' => trim($c['symbol'] ?? '') ?: $code,
                'name' => trim($c['name'] ?? '') ?: $code,
                'decimals' => $decimals,
                'rate_to_base' => $rate,
                'active' => $isBase ? true : (bool) ($c['active'] ?? true),
                'is_base' => $isBase,
            ];
        }

        if (empty($rows)) {
            $this->dispatch('error', ['message' => 'Add at least one currency']);

            return;
        }
        if ($baseCode === '' || ! isset($seen[$baseCode])) {
            $this->dispatch('error', ['message' => 'Select a valid base currency']);

            return;
        }

        Configuration::updateOrCreate(['key' => 'currencies'], ['value' => json_encode($rows)]);
        Configuration::updateOrCreate(['key' => 'base_currency_code'], ['value' => $baseCode]);

        // Keep the legacy single-currency keys in sync with the base currency so
        // existing code that reads currency_symbol/currency_code keeps working.
        $base = collect($rows)->firstWhere('is_base', true);
        Configuration::updateOrCreate(['key' => 'currency_code'], ['value' => $base['code']]);
        Configuration::updateOrCreate(['key' => 'currency_symbol'], ['value' => $base['symbol']]);

        Cache::forget('currencies');
        Cache::forget('base_currency_code');
        Cache::forget('currency_code');
        Cache::forget('currency_symbol');

        $this->dispatch('success', ['message' => 'Currencies updated successfully']);
    }

    public function render()
    {
        return view('livewire.settings.currency-configuration');
    }
}
