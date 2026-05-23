<?php

namespace App\Trading\Contracts;

use App\Trading\DataObjects\OrderRequest;
use App\Trading\Risk\RiskDecision;

interface RiskRule
{
    public function code(): string;

    public function check(OrderRequest $request, array $context = []): RiskDecision;
}
