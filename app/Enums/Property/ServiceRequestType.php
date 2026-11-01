<?php

namespace App\Enums\Property;

enum ServiceRequestType: string
{
    case Connect = 'connect';
    case Disconnect = 'disconnect';

    public function label(): string
    {
        return match ($this) {
            self::Connect => 'Connect',
            self::Disconnect => 'Disconnect',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Connect => 'success',
            self::Disconnect => 'danger',
        };
    }
}
