<?php

namespace App\Exceptions;

class RentalStatusConflictException extends \RuntimeException
{
    public function __construct(string $message = 'The rental status transition is not allowed.')
    {
        parent::__construct($message);
    }
}
