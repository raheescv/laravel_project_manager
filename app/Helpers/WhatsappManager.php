<?php

namespace App\Helpers;

use App\Models\Configuration;
use Illuminate\Support\Facades\Log;

class WhatsappManager
{
    public function driverName(): string
    {
        try {
            $configured = Configuration::where('key', 'whatsapp_driver')->value('value');
        } catch (\Throwable $e) {
            Log::debug('Could not read WhatsApp driver configuration', ['message' => $e->getMessage()]);
            $configured = null;
        }

        return $configured ?: config('services.whatsapp.driver', 'meta');
    }

    public function helper(): object
    {
        return match ($this->driverName()) {
            'core_connecta', 'personal_gateway' => new CoreConnectaHelper(),
            'localhost' => new LocalhostWhatsappHelper(),
            default => new WhatsappHelper(),
        };
    }

    public function __call(string $method, array $arguments)
    {
        $helper = $this->helper();

        if (! method_exists($helper, $method)) {
            throw new \BadMethodCallException(sprintf(
                'WhatsApp driver [%s] does not support method [%s].',
                $this->driverName(),
                $method
            ));
        }

        return $helper->{$method}(...$arguments);
    }
}
