<?php

namespace App\Trading\Contracts;

interface AlertChannel
{
    public function code(): string;

    /**
     * Deliver a single alert payload to this channel.
     *
     * @param  array<string, mixed>  $payload
     */
    public function send(string $title, string $body, array $payload = []): bool;
}
