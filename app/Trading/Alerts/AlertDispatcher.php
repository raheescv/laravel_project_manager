<?php

namespace App\Trading\Alerts;

use App\Models\TradingAlert;
use App\Models\TradingAlertRule;
use App\Trading\Contracts\AlertChannel;
use Illuminate\Support\Facades\Cache;

/**
 * Routes incoming events to matching AlertRules and fans the resulting
 * messages out to each rule's channels. Rate-limit + quiet-hour checks
 * happen here so individual channels stay simple.
 */
class AlertDispatcher
{
    /** @var array<string, AlertChannel> */
    private array $channels = [];

    public function registerChannel(AlertChannel $channel): void
    {
        $this->channels[$channel->code()] = $channel;
    }

    public function channels(): array
    {
        return $this->channels;
    }

    /**
     * Dispatch a structured event payload. Looks up matching alert rules,
     * filters by conditions, then writes/sends one alert per matching rule.
     */
    public function dispatch(string $event, string $title, string $body, array $payload = []): void
    {
        $rules = TradingAlertRule::query()
            ->where('is_active', true)
            ->where('event', $event)
            ->get();

        if ($rules->isEmpty()) {
            // Even with no rule, write a database record so the UI shows it.
            $this->emit(null, $title, $body, $payload + ['event' => $event], ['database']);

            return;
        }

        foreach ($rules as $rule) {
            if (! $this->matchesConditions($rule, $payload)) {
                continue;
            }
            if (! $this->withinQuietHours($rule)) {
                continue;
            }
            if (! $this->withinRateLimit($rule)) {
                continue;
            }

            $channelCodes = $rule->channels ?: ['database'];
            $this->emit(
                rule: $rule,
                title: $title,
                body: $body,
                payload: array_merge($payload, ['event' => $event, 'rule_id' => $rule->id, 'severity' => $rule->severity]),
                channelCodes: $channelCodes,
            );
        }
    }

    private function emit(?TradingAlertRule $rule, string $title, string $body, array $payload, array $channelCodes): void
    {
        $delivered = [];
        $failed = [];

        foreach ($channelCodes as $code) {
            $channel = $this->channels[$code] ?? null;
            if (! $channel) {
                $failed[] = $code;

                continue;
            }
            try {
                $ok = $channel->send($title, $body, $payload);
                $ok ? $delivered[] = $code : $failed[] = $code;
            } catch (\Throwable) {
                $failed[] = $code;
            }
        }

        TradingAlert::create([
            'alert_rule_id' => $rule?->id,
            'title' => $title,
            'body' => $body,
            'severity' => $payload['severity'] ?? 'info',
            'payload' => $payload,
            'delivered_to' => $delivered,
            'failed_channels' => $failed,
        ]);
    }

    private function matchesConditions(TradingAlertRule $rule, array $payload): bool
    {
        $cond = $rule->conditions ?: [];
        if (empty($cond)) {
            return true;
        }
        foreach ($cond as $key => $expected) {
            $actual = data_get($payload, $key);
            if (is_array($expected)) {
                if (! in_array($actual, $expected, false)) {
                    return false;
                }
            } else {
                if ($actual != $expected) {
                    return false;
                }
            }
        }

        return true;
    }

    private function withinQuietHours(TradingAlertRule $rule): bool
    {
        if (! $rule->quiet_from || ! $rule->quiet_until) {
            return true;
        }
        $now = now()->format('H:i:s');
        $from = $rule->quiet_from;
        $until = $rule->quiet_until;
        if ($from <= $until) {
            return ! ($now >= $from && $now <= $until);
        }

        // Window crosses midnight.
        return ! ($now >= $from || $now <= $until);
    }

    private function withinRateLimit(TradingAlertRule $rule): bool
    {
        $key = "trading:alert_rate:{$rule->id}:".now()->format('YmdH');
        $count = (int) Cache::get($key, 0);
        if ($count >= ($rule->rate_limit_per_hour ?: 60)) {
            return false;
        }
        Cache::put($key, $count + 1, now()->addHour());

        return true;
    }
}
