<?php

namespace App\Enums;

use InvalidArgumentException;

final class PayoutStatus
{
    const PENDING = 'PENDING';
    const SUCCESS = 'SUCCESS';
    const FAILED = 'FAILED';

    public static function values()
    {
        return [self::PENDING, self::SUCCESS, self::FAILED];
    }

    public static function isValid($status)
    {
        return in_array($status, self::values(), true);
    }

    public static function assertValid($status)
    {
        if (!self::isValid($status)) {
            throw new InvalidArgumentException('Invalid payout status.');
        }
    }
}
