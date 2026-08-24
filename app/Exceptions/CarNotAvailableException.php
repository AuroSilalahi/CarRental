<?php

namespace App\Exceptions;

class CarNotAvailableException extends \RuntimeException
{
    public function __construct(string $message = 'The selected car is not available for the requested dates.')
    {
        parent::__construct($message);
    }
}
