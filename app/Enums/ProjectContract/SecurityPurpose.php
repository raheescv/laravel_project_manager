<?php

namespace App\Enums\ProjectContract;

enum SecurityPurpose: string
{
    case PerformanceBond = 'performance_bond';
    case AdvancePaymentGuarantee = 'advance_payment_guarantee';
    case RetentionSecurity = 'retention_security';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::PerformanceBond => 'Performance bond',
            self::AdvancePaymentGuarantee => 'Advance payment guarantee',
            self::RetentionSecurity => 'Retention security',
            self::General => 'General security deposit',
        };
    }
}
