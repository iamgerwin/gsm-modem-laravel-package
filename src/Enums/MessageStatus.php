<?php

declare(strict_types=1);

namespace Iamgerwin\GsmModem\Enums;

enum MessageStatus: string
{
    case UNREAD = 'REC UNREAD';
    case READ = 'REC READ';
    case UNSENT = 'STO UNSENT';
    case SENT = 'STO SENT';
    case ALL = 'ALL';

    public function getAtCommand(): string
    {
        return match ($this) {
            self::UNREAD => '"REC UNREAD"',
            self::READ => '"REC READ"',
            self::UNSENT => '"STO UNSENT"',
            self::SENT => '"STO SENT"',
            self::ALL => '"ALL"',
        };
    }
}