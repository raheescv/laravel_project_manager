<?php

namespace App\Enums\ProjectContract;

enum SecurityForm: string
{
    case BankGuarantee = 'bank_guarantee';
    case Cash = 'cash';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::BankGuarantee => 'Bank guarantee',
            self::Cash => 'Cash deposit',
            self::Cheque => 'Cheque',
        };
    }

    /** Only cash changes hands when the security is lodged; guarantees and cheques are tracked until encashed. */
    public function movesMoney(): bool
    {
        return $this === self::Cash;
    }
}
