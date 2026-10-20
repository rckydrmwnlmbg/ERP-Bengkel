<?php

namespace App\Exceptions;

use Exception;

class InvalidStockMovementException extends Exception
{
    public function __construct(string $message = "Operasi pergerakan stok tidak valid.", int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
