<?php

namespace App\Exceptions;

class EmailNotVerifiedException extends \RuntimeException
{
    public function __construct(string $message = 'Your email address has not been verified.')
    {
        parent::__construct($message);
    }
}
