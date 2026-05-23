<?php

namespace App\Trading\Alerts\Channels;

use App\Trading\Contracts\AlertChannel;
use Illuminate\Support\Facades\Log;

class LogChannel implements AlertChannel
{
    public function code(): string
    {
        return 'log';
    }

    public function send(string $title, string $body, array $payload = []): bool
    {
        Log::channel(config('trading.alerts.log_channel', 'stack'))
            ->info("[trading] {$title} — {$body}", $payload);

        return true;
    }
}
