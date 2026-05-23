<?php

namespace App\Trading\Alerts\Channels;

use App\Models\TradingAlert;
use App\Trading\Contracts\AlertChannel;

class DatabaseChannel implements AlertChannel
{
    public function code(): string
    {
        return 'database';
    }

    public function send(string $title, string $body, array $payload = []): bool
    {
        TradingAlert::create([
            'alert_rule_id' => $payload['rule_id'] ?? null,
            'title' => $title,
            'body' => $body,
            'severity' => $payload['severity'] ?? 'info',
            'payload' => $payload,
            'delivered_to' => ['database'],
        ]);

        return true;
    }
}
