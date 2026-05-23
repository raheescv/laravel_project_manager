<?php

namespace App\Trading\DataObjects;

final class OrderResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $orderId = null,
        public readonly ?string $brokerCode = null,
        public readonly ?float $filledPrice = null,
        public readonly int $filledQty = 0,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    public static function failure(string $error, array $raw = [], ?string $brokerCode = null): self
    {
        return new self(success: false, error: $error, brokerCode: $brokerCode, raw: $raw);
    }

    public static function ok(string $orderId, string $brokerCode, array $raw = [], ?float $filledPrice = null, int $filledQty = 0): self
    {
        return new self(
            success: true,
            orderId: $orderId,
            brokerCode: $brokerCode,
            filledPrice: $filledPrice,
            filledQty: $filledQty,
            raw: $raw,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'order_id' => $this->orderId,
            'broker_code' => $this->brokerCode,
            'filled_price' => $this->filledPrice,
            'filled_qty' => $this->filledQty,
            'error' => $this->error,
            'raw' => $this->raw,
        ];
    }
}
