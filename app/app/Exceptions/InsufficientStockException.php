<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(string $message = "Stok tidak mencukupi untuk memenuhi permintaan pergerakan stok (BR-S2).", int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
