<?php

namespace App\Enums\RentOut;

enum PaymentMode: string
{
    case Cash = 'cash';
    case Cheque = 'cheque';
    case Pos = 'pos';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Cheque => 'Cheque',
            self::Pos => 'POS',
            self::BankTransfer => 'Bank Transfer',
        };
    }
}
