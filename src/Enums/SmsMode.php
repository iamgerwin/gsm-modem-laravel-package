<?php

declare(strict_types=1);

namespace Iamgerwin\GsmModem\Enums;

enum SmsMode: string
{
    case PDU = 'PDU';
    case TEXT = 'TEXT';

    public function getValue(): int
    {
        return match ($this) {
            self::PDU => 0,
            self::TEXT => 1,
        };
    }
}