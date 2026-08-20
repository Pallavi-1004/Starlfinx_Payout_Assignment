<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientBalanceException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Insufficient balance');
    }
}
