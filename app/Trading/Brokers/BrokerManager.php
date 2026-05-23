<?php

namespace App\Trading\Brokers;

use App\Trading\Contracts\BrokerContract;

/**
 * Lookup + selection layer over all registered brokers. The rest of the
 * trading system depends ONLY on this — never on a specific adapter.
 */
class BrokerManager
{
    /** @var array<string, BrokerContract> */
    private array $brokers = [];

    private ?string $defaultCode = null;

    public function register(BrokerContract $broker, bool $default = false): void
    {
        $this->brokers[$broker->code()] = $broker;
        if ($default || $this->defaultCode === null) {
            $this->defaultCode = $broker->code();
        }
    }

    public function broker(?string $code = null): BrokerContract
    {
        // When an explicit code is passed, behave strictly: registered or throw.
        // The caller asked for a specific adapter and we mustn't silently
        // hand back a different one.
        if ($code !== null) {
            if (! isset($this->brokers[$code])) {
                throw new \RuntimeException("Broker [{$code}] is not registered");
            }

            return $this->brokers[$code];
        }

        // No explicit code → try config('trading.default_broker'), then the
        // last-registered default, then the first registered broker. This
        // graceful fallback keeps the dashboard alive when a live adapter
        // failed to construct (e.g. missing credentials).
        foreach ([$this->configuredDefault(), $this->defaultCode, array_key_first($this->brokers)] as $candidate) {
            if ($candidate && isset($this->brokers[$candidate])) {
                return $this->brokers[$candidate];
            }
        }

        throw new \RuntimeException('No broker is registered');
    }

    /** Returns the configured "live" broker — used by PaperBroker for quotes. */
    public function live(): ?BrokerContract
    {
        $code = $this->configuredDefault() ?? $this->defaultCode;

        return $code && isset($this->brokers[$code]) && $code !== 'paper' ? $this->brokers[$code] : null;
    }

    /** @return BrokerContract[] */
    public function all(): array
    {
        return $this->brokers;
    }

    public function has(string $code): bool
    {
        return isset($this->brokers[$code]);
    }

    /**
     * Read config('trading.default_broker') safely.
     * Returns null when called outside a booted Laravel container (unit tests).
     */
    private function configuredDefault(): ?string
    {
        try {
            if (function_exists('config')) {
                return config('trading.default_broker');
            }
        } catch (\Throwable) {
            // ignore — fall through
        }

        return null;
    }
}
