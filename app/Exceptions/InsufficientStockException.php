<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct($message = "Insufficient stock for this transaction.", $code = 422)
    {
        parent::__construct($message, $code);
    }
}
