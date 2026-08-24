<?php

namespace App\Exceptions;

class PaymentAlreadyPaidException extends \RuntimeException
{
    public function __construct(string $message = 'This rental has already been paid.')
    {
        parent::__construct($message);
    }
}
